<?php
	/*database functions*/
	include "functions/database.php";
	$conn = connection();
	
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*Verification functions*/
	include "functions/verification.php";
	
	if(isAdministrator($conn, $_SESSION['broncoNetID'])) {
		
		if(isset($_GET["downloadApplications"])){
			
			$res = getApplications($conn, '');
			
			$list = [];
			for($i = 0; $i < count($res); $i++){
				$application = $res[$i];
				if($application instanceof Applicaion) echo 'is an application...';
				array_push($list, array($application->id,$application->name,$application->dept,$application->getPurpose(),$application->dest,
										$application->rTitle,$application->tStart,$application->tEnd,$application->aStart,$application->aEnd,
										$application->getTotalBudget(),$application->aReq,' ',' '));
			}
			
			// output headers so that the file is downloaded rather than displayed
			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename="ApplicationSummarySheet.csv"');
			 
			// do not cache the file
			header('Pragma: no-cache');
			header('Expires: 0');
			 
			// create a file pointer connected to the output stream
			$file = fopen('php://output', 'w');
			 
			// send the column headers
			fputcsv($file, array('Application Number', 'Name', 'Department', 'Purpose', 'Destination',
								'Title', 'Travel Date Start', 'Travel Date End', 'Project Date Start', 'Project Date End',
								'Total Budget', 'Requested Reward',	'Amount Awarded', 'Comments'));
			
			foreach($list as $line) {
			//foreach($res as $line) {
				fputcsv($file, $line);
			}
			
			fclose($file);
			$sql = null;
			exit;	
		}
		
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
		<div class="container-fluid" id="adminPage">
			
			
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<h1>Administrator View</h1>
					
				
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
						<h2>Administrators:</h2>
						<table class="table table-bordered table-sm">
							<thead>
								<tr>
									<th>BroncoNetID</th>
									<th>Name</th>
									<th> </th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="x in admins">
									<td>{{ x.BroncoNetID }}</td>
									<td>{{ x.Name }}</td>
									<td><a class="btn btn-danger" href="?removeAdminID={{x.BroncoNetID}}">REMOVE</a></td> 
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
						<h2>Application Approvers:</h2>
						<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th> </th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="x in applicationApprovers">
								<td>{{ x.BroncoNetID }}</td>
								<td>{{ x.Name }}</td>
								<td><a class="btn btn-danger" href="?removeApproverID={{x.BroncoNetID}}">REMOVE</a></td> 
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
						<h2>Committee Members:</h2>
						<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th> </th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="x in committee">
								<td>{{ x.BroncoNetID }}</td>
								<td>{{ x.Name }}</td>
								<td><a class="btn btn-danger" href="?removeCommitteeID={{x.BroncoNetID}}">REMOVE</a></td> 
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
						<h2>Follow-Up Report Approvers:</h2>
						<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th>BroncoNetID</th>
								<th>Name</th>
								<th> </th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="x in followUpReportApprovers">
								<td>{{ x.BroncoNetID }}</td>
								<td>{{ x.Name }}</td>
								<td><a class="btn btn-danger" href="?removeFollowupID={{x.BroncoNetID}}">REMOVE</a></td> 
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
					
					<button id="applicantTableButton" data-toggle="collapse" class="btn btn-info" data-target="#applicantTable">Show Applicants</button>
					
					<!--Applicant Names Table, should not edit this table at all as Administrator-->
					<div ng-app="myApp" id="applicantTable" class="collapse" ng-controller="applicantCtrl">
						<h2>Applicants:</h2>
						<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th>BroncoNetID</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="x in applicants">
								<td>{{ x.BroncoNetID }}</td>
							</tr>
						</tbody>
						</table>
					</div>
					<br />
					<br />
					
					<button id="applicationTableButton" data-toggle="collapse" class="btn btn-info" data-target="#applicationTable">Show Applications</button>
					
					<!-- applications -->
					<div ng-app="myApp" id="applicationTable" class="collapse" ng-controller="applicationCtrl">
						<h2>Applications:</h2>
						<div class="row">
						<!--Filter first date-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="filterDateFrom">Filter date after:</label>
									<input type="date" ng-model="filterFrom" class="form-control" id="filterDateFrom" name="filterDateFrom" value="{{oldDate}}" />
								</div>
							</div>
						<!--Filter last date-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="filterDateTo">Filter date up to:</label>
									<input type="date" ng-model="filterTo" class="form-control" id="filterDateTo" name="filterDateTo" value="{{curDate}}" />
								</div>
							</div>
						<!--Filter status-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="filterStatus">Filter by status:</label><br>
									<select ng-model="filterStatus" id="filterStatus" name="filterStatus">
										<option value=""></option>
										<option value="Approved">Approved</option>
										<option value="Pending">Pending</option>
										<option value="Denied">Denied</option>
									</select>
								</div>
							</div>
						</div>
						<table class="table table-bordered table-sm">
						<thead>
							<tr>
								<th>ID</th>
								<th>Name</th>
								<th>Title</th>
								<th>Date Submitted</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="x in applications | dateFilter:filterFrom:filterTo | filter: {statusText: filterStatus}">
								<td>{{ x.id }}</td>
								<td>{{ x.name }}</td>
								<td><a href="application_admin.php?id={{ x.id }}">{{ x.rTitle }}</a></td>
								<td>{{ x.dateS | date: 'MM/dd/yyyy'}}</td>
								<td class="{{x.statusText}}">{{ x.statusText }}</td>
							</tr>
						</tbody>
						</table>
					</div>
					<br />
					<br />
					
					<a id="applicationSummaryDownload" class="btn btn-success" href="?downloadApplications=1">Download Application Summary Sheet</a><br>
				
				</div>
				<div class="col-md-3"></div>
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
		
		var currentDate = new Date(); //get current date
		var olderDate = new Date(); olderDate.setMonth(olderDate.getMonth() - 6); //get date from 6 months ago
		
		/*Controller to output administrators*/
		myApp.controller('adminCtrl', function($scope, $http) {
			$scope.admins = <?php echo json_encode($administrators) ?>;
		});
		
		/*Controller to output applicants*/
		myApp.controller('applicantCtrl', function($scope, $http) {
			$scope.applicants = <?php echo json_encode($applicants) ?>;
		});
		
		/*Controller to output applications*/
		myApp.controller('applicationCtrl', function($scope, $filter) {
			$scope.applications = <?php echo json_encode($applications) ?>;
			$scope.curDate = $filter("date")(currentDate, 'yyyy-MM-dd');
			$scope.oldDate = $filter("date")(olderDate, 'yyyy-MM-dd');
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
		
		/*Custom filter used to filter within a date range*/
		myApp.filter("dateFilter", function() {
			//alert("running date filter");
			return function(items, dateFrom, dateTo) {
				var result = [];  
				/*alert("First date: " + new Date(items[0].dateS));
				alert("Date from: " + dateFrom);
				alert("Date to: " + dateTo);*/
				
				var testFrom = dateFrom;
				if(dateFrom == null){testFrom = olderDate;}
				var testTo = dateTo;
				if(dateTo == null){testTo = currentDate;}
				
				for (var i=0; i<items.length; i++){
					var dateSub = new Date(items[i].dateS);
					if (dateSub >= testFrom && dateSub <= testTo)  {
						result.push(items[i]);
					}
				}
				
				return result;
			};
		});
	</script>
	<!-- End Script -->
	
</html>

<?php
	$conn = null; //close connection
?>