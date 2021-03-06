<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../CAS/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php");

	/*Logger*/
	include_once(dirname(__FILE__) . "/../../server/Logger.php");

	/*Site Warning*/
	include_once(dirname(__FILE__) . "/../../server/SiteWarning.php");

	$logger = new Logger(); //for logging to files
	$database = new DatabaseHelper($logger); //database helper object used for some verification and insertion
	$siteWarning = new SiteWarning($database); //used to determine if a site warning exists and should be displayed
	$databaseLastBackedUp = 0; //timestamp of the last time the database was backed up
		
	if($database->isAdministrator($CASbroncoNetID)) 
	{ 
		$administrators = $database->getAdministrators();
		$applicationApprovers = $database->getApplicationApprovers();
		$committee = $database->getCommittee();
		$finalReportApprovers = $database->getfinalReportApprovers();
		$siteWarningString = $database->getSiteWarning();
		$databaseLastBackedUp = $database->getDatabaseLastBackedUpTime();
?>




<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../include/head_content.html'; ?>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_administrators = <?php echo json_encode($administrators); ?>;
			var scope_committee = <?php echo json_encode($committee); ?>;
			var scope_applicationApprovers = <?php echo json_encode($applicationApprovers); ?>;
			var scope_finalReportApprovers = <?php echo json_encode($finalReportApprovers); ?>;
			var scope_siteWarningString = <?php echo json_encode($siteWarningString); ?>;
			var var_databaseLastBackedUp = <?php echo json_encode($databaseLastBackedUp); ?>;
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="administrator.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include '../include/site_banner.html'; ?>

		<div id="MainContent" role="main">
			<?php $siteWarning->showIfExists() ?> <!-- show site warning if it exists -->
			<script src="../include/outdatedbrowser.js" nomodule></script> <!-- show site error if outdated -->
			<?php include '../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->
	
				<!--AngularJS Controller-->
				<div class="container-fluid" ng-controller="adminCtrl" id="adminCtrl" ng-cloak>
				
					<h1 class="title">Administrator View</h1>
					
					<!-- View & Remove Admins -->
					<table class="table table-bordered table-sm">
						<caption class="title">Administrators:</caption>
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="admin in administrators">
								<td>{{ admin[0] }}</td>
								<td>{{ admin[1] }}</td>
								<td><button type="button" ng-click="removeAdmin(admin[0])" class="btn btn-danger"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>REMOVE</button></td> 
							</tr>
						</tbody>
					</table>
					<!--Add Admin-->
					<h3>Add Administrator:</h3>
					<form class="form-inline" ng-submit="addAdmin()"> 
						<div class="form-group">
							<label for="addAdminID">BroncoNetID:</label>
							<input type="text" ng-model="addAdminID" id="addAdminID" name="addAdminID">
						</div>
						<div class="form-group">
							<label for="addAdminName">Name:</label>
							<input type="text" ng-model="addAdminName" id="addAdminName" name="addAdminName">
						</div>
						<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>Submit</button>
					</form>

					<hr>

					<!-- View & Remove Committee Members -->
					<table class="table table-bordered table-sm">
						<caption class="title">Committee Members:</caption>
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="member in committee">
								<td>{{ member[0] }}</td>
								<td>{{ member[1] }}</td>
								<td><button type="button" ng-click="removeCommittee(member[0])" class="btn btn-danger"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>REMOVE</button></td> 
							</tr>
						</tbody>
					</table>
					<!--Add Committee Member-->
					<h3>Add Committee Member:</h3>
					<form class="form-inline" ng-submit="addCommittee()"> 
						<div class="form-group">
							<label for="addCommitteeID">BroncoNetID:</label>
							<input type="text" ng-model="addCommitteeID" id="addCommitteeID" name="addCommitteeID">
						</div>
						<div class="form-group">
							<label for="addCommitteeName">Name:</label>
							<input type="text" ng-model="addCommitteeName" id="addCommitteeName" name="addCommitteeName">
						</div>
						<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>Submit</button>
					</form>

					<hr>

					<!-- View & Remove Application Approvers -->
					<table class="table table-bordered table-sm">
						<caption class="title">Application Approvers:</caption>
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="approver in applicationApprovers">
								<td>{{ approver[0] }}</td>
								<td>{{ approver[1] }}</td>
								<td><button type="button" ng-click="removeApplicationApprover(approver[0])" class="btn btn-danger"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>REMOVE</button></td> 
							</tr>
						</tbody>
					</table>
					<!--Add Application Approver-->
					<h3>Add Application Approver:</h3>
					<form class="form-inline" ng-submit="addApplicationApprover()"> 
						<div class="form-group">
							<label for="addApplicationApproverID">BroncoNetID:</label>
							<input type="text" ng-model="addApplicationApproverID" id="addApplicationApproverID" name="addApplicationApproverID">
						</div>
						<div class="form-group">
							<label for="addApplicationApproverName">Name:</label>
							<input type="text" ng-model="addApplicationApproverName" id="addApplicationApproverName" name="addApplicationApproverName">
						</div>
						<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>Submit</button>
					</form>

					<hr>

					<!-- View & Remove Final Report Approvers -->
					<table class="table table-bordered table-sm">
						<caption class="title">Final Report Approvers:</caption>
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="approver in finalReportApprovers">
								<td>{{ approver[0] }}</td>
								<td>{{ approver[1] }}</td>
								<td><button type="button" ng-click="removeFinalReportApprover(approver[0])" class="btn btn-danger"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>REMOVE</button></td> 
							</tr>
						</tbody>
					</table>
					<!--Add Final Report Approver-->
					<h3>Add Final Report Approver:</h3>
					<form class="form-inline" ng-submit="addFinalReportApprover()"> 
						<div class="form-group">
							<label for="addFinalReportApproverID">BroncoNetID:</label>
							<input type="text" ng-model="addFinalReportApproverID" id="addFinalReportApproverID" name="addFinalReportApproverID">
						</div>
						<div class="form-group">
							<label for="addFinalReportApproverName">Name:</label>
							<input type="text" ng-model="addFinalReportApproverName" id="addFinalReportApproverName" name="addFinalReportApproverName">
						</div>
						<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>Submit</button>
					</form>

					<hr>

					<!-- Basic Site Warning Option -->
					<h3>Site Warning Notification</h3>
					<h4>Use this option to specify a warning that will appear on the top of every page of the site in a striped yellow banner. This can be used to do things like schedule upcoming down times for site maintenance. If enabled, it will show up upon subsequent page refreshes.</h4>
					<form class="form-inline site-warning-form" ng-submit="saveSiteWarning()"> 
						<div class="form-group">
							<label for="siteWarning">Site Warning Message:</label>
							<textarea class="form-control" ng-model="siteWarning" id="siteWarning" name="siteWarning" placeholder="Enter Warning Message" rows="2"> </textarea>
						</div>
						<button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>Save Message</button>
						<button type="button" class="btn btn-danger" ng-click="clearSiteWarning()"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>Clear Message</button>
					</form>
					<hr>

					<!-- Additional Information -->
					<h3>Additional Information</h3>
					<h4>Database last backed up: {{databaseLastBackedUp}}</h4>

					<div class="alert alert-{{alertType}} alert-dismissible" ng-class="{hideAlert: !alertMessage}">
						<button type="button" title="Close this alert." class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
					</div>


					<div class="buttons-group bottom-buttons"> 
						<a href="../home/home.php" class="btn btn-info"><span class="glyphicon glyphicon-home" aria-hidden="true"></span>LEAVE PAGE</a>
					</div>
				</div>

			</div>
			
		</div>

		<!-- Shared Site Footer -->
		<?php include '../include/site_footer.php'; ?>
	</body>
</html>
<?php
	}else{
		include '../include/permission_denied.html';
	}
	$database->close(); //close database connections
?>