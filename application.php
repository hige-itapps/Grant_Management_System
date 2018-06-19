<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/functions/verification.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/functions/documents.php");

	/*For sending custom emails*/
	include_once(dirname(__FILE__) . "/functions/customEmail.php");


	/*for dept. chair email message*/
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
?>



<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<?php include 'include/head_content.html'; ?>
	<body ng-app="HIGE-app" >
	
		<!-- Shared Site Banner -->
		<?php include 'include/site_banner.html'; ?>

	<div id="MainContent" role="main">
		
		<?php
		
		/*save the current date*/
		$currentDate = DateTime::createFromFormat('Y/m/d', date("Y/m/d"));

		$app = null; //only set app if it exists (if not creating one)

		$submitDate = null; //date this app was submitted, if at all
		
		/*get initial character limits for text fields*/
		$appCharMax = getApplicationsMaxLengths($conn);
		$appBudgetCharMax = getApplicationsBudgetsMaxLengths($conn);

		//echo var_dump($appCharMax);
		
		$maxName = $appCharMax[array_search('Name', array_column($appCharMax, 0))][1]; //name char limit
		$maxDep = $appCharMax[array_search('Department', array_column($appCharMax, 0))][1]; //department char limit
		$maxTitle = $appCharMax[array_search('Title', array_column($appCharMax, 0))][1]; //title char limit
		$maxDestination = $appCharMax[array_search('Destination', array_column($appCharMax, 0))][1]; //destination char limit
		$maxOtherEvent = $appCharMax[array_search('IsOtherEventText', array_column($appCharMax, 0))][1]; //other event text char limit
		$maxOtherFunding = $appCharMax[array_search('OtherFunding', array_column($appCharMax, 0))][1]; //other funding char limit
		$maxProposalSummary = $appCharMax[array_search('ProposalSummary', array_column($appCharMax, 0))][1]; //proposal summary char limit
		$maxDepChairSig = $appCharMax[array_search('DepartmentChairSignature', array_column($appCharMax, 0))][1];//signature char limit
		
		$maxBudgetComment = $appBudgetCharMax[array_search('Comment', array_column($appBudgetCharMax, 0))][1]; //budget comment char limit
		
		
		/*Initialize all user permissions to false*/
		$isCreating = false; //user is an applicant initially creating application
		$isReviewing = false; //user is an applicant reviewing their already created application
		$isAdmin = false; //user is an administrator
		$isAdminUpdating = false; //user is an administrator who is updating the application
		$isCommittee = false; //user is a committee member
		$isChair = false; //user is the associated department chair
		$isChairReviewing = false; //user is the associated department chair, but cannot do anything (just for reviewing purposes)
		$isApprover = false; //user is an application approver (director)
		
		$permissionSet = false; //boolean set to true when a permission has been set- used to force only 1 permission at most
		
		/*User is trying to download a document*/
		if(isset($_GET["doc"]))
		{
			downloadDocs($_GET["id"], $_GET["doc"]);
		}
		/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
		For everything besides application creation, the app ID MUST BE SET*/
		if(isset($_GET["id"]))
		{
			//admin check
			$isAdmin = isAdministrator($conn, $CASbroncoNetID); //admin is viewing page
			$permissionSet = $isAdmin;
			
			//application approver check
			if(!$permissionSet)
			{
				$isApprover = isApplicationApprover($conn, $CASbroncoNetID); //application approver(director) can write notes, choose awarded amount, and generate email text
				$permissionSet = $isApprover;
			}
			
			//department chair check
			if(!$permissionSet)
			{
				$isChair = isUserAllowedToSignApplication($conn, $CASemail, $_GET['id']); //chair member; can sign application
				$permissionSet = $isChair;
			}

			//department chair reviewing check
			if(!$permissionSet)
			{
				$isChairReviewing = isUserDepartmentChair($conn, $CASemail, $_GET['id']); //chair member; can view the application, but cannot sign
				$permissionSet = $isChairReviewing;
			}
			
			//committee member check
			if(!$permissionSet)
			{
				$isCommittee = isUserAllowedToSeeApplications($conn, $CASbroncoNetID); //committee member; can only view!
				$permissionSet = $isCommittee;
			}
			
			//applicant reviewing check
			if(!$permissionSet)
			{
				$isReviewing = doesUserOwnApplication($conn, $CASbroncoNetID, $_GET['id']); //applicant is reviewing their application
				$permissionSet = $isReviewing;
			}
		}
		//applicant creating check. Note- if the app id is set, then by default the application cannot be created
		if(!$permissionSet && !isset($_GET["id"]))
		{
			$isCreating = isUserAllowedToCreateApplication($conn, $CASbroncoNetID, $CASallPositions, true); //applicant is creating an application (check latest date possible)
			$permissionSet = $isCreating; //will set to true if user is creating a new application
		}
		
		/*Verify that user is allowed to render application*/
		if($permissionSet)
		{
			/*User is trying to download a document*/
			if(isset($_GET["doc"]))
			{
				downloadDocs($_GET["id"], $_GET["doc"]);
			}
			/*User is trying to upload a document (only allowed to if has certain permissions)*/
			if($isCreating || $isReviewing || $isAdminUpdating){
				if(isset($_REQUEST["uploadDocs"]) || isset($_REQUEST["uploadDocsF"]))
				{
					uploadDocs($_REQUEST["updateID"]);
				}
			}



			$P = array();
			$S = array();
			
			/*Initialize variables if application has already been created*/
			if(!$isCreating)
			{
				$appID = $_GET["id"];
				
				$app = getApplication($conn, $appID); //get application Data

				$submitDate = DateTime::createFromFormat('Y-m-d', $app->dateSubmitted);
				

				/*
				$docs = listDocs($appID); //get documents
				for($i = 0; $i < count($docs); $i++)
				{
					if(substr($docs[$i], 0, 1) == 'P')
						array_push($P, "<a href='?id=" . $appID . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>");
					if(substr($docs[$i], 0, 1) == 'S')
						array_push($S, "<a href='?id=" . $appID . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>");
				}
				
				/*Admin wants to update application*/
				if($isAdminUpdating && isset($_POST["cancelUpdateApp"]))
				{
					header('Location: ?id=' . $appID); //reload page as admin
				}

				/*Admin wants to cancel updating this application*/
				if($isAdmin && isset($_POST["updateApp"]))
				{
					header('Location: ?id=' . $appID . '&updating'); //reload page as admin updating
				}
				
				/*User wants to approve this application*/
				if(isset($_POST["approveA"]))
				{
					if(approveApplication($conn, $appID, $_POST["amountAwarded"]))
					{
						customEmail(trim($app->email), nl2br($_POST["approverEmail"]), "");
						header('Location: index.php'); //redirect
					}
				}
				
				/*User wants to deny this application*/
				if(isset($_POST["denyA"]))
				{
					if(denyApplication($conn, $appID))
					{
						customEmail(trim($app->email), nl2br($_POST["approverEmail"]), "");
						header('Location: index.php'); //redirect
					}
				}
				/*User wants to HOLD this application*/
				if(isset($_POST["holdA"]))
				{
					if(holdApplication($conn, $appID))
					{
						customEmail(trim($app->email), nl2br($_POST["approverEmail"]), "");
						header('Location: index.php'); //redirect
					}
				}
				
				/*Check for trying to sign application*/
				if($isChair && isset($_POST["signApp"]))
				{
					signApplication($conn, $appID, $_POST["deptChairApproval"]);
					header('Location: index.php'); //redirect to homepage
				}
			}
		?>
		<!--HEADER-->
		
			<!--BODY-->
			<div class="container-fluid">

				<div ng-controller="appCtrl">

					<div ng-cloak ng-show="isAdmin || isAdminUpdating" class="buttons-group"> 
						<button type="button" ng-click="toggleAdminUpdate()" class="btn btn-warning">TURN {{isAdminUpdating ? "OFF" : "ON"}} ADMIN UPDATE MODE</button>
						<button type="button" ng-click="populateForm(null)" class="btn btn-warning">RELOAD SAVED DATA</button>
						<button type="button" ng-click="insertApplication()" class="btn btn-warning">SUBMIT CHANGES</button>
					</div>

						<!-- application form -->
					<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" ng-submit="insertApplication()">


						<div class="row">
							<h1 class="title">APPLICATION:</h1>
						</div>
						
						<!--SUBMISSION CYCLE WARNING-->
						<div ng-cloak class="row" ng-show="shouldWarn">
							<h3 class="title warning">WARNING! DO NOT SUBMIT APPLICATION AFTER THE MIDNIGHT OF A CYCLE'S DUE DATE! <br/>
								<br/>If you do, your application will be automatically moved forward by one cycle!</h3>
						</div>
					
						<!--SUBMISSION CYCLE-->
						<div class="row">
							<div class="col-md-4"></div>
							<div class="col-md-4">
								<fieldset>
								<legend>Submission Cycle:</legend>
									<div class="checkbox">
										<p>{{isCreating ? "Current date: "+currentDate : "Date Submitted: "+dateSubmitted}}</p>
										<div class="radio">
										<label><input ng-disabled="!allowedFirstCycle || appFieldsDisabled" type="radio" value="this" ng-model="formData.cycleChoice" name="cycleChoice">Submit For This Cycle ({{thisCycle}})</label>
										</div>
										<div class="radio">
										<label><input ng-disabled="appFieldsDisabled" type="radio" value="next" ng-model="formData.cycleChoice" name="cycleChoice">Submit For Next Cycle ({{nextCycle}})</label>
										</div>
									</div>
								</fieldset>
							</div>
							<div class="col-md-4"></div>
						</div>
					
					
					
						<!--APPLICANT INFO-->
						<div class="row">
							<h2 class="title">Applicant Information:</h2>
						</div>
						
						
						
						<div class="row">
						<!--NAME-->
							<div class="col-md-5">
								<div class="form-group">
									<label for="name">Name{{isCreating || isAdminUpdating ? " (Required) ("+(maxName-formData.name.length)+" characters remaining)" : ""}}:</label>
									<input type="text" class="form-control" maxlength="{{maxName}}" ng-model="formData.name" ng-disabled="appFieldsDisabled" id="name" name="name" placeholder="Enter Name" />
									<span class="help-block" ng-show="errors.name" aria-live="polite">{{ errors.name }}</span> 
								</div>
							</div>
						<!--EMAIL-->
							<div class="col-md-7">
								<div class="form-group">
									<label for="email">Email Address{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="email" class="form-control" maxlength="{{maxEmail}}" ng-model="formData.email" ng-disabled="appFieldsDisabled" id="email" name="email" placeholder="Enter Email Address" />
									<span class="help-block" ng-show="errors.email" aria-live="polite">{{ errors.email }}</span> 
								</div>
							</div>
						</div>
						
						
						
						<div class="row">
						<!--DEPARTMENT-->
						<div class="col-md-5">
								<div class="form-group">
									<label for="department">Department{{isCreating || isAdminUpdating ? " (Required) ("+(maxDep-formData.department.length)+" characters remaining)" : ""}}:</label>
									<input type="text" class="form-control" maxlength="{{maxDep}}" ng-model="formData.department" ng-disabled="appFieldsDisabled" id="department" name="department" placeholder="Enter Department" />
									<span class="help-block" ng-show="errors.department" aria-live="polite">{{ errors.department }}</span> 
								</div>
							</div>
						<!--DEPT CHAIR EMAIL-->
							<div class="col-md-7">
								<div class="form-group">
									<label for="deptChairEmail">Department Chair's WMU Email Address{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="email" class="form-control" maxlength="{{maxDepEmail}}" ng-model="formData.deptChairEmail" ng-disabled="appFieldsDisabled" id="deptChairEmail" name="deptChairEmail" placeholder="Enter Department Chair's Email Address" />
									<span class="help-block" ng-show="errors.deptChairEmail" aria-live="polite">{{ errors.deptChairEmail }}</span> 
								</div>
							</div>
						</div>
						
						
						
						<!--RESEARCH INFO-->
						<div class="row">
							<h2 class="title">Travel Information:</h2>
						</div>
						
						
						
						<div class="row">
						<!--TRAVEL DATE FROM-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="travelFrom">Travel Date From{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="date" class="form-control" ng-model="formData.travelFrom" ng-disabled="appFieldsDisabled" id="travelFrom" name="travelFrom" />
									<span class="help-block" ng-show="errors.travelFrom" aria-live="polite">{{ errors.travelFrom }}</span> 
								</div>
							</div>
						<!--TRAVEL DATE TO-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="travelTo">Travel Date To{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="date" class="form-control" ng-model="formData.travelTo" ng-disabled="appFieldsDisabled" id="travelTo" name="travelTo" />
									<span class="help-block" ng-show="errors.travelTo" aria-live="polite">{{ errors.travelTo }}</span> 
								</div>
							</div>
						<!--ACTIVITY DATE FROM-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="activityFrom">Activity Date From{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="date" class="form-control" ng-model="formData.activityFrom"ng-disabled="appFieldsDisabled"  id="activityFrom" name="activityFrom" />
									<span class="help-block" ng-show="errors.activityFrom" aria-live="polite">{{ errors.activityFrom }}</span> 
								</div>
							</div>
						<!--ACTIVITY DATE TO-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="activityTo">Activity Date To{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="date" class="form-control" ng-model="formData.activityTo" ng-disabled="appFieldsDisabled" id="activityTo" name="activityTo" />
									<span class="help-block" ng-show="errors.activityTo" aria-live="polite">{{ errors.activityTo }}</span> 
								</div>
							</div>
						</div>
						
						
						
						<div class="row">
						<!--TITLE-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="title">Project Title{{isCreating || isAdminUpdating ? " (Required) ("+(maxTitle-formData.title.length)+" characters remaining)" : ""}}:</label>
									<input type="text" class="form-control" maxlength="{{maxTitle}}" ng-model="formData.title" ng-disabled="appFieldsDisabled" id="title" name="title" placeholder="Enter Title of Research" />
									<span class="help-block" ng-show="errors.title" aria-live="polite">{{ errors.title }}</span> 
								</div>
							</div>
						<!--DESTINATION-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="destination">Destination{{isCreating || isAdminUpdating ? " (Required) ("+(maxDestination-formData.destination.length)+" characters remaining)" : ""}}:</label>
									<input type="text" class="form-control" maxlength="{{maxDestination}}" ng-model="formData.destination" ng-disabled="appFieldsDisabled" id="destination" name="destination" placeholder="Enter Destination" />
									<span class="help-block" ng-show="errors.destination" aria-live="polite">{{ errors.destination }}</span> 
								</div>
							</div>
						<!--AMOUNT REQ-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="amountRequested">Amount Requested($){{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
									<input type="text" class="form-control" ng-model="formData.amountRequested" ng-disabled="appFieldsDisabled" id="amountRequested" name="amountRequested" placeholder="Enter Amount Requested($)" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' />
									<span class="help-block" ng-show="errors.amountRequested" aria-live="polite">{{ errors.amountRequested }}</span> 
								</div>
							</div>
						</div>
						
						
						
						<!--PURPOSES-->
						<fieldset>
						<legend>Purpose of Travel {{isCreating || isAdminUpdating ? " (Required)" : ""}}:</legend>
						
							<!--PURPOSE:RESEARCH-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.purpose1" ng-disabled="appFieldsDisabled" name="purpose1" type="checkbox" value="purpose1">Research</label>
									</div>
								</div>
							</div>
							<!--PURPOSE:CONFERENCE-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.purpose2" ng-disabled="appFieldsDisabled" name="purpose2" type="checkbox" value="purpose2">Conference</label>
									</div>
								</div>
							</div>
							<!--PURPOSE:CREATIVE ACTIVITY-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.purpose3" ng-disabled="appFieldsDisabled" name="purpose3" type="checkbox" value="purpose3">Creative Activity</label>
									</div>
								</div>
							</div>
							<!--PURPOSE:OTHER-->
							<div class="row">
								<div class="col-md-2">
									<div class="checkbox">
										<label><input ng-model="formData.purpose4OtherDummy" ng-disabled="appFieldsDisabled" name="purpose4OtherDummy" id="purpose4OtherDummy" type="checkbox" value="purpose4OtherDummy">Other, explain.</label>
									</div>
								</div>
								<!-- OTHER PURPOSE TEXT BOX -->
								<div class="col-md-10">
									<div class="form-group">
										<label for="purpose4Other">Explain other purpose{{isCreating || isAdminUpdating ? " ("+(maxOtherEvent-formData.purpose4Other.length)+" characters remaining)" : ""}}:</label>
										<input type="text" class="form-control" maxlength="{{maxOtherEvent}}" ng-model="formData.purpose4Other" ng-disabled="appFieldsDisabled || !formData.purpose4OtherDummy" id="purpose4Other" name="purpose4Other" placeholder="Enter Explanation" />
									</div>
								</div>
							</div>
						
							<span class="help-block" ng-show="errors.purpose" aria-live="polite">{{ errors.purpose }}</span> 
						</fieldset>
						
						
						<!--OTHER FUNDING-->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="otherFunding">Are you receiving other funding? Who is providing the funds? How much?{{isCreating || isAdminUpdating ? " ("+(maxOtherFunding-formData.otherFunding.length)+" characters remaining)" : ""}}:</label>
									<input type="text" class="form-control" maxlength="{{maxOtherFunding}}" ng-model="formData.otherFunding" ng-disabled="appFieldsDisabled" id="otherFunding" name="otherFunding" placeholder="Explain here" />	
									<span class="help-block" ng-show="errors.otherFunding" aria-live="polite">{{ errors.otherFunding }}</span> 
								</div>
							</div>
						</div>
						
						
						
						<!--PROPOSAL SUMMARY-->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="proposalSummary">Proposal Summary{{isCreating || isAdminUpdating ? " (Required) ("+(maxProposalSummary-formData.proposalSummary.length)+" characters remaining)" : ""}} (We recommend up to 150 words):</label>
									<textarea class="form-control" maxlength="{{maxProposalSummary}}" ng-model="formData.proposalSummary" ng-disabled="appFieldsDisabled" id="proposalSummary" name="proposalSummary" placeholder="Enter Proposal Summary" rows="10"> </textarea>
									<span class="help-block" ng-show="errors.proposalSummary" aria-live="polite">{{ errors.proposalSummary }}</span> 
								</div>
							</div>
						</div>
						
						
						
						<!--GOALS-->
						<fieldset>
						<legend>Please indicate which of the prioritized goals of the IEFDF this proposal fulfills{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</legend>
						
							<!--GOAL 1-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.goal1" ng-disabled="appFieldsDisabled" name="goal1" type="checkbox" value="goal1">
										Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
									</div>
								</div>
							</div>
							<!--GOAL 2-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.goal2" ng-disabled="appFieldsDisabled" name="goal2" type="checkbox" value="goal2">
										Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
									</div>
								</div>
							</div>
							<!--GOAL 3-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.goal3" ng-disabled="appFieldsDisabled" name="goal3" type="checkbox" value="goal3">
										Support for attendance at international conferences, seminars or workshops.</label>
									</div>
								</div>
							</div>
							<!--GOAL 4-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<label><input ng-model="formData.goal4" ng-disabled="appFieldsDisabled" name="goal4" type="checkbox" value="goal4">
										Support for scholarly international travel in order to enrich international knowledge, which will directly
										contribute to the internationalization of the WMU curricula.</label>
									</div>
								</div>
							</div>

							<span class="help-block" ng-show="errors.goal" aria-live="polite">{{ errors.goal }}</span> 
						</fieldset>
						
						
						<!--BUDGET-->
						<div class="row">
							<h2 class="title">Budget{{isCreating || isAdminUpdating ? " (Required) (please separate room and board calculating per diem)" : ""}}:</h2>
						</div>
						
						<div id="exampleBudgetHolder">
							<button type="button" id="budgetExampleButton" data-toggle="collapse" class="btn btn-info" data-target="#budgetExample">Click here for an example of how to construct a budget!</button>
							<div id="budgetExample" class="collapse">
								<img src="images/BudgetExample.PNG" alt="Here is an example budget item: Expense: Registration Fee, Comments: Conference Registration, Amount($): 450" class="exampleBudget" />
							</div>
						</div>
						
						<div class="row">
							<div class="col-md-12">
								<ol id="budgetList" class="list-group list-group-flush">
									<li ng-repeat="budgetItem in formData.budgetItems" class="row list-group-item"> 
									<!--BUDGET:EXPENSE-->
										<div class="form-group col-md-4">
											<label for="{{budgetItem.expense}}">Expense:</label>
											<select class="form-control" ng-model="budgetItem.expense" ng-disabled="appFieldsDisabled" name="{{budgetItem.expense}}" value="{{budgetItem.expense}}" >
												<option ng-repeat="o in options" value="{{o.name}}">{{o.name}}</option>
											</select>
											<span class="help-block"  ng-show="errors['budgetArray {{$index+1}} expense']" aria-live="polite">{{errors['budgetArray '+($index+1)+' expense']}}</span>
										</div>
									<!--BUDGET:COMMENTS-->
										<div class="form-group col-md-4">
											<label for="{{budgetItem.comment}}">Comments{{isCreating || isAdminUpdating ? " (Required) ("+(maxBudgetComment-budgetItem.comment.length)+" characters remaining)" : ""}}:</label>
											<input type="text" class="form-control" ng-model="budgetItem.comment" ng-disabled="appFieldsDisabled" maxlength="{{maxBudgetComment}}" name="{{budgetItem.comment}}" placeholder="Explain..." />
											<span class="help-block"  ng-show="errors['budgetArray {{$index+1}} comment']" aria-live="polite">{{errors['budgetArray '+($index+1)+' comment']}}</span>
										</div>
									<!--BUDGET:AMOUNT-->
										<div class="form-group col-md-4">
											<label for="{{budgetItem.amount}}">Amount($):</label>
											<input type="text" class="form-control" ng-model="budgetItem.amount" ng-disabled="appFieldsDisabled" name="{{budgetItem.amount}}" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' />
											<span class="help-block"  ng-show="errors['budgetArray {{$index+1}} amount']" aria-live="polite">{{errors['budgetArray '+($index+1)+' amount']}}</span>
										</div>
									</li>
								</ol>
							</div>
						</div>

						
						
						<!--BUDGET:ADD OR REMOVE-->
						<div class="row" ng-show="isCreating || isAdminUpdating">
							<div class="col-md-3"></div>
							<div id="budgetButtons" class="col-md-6">
								<button type="button" ng-cloak ng-show="isCreating || isAdminUpdating" ng-click="addBudgetItem()" class="btn btn-primary" id="addBudget">Add Budget Item</button>
								<button type="button" ng-cloak ng-show="isCreating || isAdminUpdating" ng-click="removeBudgetItem()" class="btn btn-danger" id="removeBudget">Remove Budget Item</button>
							</div>
							<div class="col-md-3"></div>
						</div>
						
						
						
						<!--BUDGET:TOTAL-->
						<div class="row">
							<div class="col-md-5"></div>
							<div class="col-md-2">
								<h3>Total: ${{ getTotal() }}</h3>
							</div>
							<div class="col-md-5"></div>
						</div>
						
						
						
						<div class="row">
							<h2 class="title">Attachments:</h2>
							<h3>Please Upload Documentation (Proposal Narrative, Conference Acceptance, Letter Of Invitation For Research, Etc.)</h3>
						</div>
						
						
						
						<!--UPLOADS-->
						<div class="row">
							<div class="col-md-6">
								<?php if($isCreating || $isReviewing || $isAdminUpdating){ //for uploading documents; both admins and applicants ?>
									<label for="fD">UPLOAD PROPOSAL NARRATIVE:</label><input type="file" name="fD" id="fD" accept=".txt, .rtf, .doc, .docx, 
									.xls, .xlsx, .ppt, .pptx, .pdf, .jpg, .png, .bmp, .tif"/>
								<?php } //for viewing uploaded documents; ANYONE can ?>
								<p class="title">UPLOADED PROPOSAL NARRATIVE: <?php if(count($P > 0)) { echo "<table>"; foreach($P as $ip) { echo "<tr><td>" . $ip . "</td></tr>"; } echo "</table>"; } else echo "none"; ?> </p>
								</div>
							
							
							<div class="col-md-6">
								<?php if($isCreating || $isReviewing || $isAdminUpdating){ //for uploading documents; both admins and applicants ?>
									<label for="sD">UPLOAD SUPPORTING DOCUMENTS:</label><input type="file" name="sD[]" id="sD" accept=".txt, .rtf, .doc, .docx, 
									.xls, .xlsx, .ppt, .pptx, .pdf, .jpg, .png, .bmp, .tif" multiple />
								<?php } //for viewing uploaded documents; ANYONE can ?>
								<p class="title">UPLOADED SUPPORTING DOCUMENTS: <?php if(count($S > 0)) { echo "<table>"; foreach($S as $is) { echo "<tr><td>" . $is . "</td></tr>"; } echo "</table>"; } else echo "none"; ?> </p>
							</div>
						</div>
						
						
						
						<div class="row">
						<!--DEPARTMENT CHAIR APPROVAL-->
							<div class="col-md-3"></div>
							<div class="col-md-6">
								<h3 class="title">Note: Applications received without the approval of the chair will not be considered.</h3>
								<div class="form-group">
									<label for="deptChairApproval">{{isChair ? "Your Approval ("+(maxProposalSummary-formData.proposalSummary.length)+" characters remaining):" : "Department Chair Approval:"}}</label>
									<input type="text" class="form-control" ng-model="formData.deptChairApproval" ng-disabled="{{!isChair ? true : false}}" id="deptChairApproval" name="deptChairApproval" placeholder="{{isChair ? 'Type Your Full Name Here' : 'Department Chair Must Type Name Here'}}" />
								</div>
							</div>
							<div class="col-md-3"></div>
						</div>


						<div class="row" ng-cloak ng-show="isAdmin || isApprover">
						<!--EMAIL EDIT-->
							<div class="col-md-12">
								<div class="form-group">
									<label for="approverEmail">EMAIL TO BE SENT:</label>
									<textarea class="form-control" ng-model="formData.approverEmail" id="approverEmail" name="approverEmail" placeholder="Enter email body, with greetings." rows=20 /></textarea>
								</div>
							</div>
						</div>
						<div class="row" ng-cloak ng-show="isAdmin || isApprover">
						<!--AMOUNT AWARDED-->
							<div class="col-md-12">
								<div class="form-group">
									<label for="amountAwarded">AMOUNT AWARDED($):</label>
									<input type="text" class="form-control" ng-model="formData.amountAwarded" id="amountAwarded" name="amountAwarded" placeholder="AMOUNT AWARDED" value="<?php echo $app->amountAwarded; ?>" onkeypress='return (event.which >= 48 && event.which <= 57) 
									|| event.which == 8 || event.which == 46' />
								</div>
							</div>
						</div>


						<div class="alert alert-{{alertType}} alert-dismissible fade in" ng-show='alertMessage'>
							<button type="button" class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
						</div>


						
						<div class="row">
							<div class="col-md-2"></div>
						<!--SUBMIT BUTTONS-->
							<div class="col-md-6">
								<?php if($isCreating){ //show submit application button if creating ?>
									<input type="submit" onclick="return confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT APPLICATION" />
								<?php }else if($isAdminUpdating){ //show submit edits button if editing?>
									<input type="submit" onclick="return confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT EDITS" />
								<?php }else if($isAdmin || $isApprover){ //show approve, hold, and deny buttons if admin or approver ?>
									<input type="submit" onclick="return confirm ('By confirming, your email will be sent to the applicant! Are you sure you want to approve this application?')" class="btn btn-success" id="approveApp" name="approveA" value="APPROVE APPLICATION" <?php if(isApplicationSigned($conn, $appID) == 0) { ?> disabled="true" <?php } ?> />
									<input type="submit" onclick="return confirm ('By confirming, your email will be sent to the applicant! Are you sure you want to place this application on hold?')" class="btn btn-primary" id="holdApp" name="holdA" value="PLACE APPLICATION ON HOLD" />
									<input type="submit" onclick="return confirm ('By confirming, your email will be sent to the applicant! Are you sure you want to deny this application?')" class="btn btn-danger" id="denyApp" name="denyA" value="DENY APPLICATION" />
								<?php }else if($isChair){ //show sign button if dep chair ?>
									<input type="submit" onclick="return confirm ('By approving this application, you affirm that this applicant holds a board-appointed faculty rank and is a member of the bargaining unit.')" class="btn btn-success" id="signApp" name="signApp" value="APPROVE APPLICATION" />
								<?php }else if($isReviewing){ ?>
									<input type="submit" class="btn btn-primary" id="uploadDocs" name="uploadDocs" value="UPLOAD MORE DOCUMENTS" />
								<?php } ?>
							</div>
							<div class="col-md-2">
								<a href="index.php" <?php if($isCreating || $isAdminUpdating || $isAdmin || $isApprover || $isChair){ ?> onclick="return confirm ('Are you sure you want to leave this page? Any unsaved data will be lost.')" <?php } ?> class="btn btn-info">LEAVE PAGE</a>
							</div>
							<div class="col-md-2"></div>
						</div>

						<div class="buttons-group"> 
							<button ng-show="isCreating" type="button" ng-click="insertApplication()" class="btn btn-success">SUBMIT APPLICATION</button> <!-- For applicant submitting for first time -->
							<button ng-show="isApprover || isAdmin" type="button" ng-click="approveApplication()" class="btn btn-success">APPROVE APPLICATION</button> <!-- For approver or admin approving -->
							<button ng-show="isApprover || isAdmin" type="button" ng-click="holdApplication()" class="btn btn-primary">HOLD APPLICATION</button> <!-- For approver or admin holding -->
							<button ng-show="isApprover || isAdmin" type="button" ng-click="denyApplication()" class="btn btn-danger">DENY APPLICATION</button> <!-- For approver or admin denying -->
							<button ng-show="isChair" type="button" ng-click="chairApproval()" class="btn btn-success">APPROVE APPLICATION</button> <!-- For department chair approving -->
							<button ng-show="isReviewing" type="button" ng-click="uploadDocs()" class="btn btn-success">UPLOAD DOCS</button> <!-- For applicant reviewing application -->
							<a href="" class="btn btn-info" ng-click="redirectToHomepage(null)">LEAVE PAGE</a> <!-- For anyone to leave the page -->
						</div>
					</form>

					<!-- SHOW DATA FROM INPUTS AS THEY ARE BEING TYPED -->
					<pre>
						{{ alertMessage }}
					</pre>
					<pre>
						{{ formData }}
					</pre>
					<pre>
						{{ formData.budgetItems }}
					</pre>


			</div>
			<!--BODY-->
		
		<?php
		}
		else{
		?>
			<h1>You do not have permission to access an application!</h1>
		<?php
		}
		?>

	</div>	
	</body>
	
	<!-- AngularJS Script -->
	<script>
		
		var myApp = angular.module('HIGE-app', []);
		
		myApp.controller('appCtrl', ['$scope', '$http', '$sce', '$filter', function($scope, $http, $sce, $filter){

			/*Functions*/

			//Add new budget item
			$scope.addBudgetItem = function(expense, comment, amount) {
				if(typeof expense === 'undefined'){expense = "Other";}
				if(typeof comment === 'undefined'){comment = "";}
				if(typeof amount === 'undefined'){amount = 0;}
				$scope.formData.budgetItems.push({
					expense: expense,
					comment: comment,
					amount: amount
				})       
			}
			//Remove last budget item
			$scope.removeBudgetItem = function() {
				if($scope.formData.budgetItems.length > 1)
					$scope.formData.budgetItems.splice($scope.formData.budgetItems.length - 1, 1);
			}
			//Get total budget cost
			$scope.getTotal = function(){
				var total = 0;
				for(var i = 0; i < $scope.formData.budgetItems.length; i++){
					newVal = parseFloat($scope.formData.budgetItems[i]["amount"]);
					if(!isNaN(newVal)){total += newVal;}
				}
				return (total).toFixed(2);
			}

			//remove the alert from the page
			$scope.removeAlert = function(){
    			$scope.alertMessage = null;
 			}

			//function to turn on/off admin updating
			$scope.toggleAdminUpdate = function(){
				$scope.isAdmin = !$scope.isAdmin; //toggle the isAdmin permission
				$scope.isAdminUpdating = !$scope.isAdmin; //set the isAdminUpdating permission to the opposite of isAdmin
				$scope.appFieldsDisabled = $scope.isAdmin; //update the fields to be editable or non-editable
			}

			//redirect the user to the homepage; optionally, send a message which will be displayed as a success alert popup on the homepage.
			$scope.redirectToHomepage = function($message){
				if(confirm ('Are you sure you want to leave this page? Any unsaved data will be lost.'))
				{
					window.location.replace("index.php");
				}
			}

			//fill in the form with app data; send existing data in to be populated, or if nothing is given, attempt to retrieve the most up-to-date app data with an AJAX call
			$scope.populateForm = function($existingApp){

				//first, get the application if it doesn't already exist
				if($existingApp == null)
				{
					$http({
						method  : 'POST',
						url     : '/ajax/get_application.php',
						data    : $.param({appID: $scope.formData.updateID}),  // pass in data as strings
						headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
					})
					.then(function (response) {
						console.log(response, 'res');
						//data = response.data;
						$scope.populateForm(response.data);//recurse to this function again with a real app
					},function (error){
						console.log(error, 'can not get data.');
					});
				}
				else //if it exists, then populate form
				{
					//alert($populating form with:);
					//console.log($existingApp);

					$scope.formData.cycleChoice = $existingApp.nextCycle ? "next" : "this";
					$scope.formData.name = $existingApp.name;
					$scope.formData.email = $existingApp.email;
					$scope.formData.department = $existingApp.department;
					$scope.formData.deptChairEmail = $existingApp.deptChairEmail;
					//dates require a bit of extra work to convert properly! Javascript offsets the dates based on timezones, and one way to combat that is by replacing hyphens with slashes (don't ask me why)
					/*alert(new Date($existingApp.travelFrom));
					alert(new Date($existingApp.travelFrom.replace(/-/g, '\/')));*/
					$scope.formData.travelFrom = new Date($existingApp.travelFrom.replace(/-/g, '\/'));
					$scope.formData.travelTo = new Date($existingApp.travelTo.replace(/-/g, '\/'));
					$scope.formData.activityFrom = new Date($existingApp.activityFrom.replace(/-/g, '\/'));
					$scope.formData.activityTo = new Date($existingApp.activityTo.replace(/-/g, '\/'));
					$scope.formData.title = $existingApp.title;
					$scope.formData.destination = $existingApp.destination;
					$scope.formData.amountRequested = $existingApp.amountRequested;
					//check boxes using conditional (saved as numbers; need to be converted to true/false)
					$scope.formData.purpose1 = $existingApp.purpose1 ? true : false;
					$scope.formData.purpose2 = $existingApp.purpose2 ? true : false;
					$scope.formData.purpose3 = $existingApp.purpose3 ? true : false;
					$scope.formData.purpose4OtherDummy = $existingApp.purpose4 ? true : false; //set to true if any value exists
					$scope.formData.purpose4Other = $existingApp.purpose4;
					$scope.formData.otherFunding = $existingApp.otherFunding;
					$scope.formData.proposalSummary = $existingApp.proposalSummary;
					$scope.formData.goal1 = $existingApp.goal1 ? true : false;
					$scope.formData.goal2 = $existingApp.goal2 ? true : false;
					$scope.formData.goal3 = $existingApp.goal3 ? true : false;
					$scope.formData.goal4 = $existingApp.goal4 ? true : false;

					$scope.formData.budgetItems = []; //empty budget items array
					//add the budget items
					for($i = 0; $i < $existingApp.budget.length; $i++) {
						$scope.addBudgetItem($existingApp.budget[$i][2], $existingApp.budget[$i][4], $existingApp.budget[$i][3]);
					}
		
					$scope.formData.deptChairApproval = $existingApp.deptChairApproval;
				}
			}

			// process the form (AJAX request)
			$scope.insertApplication = function() {

				$sendData = JSON.parse(JSON.stringify($scope.formData)); //create a deep copy of the formdata to send. This almost formats the dates to a good format, but also tacks on a timestamp such as T04:00:00.000Z, which still needs to be removed.
				//alert("date null? " + ($sendData.travelTo == null));
				//alert($sendData.travelTo);

				if($sendData.travelTo != null){		$sendData.travelTo = $sendData.travelTo.substr(0,$sendData.travelTo.indexOf('T'));} //Remove everything starting from 'T'
				if($sendData.travelFrom != null){	$sendData.travelFrom = $sendData.travelFrom.substr(0,$sendData.travelFrom.indexOf('T'));} //Remove everything starting from 'T'
				if($sendData.activityTo != null){	$sendData.activityTo = $sendData.activityTo.substr(0,$sendData.activityTo.indexOf('T'));} //Remove everything starting from 'T'
				if($sendData.activityFrom != null){	$sendData.activityFrom = $sendData.activityFrom.substr(0,$sendData.activityFrom.indexOf('T'));} //Remove everything starting from 'T'

				$http({
					method  : 'POST',
					url     : '/ajax/submit_application.php',
					data    : $.param($sendData),  // pass in data as strings
					headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
				})
				.then(function (response) {
					console.log(response, 'res');
					//data = response.data;
					
					if(response.data.success)
					{
						$scope.errors = []; //clear any old errors
						$scope.alertType = "success";
						$scope.alertMessage = "Success! The application has been received.";
					}
					else
					{
						$scope.errors = response.data.errors;
						$scope.alertType = "danger";
						$scope.alertMessage = "There was an error with your submission, please double check your form for errors, then try resubmitting.";
					}
					
				},function (error){
					console.log(error, 'can not get data.');
				});
			};




			/*On startup*/

			// create a blank object to hold our form information
			// $scope will allow this to pass between controller and view
			$scope.formData = {};
			$scope.formData.budgetItems = []; //array of budget items

			$scope.errors = {};//all current errors

			//expense types
			$scope.options = [{ name: "Air Travel"}, 
								{ name: "Ground Travel"},
								{ name: "Hotel"},
								{ name: "Registration Fee"},
								{ name: "Per Diem"},
								{ name: "Other"}];

			//current date
			$scope.currentDate = <?php echo json_encode($currentDate->format('Y-m-d')); ?>;

			//init vars false
			$scope.allowedFirstCycle = false;
			$scope.shouldWarn = false;

			//get names of this and next cycle
			$scope.thisCycle = <?php echo json_encode(getCycleName($currentDate, false, true)); ?>;
			$scope.nextCycle = <?php echo json_encode(getCycleName($currentDate, true, true)); ?>;

			/*Get user's email address*/
			$CASemail = <?php echo json_encode($CASemail); ?>;

			/*Get character limits from php code*/				
			$scope.maxName = <?php echo json_encode($maxName); ?>;
			$scope.maxDep = <?php echo json_encode($maxDep); ?>;
			$scope.maxTitle = <?php echo json_encode($maxTitle); ?>;
			$scope.maxDestination = <?php echo json_encode($maxDestination); ?>;
			$scope.maxOtherEvent = <?php echo json_encode($maxOtherEvent); ?>;
			$scope.maxOtherFunding = <?php echo json_encode($maxOtherFunding); ?>;
			$scope.maxProposalSummary = <?php echo json_encode($maxProposalSummary); ?>;
			$scope.maxDepChairSig = <?php echo json_encode($maxDepChairSig); ?>;
			$scope.maxBudgetComment = <?php echo json_encode($maxBudgetComment); ?>;
			
			
			/*Get user permissions from php code*/
			$scope.isCreating = <?php echo json_encode($isCreating); ?>;
			$scope.isReviewing = <?php echo json_encode($isReviewing); ?>;
			$scope.isAdmin = <?php echo json_encode($isAdmin); ?>;
			$scope.isAdminUpdating = <?php echo json_encode($isAdminUpdating); ?>;
			$scope.isCommittee = <?php echo json_encode($isCommittee); ?>;
			$scope.isChair = <?php echo json_encode($isChair); ?>;
			$scope.isChairReviewing = <?php echo json_encode($isChairReviewing); ?>;
			$scope.isApprover = <?php echo json_encode($isApprover); ?>;

			/*If not creating, get app data and populate entire form*/
			if(!$scope.isCreating)
			{
				$app = <?php echo json_encode($app); ?>; //app data from php code
				$scope.formData.updateID = $app.id; //set the update id for the server
				$scope.dateSubmitted = $app.dateSubmitted; //set the submission date

				$scope.allowedFirstCycle = true; //allow selection of first cycle- only relevant if user is an admin updating.

				//overwrite the cycle & nextCycle based off the date submitted, not the current date
				$scope.thisCycle = <?php echo json_encode(getCycleName($submitDate, false, true)); ?>;
				$scope.nextCycle = <?php echo json_encode(getCycleName($submitDate, true, true)); ?>;

				//disable app inputs
				$scope.appFieldsDisabled = true;

				//populate the form with the app data
				$scope.populateForm($app);
			}
			else //otherwise, only fill in a few fields
			{
				//find out if user is allowed to create application for the first available cycle
				$scope.allowedFirstCycle = <?php echo json_encode(isUserAllowedToCreateApplication($conn, $CASbroncoNetID, $CASallPositions, false)); ?>;

				//find out if the submission warning should display
				$scope.shouldWarn = <?php echo json_encode($isCreating && isWithinWarningPeriod($currentDate)); ?>;

				if($scope.allowedFirstCycle)
				{
					$scope.formData.cycleChoice = "this"; //set default cycle to this cycle
				}
				else
				{
					$scope.formData.cycleChoice = "next"; //set default cycle to next cycle
				}
				//by default, set the email field to this user's email
				$scope.formData.email = $CASemail;

				//add a few blank budget items
				$scope.addBudgetItem();
				$scope.addBudgetItem();
				$scope.addBudgetItem();
			}

		}]);
	
	</script>
	<!-- End Script -->
</html>
<?php
	$conn = null; //close connection
?>