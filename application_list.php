<?php
	ob_start();
	
	/*Debug user validation*/
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

			$isAllowedToSeeApplications = (isUserAllowedToSeeApplications($conn, $CASbroncoNetID) ? true : false); //permission for whether or not user is allowed to see everyone's applications

			if($isAllowedToSeeApplications || $signedAppsNumber > 0 || $appsToSignNumber > 0|| count($totalPrevApps) > 0) //user is allowed to see SOMETHING
			{
				$apps = [];
				//use user permission to get specific set of applications
				if($signedAppsNumber > 0 && isset($_GET["previousApproval"])){ //department chair- already signed
					$apps = getSignedApplications($conn, $CASemail);//get all signed applications only
				}
				else if($appsToSignNumber > 0 && isset($_GET["approval"])){ //department chair- to sign
					$apps = getApplicationsToSign($conn, $CASemail);//get all applications to sign only
				}
				else if(count($totalPrevApps) > 0 && isset($_GET["previousSubmit"])){ //normal applicant viewing previous applications
					$apps = $totalPrevApps;
				}
				else if($isAllowedToSeeApplications){//default privileges (for HIGE staff)
					$apps = getApplications($conn, "");//get all applications
				}

				$appCycles = []; //array to hold all app cycles as strings
				
				foreach($apps as $curApp)
				{
					$curApp->statusText = $curApp->getStatus(); //retrieve the current status for each application

					//If this is a user's own application and they are allowed, let them create a follow-up report
					if(isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetID, $curApp->id))
					{
						$curApp->FollowUpReportCreate = true; //let user know they can create a follow up report
					}
					else
					{
						$curApp->FollowUpReport = getFollowUpReport($conn, $curApp->id); //load up an existing follow up report if possible
						if($curApp->FollowUpReport) //if they have one
						{
							$curApp->reportStatusText = $curApp->FollowUpReport->getStatus(); //retrieve the current status of the follow-up report
						}
					}

					$curApp->cycle = getCycleName(DateTime::createFromFormat('Y-m-d', $curApp->dateSubmitted), $curApp->nextCycle, false); //retrieve the cycle this application was submitted during
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
								<option value="">All</option>
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
						<table class="table table-striped" id="appTable">
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
								<!-- Apply all filters to the list based on: dates, cycles, name, and status -->
								<tr ng-repeat="application in (filteredApplications = (applications | dateFilter:filterFrom:filterTo | filter: (!!filterCycle || undefined)&&{cycle: filterCycle} | filter: {name: filterName, statusText: filterStatus}))">
									<td>{{ application.id }}</td>
									<td>{{ application.name }}</td>
									<td>{{ application.title }}</td>
									<td>{{ application.cycle }}</td>
									<td>{{ application.dateSubmitted | date: 'MM/dd/yyyy'}}</td>
									<td class="{{application.statusText}}">{{ application.statusText }}</td>
									<td>{{application.deptChairApproval}}</td>
									<td><a href="application.php?id={{application.id}}">Application</a></td>
									<td ng-if="application.FollowUpReport"><a href="follow_up.php?id={{application.id}}">Follow-Up Report</a></td>					<td class="{{application.reportStatusText}}" ng-if="application.FollowUpReport">{{x.reportStatusText}}</td>
										<td ng-if="application.FollowUpReportCreate"><a href="follow_up.php?id={{application.id}}">Create Follow-Up Report</a></td>	<td ng-if="application.FollowUpReportCreate">N/A</td>
										<td ng-if="!application.FollowUpReport && !application.FollowUpReportCreate">N/A</td>										<td ng-if="!application.FollowUpReport && !application.FollowUpReportCreate">N/A</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-md-1"></div>
				</div>

				<div class="buttons-group bottom-buttons"> 
					<button type="button" ng-click="exportExcelSheet()" class="btn btn-success">DOWNLOAD SUMMARY SHEET</button> <!-- For anyone to download excel sheet of current data -->
					<a href="index.php" class="btn btn-info">LEAVE PAGE</a> <!-- For anyone to leave the page -->
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
	<script type="module">

		//import saveAs function from FileSaver.js library so that user can download as excel sheet;
		import { saveAs } from '/FileSaver.js-master/src/FileSaver.js';

		var myApp = angular.module('HIGE-app', []);
		
		//var currentDate = new Date(); //get current date
		//var olderDate = new Date(); olderDate.setMonth(olderDate.getMonth() - 6); //get date from 6 months ago
		
		/*Controller to set date inputs and list*/
		myApp.controller('listCtrl', function($scope, $filter) {
			$scope.applications = <?php echo json_encode($apps) ?>;
			$scope.appCycles = <?php echo json_encode($appCycles) ?>;

			$scope.exportExcelSheet = function () {
				//console.log($scope.filteredApplications); //the current selection of applications to be exported
				var exportList = "Application ID\tName\tDepartment\tPrevious Approved Cycles\tPurpose(s)\tDestination\tProject Title\tTravel From\tTravel To\tActivity From\tActivity To\tTotal Budget\tRequested Award\tAmount Awarded\tComments\n"; //initialize final export data, also set the header line
				
				$scope.filteredApplications.forEach(function(app){ //append each row onto the exportList
					exportList += app['id'] + "\t";
					exportList += app['name'] + "\t";
					exportList += app['department'] + "\t";
					exportList += (app['pastApprovedCycles'] != null ? app['pastApprovedCycles'].join(", ") : "") + "\t"; //turn into comma separated list, or empty string if null
					
					var purposesArray = new Array(); //array of purposes as strings
					if(app['purpose1'] === 1) {purposesArray.push("Research");}
					if(app['purpose2'] === 1) {purposesArray.push("Conference");}
					if(app['purpose3'] === 1) {purposesArray.push("Creative Activity");}
					if(app['purpose4'] !== '') {purposesArray.push("Other");}
					exportList += purposesArray.join(", ") + "\t"; //turn into comma separated list

					exportList += app['destination'] + "\t";
					exportList += app['title'] + "\t";
					exportList += app['travelFrom'] + "\t";
					exportList += app['travelTo'] + "\t";
					exportList += app['activityFrom'] + "\t";
					exportList += app['activityTo'] + "\t";

					var totalBudget = 0; //the total budget for this app
					app['budget'].forEach(function(budgetItem){
						totalBudget += budgetItem[3]*100; //add this cost to the total (AS CENTS TO AVOID FLOATING POINT ISSUES)
					});
					var totalBudgetString = totalBudget.toString();//format as string
					exportList += "$" + totalBudgetString.substr(0, totalBudgetString.length-2) + "." + totalBudgetString.substr(totalBudgetString.length-2) + "\t"; //splice in a decimal point in the correct spot

					exportList += "$" + app['amountRequested'] + "\t";
					exportList += (app['amountAwarded'] != null ? "$"+app['amountAwarded'] : "") + "\t";//amount awarded; if something has already been awarded, mention it, but otherwise leave it blank
					exportList += "\n"; //empty comments row
				});
				
				//console.log(exportList);

				var blob = new Blob([exportList], {
					type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
				});
				saveAs(blob, "SummarySheet.xls");
			};


			/*On startup*/

			/*User permissions*/
			$scope.isAllowedToSeeApplications = <?php echo json_encode($isAllowedToSeeApplications); ?>;
		});
		
		/*Custom filter used to filter within a date range*/
		myApp.filter("dateFilter", function() {
			//alert("running date filter");
			return function(items, dateFrom, dateTo) {
				var result = [];  

				for (var i=0; i<items.length; i++){
					var dateSubmittedub = new Date(items[i].dateSubmitted);
					if(dateFrom == null && dateTo == null)		{result.push(items[i]);} //if neither filter date is defined
					else if(dateFrom != null && dateTo != null)	{if (dateSubmittedub >= dateFrom && dateSubmittedub <= dateTo){result.push(items[i]);}} //if both filter dates are defined
					else if(dateFrom != null) 					{if (dateSubmittedub >= dateFrom){result.push(items[i]);}} //if only from date is defined
					else if(dateTo != null)						{if (dateSubmittedub <= dateTo){result.push(items[i]);}} //if only to date to is defined
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