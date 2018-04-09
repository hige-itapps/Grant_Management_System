<?php
	ob_start();
	
	/*Debug user validation*/
	//include "include/debugAuthentication.php";
	include "include/CAS_login.php";

	/*get database connection*/
	include "functions/database.php";
	$conn = connection();
	
	/*Verification functions*/
	include "functions/verification.php";
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

		<div id="MainContent" role="main">

		<?php

			$totalPrevApps = getApplications($conn, $CASbroncoNetId);
			$signedAppsNumber = getNumberOfSignedApplications($conn, $CASemail);
			$appsToSignNumber = getNumberOfApplicationsToSign($conn, $CASemail);

			if(isUserAllowedToSeeApplications($conn, $CASbroncoNetId) || $signedAppsNumber > 0 || $appsToSignNumber > 0|| count($totalPrevApps) > 0) //user is allowed to see SOMETHING
			{
				$apps = [];
				//use user permission to get specific set of applications
				if($signedAppsNumber > 0 && isset($_GET["previousApproval"])) //department chair- already signed
				{
					$apps = getSignedApplications($conn, $CASemail);//get all signed applications only
				}
				else if($appsToSignNumber > 0 && isset($_GET["approval"])) //department chair- to sign
				{
					$apps = getApplicationsToSign($conn, $CASemail);//get all applications to sign only
				}
				else if(count($totalPrevApps) > 0 && isset($_GET["previousSubmit"])) //normal applicant
				{
					$apps = $totalPrevApps;
				}
				else if(isUserAllowedToSeeApplications($conn, $CASbroncoNetId))//default privileges (for HIGE staff)
				{
					$apps = getApplications($conn, "");//get all applications
				}

				$appCycles = []; //array to hold all app cycles as strings
				
				foreach($apps as $curApp)
				{
					$curApp->statusText = $curApp->getStatus();
					//echo $curApp->dateS;
					$curApp->cycle = getCycleName(DateTime::createFromFormat('Y-m-d', $curApp->dateS), $curApp->nextCycle, false);
					if (!in_array($curApp->cycle, $appCycles)) {//push cycle to all cycles if it's not there already
						$appCycles[] = $curApp->cycle;
					}
				}
				
				$appCycles = array_reverse($appCycles); //reverse cycles so that the newest ones are first
			?>
			<!--HEADER-->
			<div class="container-fluid" ng-controller="listCtrl">
				<div class="row">
					<center><h2 class="title">All Applications</h2></center>
				</div>
				<div class="row">
					<div class="col-md-1"></div>
				<!--Filter name-->
				<div class="col-md-2">
						<div class="form-group">
							<label for="filterName">Filter by name:</label>
							<input type="text" ng-model="filterName" class="listInput form-control" id="filterName" name="filterName" />
						</div>
					</div>
				<!--Filter cycle-->
					<div class="col-md-2">
						<div class="form-group">
							<label for="filterCycle">Filter by cycle:</label><br>
							<select ng-options="item as item for item in appCycles track by item" class="listInput" ng-init="filterCycle = appCycles[0]" ng-model="filterCycle" id="filterCycle" name="filterCycle">
								<option value="">All</option>
							</select>
						</div>
					</div>
				<!--Filter first date-->
					<div class="col-md-2">
						<div class="form-group">
							<label for="filterDateFrom">Filter date after:</label>
							<input type="date" ng-model="filterFrom" class="listInput form-control" id="filterDateFrom" name="filterDateFrom" />
						</div>
					</div>
				<!--Filter last date-->
					<div class="col-md-2">
						<div class="form-group">
							<label for="filterDateTo">Filter date up to:</label>
							<input type="date" ng-model="filterTo" class="listInput form-control" id="filterDateTo" name="filterDateTo" />
						</div>
					</div>
				<!--Filter status-->
					<div class="col-md-2">
						<div class="form-group">
							<label for="filterStatus">Filter by status:</label><br>
							<select ng-model="filterStatus" class="listInput" id="filterStatus" name="filterStatus">
								<option value=""></option>
								<option value="Approved">Approved</option>
								<option value="Pending">Pending</option>
								<option value="Denied">Denied</option>
								<option value="Hold">Hold</option>
							</select>
						</div>
					</div>
					<div class="col-md-1"></div>
				</div>
				<div class="row">
					<div class="col-md-1"></div>
					<div class="col-md-10">
						<table class="table">
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Title</th>
									<th>Cycle</th>
									<th>Date Submitted</th>
									<th>Status</th>
									<th>Approval</th>
									<th>Follow-up Report</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-if="filterCycle" ng-repeat="x in applications | dateFilter:filterFrom:filterTo | filter: {name: filterName, statusText: filterStatus, cycle: filterCycle}">
									<td>{{ x.id }}</td>
									<td>{{ x.name }}</td>
									<td><a href="application.php?id={{ x.id }}">{{ x.rTitle }}</a></td>
									<td>{{ x.cycle }}</td>
									<td>{{ x.dateS | date: 'MM/dd/yyyy'}}</td>
									<td class="{{x.statusText}}">{{ x.statusText }}</td>
									<td>{{x.deptCS}}</td>
									<td><a href="/follow_up.php?id={{x.id}}">Follow-Up Report</a></td>
								</tr>
								<tr ng-if="!filterCycle" ng-repeat="x in applications | dateFilter:filterFrom:filterTo | filter: {name: filterName, statusText: filterStatus}">
									<td>{{ x.id }}</td>
									<td>{{ x.name }}</td>
									<td><a href="application.php?id={{ x.id }}">{{ x.rTitle }}</a></td>
									<td>{{ x.cycle }}</td>
									<td>{{ x.dateS | date: 'MM/dd/yyyy'}}</td>
									<td class="{{x.statusText}}">{{ x.statusText }}</td>
									<td>{{x.deptCS}}</td>
									<td><a href="/follow_up.php?id={{x.id}}">Follow-Up Report</a></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-md-1"></div>
				</div>
				<div class="row">
					<div class="col-md-5"></div>
					<div class="col-md-2">
						<a href="index.php" class="btn btn-info">LEAVE PAGE</a>
					</div>
					<div class="col-md-5"></div>
				</div>
			</div>
			<?php
				}
				else{
				?>
					<h1>You are not allowed to view applications!</h1>
				<?php
				}
			?>

		</div>
	</body>
	
	<!-- AngularJS Script -->
	<script>
		var myApp = angular.module('HIGE-app', []);
		
		//var currentDate = new Date(); //get current date
		//var olderDate = new Date(); olderDate.setMonth(olderDate.getMonth() - 6); //get date from 6 months ago
		
		/*Controller to set date inputs and list*/
		myApp.controller('listCtrl', function($scope, $filter) {
			$scope.applications = <?php echo json_encode($apps) ?>;
			$scope.appCycles = <?php echo json_encode($appCycles) ?>;
			//$scope.curDate = $filter("date")(currentDate, 'yyyy-MM-dd');
			//$scope.oldDate = $filter("date")(olderDate, 'yyyy-MM-dd');
		});
		
		/*Custom filter used to filter within a date range*/
		myApp.filter("dateFilter", function() {
			//alert("running date filter");
			return function(items, dateFrom, dateTo) {
				var result = [];  
				/*alert("First date: " + new Date(items[0].dateS));
				alert("Date from: " + dateFrom);
				alert("Date to: " + dateTo);*/
				
				/*var testFrom = dateFrom;
				if(dateFrom == null){testFrom = olderDate;}
				var testTo = dateTo;
				if(dateTo == null){testTo = currentDate;}*/
				
				for (var i=0; i<items.length; i++){
					var dateSub = new Date(items[i].dateS);
					if(dateFrom == null && dateTo == null)		{result.push(items[i]);} //if neither filter date is defined
					else if(dateFrom != null && dateTo != null)	{if (dateSub >= dateFrom && dateSub <= dateTo){result.push(items[i]);}} //if both filter dates are defined
					else if(dateFrom != null) 					{if (dateSub >= dateFrom){result.push(items[i]);}} //if only from date is defined
					else if(dateTo != null)						{if (dateSub <= dateTo){result.push(items[i]);}} //if only date to is defined
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