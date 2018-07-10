<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();
		
	if(isAdministrator($conn, $CASbroncoNetID)) 
	{ 
		$administrators = getAdministrators($conn);
		$applicationApprovers = getApplicationApprovers($conn);
		$committee = getCommittee($conn);
		$followUpApprovers = getFollowUpReportApprovers($conn);
?>




<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../../include/head_content.html'; ?>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_administrators = <?php echo json_encode($administrators); ?>;
			var scope_committee = <?php echo json_encode($committee); ?>;
			var scope_applicationApprovers = <?php echo json_encode($applicationApprovers); ?>;
			var scope_followUpApprovers = <?php echo json_encode($followUpApprovers); ?>;
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="administrator.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include '../../include/site_banner.html'; ?>

		<div id="MainContent" role="main">
			<script src="../../include/outdatedbrowser.js"></script> <!-- show site error if outdated -->
			<?php include '../../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->
	
				<!--AngularJS Controller-->
				<div class="container-fluid" ng-controller="adminCtrl" id="adminCtrl">
				
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
								<td><button type="button" ng-click="removeAdmin(admin[0])" class="btn btn-danger">REMOVE</button></td> 
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
						<button type="submit" class="btn btn-success">Submit</button>
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
								<td><button type="button" ng-click="removeCommittee(member[0])" class="btn btn-danger">REMOVE</button></td> 
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
						<button type="submit" class="btn btn-success">Submit</button>
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
								<td><button type="button" ng-click="removeApplicationApprover(approver[0])" class="btn btn-danger">REMOVE</button></td> 
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
						<button type="submit" class="btn btn-success">Submit</button>
					</form>

					<hr>

					<!-- View & Remove Follow Up Approvers -->
					<table class="table table-bordered table-sm">
						<caption class="title">Follow Up Report Approvers:</caption>
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="approver in followUpApprovers">
								<td>{{ approver[0] }}</td>
								<td>{{ approver[1] }}</td>
								<td><button type="button" ng-click="removeFollowUpApprover(approver[0])" class="btn btn-danger">REMOVE</button></td> 
							</tr>
						</tbody>
					</table>
					<!--Add Follow Up Approver-->
					<h3>Add Follow Up Report Approver:</h3>
					<form class="form-inline" ng-submit="addFollowUpApprover()"> 
						<div class="form-group">
							<label for="addFollowUpApproverID">BroncoNetID:</label>
							<input type="text" ng-model="addFollowUpApproverID" id="addFollowUpApproverID" name="addFollowUpApproverID">
						</div>
						<div class="form-group">
							<label for="addFollowUpApproverName">Name:</label>
							<input type="text" ng-model="addFollowUpApproverName" id="addFollowUpApproverName" name="addFollowUpApproverName">
						</div>
						<button type="submit" class="btn btn-success">Submit</button>
					</form>

					<hr>

					<div class="alert alert-{{alertType}} alert-dismissible fade in" ng-show='alertMessage'>
						<button type="button" title="Close this alert." class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
					</div>


					<div class="buttons-group bottom-buttons"> 
						<a href="../home/home.php" class="btn btn-info">LEAVE PAGE</a>
					</div>
				</div>

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