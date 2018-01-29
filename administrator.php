<?php
	/*database functions*/
	include "functions/database.php";
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
		?>
		<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
			
			<center>
				<p class="title">Administrator View</p>
			</center>
		
			<?php
				$conn = connection(); //connect to DB
				
				/*get all database content*/
				$administrators = getAdministrators($conn);
				$applicants = getApplicants($conn);
				$applications = getApplications($conn, "");
				$applicationApprovers = getApplicationApprovers($conn);
				$committee = getCommittee($conn);
				$followUpReportApprovers = getFollowUpReportApprovers($conn);
				
				$conn = null; //close connection
			?>
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
					</tr>
				</table>
			</div>
			<br />
			<br />
			
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
			
			<div ng-app="myApp" ng-controller="applicationCtrl">
				<p class="title">Applications:</p>
				<table>
					<tr>
						<th>ID</th>
						<th>Applicant</th>
						<th>Date</th>
					</tr>
					<tr ng-repeat="x in applications">
						<td>{{ x.ID }}</td>
						<td>{{ x.Applicant }}</td>
						<td>{{ x.Date }}</td>
					</tr>
				</table>
			</div>
			<br />
			<br />
			
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
					</tr>
				</table>
			</div>
			<br />
			<br />
			
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
					</tr>
				</table>
			</div>
			<br />
			<br />
			
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
					</tr>
				</table>
			</div>
			<br />
			<br />
			
		</div>
		<!--BODY-->
	
	</body>
	
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