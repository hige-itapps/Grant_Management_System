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
	
	<!-- Page Head -->
	<?php include 'include/head_content.html'; ?>
	<body ng-app="HIGE-app">
	
		<!-- Shared Site Banner -->
		<?php include 'include/site_banner.html'; ?>

		<div id="MainContent" role="main">

		<?php

			$totalPrevApps = getApplications($conn, $CASbroncoNetID);
			$signedAppsNumber = getNumberOfSignedApplications($conn, $CASemail);
			$appsToSignNumber = getNumberOfApplicationsToSign($conn, $CASemail);

			$isAllowedToSeeApplications = false; //permission for whether or not user is allowed to see everyone's applications

			if(isUserAllowedToSeeApplications($conn, $CASbroncoNetID) || $signedAppsNumber > 0 || $appsToSignNumber > 0|| count($totalPrevApps) > 0) //user is allowed to see SOMETHING
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
				else if(count($totalPrevApps) > 0 && isset($_GET["previousSubmit"])) //normal applicant viewing previous applications
				{
					$apps = $totalPrevApps;
				}
				else if(isUserAllowedToSeeApplications($conn, $CASbroncoNetID))//default privileges (for HIGE staff)
				{
					$isAllowedToSeeApplications = true;
					$apps = getApplications($conn, "");//get all applications
				}

				$appCycles = []; //array to hold all app cycles as strings
				
				foreach($apps as $curApp)
				{
					$curApp->statusText = $curApp->getStatus();

					//If this is a user's own application and they are allowed, let them create a follow-up report
					if(isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetID, $curApp->id))
					{
						$curApp->FollowUpReportCreate = true; //let user know they can create a follow up report
					}
					else
					{
						$curApp->FollowUpReport = getFollowUpReport($conn, $curApp->id); //load up an existing follow up report if possible
						if($curApp->FollowUpReport)
						{
							$curApp->reportStatusText = $curApp->FollowUpReport->getStatus();
						}
					}

					//echo $curApp->dateSubmitted;
					$curApp->cycle = getCycleName(DateTime::createFromFormat('Y-m-d', $curApp->dateSubmitted), $curApp->nextCycle, false);
					if (!in_array($curApp->cycle, $appCycles)) {//push cycle to all cycles if it's not there already
						$appCycles[] = $curApp->cycle;
					}

					$curApp->pastApprovedCycles = getPastApprovedCycles($conn, $curApp->broncoNetID); //save the previously approved cycles for this user
				}
				
				$appCycles = sortCycles($appCycles); //sort cycles in descending order
			?>
			<!--HEADER-->
			<div class="container-fluid" ng-controller="listCtrl">
				<div class="row">
					<h1 class="title">All Applications</h1>
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
						<table class="table" id="appTable">
							<caption>Selected Applications:</caption>
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Title</th>
									<th>Cycle</th>
									<th>Date Submitted</th>
									<th>Status</th>
									<th>Approval</th>
									<th>Application Link</th>
									<th>Follow-up Report Link</th>
									<th>Follow-up Report Status</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-if="filterCycle" ng-repeat="x in applications | dateFilter:filterFrom:filterTo | filter: {name: filterName, statusText: filterStatus, cycle: filterCycle}">
									<td>{{ x.id }}</td>
									<td>{{ x.name }}</td>
									<td>{{ x.title }}</td>
									<td>{{ x.cycle }}</td>
									<td>{{ x.dateSubmitted | date: 'MM/dd/yyyy'}}</td>
									<td class="{{x.statusText}}">{{ x.statusText }}</td>
									<td>{{x.deptChairApproval}}</td>
									<td><a href="application.php?id={{x.id}}">Application</a></td>
									<td ng-if="x.FollowUpReport"><a href="follow_up.php?id={{x.id}}">Follow-Up Report</a></td>				<td class="{{x.reportStatusText}}" ng-if="x.FollowUpReport">{{x.reportStatusText}}</td>
										<td ng-if="x.FollowUpReportCreate"><a href="follow_up.php?id={{x.id}}">Create Follow-Up Report</a>	</td><td ng-if="x.FollowUpReportCreate">N/A</td>
										<td ng-if="!x.FollowUpReport && !x.FollowUpReportCreate">N/A</td>									<td ng-if="!x.FollowUpReport && !x.FollowUpReportCreate">N/A</td>
								</tr>
								<tr ng-if="!filterCycle" ng-repeat="x in applications | dateFilter:filterFrom:filterTo | filter: {name: filterName, statusText: filterStatus}">
									<td>{{ x.id }}</td>
									<td>{{ x.name }}</td>
									<td>{{ x.title }}</td>
									<td>{{ x.cycle }}</td>
									<td>{{ x.dateSubmitted | date: 'MM/dd/yyyy'}}</td>
									<td class="{{x.statusText}}">{{ x.statusText }}</td>
									<td>{{x.deptChairApproval}}</td>
									<td><a href="application.php?id={{x.id}}">Application</a></td>
									<td ng-if="x.FollowUpReport"><a href="follow_up.php?id={{x.id}}">Follow-Up Report</a></td>				<td class="{{x.reportStatusText}}" ng-if="x.FollowUpReport">{{x.reportStatusText}}</td>
										<td ng-if="x.FollowUpReportCreate"><a href="follow_up.php?id={{x.id}}">Create Follow-Up Report</a>	</td><td ng-if="x.FollowUpReportCreate">N/A</td>
										<td ng-if="!x.FollowUpReport && !x.FollowUpReportCreate">N/A</td>									<td ng-if="!x.FollowUpReport && !x.FollowUpReportCreate">N/A</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-md-1"></div>
				</div>

				<div class="row">
					<div class="col-md-5"></div>
					<div class="col-md-2">
						<?php if($isAllowedToSeeApplications){ //if the user can see all applications, give them the option to download an excel summary sheet of them ?>
							<a href="" class="btn btn-success" ng-click="exportExcelSheet()">
								Download Excel Summary Sheet
							</a>
						<?php } ?>

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

		//import saveAs from "FileSaver.js";

		var myApp = angular.module('HIGE-app', []);
		
		//var currentDate = new Date(); //get current date
		//var olderDate = new Date(); olderDate.setMonth(olderDate.getMonth() - 6); //get date from 6 months ago
		
		/*Controller to set date inputs and list*/
		myApp.controller('listCtrl', function($scope, $filter) {
			$scope.applications = <?php echo json_encode($apps) ?>;
			$scope.appCycles = <?php echo json_encode($appCycles) ?>;
			//$scope.curDate = $filter("date")(currentDate, 'yyyy-MM-dd');
			//$scope.oldDate = $filter("date")(olderDate, 'yyyy-MM-dd');
			$scope.exportExcelSheet = function () {
				alert("Exporting to excel!");
				var blob = new Blob(["Hello World!"], {
					type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
				});
				saveAs(blob, "SummarySheet.xls");

				 /*window.open('data:application/vnd.ms-excel,' + $('#appTable').html());
   				 e.preventDefault();*/
			};
		});
		
		/*Custom filter used to filter within a date range*/
		myApp.filter("dateFilter", function() {
			//alert("running date filter");
			return function(items, dateFrom, dateTo) {
				var result = [];  
				/*alert("First date: " + new Date(items[0].dateSubmitted));
				alert("Date from: " + dateFrom);
				alert("Date to: " + dateTo);*/
				
				/*var testFrom = dateFrom;
				if(dateFrom == null){testFrom = olderDate;}
				var testTo = dateTo;
				if(dateTo == null){testTo = currentDate;}*/
				
				for (var i=0; i<items.length; i++){
					var dateSubmittedub = new Date(items[i].dateSubmitted);
					if(dateFrom == null && dateTo == null)		{result.push(items[i]);} //if neither filter date is defined
					else if(dateFrom != null && dateTo != null)	{if (dateSubmittedub >= dateFrom && dateSubmittedub <= dateTo){result.push(items[i]);}} //if both filter dates are defined
					else if(dateFrom != null) 					{if (dateSubmittedub >= dateFrom){result.push(items[i]);}} //if only from date is defined
					else if(dateTo != null)						{if (dateSubmittedub <= dateTo){result.push(items[i]);}} //if only date to is defined
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