<?php
	/*database functions*/
	include "functions/database.php";
	$conn = connection();
	
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*Verification functions*/
	include "functions/verification.php";
	
	if(isAdministrator($conn, $_SESSION['broncoNetID'])) {
		
		if(isset($_GET["addAdminID"]) && isset($_GET["addAdminName"])) {
			addAdmin($conn, $_GET["addAdminID"], $_GET["addAdminName"]);
		}
		
		// set an application as denied, cannot remove an application ever 
		/* if(isset($_GET["denyApplicationID"])) {
			
			echo 'in denyApplicationID!!';
			denyApplication($conn, $_GET["denyApplicationID"]);
			
		} */
		
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
	}
	
	
	
?>
<!DOCTYPE html>
<html lang="en">
	<head>
                <title>HIGE</title>

		<!-- ANGULARJS -->
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.6/angular.min.js"></script>
		
		<!--JQuery-->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

		<!-- Custom stylesheet -->
		<link rel="stylesheet" href="style/style.css">
		
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		
		<!-- ENABLE BOOTSTRAP -->
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		
		<!-- Font-Awesome ICONS -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	
	</head>
	<body ng-app="HIGE-app">
	
		<!--HEADER-->
		<?php
			include 'include/header.php';
			
			if(isset($_GET["removeAdminID"])) {
				$_removeAdminID = $_GET['removeAdminID'];
				
				if($_SESSION['broncoNetID'] != $_removeAdminID) {
					removeAdmin($conn, $_removeAdminID);
				} else {
					?>
					<script language="javascript">
					alert("Cannot remove yourself as an admin!")
					</script>
			<?php
				}
			}
			
			if(isAdministrator($conn, $_SESSION['broncoNetID'])) 
			{ 
		?>
		<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
			
			<center>
				<p class="title">Administrator View</p>
			</center>
		
			<?php
				/*get all database content*/
				$administrators = getAdministrators($conn);
				$applicants = getApplicants($conn);
				$applications = getApplications($conn, "");
				$applicationApprovers = getApplicationApprovers($conn);
				$committee = getCommittee($conn);
				$followUpReportApprovers = getFollowUpReportApprovers($conn);
			?>
			
			<!--Admin Table-->
			<div ng-app="myApp" ng-controller="adminCtrl">
				<p class="title">Administrators:</p>
				<table>
					<tr>
						<th>BroncoNetID</th>
						<th>Name</th>
					</tr>
					<tr ng-repeat="x in admins">
						<td>{{ x.BroncoNetID }}</td>
						<td>{{ x.Name }}</td>
						<td><a href="?removeAdminID={{x.BroncoNetID}}">REMOVE</a></td> 
					</tr>
				</table>
			</div>
			
			<!--Add Admin-->
			<input type="button" id="addAdmin" value="Add">
			<div id="addAdminContent" style="display:none"> 
				<form action="/administrator.php" method="GET"> 
					BroncoNetID: <input type="text" id="addAdminID" name="addAdminID"><br>
					Name: <input type="text" id="addAdminName" name="addAdminName"><br>
					<input name="submitAddAdmin" type="submit" value="Submit">
				</form>
			</div>
		
			
			<br />
			<br />
			
			<!--Applicant Names Table, should not edit this table at all as Administrator-->
			<div ng-app="myApp" ng-controller="applicantCtrl">
				<p class="title">Applicants:</p>
				<table>
					<tr>
						<th>BroncoNetID</th>
					</tr>
					<tr ng-repeat="x in applicants">
						<td>{{ x.BroncoNetID }}</td>
					</tr>
				</table>
			</div>
			<br />
			<br />
			
			<!-- applications -->
			<div ng-app="myApp" ng-controller="applicationCtrl">
				<p class="title">Applications:</p>
				<table>
					<tr>
						<th>ID</th>
						<th>Applicant</th>
						<th>Date</th>
					</tr>
					<tr ng-repeat="x in applications">
						<td>{{ x.id }}</td>
						<td>{{ x.name }}</td>
						<td>{{ x.dateS }}</td>
						<td><a href="../application_admin.php?id={{ x.id }}">VIEW</a></td> 
					</tr>
				</table>
			</div>
			<br />
			<br />
			
			<!--Add or remove application approvers-->
			<div ng-app="myApp" ng-controller="applicationApproverCtrl">
				<p class="title">Application Approvers:</p>
				<table>
					<tr>
						<th>BroncoNetID</th>
						<th>Name</th>
					</tr>
					<tr ng-repeat="x in applicationApprovers">
						<td>{{ x.BroncoNetID }}</td>
						<td>{{ x.Name }}</td>
						<td><a href="?removeApproverID={{x.BroncoNetID}}">REMOVE</a></td> 
					</tr>
				</table>
			</div>
			
			<!--Add approver-->
			<input type="button" id="addApprover" value="Add">
			<div id="addApproverContent" style="display:none"> 
				<form action="/administrator.php" method="GET"> 
					BroncoNetID: <input type="text" id="addApproverID" name="addApproverID"><br>
					Name: <input type="text" id="addApproverName" name="addApproverName"><br>
					<input name="submitAddApprover" type="submit" value="Submit">
				</form>
			</div>
			
			<br />
			<br />
			
			
			<!--Committee table-->
			<div ng-app="myApp" ng-controller="committeeCtrl">
				<p class="title">Committee Members:</p>
				<table>
					<tr>
						<th>BroncoNetID</th>
						<th>Name</th>
					</tr>
					<tr ng-repeat="x in committee">
						<td>{{ x.BroncoNetID }}</td>
						<td>{{ x.Name }}</td>
						<td><a href="?removeCommitteeID={{x.BroncoNetID}}">REMOVE</a></td> 
					</tr>
				</table>
			</div>
			
			<!--Add committee member-->
			<input type="button" id="addCommittee" value="Add">
			<div id="addCommitteeContent" style="display:none"> 
				<form action="/administrator.php" method="GET"> 
					BroncoNetID: <input type="text" id="addCommitteeID" name="addCommitteeID"><br>
					Name: <input type="text" id="addCommitteeName" name="addCommitteeName"><br>
					<input name="submitAddCommittee" type="submit" value="Submit">
				</form>
			</div>
			
			<br />
			<br />
			
			<!--Follow-Up Report Approvers-->
			<div ng-app="myApp" ng-controller="followUpReportApproverCtrl">
				<p class="title">Follow-Up Report Approvers:</p>
				<table>
					<tr>
						<th>BroncoNetID</th>
						<th>Name</th>
					</tr>
					<tr ng-repeat="x in followUpReportApprovers">
						<td>{{ x.BroncoNetID }}</td>
						<td>{{ x.Name }}</td>
						<td><a href="?removeFollowupID={{x.BroncoNetID}}">REMOVE</a></td> 
					</tr>
				</table>
			</div>
			
			<!--Add follow-up member-->
			<input type="button" id="addFollowup" value="Add">
			<div id="addFollowupContent" style="display:none"> 
				<form action="/administrator.php" method="GET"> 
					BroncoNetID: <input type="text" id="addFollowupID" name="addFollowupID"><br>
					Name: <input type="text" id="addFollowupName" name="addFollowupName"><br>
					<input name="submitAddFollowup" type="submit" value="Submit">
				</form>
			</div>
			<br />
			<br />
			
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
		
		/*Controller to output applicants*/
		myApp.controller('applicantCtrl', function($scope, $http) {
			$scope.applicants = <?php echo json_encode($applicants) ?>;
		});
		
		/*Controller to output applications*/
		myApp.controller('applicationCtrl', function($scope, $http) {
			$scope.applications = <?php echo json_encode($applications) ?>;
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