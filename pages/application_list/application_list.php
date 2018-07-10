<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();
	
	/*Cycle functions*/
	include_once(dirname(__FILE__) . "/../../functions/cycles.php");

	//Initialize everything with PHP
	$totalPrevApps = getApplications($conn, $CASbroncoNetID);
	$signedAppsNumber = getNumberOfSignedApplications($conn, $CASemail);
	$appsToSignNumber = getNumberOfApplicationsToSign($conn, $CASemail, $CASbroncoNetID);
	$isAllowedToSeeApplications = (isUserAllowedToSeeApplications($conn, $CASbroncoNetID) ? true : false); //permission for whether or not user is allowed to see everyone's applications

	if($isAllowedToSeeApplications || $signedAppsNumber > 0 || $appsToSignNumber > 0|| count($totalPrevApps) > 0) //user is allowed to see SOMETHING
	{
		$apps = [];
		//use user permission to get specific set of applications
		if($signedAppsNumber > 0 && isset($_GET["previousApproval"])){ //department chair- already signed
			$apps = getSignedApplications($conn, $CASemail);//get all signed applications only
		}
		else if($appsToSignNumber > 0 && isset($_GET["approval"])){ //department chair- to sign
			$apps = getApplicationsToSign($conn, $CASemail, $CASbroncoNetID);//get all applications to sign only
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
			//If this is a user's own application and they are allowed, let them create a follow-up report
			if(isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetID, $curApp->id))
			{
				$curApp->FollowUpReportCreate = true; //let user know they can create a follow up report
			}
			else
			{
				$curApp->FollowUpReport = getFollowUpReport($conn, $curApp->id); //load up an existing follow up report if possible
			}

			$curApp->cycle = getCycleName(DateTime::createFromFormat('Y-m-d', $curApp->dateSubmitted), $curApp->nextCycle, false); //retrieve the cycle this application was submitted during
			if (!in_array($curApp->cycle, $appCycles)) {//push cycle to all cycles if it's not there already
				$appCycles[] = $curApp->cycle;
			}

			$curApp->pastApprovedCycles = getPastApprovedCycles($conn, $curApp->broncoNetID); //save the previously approved cycles for this user
		}
		
		$appCycles = sortCycles($appCycles); //sort cycles in descending order
?>










<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../../include/head_content.html'; ?>

		<!-- For exporting files in-browser -->
		<script type="module" src="../../FileSaver.js-master/src/FileSaver.js"></script>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_applications = <?php echo json_encode($apps); ?>;
			var scope_appCycles = <?php echo json_encode($appCycles); ?>;
			var scope_isAllowedToSeeApplications = <?php echo json_encode($isAllowedToSeeApplications); ?>;
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="application_list.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
		<!-- Shared Site Banner -->
		<?php include '../../include/site_banner.html'; ?>

		<div id="MainContent" role="main">
			<script src="../../include/outdatedbrowser.js"></script> <!-- show site error if outdated -->
			<?php include '../../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->

			<!--AngularJS Controller-->
			<div class="container-fluid" ng-controller="listCtrl" id="listCtrl">
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
								<tr ng-repeat="application in (filteredApplications = (applications | dateFilter:filterFrom:filterTo | filter: (!!filterCycle || undefined)&&{cycle: filterCycle} | filter: {name: filterName, status: filterStatus}))">
									<td>{{ application.id }}</td>
									<td>{{ application.name }}</td>
									<td>{{ application.title }}</td>
									<td>{{ application.cycle }}</td>
									<td>{{ application.dateSubmitted | date: 'MM/dd/yyyy'}}</td>
									<td class="{{application.status}}">{{ application.status }}</td>
									<td>{{application.deptChairApproval}}</td>
									<td><a href="../application/application.php?id={{application.id}}">Application</a></td>
									<td ng-if="application.FollowUpReport"><a href="../follow_up/follow_up.php?id={{application.id}}">Follow-Up Report</a></td>					<td class="{{application.FollowUpReport.status}}" ng-if="application.FollowUpReport">{{application.FollowUpReport.status}}</td>
										<td ng-if="application.FollowUpReportCreate"><a href="../follow_up/follow_up.php?id={{application.id}}">Create Follow-Up Report</a></td>	<td ng-if="application.FollowUpReportCreate">N/A</td>
										<td ng-if="!application.FollowUpReport && !application.FollowUpReportCreate">N/A</td>										<td ng-if="!application.FollowUpReport && !application.FollowUpReportCreate">N/A</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="col-md-1"></div>
				</div>

				<div class="alert alert-{{alertType}} alert-dismissible fade in" ng-show='alertMessage'>
					<button type="button" class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
				</div>

				<div class="buttons-group bottom-buttons"> 
					<button type="button" ng-click="exportExcelSheet()" class="btn btn-success">DOWNLOAD SUMMARY SHEET</button> <!-- For anyone to download excel sheet of current data -->
					<a href="../home/home.php" class="btn btn-info">LEAVE PAGE</a> <!-- For anyone to leave the page -->
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