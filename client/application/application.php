<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();
	
	/*Cycle functions*/
	include_once(dirname(__FILE__) . "/../../functions/cycles.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/../../functions/documents.php");
		
	//save the current date
	$currentDate = DateTime::createFromFormat('Y/m/d', date("Y/m/d"));
	
	//get the max file upload size
	$maxUploadSize = file_upload_max_size();

	//initialize next/current cycles to null, set depending on whether the app has been submitted yet
	$thisCycle = null;
	$nextCycle = null;

	$app = null; //only set app if it exists (when not creating a new one)
	$submitDate = null; //^
	$appFiles = null; //^
	$appEmails = null; //^

	$staffNotes = null; //only set if app exists AND user is a staff member
	
	/*get initial character limits for text fields*/
	$appCharMax = getApplicationsMaxLengths($conn);
	$appBudgetCharMax = getApplicationsBudgetsMaxLengths($conn);
	
	$maxName = $appCharMax[array_search('Name', array_column($appCharMax, 0))][1]; //name char limit
	$maxDepartment = $appCharMax[array_search('Department', array_column($appCharMax, 0))][1]; //department char limit
	$maxTitle = $appCharMax[array_search('Title', array_column($appCharMax, 0))][1]; //title char limit
	$maxDestination = $appCharMax[array_search('Destination', array_column($appCharMax, 0))][1]; //destination char limit
	$maxOtherEvent = $appCharMax[array_search('IsOtherEventText', array_column($appCharMax, 0))][1]; //other event text char limit
	$maxOtherFunding = $appCharMax[array_search('OtherFunding', array_column($appCharMax, 0))][1]; //other funding char limit
	$maxProposalSummary = $appCharMax[array_search('ProposalSummary', array_column($appCharMax, 0))][1]; //proposal summary char limit
	$maxDeptChairApproval = $appCharMax[array_search('DepartmentChairSignature', array_column($appCharMax, 0))][1];//signature char limit
	
	$maxBudgetDetails = $appBudgetCharMax[array_search('Details', array_column($appBudgetCharMax, 0))][1]; //budget details char limit
	
	
	/*Initialize all user permissions to false*/
	$isCreating = false; //user is an applicant initially creating application
	$isReviewing = false; //user is an applicant reviewing their already created application
	$isAdmin = false; //user is an administrator
	$isCommittee = false; //user is a committee member
	$isChair = false; //user is the associated department chair
	$isChairReviewing = false; //user is the associated department chair, but cannot do anything (just for reviewing purposes)
	$isApprover = false; //user is an application approver (director)
	
	$permissionSet = false; //boolean set to true when a permission has been set- used to force only 1 permission at most
	
	/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
	For everything besides application creation, the app ID MUST BE SET*/
	if(isset($_GET["id"]))
	{
		if($permissionSet = $isAdmin = isAdministrator($conn, $CASbroncoNetID)){} //admin check
		else if($permissionSet = $isApprover = isApplicationApprover($conn, $CASbroncoNetID)){} //application approver check
		else if($permissionSet = $isCommittee = isCommitteeMember($conn, $CASbroncoNetID)){} //committee member check
		else if($permissionSet = $isChair = isUserAllowedToSignApplication($conn, $CASemail, $_GET['id'], $CASbroncoNetID)){} //department chair check
		else if($permissionSet = $isChairReviewing = isUserDepartmentChair($conn, $CASemail, $_GET['id'], $CASbroncoNetID)){} //department chair reviewing check
		else if($permissionSet = $isReviewing = doesUserOwnApplication($conn, $CASbroncoNetID, $_GET['id'])){} //applicant reviewing check
	}
	if(!$permissionSet && !isset($_GET["id"])) //applicant creating check. Note- if the app id is set, then by default the application cannot be created
	{
		$permissionSet = $isCreating = isUserAllowedToCreateApplication($conn, $CASbroncoNetID, true); //applicant is creating an application (check latest date possible)
	}

	/*Verify that user is allowed to render application*/
	if($permissionSet)
	{	
		/*Initialize variables if application has already been created*/
		if(!$isCreating)
		{
			$appID = $_GET["id"];
			
			$app = getApplication($conn, $appID); //get application Data
			$submitDate = DateTime::createFromFormat('Y-m-d', $app->dateSubmitted);
			$appFiles = getFileNames($appID);
			$appEmails = getEmails($conn, $appID);

			$thisCycle = getCycleName($submitDate, false, true);
			$nextCycle = getCycleName($submitDate, true, true);

			if($isAdmin || $isApprover || $isCommittee) //if hige staff, then retrieve staff notes
			{
				$staffNotes = getStaffNotes($conn, $appID);
			}
		}
		else
		{
			$thisCycle = getCycleName($currentDate, false, true);
			$nextCycle = getCycleName($currentDate, true, true); 
		}
?>










<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../include/head_content.html'; ?>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_currentDate = <?php echo json_encode($currentDate->format('Y-m-d')); ?>;
			var scope_maxUploadSize = <?php echo json_encode($maxUploadSize); ?>;
			var scope_thisCycle = <?php echo json_encode($thisCycle); ?>;
			var scope_nextCycle = <?php echo json_encode($nextCycle); ?>;
			var scope_maxName = <?php echo json_encode($maxName); ?>;
			var scope_maxDepartment = <?php echo json_encode($maxDepartment); ?>;
			var scope_maxTitle = <?php echo json_encode($maxTitle); ?>;
			var scope_maxDestination = <?php echo json_encode($maxDestination); ?>;
			var scope_maxOtherEvent = <?php echo json_encode($maxOtherEvent); ?>;
			var scope_maxOtherFunding = <?php echo json_encode($maxOtherFunding); ?>;
			var scope_maxProposalSummary = <?php echo json_encode($maxProposalSummary); ?>;
			var scope_maxDeptChairApproval = <?php echo json_encode($maxDeptChairApproval); ?>;
			var scope_maxBudgetDetails = <?php echo json_encode($maxBudgetDetails); ?>;
			var scope_isCreating = <?php echo json_encode($isCreating); ?>;
			var scope_isReviewing = <?php echo json_encode($isReviewing); ?>;
			var scope_isAdmin = <?php echo json_encode($isAdmin); ?>;
			var scope_isCommittee = <?php echo json_encode($isCommittee); ?>;
			var scope_isChair = <?php echo json_encode($isChair); ?>;
			var scope_isChairReviewing = <?php echo json_encode($isChairReviewing); ?>;
			var scope_isApprover = <?php echo json_encode($isApprover); ?>;
			var var_app = <?php echo json_encode($app); ?>; //application data
			var var_appFiles = <?php echo json_encode($appFiles); ?>; //the associated uploaded files
			var var_appEmails = <?php echo json_encode($appEmails); ?>; //the associated saved emails
			var var_CASemail = <?php echo json_encode($CASemail); ?>;
			var scope_allowedFirstCycle = <?php echo json_encode(isUserAllowedToCreateApplication($conn, $CASbroncoNetID, false)); ?>;
			var scope_shouldWarn = <?php echo json_encode($isCreating && isWithinWarningPeriod($currentDate)); ?>;
			var scope_staffNotes = <?php echo json_encode($staffNotes); ?>; //the associated staff notes if allowed
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="application.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include '../include/site_banner.html'; ?>

	<div id="MainContent" role="main">
		<script src="../include/outdatedbrowser.js" nomodule></script> <!-- show site error if outdated -->
		<?php include '../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->

			<!--AngularJS Controller-->
			<div class="container-fluid" ng-controller="appCtrl" id="appCtrl">

				<h1 ng-cloak ng-show="!isCreating" class="{{appStatus}}-background status-bar">Application Status: {{appStatus}}</h1>

				<div ng-cloak ng-show="isAdmin || isAdminUpdating" class="buttons-group"> 
					<button type="button" ng-click="toggleAdminUpdate()" class="btn btn-warning">TURN {{isAdminUpdating ? "OFF" : "ON"}} ADMIN UPDATE MODE</button>
					<button type="button" ng-click="populateForm(null)" class="btn btn-warning">RELOAD SAVED DATA</button>
					<button type="button" ng-click="insertApplication()" class="btn btn-warning">SUBMIT CHANGES</button>
				</div>

					<!-- application form -->
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" ng-submit="submit()">

					

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
								<span class="help-block" ng-show="errors.cycleChoice" aria-live="polite">{{ errors.cycleChoice }}</span> 
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
								<input type="email" class="form-control" ng-model="formData.email" ng-disabled="appFieldsDisabled" id="email" name="email" placeholder="Enter Email Address" />
								<span class="help-block" ng-show="errors.email" aria-live="polite">{{ errors.email }}</span> 
							</div>
						</div>
					</div>
					
					
					
					<div class="row">
					<!--DEPARTMENT-->
					<div class="col-md-5">
							<div class="form-group">
								<label for="department">Department{{isCreating || isAdminUpdating ? " (Required) ("+(maxDepartment-formData.department.length)+" characters remaining)" : ""}}:</label>
								<input type="text" class="form-control" maxlength="{{maxDepartment}}" ng-model="formData.department" ng-disabled="appFieldsDisabled" id="department" name="department" placeholder="Enter Department" />
								<span class="help-block" ng-show="errors.department" aria-live="polite">{{ errors.department }}</span> 
							</div>
						</div>
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-7">
							<div class="form-group">
								<label for="deptChairEmail">Department Chair's WMU Email Address{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
								<input type="email" class="form-control" ng-model="formData.deptChairEmail" ng-disabled="appFieldsDisabled" id="deptChairEmail" name="deptChairEmail" placeholder="Enter Department Chair's Email Address" />
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
							<img src="../images/BudgetExample.PNG" alt="Here is an example budget item: Expense: Registration Fee, Description: Conference Registration, Amount($): 450" class="exampleBudget" />
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<ol id="budgetList" class="list-group list-group-flush">
								<li ng-repeat="budgetItem in formData.budgetItems" class="row list-group-item"> 
								<!--BUDGET:EXPENSE-->
									<div class="form-group col-md-4">
										<label for="budgetExpense{{$index+1}}">Expense:</label>
										<select class="form-control" ng-model="budgetItem.expense" ng-disabled="appFieldsDisabled" name="budgetExpense{{$index+1}}" id="budgetExpense{{$index+1}}" value="{{budgetItem.expense}}" >
											<option ng-repeat="o in options" value="{{o.name}}">{{o.name}}</option>
										</select>
										<span class="help-block"  ng-if="errors['budgetArray '+($index+1)+' expense']" aria-live="polite">{{errors['budgetArray '+($index+1)+' expense']}}</span>
									</div>
								<!--BUDGET:DETAILS-->
									<div class="form-group col-md-4">
										<label for="budgetDetails{{$index+1}}">Description{{isCreating || isAdminUpdating ? " (Required) ("+(maxBudgetDetails-budgetItem.details.length)+" characters remaining)" : ""}}:</label>
										<input type="text" class="form-control" ng-model="budgetItem.details" ng-disabled="appFieldsDisabled" maxlength="{{maxBudgetDetails}}" name="budgetDetails{{$index+1}}" id="budgetDetails{{$index+1}}" placeholder="Explain..." />
										<span class="help-block"  ng-if="errors['budgetArray '+($index+1)+' details']" aria-live="polite">{{errors['budgetArray '+($index+1)+' details']}}</span>
									</div>
								<!--BUDGET:AMOUNT-->
									<div class="form-group col-md-2">
										<label for="budgetAmount{{$index+1}}">Amount($):</label>
										<input type="text" class="form-control" ng-model="budgetItem.amount" ng-disabled="appFieldsDisabled" name="budgetAmount{{$index+1}}" id="budgetAmount{{$index+1}}" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' />
										<span class="help-block"  ng-if="errors['budgetArray '+($index+1)+' amount']" aria-live="polite">{{errors['budgetArray '+($index+1)+' amount']}}</span>
									</div>
								<!--REMOVE BUTTON-->
									<div class="form-group col-md-2">
										<button type="button" ng-cloak ng-show="isCreating || isAdminUpdating" ng-click="removeBudgetItem($index)" class="btn btn-danger removeBudget">Delete Item</button>
										<h3>Item {{$index+1}}</h3>
									</div>
								</li>
							</ol>
						</div>
					</div>

					
					
					<!--BUDGET:ADD NEW ITEM-->
					<div class="row" ng-show="isCreating || isAdminUpdating">
						<div class="col-md-3"></div>
						<div id="budgetButtons" class="col-md-6">
							<button type="button" ng-cloak ng-show="isCreating || isAdminUpdating" ng-click="addBudgetItem()" class="btn btn-primary" id="addBudget">Add Budget Item</button>
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
						<h3 ng-show="isCreating || isReviewing || isAdminUpdating">Please Upload Documentation (Proposal Narrative, Conference Acceptance, Letter Of Invitation For Research, Etc.). The maximum allowed size for each file is {{maxUploadSize/1048576}}MB. </h3>
					</div>
					
					
					
					<!--UPLOADS-->
					<div class="row">
						<div class="col-md-6">
							<div ng-cloak ng-show="isCreating || isReviewing || isAdminUpdating" class="uploadedList">
								<hr>
								<div class="upload-button-holder">
									<label class="btn btn-primary">
										UPLOAD PROPOSAL NARRATIVE<input type="file" readproposalnarrative="uploadProposalNarrative" name="uploadProposalNarrative" accept=".txt, .rtf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pdf, .jpg, .png, .bmp, .tif"/>
									</label>
								</div>
								<h4>Your selected proposal narrative:</h4>
								<ul>
									<li ng-repeat="file in uploadProposalNarrative">{{file.name}} <a href="" ng-click="removeProposalNarrative($index)" class="remove-file">REMOVE</a> </li>
								</ul>
							</div>
							<hr>
							<div class="uploadedList">
								<h4 class="title">UPLOADED PROPOSAL NARRATIVE (Click file to download): </h4>
								<ul>
									<li ng-repeat="file in appFiles" ng-if="file.indexOf('PN') == 0"><a href="" ng-click="downloadFile(file)">{{file}}</a></li>
								</ul>
							</div>
						</div>
						
						<div class="col-md-6">
							<div ng-cloak ng-show="isCreating || isReviewing || isAdminUpdating" class="uploadedList">
								<hr>
								<div class="upload-button-holder">
									<label class="btn btn-primary">
										UPLOAD SUPPORTING DOCUMENTS<input type="file" readsupportingdocs="uploadSupportingDocs" name="uploadSupportingDocs" multiple accept=".txt, .rtf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pdf, .jpg, .png, .bmp, .tif"/>
									</label>
								</div>
								<h4>Your selected supporting documents:</h4>
								<ul>
									<li ng-repeat="file in uploadSupportingDocs">{{file.name}} <a href="" ng-click="removeSupportingDoc($index)" class="remove-file">REMOVE</a> </li>
								</ul>
							</div>
							<hr>
							<div class="uploadedList">
								<h4 class="title">UPLOADED SUPPORTING DOCUMENTS (Click file to download): </h4>
								<ul>
									<li ng-repeat="file in appFiles" ng-if="file.indexOf('SD') == 0"><a href="" ng-click="downloadFile(file)">{{file}}</a></li>
								</ul>
							</div>
						</div>
					</div>

					
					
					<div class="row">
					<!--DEPARTMENT CHAIR APPROVAL-->
						<div class="col-md-3"></div>
						<div class="col-md-6">
							<h3 class="title">Note: Applications received without the approval of the chair will not be considered.</h3>
							<h3 class="title" ng-show="isChair">By approving this application, you affirm that this applicant holds a board-appointed faculty rank and is a member of the bargaining unit.</h3>
							<div class="form-group">
								<label for="deptChairApproval">{{isChair ? "Your Approval ("+(maxDeptChairApproval-formData.deptChairApproval.length)+" characters remaining):" : "Department Chair Approval:"}}</label>
								<input type="text" class="form-control" maxlength="{{maxDeptChairApproval}}" ng-model="formData.deptChairApproval" ng-disabled="{{!isChair ? true : false}}" id="deptChairApproval" name="deptChairApproval" placeholder="{{isChair ? 'Type Your Full Name Here' : 'Department Chair Must Type Name Here'}}" />
							</div>
						</div>
						<div class="col-md-3"></div>
					</div>



					<!--STAFF NOTES-->
					<div id="staffNotesHolder" class="row" ng-show="isAdmin || isApprover || isCommittee">
						<div class="col-md-3"></div>
						<div class="col-md-6">
							<label for="staffNotes">Staff Notes:</label>
							<textarea class="form-control" ng-disabled="!isAdmin && !isApprover" ng-model="staffNotes[1]" id="staffNotes" name="staffNotes" rows=10 /></textarea>
							<button type="button" ng-show="isAdmin || isApprover" ng-click="saveNote()" class="btn btn-success">SAVE NOTE</button>
						</div>
						<div class="col-md-3"></div>
					</div>



					<!--PREVIOUSLY SENT EMAILS-->
					<div id="previousEmailsHolder" ng-show="!isCreating">
						<button type="button" id="previousEmailsButton" data-toggle="collapse" class="btn btn-info" data-target="#previousEmails">Click here to see sent emails associated with this application</button>
						<ol id="previousEmails" class="collapse list-group">
							<li class="list-group-item" ng-if="appEmails.length <= 0">There are no sent emails!</li>
							<li class="list-group-item" ng-repeat="email in appEmails">
								<h5 class="list-group-item-heading">{{email[2]}}: sent {{email[4]}}</h5>
								<hr>
								<p class="list-group-item-text" ng-bind-html="email[3]"></p>
							</li>
						</ol>
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



					<div class="row" ng-cloak ng-show="!isCreating">
					<!--AMOUNT AWARDED-->
						<div class="col-md-5"></div>
						<div class="col-md-2">
							<div class="form-group">
								<label for="amountAwarded">AMOUNT AWARDED($):</label>
								<input type="text" class="form-control" ng-model="formData.amountAwarded" ng-disabled="!isAdmin && !isApprover" id="amountAwarded" name="amountAwarded" placeholder="{{isAdmin || isApprover ? 'Amount($)' : 'N/A'}}" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' />
							</div>
						</div>
						<div class="col-md-5"></div>
					</div>



					<div class="alert alert-{{alertType}} alert-dismissible" ng-class="{hideAlert: !alertMessage}">
						<button type="button" title="Close this alert." class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
					</div>



					<div class="buttons-group bottom-buttons"> 
						<button ng-show="isCreating" type="submit" ng-click="submitFunction='insertApplication'" class="btn btn-success">SUBMIT APPLICATION</button> <!-- For applicant submitting for first time -->
						<button ng-show="isApprover || isAdmin" type="submit" ng-click="submitFunction='approveApplication'" class="btn btn-success">APPROVE APPLICATION</button> <!-- For approver or admin approving -->
						<button ng-show="isApprover || isAdmin" type="submit" ng-click="submitFunction='holdApplication'" class="btn btn-primary">HOLD APPLICATION</button> <!-- For approver or admin holding -->
						<button ng-show="isApprover || isAdmin" type="submit" ng-click="submitFunction='denyApplication'" class="btn btn-danger">DENY APPLICATION</button> <!-- For approver or admin denying -->
						<button ng-show="isChair" type="submit" ng-click="submitFunction='chairApproval'" class="btn btn-success">APPROVE APPLICATION</button> <!-- For department chair approving -->
						<button ng-show="isReviewing" type="submit" ng-click="submitFunction='uploadFiles'" class="btn btn-success">UPLOAD DOCS</button> <!-- For applicant reviewing application -->
						<a href="" class="btn btn-info" ng-click="redirectToHomepage(null, null)">LEAVE PAGE</a> <!-- For anyone to leave the page -->
					</div>
				</form>

			</div>

		</div>	
	</body>
</html>
<?php
	}else{
		include '../include/permission_denied.html';
	}
	$conn = null; //close connection
?>