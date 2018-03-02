<?php
	ob_start();
	
	set_include_path('/home/egf897jck0fu/public_html/');
	/*get database connection*/
	include "functions/database.php";
	$conn = connection();
	
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
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
			
			if(isUserAllowedToSeeApplications($conn, $_SESSION['broncoNetID']))
			{
				$apps = getApplications($conn, "");//get all applications
				foreach($apps as $curApp)
				{
					$curApp->statusText = $curApp->getStatus();
				}
		?>
		<!--HEADER-->
		<div class="container-fluid" ng-controller="listCtrl">
			<div class="row">
				<center><h2 class="title">Pending Applications</h2></center>
			</div>
			<div class="row">
				<div class="col-md-3"></div>
			<!--Filter first date-->
				<div class="col-md-2">
					<div class="form-group">
						<label for="filterDateFrom">Filter date after:</label>
						<input type="date" ng-model="filterFrom" class="form-control" id="filterDateFrom" name="filterDateFrom" value="{{oldDate}}" />
					</div>
				</div>
			<!--Filter last date-->
				<div class="col-md-2">
					<div class="form-group">
						<label for="filterDateTo">Filter date up to:</label>
						<input type="date" ng-model="filterTo" class="form-control" id="filterDateTo" name="filterDateTo" value="{{curDate}}" />
					</div>
				</div>
			<!--Filter status-->
				<div class="col-md-2">
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
				<div class="col-md-3"></div>
			</div>
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<table class="table">
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
								<td><a href="application_review.php?id={{ x.id }}">{{ x.rTitle }}</a></td>
								<td>{{ x.dateS | date: 'MM/dd/yyyy'}}</td>
								<td class="{{x.statusText}}">{{ x.statusText }}</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="col-md-3"></div>
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
	</body>
	
	<!-- AngularJS Script -->
	<script>
		var myApp = angular.module('HIGE-app', []);
		
		var currentDate = new Date(); //get current date
		var olderDate = new Date(); olderDate.setMonth(olderDate.getMonth() - 6); //get date from 6 months ago
		
		/*Controller to set date inputs and list*/
		myApp.controller('listCtrl', function($scope, $filter) {
			$scope.applications = <?php echo json_encode($apps) ?>;
			$scope.curDate = $filter("date")(currentDate, 'yyyy-MM-dd');
			$scope.oldDate = $filter("date")(olderDate, 'yyyy-MM-dd');
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