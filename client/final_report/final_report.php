<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../CAS/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php");
	
	/*Cycle functions*/
	include_once(dirname(__FILE__) . "/../../server/Cycles.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/../../server/DocumentsHelper.php");

	/*Logger*/
	include_once(dirname(__FILE__) . "/../server/Logger.php");

	$logger = new Logger(); //for logging to files
	$database = new DatabaseHelper($logger); //database helper object used for some verification and insertion
	$cycles = new Cycles(); //Cycles helper object
	$documentsHelper = new DocumentsHelper($logger); //initialize DocumentsHelper object

	$maxUploadSize = $documentsHelper->file_upload_max_size(); //get the max file upload size
	$uploadTypes = $settings["upload_types"]; //get the allowed upload types, keep it as a comma separated string
	
	/*get initial character limits for text fields*/
	$reportCharMax = $database->getFinalReportsMaxLengths();

	$maxProjectSummary = $reportCharMax[array_search('ProjectSummary', array_column($reportCharMax, 0))][1]; //project summary char limit
	
	$report = null; //only set report if it exists (when not creating a new one)
	$reportFiles = null; //^
	$reportEmails = null; //^

	$staffNotes = null; //only set if report exists AND user is a staff member

	/*Initialize all user permissions to false*/
	$isCreating = false; //user is an applicant initially creating report
	$isReviewing = false; //user is an applicant reviewing their already created report
	$isAdmin = false; //user is an administrator
	$isCommittee = false; //user is a committee member
	$isChairReviewing = false; //user is the associated department chair, but cannot do anything (just for reviewing purposes)
	$isFinalReportApprover = false; //user is a final report approver
	
	$permissionSet = false; //boolean set to true when a permission has been set- used to force only 1 permission at most
	
	/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
	No matter what, the app ID MUST BE SET*/
	if(isset($_GET["id"]))
	{
		if($permissionSet = $isAdmin = $database->isAdministrator($CASbroncoNetID)){} //admin check
		else if($permissionSet = $isFinalReportApprover = $database->isFinalReportApprover($CASbroncoNetID)){} //final report approver check
		else if($permissionSet = $isCommittee = $database->isCommitteeMember($CASbroncoNetID)){} //committee member check
		else if($permissionSet = $isChairReviewing = $database->isUserDepartmentChair($$CASemail, $_GET['id'], $CASbroncoNetID)){} //department chair reviewing check
		else if($permissionSet = $isCreating = $database->isUserAllowedToCreateFinalReport($CASbroncoNetID, $_GET['id'])){} //applicant creating check
		else if($permissionSet = $isReviewing = $database->doesUserOwnApplication($CASbroncoNetID, $_GET['id']) && $database->getFinalReport($_GET['id'])){} //applicant reviewing check
	}
	
	/*Verify that user is allowed to render report*/
	if($permissionSet)
	{
		$appID = $_GET["id"];
		$app = $database->getApplication($appID); //get application Data

		/*Initialize variables if report has already been created*/
		if(!$isCreating)
		{
			$report = $database->getFinalReport($appID); //get final report data
			$reportFiles = $documentsHelper->getFileNames($appID);
			$reportEmails = $database->getEmails($appID);

			if($isAdmin || $isFinalReportApprover || $isCommittee) //if hige staff, then retrieve staff notes
			{
				$staffNotes = $database->getStaffNotes($appID);
			}
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
			var scope_maxUploadSize = <?php echo json_encode($maxUploadSize); ?>;
			var scope_uploadTypes = <?php echo json_encode($uploadTypes); ?>;
			var scope_maxProjectSummary = <?php echo json_encode($maxProjectSummary); ?>;
			var scope_isCreating = <?php echo json_encode($isCreating); ?>;
			var scope_isReviewing = <?php echo json_encode($isReviewing); ?>;
			var scope_isAdmin = <?php echo json_encode($isAdmin); ?>;
			var scope_isCommittee = <?php echo json_encode($isCommittee); ?>;
			var scope_isChairReviewing = <?php echo json_encode($isChairReviewing); ?>;
			var scope_isFinalReportApprover = <?php echo json_encode($isFinalReportApprover); ?>;
			var var_app = <?php echo json_encode($app); ?>; //application data
			var var_report = <?php echo json_encode($report); ?>; //report data
			var var_reportFiles = <?php echo json_encode($reportFiles); ?>; //the associated uploaded files
			var var_reportEmails = <?php echo json_encode($reportEmails); ?>; //the associated saved emails
			var scope_staffNotes = <?php echo json_encode($staffNotes); ?>; //the associated staff notes if allowed
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="final_report.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include '../include/site_banner.html'; ?>

	<div id="MainContent" role="main">
		<script src="../include/outdatedbrowser.js" nomodule></script> <!-- show site error if outdated -->
		<?php include '../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->
		
			<!--AngularJS Controller-->
			<div class="container-fluid" ng-controller="reportCtrl" id="reportCtrl">

				<h1 ng-cloak ng-show="!isCreating" class="{{reportStatus}}-background status-bar">Report Status: {{reportStatus}}</h1>

				<div ng-cloak ng-show="isAdmin || isAdminUpdating" class="buttons-group"> 
					<button type="button" ng-click="toggleAdminUpdate()" class="btn btn-warning">TURN {{isAdminUpdating ? "OFF" : "ON"}} ADMIN UPDATE MODE</button>
					<button type="button" ng-click="populateForm(null)" class="btn btn-warning">RELOAD SAVED DATA</button>
					<button type="button" ng-click="insertReport()" class="btn btn-warning">SUBMIT CHANGES</button>
				</div>
				
				<!-- final report form -->
				<form enctype="multipart/form-data" class="form-horizontal" id="reportForm" name="reportForm" ng-submit="submit()">
				

				
					<div class="row">
						<h1 class="title">FINAL REPORT:</h1>
					</div>
				
					<!--APPLICANT INFO-->
					<div class="row">
						<h2 class="title">Applicant Information:</h2>
					</div>
					


					<div class="row">
					<!--NAME-->
						<div class="col-md-5">
							<div class="form-group">
								<label for="name">Name:</label>
								<input type="text" class="form-control" ng-model="formData.name" ng-disabled="true" id="name" name="name" />
							</div>
						</div>
					<!--EMAIL-->
						<div class="col-md-7">
							<div class="form-group">
								<label for="email">Email Address:</label>
								<input type="email" class="form-control" ng-model="formData.email" ng-disabled="true" id="email" name="email" />
							</div>
						</div>
					</div>
					
					
					
					<div class="row">
					<!--DEPARTMENT-->
						<div class="col-md-5">
							<div class="form-group">
								<label for="department">Department:</label>
								<input type="text" class="form-control" ng-model="formData.department" ng-disabled="true" id="department" name="department" />
							</div>
						</div>
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-7">
							<div class="form-group">
								<label for="deptChairEmail">Department Chair's Email Address:</label>
								<input type="email" class="form-control" ng-model="formData.deptChairEmail" ng-disabled="true" id="deptChairEmail" name="deptChairEmail" />
							</div>
						</div>
					</div>
					
					
					
					<!--RESEARCH INFO-->
					<div class="row">
						<p><h2 class="title">Final Report Information:</h2></p>
					</div>
					


					<div class="row">
					<!--TITLE-->
						<div class="col-md-6">
							<div class="form-group">
								<label for="title">Project Title:</label>
								<input type="text" class="form-control" ng-model="formData.title" ng-disabled="true" id="title" name="title" />
							</div>
						</div>
					<!--DESTINATION-->
						<div class="col-md-6">
							<div class="form-group">
								<label for="destination">Destination:</label>
								<input type="text" class="form-control" ng-model="formData.destination" ng-disabled="true" id="destination" name="destination" />
							</div>
						</div>
					</div>

					
					
					<div class="row">
					<!--TRAVEL DATE FROM-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="travelFrom">Travel Date From{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
								<input type="date" class="form-control" ng-model="formData.travelFrom" ng-disabled="reportFieldsDisabled" id="travelFrom" name="travelFrom" />
								<span class="help-block" ng-show="errors.travelFrom" aria-live="polite">{{ errors.travelFrom }}</span> 
							</div>
						</div>
					<!--TRAVEL DATE TO-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="travelTo">Travel Date To{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
								<input type="date" class="form-control" ng-model="formData.travelTo" ng-disabled="reportFieldsDisabled" id="travelTo" name="travelTo" />
								<span class="help-block" ng-show="errors.travelTo" aria-live="polite">{{ errors.travelTo }}</span> 
							</div>
						</div>
					<!--ACTIVITY DATE FROM-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="activityFrom">Activity Date From{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
								<input type="date" class="form-control" ng-model="formData.activityFrom"ng-disabled="reportFieldsDisabled"  id="activityFrom" name="activityFrom" />
								<span class="help-block" ng-show="errors.activityFrom" aria-live="polite">{{ errors.activityFrom }}</span> 
							</div>
						</div>
					<!--ACTIVITY DATE TO-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="activityTo">Activity Date To{{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
								<input type="date" class="form-control" ng-model="formData.activityTo" ng-disabled="reportFieldsDisabled" id="activityTo" name="activityTo" />
								<span class="help-block" ng-show="errors.activityTo" aria-live="polite">{{ errors.activityTo }}</span> 
							</div>
						</div>
					</div>
					
					
					
					<!--AMOUNT AWARDED SPENT-->
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="amountAwardedSpent">Awarded Amount Spent($){{isCreating || isAdminUpdating ? " (Required)" : ""}}:</label>
								<input type="text" class="form-control" ng-model="formData.amountAwardedSpent" ng-disabled="reportFieldsDisabled" id="amountAwardedSpent" name="amountAwardedSpent" placeholder="Enter Awarded Amount Spent($)" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' />
								<span class="help-block" ng-show="errors.amountAwardedSpent" aria-live="polite">{{ errors.amountAwardedSpent }}</span> 
							</div>
						</div>
					</div>


					
					<!--PROJECT SUMMARY-->
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="projectSummary">Project Summary{{isCreating || isAdminUpdating ? " (Required) ("+(maxProjectSummary-formData.projectSummary.length)+" characters remaining)" : ""}}:</label>
								<textarea class="form-control" maxlength="{{maxProjectSummary}}" ng-model="formData.projectSummary" ng-disabled="reportFieldsDisabled" id="projectSummary" name="projectSummary" placeholder="Enter Project Summary" rows="10"> </textarea>
								<span class="help-block" ng-show="errors.projectSummary" aria-live="polite">{{ errors.projectSummary }}</span> 
							</div>
						</div>
					</div>



					<div class="row">
						<h2 class="title">Attachments:</h2>
						<h3 ng-show="isCreating || isReviewing || isAdminUpdating">Please Upload Documentation (Travel Expense Vouchers, Receipts, Travel Authorization Forms, Etc.). The maximum allowed size for each file is {{maxUploadSize/1048576}}MB.</h3>
					</div>
					
					
					
					<!--UPLOADS-->
					<div class="row">
						<div class="col-md-12">
							<div ng-cloak ng-show="isCreating || isReviewing || isAdminUpdating" class="uploadedList">
								<hr>
								<div class="upload-button btn btn-primary">
									<label for="uploadDocs">UPLOAD DOCUMENTS</label>
									<input type="file" readdocuments="uploadDocs" id="uploadDocs" name="uploadDocs" multiple accept="{{uploadTypes}}"/>
								</div>
								<h4>Your selected documents:</h4>
								<ul>
									<li ng-repeat="file in uploadDocs">{{file.name}} <a href="" ng-click="removeDoc($index)" class="remove-file">REMOVE</a> </li>
								</ul>
							</div>
							<hr>
							<div class="uploadedList">
								<h4 class="title">UPLOADED DOCUMENTS (Click file to download): </h4>
								<ul>
									<li ng-repeat="file in reportFiles" ng-if="file.indexOf('FD') == 0"><a href="" ng-click="downloadFile(file)">{{file}}</a></li>
								</ul>
							</div>
						</div>
					</div>



					<!--STAFF NOTES-->
					<div id="staffNotesHolder" class="row" ng-show="isAdmin || isFinalReportApprover || isCommittee">
						<div class="col-md-3"></div>
						<div class="col-md-6">
							<label for="staffNotes">Staff Notes:</label>
							<textarea class="form-control" ng-disabled="!isAdmin && !isFinalReportApprover" ng-model="staffNotes[1]" id="staffNotes" name="staffNotes" rows=10 /></textarea>
							<button type="button" ng-show="isAdmin || isFinalReportApprover" ng-click="saveNote()" class="btn btn-success">SAVE NOTE</button>
						</div>
						<div class="col-md-3"></div>
					</div>



					<!--PREVIOUSLY SENT EMAILS-->
					<div id="previousEmailsHolder" ng-show="!isCreating">
						<button type="button" id="previousEmailsButton" data-toggle="collapse" class="btn btn-info" data-target="#previousEmails">Click here to see saved emails associated with this application</button>
						<ol id="previousEmails" class="collapse list-group">
							<li class="list-group-item" ng-if="reportEmails.length <= 0">There are no saved emails!</li>
							<li class="list-group-item" ng-repeat="email in reportEmails">
								<h5 class="list-group-item-heading">{{email[2]}}: saved {{email[4]}}</h5>
								<hr>
								<p class="list-group-item-text" ng-bind-html="email[3]"></p>
							</li>
						</ol>
					</div>
					
					
					
					<div class="row" ng-cloak ng-show="isAdmin || isFinalReportApprover">
					<!--EMAIL EDIT-->
						<div class="col-md-12">
							<div class="form-group">
								<label for="approverEmail">EMAIL TO BE SENT:</label>
								<textarea class="form-control" ng-model="formData.approverEmail" id="approverEmail" name="approverEmail" placeholder="Enter email body, with greetings." rows=20 /></textarea>
							</div>
						</div>
					</div>
					
					

					<div class="alert alert-{{alertType}} alert-dismissible" ng-class="{hideAlert: !alertMessage}">
						<button type="button" title="Close this alert." class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
					</div>



					<div class="buttons-group bottom-buttons"> 
						<button ng-show="isCreating" type="submit" ng-click="submitFunction='insertReport'" class="btn btn-success">SUBMIT FINAL REPORT</button> <!-- For applicant submitting for first time -->
						<button ng-show="isFinalReportApprover || isAdmin" type="submit" ng-click="submitFunction='approveReport'" class="btn btn-success">APPROVE REPORT</button> <!-- For approver or admin approving -->
						<button ng-show="isFinalReportApprover || isAdmin" type="submit" ng-click="submitFunction='holdReport'" class="btn btn-primary">HOLD REPORT</button> <!-- For approver or admin holding -->
						<button ng-show="isReviewing" type="submit" ng-click="submitFunction='uploadFiles'" class="btn btn-success">UPLOAD DOCS</button> <!-- For applicant reviewing report -->
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
	$database->close(); //close database connections
?>