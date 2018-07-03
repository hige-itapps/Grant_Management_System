<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../../functions/verification.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/../../functions/documents.php");
	
	/*get initial character limits for text fields*/
	$reportCharMax = getFollowUpReportsMaxLengths($conn);

	$maxProjectSummary = $reportCharMax[array_search('ProjectSummary', array_column($reportCharMax, 0))][1]; //project summary char limit
	
	$report = null; //only set report if it exists (when not creating a new one)
	$reportFiles = null; //^
	$reportEmails = null; //^

	/*Initialize all user permissions to false*/
	$isCreating = false; //user is an applicant initially creating report
	$isReviewing = false; //user is an applicant reviewing their already created report
	$isAdmin = false; //user is an administrator
	$isCommittee = false; //user is a committee member
	$isChairReviewing = false; //user is the associated department chair, but cannot do anything (just for reviewing purposes)
	$isFollowUpApprover = false; //user is a follow-up report approver
	
	$permissionSet = false; //boolean set to true when a permission has been set- used to force only 1 permission at most
	
	/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
	No matter what, the app ID MUST BE SET*/
	if(isset($_GET["id"]))
	{
		if($permissionSet = $isAdmin = isAdministrator($conn, $CASbroncoNetID)){} //admin check
		else if($permissionSet = $isFollowUpApprover = isFollowUpReportApprover($conn, $CASbroncoNetID)){} //follow up report approver check
		else if($permissionSet = $isChairReviewing = isUserDepartmentChair($conn, $CASemail, $_GET['id'], $CASbroncoNetID)){} //department chair reviewing check
		else if($permissionSet = $isCommittee = isUserAllowedToSeeApplications($conn, $CASbroncoNetID)){} //committee member check
		else if($permissionSet = $isCreating = isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetID, $_GET['id'])){} //applicant creating check
		else if($permissionSet = $isReviewing = doesUserOwnApplication($conn, $CASbroncoNetID, $_GET['id']) && getFollowUpReport($conn, $_GET['id'])){} //applicant reviewing check
	}
	
	/*Verify that user is allowed to render report*/
	if($permissionSet)
	{
		$appID = $_GET["id"];
		$app = getApplication($conn, $appID); //get application Data

		/*Initialize variables if report has already been created*/
		if(!$isCreating)
		{
			$report = getFollowUpReport($conn, $appID); //get follow up report data
			$reportFiles = getFileNames($appID);
			$reportEmails = getEmails($conn, $appID);
		} 
?>










<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../../include/head_content.html'; ?>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_maxProjectSummary = <?php echo json_encode($maxProjectSummary); ?>;
			var scope_isCreating = <?php echo json_encode($isCreating); ?>;
			var scope_isReviewing = <?php echo json_encode($isReviewing); ?>;
			var scope_isAdmin = <?php echo json_encode($isAdmin); ?>;
			var scope_isCommittee = <?php echo json_encode($isCommittee); ?>;
			var scope_isChairReviewing = <?php echo json_encode($isChairReviewing); ?>;
			var scope_isFollowUpApprover = <?php echo json_encode($isFollowUpApprover); ?>;
			var var_app = <?php echo json_encode($app); ?>; //application data
			var var_report = <?php echo json_encode($report); ?>; //report data
			var var_reportFiles = <?php echo json_encode($reportFiles); ?>; //the associated uploaded files
			var var_reportEmails = <?php echo json_encode($reportEmails); ?>; //the associated saved emails
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="follow_up.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include '../../include/site_banner.html'; ?>

	<div id="MainContent" role="main">
		<script src="../../include/outdatedbrowser.js"></script> <!-- show site error if outdated -->
		<?php include '../../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->
		
			<!--AngularJS Controller-->
			<div class="container-fluid" ng-controller="reportCtrl" id="reportCtrl">

				<h1 ng-cloak ng-show="!isCreating" class="{{reportStatus}}-background status-bar">Report Status: {{reportStatus}}</h1>

				<div ng-cloak ng-show="isAdmin || isAdminUpdating" class="buttons-group"> 
					<button type="button" ng-click="toggleAdminUpdate()" class="btn btn-warning">TURN {{isAdminUpdating ? "OFF" : "ON"}} ADMIN UPDATE MODE</button>
					<button type="button" ng-click="populateForm(null)" class="btn btn-warning">RELOAD SAVED DATA</button>
					<button type="button" ng-click="insertReport()" class="btn btn-warning">SUBMIT CHANGES</button>
				</div>
				
				<!-- follow-up form -->
				<form enctype="multipart/form-data" class="form-horizontal" id="reportForm" name="reportForm" ng-submit="insertReport()">
				

				
					<div class="row">
						<h1 class="title">FOLLOW-UP REPORT:</h1>
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
						<p><h2 class="title">Follow-Up Information:</h2></p>
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
						<h3 ng-show="isCreating || isReviewing || isAdminUpdating">Please Upload Documentation (Travel Expense Vouchers, Receipts, Travel Authorization Forms, Etc.)</h3>
					</div>
					
					
					
					<!--UPLOADS-->
					<div class="row">
						<div class="col-md-12">
							<div ng-cloak ng-show="isCreating || isReviewing || isAdminUpdating" class="uploadedList">
								<hr>
								<div class="upload-button-holder">
									<label class="btn btn-primary">
										UPLOAD DOCUMENTS<input type="file" hidden readdocuments="uploadDocs" name="uploadDocs" multiple accept=".txt, .rtf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pdf, .jpg, .png, .bmp, .tif"/>
									</label>
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



					<!--PREVIOUSLY SENT EMAILS-->
					<div id="previousEmailsHolder" ng-show="!isCreating">
						<button type="button" id="previousEmailsButton" data-toggle="collapse" class="btn btn-info" data-target="#previousEmails">Click here to see sent emails associated with this application</button>
						<ol id="previousEmails" class="collapse list-group">
							<li class="list-group-item" ng-if="reportEmails.length <= 0">There are no sent emails!</li>
							<li class="list-group-item" ng-repeat="email in reportEmails">
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
					
					

					<div class="alert alert-{{alertType}} alert-dismissible fade in" ng-show='alertMessage'>
						<button type="button" class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
					</div>



					<div class="buttons-group bottom-buttons"> 
						<button ng-show="isCreating" type="button" ng-click="insertReport()" class="btn btn-success">SUBMIT FOLLOW UP REPORT</button> <!-- For applicant submitting for first time -->
						<button ng-show="isApprover || isAdmin" type="button" ng-click="approveReport('Approved')" class="btn btn-success">APPROVE REPORT</button> <!-- For approver or admin approving -->
						<button ng-show="isApprover || isAdmin" type="button" ng-click="approveReport('Denied')" class="btn btn-danger">DENY REPORT</button> <!-- For approver or admin denying -->
						<button ng-show="isReviewing" type="button" ng-click="uploadFiles()" class="btn btn-success">UPLOAD DOCS</button> <!-- For applicant reviewing report -->
						<a href="" class="btn btn-info" ng-click="redirectToHomepage(null, null)">LEAVE PAGE</a> <!-- For anyone to leave the page -->
					</div>
				</form>

			</div>

		</div>
	</body>
</html>
<?php
	}else{
		include '../../include/permission_denied.html';
	}
	$conn = null; //close connection
?>