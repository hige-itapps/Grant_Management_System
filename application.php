<?php
//For AJAX access
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

	/*Debug user validation*/
	//include "include/debugAuthentication.php";
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
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include 'include/site_banner.html'; ?>

	<div id="MainContent" role="main">
		
		<?php
		
		/*save the current date*/
		$newDate = DateTime::createFromFormat('Y/m/d', date("Y/m/d"));

		$submitDate = null; //date this app was submitted, if at all
		
		/*get initial character limits for text fields*/
		$appCharMax = getApplicationsMaxLengths($conn);
		$appBudgetCharMax = getApplicationsBudgetsMaxLengths($conn);

		//echo var_dump($appCharMax);
		
		$maxName = $appCharMax[array_search('Name', array_column($appCharMax, 0))][1]; //name char limit
		$maxEmail = $appCharMax[array_search('Email', array_column($appCharMax, 0))][1]; //email char limit
		$maxDep = $appCharMax[array_search('Department', array_column($appCharMax, 0))][1]; //department char limit
		$maxDepEmail = $appCharMax[array_search('DepartmentChairEmail', array_column($appCharMax, 0))][1]; //dep email char limit
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
			//admin updating check
			$isAdminUpdating = (isAdministrator($conn, $CASbroncoNetId) && isset($_GET["updating"])); //admin is viewing page; can edit stuff
			$permissionSet = $isAdminUpdating;

			//admin check
			if(!$permissionSet)
			{
				$isAdmin = isAdministrator($conn, $CASbroncoNetId); //admin is viewing page
				$permissionSet = $isAdmin;
			}
			
			//application approver check
			if(!$permissionSet)
			{
				$isApprover = isApplicationApprover($conn, $CASbroncoNetId); //application approver(director) can write notes, choose awarded amount, and generate email text
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
				$isCommittee = isUserAllowedToSeeApplications($conn, $CASbroncoNetId); //committee member; can only view!
				$permissionSet = $isCommittee;
			}
			
			//applicant reviewing check
			if(!$permissionSet)
			{
				$isReviewing = doesUserOwnApplication($conn, $CASbroncoNetId, $_GET['id']); //applicant is reviewing their application
				$permissionSet = $isReviewing;
			}
		}
		//applicant creating check. Note- if the app id is set, then by default the application cannot be created
		if(!$permissionSet && !isset($_GET["id"]))
		{
			$isCreating = isUserAllowedToCreateApplication($conn, $CASbroncoNetId, $CASallPositions, true); //applicant is creating an application (check latest date possible)
			$permissionSet = $isCreating; //will set to true if user is 
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



			$app = null; //only set app if it exists (if not creating one)
			
			/*Initialize variables if application has already been created*/
			if(!$isCreating)
			{
				$idA = $_GET["id"];
				
				$app = getApplication($conn, $idA); //get application Data

				$submitDate = DateTime::createFromFormat('Y-m-d', $app->dateS);
				
				$docs = listDocs($idA); //get documents
				for($i = 0; $i < count($docs); $i++)
				{
					if(substr($docs[$i], 0, 1) == 'P')
						array_push($P, "<a href='?id=" . $idA . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>");
					if(substr($docs[$i], 0, 1) == 'S')
						array_push($S, "<a href='?id=" . $idA . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>");
				}
				
				/*Admin wants to update application*/
				if($isAdminUpdating && isset($_POST["cancelUpdateApp"]))
				{
					header('Location: ?id=' . $idA); //reload page as admin
				}

				/*Admin wants to cancel updating this application*/
				if($isAdmin && isset($_POST["updateApp"]))
				{
					header('Location: ?id=' . $idA . '&updating'); //reload page as admin updating
				}
				
				/*User wants to approve this application*/
				if(isset($_POST["approveA"]))
				{
					if(approveApplication($conn, $idA, $_POST["aAw"]))
					{
						customEmail(trim($app->email), nl2br($_POST["finalE"]), "");
						header('Location: index.php'); //redirect
					}
				}
				
				/*User wants to deny this application*/
				if(isset($_POST["denyA"]))
				{
					if(denyApplication($conn, $idA))
					{
						customEmail(trim($app->email), nl2br($_POST["finalE"]), "");
						header('Location: index.php'); //redirect
					}
				}
				/*User wants to HOLD this application*/
				if(isset($_POST["holdA"]))
				{
					if(holdApplication($conn, $idA))
					{
						customEmail(trim($app->email), nl2br($_POST["finalE"]), "");
						header('Location: index.php'); //redirect
					}
				}
				
				/*Check for trying to sign application*/
				if($isChair && isset($_POST["signApp"]))
				{
					signApplication($conn, $idA, $_POST["deptChairApproval"]);
					header('Location: index.php'); //redirect to homepage
				}
			}
		?>
		<!--HEADER-->
		
			<!--BODY-->
			<div class="container-fluid">

				<?php if($isAdmin){ //form for admin updates- start ?>
					<form enctype="multipart/form-data" class="form-horizontal" id="updateForm" name="updateForm" method="POST" action="#">
						<input type="submit" onclick="return confirm ('Are you sure you want to enter update mode? Any unsaved data will be lost.')" class="btn btn-warning" id="updateApp" name="updateApp" value="---UPDATE MODE---" />
					</form>
				<?php }else if($isAdminUpdating){ //form for admin updates- end ?>
					<form enctype="multipart/form-data" class="form-horizontal" id="updateForm" name="updateForm" method="POST" action="#">
						<input type="submit" onclick="return confirm ('Are you sure you want to cancel updating? Any unsaved data will be lost.')" class="btn btn-warning" id="cancelUpdateApp" name="cancelUpdateApp" value="---CANCEL EDITS---" />
					</form>
				<?php } ?>



				<div ng-controller="appCtrl">

					<!-- SHOW ERROR/SUCCESS MESSAGES -->
					<div id="messages"></div>

						<!-- application form -->
					<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" ng-submit="processForm()">

						<?php if(!$isCreating){ //add extra POST parameter to keep track of app ID when updating ?>
							<input type="hidden" name="updateID" value="<?php echo $idA ?>" />
						<?php } ?>
					
						
						<?php
							if(isset($insertReturn)) //if we got a return, then there must be an error from inserting/updating the application the first time
							{
								echo '<h1 class="warning" id="smsg">Error(Code #'.$insertReturn[0].'): '.$insertReturn[1].'.</h1>';
							}
						?>


						<div class="row">
							<h1 class="title">APPLICATION:</h1>
						</div>
						

						<?php if($isCreating && isWithinWarningPeriod($newDate)){ //only display a warning if creating ?>
							<!--SUBMISSION CYCLE WARNING-->
							<div class="row">
								<h3 class="title warning">WARNING! DO NOT SUBMIT APPLICATION AFTER THE MIDNIGHT OF A CYCLE'S DUE DATE! <br/>
									<br/>If you do, your application will be automatically moved forward by one cycle!</h3>
							</div>
						<?php } ?>
						
					
						<!--SUBMISSION CYCLE-->
						<div class="row">
							<div class="col-md-4"></div>
							<div class="col-md-4">
								<fieldset>
								<legend>Submission Cycle:</legend>
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<p>Current date: <?php echo $newDate->format('Y/m/d'); ?></p>
											<div class="radio">
											<label><input ng-disabled="!allowedFirstCycle" type="radio" value="this" ng-model="formData.cycleChoice" name="cycleChoice">Submit For This Cycle (<?php echo getCycleName($newDate, false, true); ?>)</label>
											</div>
											<div class="radio">
											<label><input type="radio" value="next" ng-model="formData.cycleChoice" name="cycleChoice">Submit For Next Cycle (<?php echo getCycleName($newDate, true, true); ?>)</label>
											</div>
											
										<?php } else{ //for viewing or updating applications?>
											<p>Submission date: <?php echo $submitDate->format('Y/m/d'); ?></p>
											<div class="radio">
											<label><input <?php if($app->nextCycle != 1) echo "checked"; ?> <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> type="radio" value="this" ng-model="formData.cycleChoice" name="cycleChoice">Submit For This Cycle (<?php echo getCycleName($submitDate, false, true); ?>)</label>
											</div>
											<div class="radio">
											<label><input <?php if($app->nextCycle == 1) echo "checked"; ?> <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> type="radio" value="next" ng-model="formData.cycleChoice" name="cycleChoice">Submit For Next Cycle (<?php echo getCycleName($submitDate, true, true); ?>)</label>
											</div>
										<?php } ?>
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
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="name">Name ({{maxName-formData.name.length}} characters remaining):</label>
										<input type="text" class="form-control" maxlength="{{maxName}}" ng-model="formData.name" id="name" name="name" placeholder="Enter Name"  <?php if($isAdminUpdating){echo 'value="'.$app->name.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<label for="name">Name:</label>
										<input type="text" class="form-control" ng-model="formData.name" id="name" name="name" placeholder="Enter Name" disabled="true" value="<?php echo $app->name; ?>"/>
									<?php } ?>
								</div>
							</div>

						<!--EMAIL-->
							<div class="col-md-7">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="email">Email Address ({{maxEmail-formData.email.length}} characters remaining):</label>
										<input type="email" class="form-control" maxlength="{{maxEmail}}" ng-model="formData.email" id="email" name="email" placeholder="Enter Email Address"  <?php if($isAdminUpdating){ echo 'value="'.$app->email.'"'; } ?> />
									<?php }else{ //for viewing applications ?>
										<label for="email">Email Address:</label>
										<input type="email" class="form-control" ng-model="formData.email" id="email" name="email" placeholder="Enter Email Address" disabled="true" value="<?php echo $app->email; ?>"/>
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<div class="row">
						<!--DEPARTMENT-->
						<div class="col-md-5">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="department">Department ({{maxDep-formData.department.length}} characters remaining):</label>
										<input type="text" class="form-control" maxlength="{{maxDep}}" ng-model="formData.department" id="department" name="department" placeholder="Enter Department" <?php if($isAdminUpdating){ echo 'value="'.$app->dept.'"'; } ?> />
									<?php  }else{ //for viewing applications ?>
										<label for="department">Department:</label>
										<input type="text" class="form-control" ng-model="formData.department" id="department" name="department" placeholder="Enter Department" disabled="true" value="<?php echo $app->dept; ?>"/>
									<?php } ?>
								</div>
							</div>

							
						<!--DEPT CHAIR EMAIL-->
							<div class="col-md-7">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="deptChairEmail">Department Chair's WMU Email Address ({{maxDepEmail-formData.deptChairEmail.length}} characters remaining):</label>
										<input type="email" class="form-control" maxlength="{{maxDepEmail}}" ng-model="formData.deptChairEmail" id="deptChairEmail" name="deptChairEmail" placeholder="Enter Department Chair's Email Address" <?php if($isAdminUpdating){echo 'value="'.$app->deptCE.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<label for="deptChairEmail">Department Chair's WMU Email Address:</label>
										<input type="email" class="form-control" ng-model="formData.deptChairEmail" id="deptChairEmail" name="deptChairEmail" placeholder="Enter Department Chair's Email Address" disabled="true" value="<?php echo $app->deptCE; ?>" />
									<?php } ?>
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
									<label for="travelFrom">Travel Date From:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" ng-model="formData.travelFrom" id="travelFrom" name="travelFrom" <?php if($isAdminUpdating){echo 'value="'.$app->tStart.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" ng-model="formData.travelFrom" id="travelFrom" name="travelFrom" disabled="true" value="<?php echo $app->tStart; ?>" />
									<?php } ?>
								</div>
							</div>
							
						
						<!--TRAVEL DATE TO-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="travelTo">Travel Date To:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" ng-model="formData.travelTo" id="travelTo" name="travelTo" <?php if($isAdminUpdating){echo 'value="'.$app->tEnd.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" ng-model="formData.travelTo" id="travelTo" name="travelTo" disabled="true" value="<?php echo $app->tEnd; ?>" />
									<?php } ?>
								</div>
							</div>
							
							
						<!--ACTIVITY DATE FROM-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="activityFrom">Activity Date From:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" ng-model="formData.activityFrom" id="activityFrom" name="activityFrom" <?php if($isAdminUpdating){echo 'value="'.$app->aStart.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" ng-model="formData.activityFrom" id="activityFrom" name="activityFrom" disabled="true" value="<?php echo $app->aStart; ?>" />
									<?php } ?>
								</div>
							</div>
							
							
						<!--ACTIVITY DATE TO-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="activityTo">Activity Date To:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" ng-model="formData.activityTo" id="activityTo" name="activityTo" <?php if($isAdminUpdating){echo 'value="'.$app->aEnd.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" ng-model="formData.activityTo" id="activityTo" name="activityTo" disabled="true" value="<?php echo $app->aEnd; ?>" />
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<div class="row">
						<!--TITLE-->
							<div class="col-md-4">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="title">Project Title ({{maxTitle-formData.title.length}} characters remaining):</label>
										<input type="text" class="form-control" maxlength="{{maxTitle}}" ng-model="formData.title" id="title" name="title" placeholder="Enter Title of Research" <?php if($isAdminUpdating){echo 'value="'.$app->rTitle.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<label for="title">Project Title:</label>
										<input type="text" class="form-control" ng-model="formData.title" id="title" name="title" placeholder="Enter Title of Research" disabled="true" value="<?php echo $app->rTitle; ?>" />
									<?php } ?>
								</div>
							</div>
							
							
						<!--DESTINATION-->
							<div class="col-md-4">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="destination">Destination ({{maxDestination-formData.destination.length}} characters remaining):</label>
										<input type="text" class="form-control" maxlength="{{maxDestination}}" ng-model="formData.destination" id="destination" name="destination" placeholder="Enter Destination" <?php if($isAdminUpdating){echo 'value="'.$app->dest.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<label for="destination">Destination:</label>
										<input type="text" class="form-control" ng-model="formData.destination" id="destination" name="destination" placeholder="Enter Destination" disabled="true" value="<?php echo $app->dest; ?>" />
									<?php } ?>
								</div>
							</div>
							
							
						<!--AMOUNT REQ-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="amountRequested">Amount Requested($):</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="text" class="form-control" ng-model="formData.amountRequested" id="amountRequested" name="amountRequested" placeholder="Enter Amount Requested($)" onkeypress='return (event.which >= 48 && event.which <= 57) 
											|| event.which == 8 || event.which == 46' <?php if($isAdminUpdating){echo 'value="'.$app->aReq.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<input type="text" class="form-control" ng-model="formData.amountRequested" id="amountRequested" name="amountRequested" placeholder="Enter Amount Requested($)" disabled="true" value="<?php echo $app->aReq; ?>" />
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<!--PURPOSES-->
						<fieldset>
						<legend>Purpose of Travel:</legend>
						
							<!--PURPOSE:RESEARCH-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.purpose1" name="purpose1" type="checkbox" value="purpose1">Research</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.purpose1" name="purpose1" type="checkbox" value="purpose1" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->pr1 == 1) echo "checked"; ?>>Research</label>
										<?php } ?>
									</div>
								</div>
							</div>
							
							
							<!--PURPOSE:CONFERENCE-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.purpose2" name="purpose2" type="checkbox" value="purpose2">Conference</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.purpose2" name="purpose2" type="checkbox" value="purpose2" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->pr2 == 1) echo "checked"; ?>>Conference</label>
										<?php } ?>
									</div>
								</div>
							</div>
							
							
							<!--PURPOSE:CREATIVE ACTIVITY-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating  applications ?>
											<label><input ng-model="formData.purpose3" name="purpose3" type="checkbox" value="purpose3">Creative Activity</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.purpose3" name="purpose3" type="checkbox" value="purpose3" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->pr3 == 1) echo "checked"; ?>>Creative Activity</label>
										<?php } ?>
									</div>
								</div>
							</div>
							
							
							<!--PURPOSE:OTHER-->
							<div class="row">
								<div class="col-md-2">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.purpose4OtherDummy" name="purpose4OtherDummy" id="purpose4OtherDummy" type="checkbox" value="purpose4OtherDummy">Other, explain.</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.purpose4OtherDummy" name="purpose4OtherDummy" id="purpose4OtherDummy" type="checkbox" value="purpose4OtherDummy" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->pr4 != "") echo "checked"; ?>>Other, explain.</label>
										<?php } ?>
									</div>
								</div>
								
								<div class="col-md-10">
									<div class="form-group">
										<?php if($isCreating){ //for creating applications ?>
											<label for="purpose4Other">Explain other purpose ({{maxOtherEvent-formData.purpose4Other.length}} characters remaining):</label>
											<input type="text" class="form-control" maxlength="{{maxOtherEvent}}" ng-model="formData.purpose4Other" id="purpose4Other" name="purpose4Other" disabled="true" placeholder="Enter Explanation" />
										<?php }else if($isAdminUpdating){ //for updating applications ?>
											<label for="purpose4Other">Explain other purpose ({{maxOtherEvent-formData.purpose4Other.length}} characters remaining):</label>
											<input type="text" class="form-control" maxlength="{{maxOtherEvent}}" ng-model="formData.purpose4Other" id="purpose4Other" name="purpose4Other" disabled="true" placeholder="Enter Explanation" disabled="true" value="<?php echo $app->pr4; ?>"/>
										<?php }else{ //for viewing applications ?>
											<label for="purpose4Other">Explain other purpose:</label>
											<input type="text" class="form-control" ng-model="formData.purpose4Other" id="purpose4Other" name="purpose4Other" disabled="true" placeholder="Enter Explanation" disabled="true" value="<?php echo $app->pr4; ?>"/>
										<?php } ?>
									</div>
								</div>
							</div>
						
						</fieldset>
						
						
						<!--OTHER FUNDING-->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="otherFunding">Are you receiving other funding? Who is providing the funds? How much? ({{maxOtherFunding-formData.otherFunding.length}} characters remaining):</label>
										<input type="text" class="form-control" maxlength="{{maxOtherFunding}}" ng-model="formData.otherFunding" id="otherFunding" name="otherFunding" placeholder="Explain here" <?php if($isAdminUpdating){echo 'value="'.$app->oF.'"';} ?>/>
									<?php }else{ //for viewing applications ?>
										<label for="otherFunding">Are you receiving other funding? Who is providing the funds? How much?:</label>
										<input type="text" class="form-control" ng-model="formData.otherFunding" id="otherFunding" name="otherFunding" placeholder="Explain here" disabled="true" value="<?php echo $app->oF; ?>"/>
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<!--PROPOSAL SUMMARY-->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="proposalSummary">Proposal Summary ({{maxProposalSummary-formData.proposalSummary.length}} characters remaining) (We recommend up to 150 words):</label>
										<textarea class="form-control" maxlength="{{maxProposalSummary}}" ng-model="formData.proposalSummary" id="proposalSummary" name="proposalSummary" placeholder="Enter Proposal Summary" rows="10"><?php if($isAdminUpdating){echo $app->pS;} ?></textarea>
									<?php }else{ //for viewing applications ?>
										<label for="proposalSummary">Proposal Summary:</label>
										<textarea class="form-control" ng-model="formData.proposalSummary" id="proposalSummary" name="proposalSummary" placeholder="Enter Proposal Summary" rows="10" disabled="true"><?php echo $app->pS; ?></textarea>
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<!--GOALS-->
						<fieldset>
						<legend>Please indicate which of the prioritized goals of the IEFDF this proposal fulfills:</legend>
						
							<!--GOAL 1-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.goal1" name="goal1" type="checkbox" value="goal1">
											Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.goal1" name="goal1" type="checkbox" value="goal1" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->fg1 == 1) echo "checked"; ?>>
											Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
										<?php } ?>
									</div>
								</div>
							</div>
							
							
							<!--GOAL 2-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.goal2" name="goal2" type="checkbox" value="goal2">
											Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.goal2" name="goal2" type="checkbox" value="goal2" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->fg2 == 1) echo "checked"; ?>>
											Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
										<?php } ?>
									</div>
								</div>
							</div>
							
							
							<!--GOAL 3-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.goal3" name="goal3" type="checkbox" value="goal3">
											Support for attendance at international conferences, seminars or workshops.</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.goal3" name="goal3" type="checkbox" value="goal3" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->fg3 == 1) echo "checked"; ?>>
											Support for attendance at international conferences, seminars or workshops.</label>
										<?php } ?>
									</div>
								</div>
							</div>
							
							
							<!--GOAL 4-->
							<div class="row">
								<div class="col-md-12">
									<div class="checkbox">
										<?php if($isCreating){ //for creating applications ?>
											<label><input ng-model="formData.goal4" name="goal4" type="checkbox" value="goal4">
											Support for scholarly international travel in order to enrich international knowledge, which will directly
											contribute to the internationalization of the WMU curricula.</label>
										<?php }else{ //for viewing or updating applications ?>
											<label><input ng-model="formData.goal4" name="goal4" type="checkbox" value="goal4" <?php if(!$isAdminUpdating){echo 'disabled="true"';} ?> <?php if($app->fg4 == 1) echo "checked"; ?>>
											Support for scholarly international travel in order to enrich international knowledge, which will directly
											contribute to the internationalization of the WMU curricula.</label>
										<?php } ?>
									</div>
								</div>
							</div>
						
						</fieldset>
						
						
						<!--BUDGET-->
						<div class="row">
							<h2 class="title">Budget: (please separate room and board calculating per diem)</h2>
						</div>
						
						<div id="exampleBudgetHolder">
							<a id="budgetExampleButton" data-toggle="collapse" class="btn btn-info" data-target="#budgetExample">Click here for an example of how to construct a budget!</a>
							<div id="budgetExample" class="collapse">
								<img src="images/BudgetExample.PNG" alt="Here is an example budget item: Expense: Registration Fee, Comments: Conference Registration, Amount($): 450" class="exampleBudget" />
							</div>
						</div>
						
						
						
						<div class="row">
							<div class="col-md-12">
								<table id="budgetList" class="table table-sm">
								<caption>Current Budget:</caption>
								<!--BUDGET:TABLE HEAD-->
									<thead>
										<tr>
											<th>Expense:</th>
											
											<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
												<th>Comments (up to {{maxBudgetComment}} characters):</th>
											<?php }else{ //for viewing applications ?>
												<th>Comments:</th>
											<?php } ?>
											
											<th>Amount($):</th>
										</tr>
									</thead>
									
									
								<!--BUDGET:TABLE BODY-->
									<tbody>
										<tr class="row" ng-repeat="budgetItem in formData.budgetItems">
										<!--BUDGET:EXPENSE-->
											<td>
												<div class="form-group">
													<?php if($isCreating){ //for creating applications ?>
														<select ng-model="budgetItem.expense" class="form-control" name="{{budgetItem.expense}}" value="{{budgetItem.expense}}" >
															<option ng-repeat="o in options" value="{{o.name}}">{{o.name}}</option>
														</select>
													<?php }else{ //for viewing or updating applications ?>
														<select ng-model="budgetItem.expense" class="form-control" name="{{budgetItem.expense}}" value="{{budgetItem.expense}}" <?php if(!$isAdminUpdating){echo 'disabled';} ?>>
															<option ng-repeat="o in options" value="{{o.name}}">{{o.name}}</option>
														</select>
													<?php } ?>
												</div>
											</td>
								
								
										
										<!--BUDGET:COMMENTS-->
											<td>
												<div class="form-group">
													<?php if($isCreating){ //for creating applications ?>
														<input type="text" class="form-control" maxlength="{{maxBudgetComment}}" name="{{budgetItem.comment}}" ng-model="budgetItem.comment" placeholder="Explain..." />
													<?php }else{ //for viewing or updating applications ?>
														<input type="text" class="form-control" name="{{budgetItem.comment}}" ng-model="budgetItem.comment" placeholder="Explain..." <?php if(!$isAdminUpdating){echo 'disabled';} ?> value="{{budgetItem.comment}}" />
													<?php } ?>
												</div>
											</td>
											
											
										<!--BUDGET:AMOUNT-->
											<td>
												<div class="form-group">
													<?php if($isCreating || $isAdminUpdating){ //for creating applications ?>
														<input type="text" class="form-control" name="{{budgetItem.amount}}" ng-model="budgetItem.amount" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' />
													<?php }else{ //for viewing or updating applications ?>
														<input type="text" class="form-control" name="{{budgetItem.amount}}" ng-model="budgetItem.amount" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' <?php if(!$isAdminUpdating){echo 'disabled';} ?> value="{{budgetItem.amount}}" />
													<?php } ?>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						
						
						
						<!--BUDGET:ADD OR REMOVE-->
						<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications only ?>
							<div class="row">
								<div class="col-md-5"></div>
								<div id="budgetButtons" class="col-md-2">
									<p id="addBudget"><i class="fa fa-plus-circle fa-2x" aria-hidden="true" ng-click="addInput()"></i></p>
									<p id="removeBudget"><i class="fa fa-minus-circle fa-2x" aria-hidden="true" ng-click="remInput()"></i></p>
								</div>
								<div class="col-md-5"></div>
							</div>
						<?php } ?>
						
						
						
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
						
						
						
						<br><br>
						<div class="row">
						<!--DEPARTMENT CHAIR APPROVAL-->
							<div class="col-md-3"></div>
							<div class="col-md-6">
								<h3 class="title">Note: Applications received without the approval of the chair will not be considered.</h3>
								<div class="form-group">
									<?php if($isChair){ //for department chair to sign ?>
											<label for="deptChairApproval">Your Approval (up to <?php echo $maxDepChairSig ?> characters):</label>
											<input type="text" class="form-control" ng-model="formData.deptChairApproval" id="deptChairApproval" name="deptChairApproval" placeholder="Type Your Full Name Here" required/>
									<?php }else{ //not department chair
											if($isCreating){ //for when user is creating application ?>
												<label for="deptChairApproval">Department Chair Approval:</label>
												<input type="text" class="form-control" ng-model="formData.deptChairApproval" id="deptChairApproval" name="deptChairApproval" placeholder="Department Chair Must Type Name Here" disabled="true"/>
											<?php }else if(isApplicationSigned($conn, $idA) > 0){ //application is signed, so show signature ?>
												<label for="deptChairApproval">Department Chair Approval:</label>
												<input type="text" class="form-control" ng-model="formData.deptChairApproval" id="deptChairApproval" name="deptChairApproval" placeholder="Department Chair Must Type Name Here" disabled="true" value="<?php echo $app->deptCS; ?>"/>
										<?php }else{ //application isn't signed, so show default message ?>
												<label for="deptChairApproval">Department Chair Approval:</label>
												<input type="text" class="form-control" ng-model="formData.deptChairApproval" id="deptChairApproval" name="deptChairApproval" placeholder="Department Chair Must Type Name Here" disabled="true"/>
									<?php }
										} ?>
								</div>
							</div>
							<div class="col-md-3"></div>
						</div>
						<?php if($isApprover || $isAdmin) { ?>
						<div class="row">
						<!--EMAIL EDIT-->
							<div class="col-md-12">
								<div class="form-group">
									<label for="finalE">EMAIL TO BE SENT:</label>
									<textarea class="form-control" ng-model="formData.finalE" id="finalE" name="finalE" placeholder="Enter email body, with greetings." rows=20 required /></textarea>
								</div>
							</div>
						</div>
						<div class="row">
						<!--AMOUNT AWARDED-->
							<div class="col-md-12">
								<div class="form-group">
									<label for="aAw">AMOUNT AWARDED($):</label>
									<input type="text" class="form-control" ng-model="formData.aAw" id="aAw" name="aAw" placeholder="AMOUNT AWARDED" value="<?php echo $app->awarded; ?>" onkeypress='return (event.which >= 48 && event.which <= 57) 
									|| event.which == 8 || event.which == 46' />
								</div>
							</div>
						</div>
						<?php } ?>
						<br><br>

						<a href="#" id="search-btn" ng-click="processForm()">submit</a>
						
						<div class="row">
							<div class="col-md-2"></div>
						<!--SUBMIT BUTTONS-->
							<div class="col-md-6">
								<?php if($isCreating){ //show submit application button if creating ?>
									<input type="submit" onclick="return confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT APPLICATION" />
								<?php }else if($isAdminUpdating){ //show submit edits button if editing?>
									<input type="submit" onclick="return confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT EDITS" />
								<?php }else if($isAdmin || $isApprover){ //show approve, hold, and deny buttons if admin or approver ?>
									<input type="submit" onclick="return confirm ('By confirming, your email will be sent to the applicant! Are you sure you want to approve this application?')" class="btn btn-success" id="approveApp" name="approveA" value="APPROVE APPLICATION" <?php if(isApplicationSigned($conn, $idA) == 0) { ?> disabled="true" <?php } ?> />
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
					</form>
					
					<span id="loadSpinner" class="lt" style="visibility: hidden;">Submitting... <i class="fa fa-spinner fa-spin" style="font-size:35px !important;"></i></span>
					
					<!-- SHOW DATA FROM INPUTS AS THEY ARE BEING TYPED -->
					<pre>
						{{ formData }}
					</pre>
					<pre>
						{{ formData.budgetItems }}
					</pre>

				</div>


				


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
		
		myApp.controller('appCtrl', ['$scope', '$http', '$sce', function($scope, $http, $sce){

			/*Functions*/

			// process the form (AJAX request)
			$scope.processForm = function() {
				//alert("Sending data");
				
				$http({
					method  : 'POST',
					url     : 'http://hige-iefdf-vm.wade.wmich.edu/application_form.php?1=1',
					data    : $.param($scope.formData),  // pass in data as strings
					headers : { 'Content-Type': 'application/x-www-form-urlencoded' }  // set the headers so angular passing info as form data (not request payload)
				})
				.then(function (response) {
					console.log(response, 'res');
					//data = response.data;
					$scope.message = response;
				},function (error){
					console.log(error, 'can not get data.');
				});

			};

			//Add new budget item
			$scope.addInput = function(expense, comment, amount) {
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
			$scope.remInput = function() {
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




			/*On startup*/

			// create a blank object to hold our form information
			// $scope will allow this to pass between controller and view
			$scope.formData = {};
			$scope.formData.budgetItems = []; //array of budget items
			//expense types
			$scope.options = [{ name: "Air Travel"}, 
								{ name: "Ground Travel"},
								{ name: "Hotel"},
								{ name: "Registration Fee"},
								{ name: "Per Diem"},
								{ name: "Other"}];


			$scope.allowedFirstCycle = false;


			/*Get user's email address*/
			$CASemail = <?php echo json_encode($CASemail); ?>;

			/*Get character limits from php code*/				
			$scope.maxName = <?php echo json_encode($maxName); ?>;
			$scope.maxEmail = <?php echo json_encode($maxEmail); ?>;
			$scope.maxDep = <?php echo json_encode($maxDep); ?>;
			$scope.maxDepEmail = <?php echo json_encode($maxDepEmail); ?>;
			$scope.maxTitle = <?php echo json_encode($maxTitle); ?>;
			$scope.maxDestination = <?php echo json_encode($maxDestination); ?>;
			$scope.maxOtherEvent = <?php echo json_encode($maxOtherEvent); ?>;
			$scope.maxOtherFunding = <?php echo json_encode($maxOtherFunding); ?>;
			$scope.maxProposalSummary = <?php echo json_encode($maxProposalSummary); ?>;
			$scope.maxDepChairSig = <?php echo json_encode($maxDepChairSig); ?>;
			$scope.maxBudgetComment = <?php echo json_encode($maxBudgetComment); ?>;
			
			
			/*Get user permissions from php code*/
			$isCreating = <?php echo json_encode($isCreating); ?>;
			$isReviewing = <?php echo json_encode($isReviewing); ?>;
			$isAdmin = <?php echo json_encode($isAdmin); ?>;
			$isAdminUpdating = <?php echo json_encode($isAdminUpdating); ?>;
			$isCommittee = <?php echo json_encode($isCommittee); ?>;
			$isChair = <?php echo json_encode($isChair); ?>;
			$isChairReviewing = <?php echo json_encode($isChairReviewing); ?>;
			$isApprover = <?php echo json_encode($isApprover); ?>;

			/*If not creating, get app data and populate entire form*/
			if(!$isCreating)
			{
				$app = <?php echo json_encode($app); ?>; //app data from php code
			}
			else //otherwise, only fill in a few fields
			{
				//find out if user is allowed to create application for the first available cycle
				$scope.allowedFirstCycle = <?php echo json_encode(isUserAllowedToCreateApplication($conn, $CASbroncoNetId, $CASallPositions, false)); ?>;

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
			}


			//add a few empty budget items in by default if creating application. Otherwise, load all the existing items
			<?php if($isCreating){ ?>
				$scope.addInput();
				$scope.addInput();
				$scope.addInput();
			<?php }else{ 
				for($i = 0; $i < count($app->budget); $i++) { ?>
					$scope.addInput("<?php echo $app->budget[$i][2]; ?>" , "<?php echo $app->budget[$i][4]; ?>" , <?php echo $app->budget[$i][3]; ?>);
				<?php }
			 } ?>

		}]);
		
		
		function onSubmission() {
			$("#loadSpinner").css("visibility", "visible");
		}
		
		/*Messages disabled*/
		/*var c = 6;
		setInterval(function() {
			if(c != 0)
				c--;
			if(c == 0)
				if(document.getElementById("smsg") != null)
					$("#smsg").remove();
		}, 1000);*/
		
		/*FIN DATES*/
		/*OTHER ACTIVITY CHECK*/
		/*activate 'other purpose' box when corresponding checkbox is checked*/
		document.getElementById('purpose4OtherDummy').onchange = function() {
			document.getElementById('purpose4Other').disabled = !this.checked;
		};
		
	</script>
	<!-- End Script -->
</html>
<?php
	$conn = null; //close connection
?>