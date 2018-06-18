<?php
	/*Debug user validation*/
	//debugAuthentication stored broncoNetID and email in $_SESSION[] variables
	//include "include/debugAuthentication.php";
	include "include/CAS_login.php";

	/*database functions*/
	include "functions/database.php";
	$conn = connection();
	
	/*Verification functions*/
	include "functions/verification.php";
	

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
			if(isAdministrator($conn, $CASbroncoNetID)) 
			{ 

				if(isset($_GET["removeAdminID"])) {
				$_removeAdminID = $_GET['removeAdminID'];

					if($CASbroncoNetID != $_removeAdminID) {
						removeAdmin($conn, $_removeAdminID);
					} else {
						?>
						<script language="javascript">
						alert("Cannot remove yourself as an admin!")
						</script>
					<?php
					}
				}
				
				
				if(isset($_GET["addAdminID"]) && isset($_GET["addAdminName"])) {
					addAdmin($conn, $_GET["addAdminID"], $_GET["addAdminName"]);
				}
				
				if(isset($_GET["addApproverID"]) && isset($_GET["addApproverName"])) {
					addApplicationApprover($conn, $_GET["addApproverID"], $_GET["addApproverName"]);
				}
				
				if(isset($_GET["removeApproverID"])) {
					removeApplicationApprover($conn, $_GET["removeApproverID"]);
				}

				if(isset($_GET["addCommitteeID"]) && isset($_GET["addCommitteeName"])) {
					addCommittee($conn, $_GET["addCommitteeID"], $_GET["addCommitteeName"]);
				}
				
				if(isset($_GET["removeCommitteeID"])) {
					removeCommittee($conn, $_GET["removeCommitteeID"]);
				}
				
				if(isset($_GET["addFollowupID"]) && isset($_GET["addFollowupName"])) {
					addFollowUpApprover($conn, $_GET["addFollowupID"], $_GET["addFollowupName"]);
				}
				
				if(isset($_GET["removeFollowupID"])) {
					removeFollowUpApprover($conn, $_GET["removeFollowupID"]);
				}
				
			
		
		?>
		<!--HEADER-->
	
		<!--BODY-->
			<div class="container-fluid" id="adminPage">
				
				
				<div class="row">
					<div class="col-md-3"></div>
					<div class="col-md-6">
						<h1 class="title">Administrator View</h1>
						
					
						<?php
							/*get all database content*/
							$administrators = getAdministrators($conn);
							$applicants = getApplicants($conn);
							$applications = getApplications($conn, "");
							foreach($applications as $curApp)
							{
								$curApp->statusText = $curApp->getStatus();
							}
							$applicationApprovers = getApplicationApprovers($conn);
							$committee = getCommittee($conn);
							$followUpReportApprovers = getFollowUpReportApprovers($conn);
						?>
						
						<!--Admin Table-->
						<div ng-app="myApp" ng-controller="adminCtrl">
							<h2 class="title">Administrators:</h2>
							<table class="table table-bordered table-sm">
								<thead>
									<tr>
										<th>BroncoNetID</th>
										<th>Name</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr ng-repeat="x in admins">
										<td>{{ x[0] }}</td>
										<td>{{ x[1] }}</td>
										<td><a class="btn btn-danger" href="?removeAdminID={{x[0]}}">REMOVE</a></td> 
									</tr>
								</tbody>
							</table>
						</div>
						
						<!--Add Admin-->
						<input type="button" class="btn btn-primary" id="addAdmin" value="Add Admin">
						<div id="addAdminContent" style="display:none"> 
							<div class="row">
								<div class="col-md-5">
									<form class="form-inline" action="/administrator.php" method="GET"> 
										<div class="form-group">
											<label for="addAdminID">BroncoNetID:</label>
											<input type="text" id="addAdminID" name="addAdminID">
										</div>
										<div class="form-group">
											<label for="addAdminName">Name:</label>
											<input type="text" id="addAdminName" name="addAdminName">
										</div>
										<button type="submit" class="btn btn-success">Submit</button>
									</form>
								</div>
							</div>	
						</div>
					
						
						<br />
						<br />
						
						<!--Add or remove application approvers-->
						<div ng-app="myApp" ng-controller="applicationApproverCtrl">
							<h2 class="title">Application Approvers:</h2>
							<table class="table table-bordered table-sm">
							<thead>
								<tr>
									<th>BroncoNetID</th>
									<th>Name</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="x in applicationApprovers">
									<td>{{ x[0] }}</td>
									<td>{{ x[1] }}</td>
									<td><a class="btn btn-danger" href="?removeApproverID={{x[0]}}">REMOVE</a></td> 
								</tr>
							</tbody>
							</table>
						</div>
						
						<!--Add approver-->			
						<input type="button" class="btn btn-primary" id="addApprover" value="Add Approver">
						<div id="addApproverContent" style="display:none"> 
							<div class="row">
								<div class="col-md-5">
									<form class="form-inline" action="/administrator.php" method="GET"> 
										<div class="form-group">
											<label for="addApproverID">BroncoNetID:</label>
											<input type="text" id="addApproverID" name="addApproverID">
										</div>
										<div class="form-group">
											<label for="addApproverName">Name:</label>
											<input type="text" id="addApproverName" name="addApproverName">
										</div>
										<button type="submit" class="btn btn-success">Submit</button>
									</form>
								</div>
							</div>	
						</div>
						
						<br />
						<br />
						
						
						<!--Committee table-->
						<div ng-app="myApp" ng-controller="committeeCtrl">
							<h2 class="title">Committee Members:</h2>
							<table class="table table-bordered table-sm">
							<thead>
								<tr>
									<th>BroncoNetID</th>
									<th>Name</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="x in committee">
									<td>{{ x[0] }}</td>
									<td>{{ x[1] }}</td>
									<td><a class="btn btn-danger" href="?removeCommitteeID={{x[0]}}">REMOVE</a></td> 
								</tr>
							</tbody>
							</table>
						</div>
						
						<!--Add committee member-->			
						<input type="button" class="btn btn-primary" id="addCommittee" value="Add Committee Member">
						<div id="addCommitteeContent" style="display:none"> 
							<div class="row">
								<div class="col-md-5">
									<form class="form-inline" action="/administrator.php" method="GET"> 
										<div class="form-group">
											<label for="addCommitteeID">BroncoNetID:</label>
											<input type="text" id="addCommitteeID" name="addCommitteeID">
										</div>
										<div class="form-group">
											<label for="addCommitteeName">Name:</label>
											<input type="text" id="addCommitteeName" name="addCommitteeName">
										</div>
										<button type="submit" class="btn btn-success">Submit</button>
									</form>
								</div>
							</div>	
						</div>
						
						<br />
						<br />
						
						<!--Follow-Up Report Approvers-->
						<div ng-app="myApp" ng-controller="followUpReportApproverCtrl">
							<h2 class="title">Follow-Up Report Approvers:</h2>
							<table class="table table-bordered table-sm">
							<thead>
								<tr>
									<th>BroncoNetID</th>
									<th>Name</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="x in followUpReportApprovers">
									<td>{{ x[0] }}</td>
									<td>{{ x[1] }}</td>
									<td><a class="btn btn-danger" href="?removeFollowupID={{x[0]}}">REMOVE</a></td> 
								</tr>
							</tbody>
							</table>
						</div>
						
						<!--Add follow-up member-->
						<input type="button" class="btn btn-primary" id="addFollowup" value="Add Follow-Up Approver">
						<div id="addFollowupContent" style="display:none"> 
							<div class="row">
								<div class="col-md-5">
									<form class="form-inline" action="/administrator.php" method="GET"> 
										<div class="form-group">
											<label for="addFollowupID">BroncoNetID:</label>
											<input type="text" id="addFollowupID" name="addFollowupID">
										</div>
										<div class="form-group">
											<label for="addFollowupName">Name:</label>
											<input type="text" id="addFollowupName" name="addFollowupName">
										</div>
										<button type="submit" class="btn btn-success">Submit</button>
									</form>
								</div>
							</div>	
						</div>

						<br />
						<br />
						
					</div>
					<div class="col-md-3"></div>
				</div>

				<div class="row">
					<div class="col-md-5"></div>
					<div class="col-md-2">
						<a href="index.php" class="btn btn-info">LEAVE PAGE</a>
					</div>
					<div class="col-md-5"></div>
				</div>
				
			</div>
			<!--BODY-->
			<?php
				}
				else{
				?>
					<h1>You do not have administrative rights!</h1>
				<?php
				}
				?>
		</div>
	</body>
	
	<!-- JQuery Script  -->
	<script>
	
		/*Function to show/hide Administrator add*/
		jQuery(document).ready(function(){
			jQuery('#addAdmin').on('click', function(event) {        
				jQuery('#addAdminContent').toggle('show');
			});
		});
		
		/*Function to show/hide Approver add*/
		jQuery(document).ready(function(){
			jQuery('#addApprover').on('click', function(event) {        
				jQuery('#addApproverContent').toggle('show');
			});
		});
		
		/*Function to show/hide Committee add*/
		jQuery(document).ready(function(){
			jQuery('#addCommittee').on('click', function(event) {        
				jQuery('#addCommitteeContent').toggle('show');
			});
		});
		
		/*Function to show/hide Follow-Up add*/
		jQuery(document).ready(function(){
			jQuery('#addFollowup').on('click', function(event) {        
				jQuery('#addFollowupContent').toggle('show');
			});
		});
		
	</script> 
	<!-- End Script -->
	
	<!-- AngularJS Script -->
	<script>
		var myApp = angular.module('HIGE-app', []);
		
		/*Controller to output administrators*/
		myApp.controller('adminCtrl', function($scope, $http) {
			$scope.admins = <?php echo json_encode($administrators) ?>;
		});
		
		/*Controller to output application approvers*/
		myApp.controller('applicationApproverCtrl', function($scope, $http) {
			$scope.applicationApprovers = <?php echo json_encode($applicationApprovers) ?>;
		});
		
		/*Controller to output committee members*/
		myApp.controller('committeeCtrl', function($scope, $http) {
			$scope.committee = <?php echo json_encode($committee) ?>;
		});
		
		/*Controller to output follow-up report approvers*/
		myApp.controller('followUpReportApproverCtrl', function($scope, $http) {
			$scope.followUpReportApprovers = <?php echo json_encode($followUpReportApprovers) ?>;
		});

	</script>
	<!-- End Script -->
	
</html>

<?php
	$conn = null; //close connection
?>