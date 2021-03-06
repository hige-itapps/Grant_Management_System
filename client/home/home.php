<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../CAS/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php");

	/*Cycle functions*/
	include_once(dirname(__FILE__) . "/../../server/Cycles.php");

	/*Logger*/
	include_once(dirname(__FILE__) . "/../../server/Logger.php");

	/*Site Warning*/
	include_once(dirname(__FILE__) . "/../../server/SiteWarning.php");

	$logger = new Logger(); //for logging to files
	$database = new DatabaseHelper($logger); //database helper object used for some verification and insertion
	$cycles = new Cycles(); //Cycles helper object
	$siteWarning = new SiteWarning($database); //used to determine if a site warning exists and should be displayed

	//Initialize everything with PHP
	$totalAppsToSign = $database->getNumberOfApplicationsToSign($CASemail, $CASbroncoNetID); //get number of applications this user needs to sign
	$totalSignedApps = $database->getNumberOfSignedApplications($CASemail); //get number of previously signed applications
	$isUserAllowedToCreateApplication = $database->isUserAllowedToCreateApplication($CASbroncoNetID, true); //Make sure user is allowed to make an application. Check the latest cycle possible
	$hasPendingApplication = $database->hasPendingApplication($CASbroncoNetID); //Let the user know they've got a pending application (if they do)
	$hasApplicationOnHold = $database->hasApplicationOnHold($CASbroncoNetID); //Let the user know they've got an application on hold (if they do)
	$totalPrevApps = $database->getNumberOfApplications($CASbroncoNetID); //Let user see the apps they've submitted, if they have at least 1
	$isUserAllowedToSeeApplications = $database->isUserAllowedToSeeApplications($CASbroncoNetID); //Verify that user is allowed to freely see applications
	$isAdmin = $database->isAdministrator($CASbroncoNetID); //Verify user as administrator to give link to admin view

	$alertType = isset($_POST["alert_type"]) ? $_POST["alert_type"] : null; //set the alert type if it exists, otherwise set to null
	$alertMessage = isset($_POST["alert_message"]) ? $_POST["alert_message"] : null; //set the alert type if it exists, otherwise set to null

	$nextApplicableCycle = ''; //init next applicable cycle as string
	if(!$isUserAllowedToCreateApplication && $totalPrevApps > 0) //if user is not allowed to create a new application but they have applied before, let them know when they can next apply
	{
		$latestApprovedApplication = $database->getMostRecentApprovedApplication($CASbroncoNetID); //get the most recent approved application, if any
		if($latestApprovedApplication != null) //actually has one
		{
			$nextApplicableCycle = $cycles->getNextCycleToApplyFor($cycles->getCycleName( //get next applicable cycle as a string
				DateTime::createFromFormat('Y-m-d', $latestApprovedApplication->dateSubmitted),
				$latestApprovedApplication->nextCycle,
				false
			));
		}
	}

	$finalReportID = -1; //set to a positive number if there is a final report to create

	$mostRecentApplication = $database->getMostRecentApprovedApplication($CASbroncoNetID); //null if none
	if($mostRecentApplication != null)
	{
		if($database->isUserAllowedToCreateFinalReport($CASbroncoNetID, $mostRecentApplication->id))
		{
			$finalReportID = $mostRecentApplication->id; //set it to the appropriate ID
		}
	}

?>
<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../include/head_content.html'; ?>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_totalAppsToSign = <?php echo json_encode($totalAppsToSign); ?>;
			var scope_totalSignedApps = <?php echo json_encode($totalSignedApps); ?>;
			var scope_isUserAllowedToCreateApplication = <?php echo json_encode($isUserAllowedToCreateApplication); ?>;
			var scope_hasPendingApplication = <?php echo json_encode($hasPendingApplication); ?>;
			var scope_hasApplicationOnHold = <?php echo json_encode($hasApplicationOnHold); ?>;
			var scope_totalPrevApps = <?php echo json_encode($totalPrevApps); ?>;
			var scope_isUserAllowedToSeeApplications = <?php echo json_encode($isUserAllowedToSeeApplications); ?>;
			var scope_isAdmin = <?php echo json_encode($isAdmin); ?>;
			var alert_type = <?php echo json_encode($alertType); ?>;
			var alert_message = <?php echo json_encode($alertMessage); ?>;
			var scope_nextApplicableCycle = <?php echo json_encode($nextApplicableCycle); ?>;
			var scope_finalReportID = <?php echo json_encode($finalReportID); ?>;
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="home.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
		<!-- Shared Site Banner -->
		<?php include '../include/site_banner.html'; ?>
	
		<div id="MainContent" role="main">
			<?php $siteWarning->showIfExists() ?> <!-- show site warning if it exists -->
			<script src="../include/outdatedbrowser.js" nomodule></script> <!-- show site error if outdated -->
			<?php include '../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->

			<!--AngularJS Controller-->
			<div class="container-fluid" ng-controller="homeCtrl" id="homeCtrl" ng-cloak>

				<h1 class="title" ng-if="totalViews > 0">Your Accessible Pages:</h1>
				<h1 class="title" ng-if="totalViews <= 0">You Have No Accessible Pages!</h1>

				<div class="row">
					<div class="col-md-4"></div>
					<div class="col-md-4">
						<ul id="pageList">
							<li ng-if="finalReportID > 0"><a href="../final_report/final_report.php?id={{finalReportID}}">Create Final Report</a></li>
							<li ng-if="totalAppsToSign > 0"><a href="../application_list/application_list.php?approval">Approve Applications ({{totalAppsToSign}} to approve)</a></li>
							<li ng-if="totalSignedApps > 0"><a href="../application_list/application_list.php?previousApproval">View Applications You've Approved As A Chair ({{totalSignedApps}} approved)</a></li>
							<li ng-if="isUserAllowedToCreateApplication"><a href="../application/application.php">Create Application</a></li>
							<li ng-if="totalPrevApps > 0"><a href="../application_list/application_list.php?previousSubmit">View Previous Applications ({{totalPrevApps}} total)</a></li>
							<li ng-if="isUserAllowedToSeeApplications"><a href="../application_list/application_list.php">List All Applications</a></li>
							<li ng-if="isAdmin"><a href="../administrator/administrator.php">Administrator Page</a></li>
						</ul>	
					</div>
					<div class="col-md-4"></div>
				</div>	
				
				<p ng-if="hasPendingApplication">Your application is pending!<p>
				<p ng-if="hasApplicationOnHold">Your application is on hold!<p>
				<p ng-if="nextApplicableCycle !== ''">You are currently unable to create a new application because not enough time has passed since your last approved application. The earliest cycle you can apply for is {{nextApplicableCycle}}.<p>

				<div class="alert alert-{{alertType}} alert-dismissible" ng-class="{hideAlert: !alertMessage}">
					<button type="button" title="Close this alert." class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
				</div>
			</div>
		</div>
		<!--BODY-->

		<!-- Shared Site Footer -->
		<?php include '../include/site_footer.php'; ?>
	</body>
	
</html>
<?php
	$database->close(); //close database connections
?>