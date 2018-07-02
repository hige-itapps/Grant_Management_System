<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../../functions/verification.php");

	//Initialize everything with PHP
	$totalAppsToSign = getNumberOfApplicationsToSign($conn, $CASemail, $CASbroncoNetID); //get number of applications this user needs to sign
	$totalSignedApps = getNumberOfSignedApplications($conn, $CASemail); //get number of previously signed applications
	$isUserAllowedToCreateApplication = isUserAllowedToCreateApplication($conn, $CASbroncoNetID, $CASallPositions, true); //Make sure user is allowed to make an application. Check the latest cycle possible
	$hasPendingApplication = hasPendingApplication($conn, $CASbroncoNetID); //Let the user know they've got a pending application (if they do)
	$totalPrevApps = getNumberOfApplications($conn, $CASbroncoNetID); //Let user see the apps they've submitted, if they have at least 1
	$isUserAllowedToSeeApplications = isUserAllowedToSeeApplications($conn, $CASbroncoNetID); //Verify that user is allowed to freely see applications
	$isAdmin = isAdministrator($conn, $CASbroncoNetID); //Verify user as administrator to give link to admin view

	$alertType = isset($_POST["alert_type"]) ? $_POST["alert_type"] : null; //set the alert type if it exists, otherwise set to null
	$alertMessage = isset($_POST["alert_message"]) ? $_POST["alert_message"] : null; //set the alert type if it exists, otherwise set to null

?>
<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<head>
		<!-- Shared head content -->
		<?php include '../../include/head_content.html'; ?>

		<!-- Set values from PHP on startup, accessible by the AngularJS Script -->
		<script type="text/javascript">
			var scope_totalAppsToSign = <?php echo json_encode($totalAppsToSign); ?>;
			var scope_totalSignedApps = <?php echo json_encode($totalSignedApps); ?>;
			var scope_isUserAllowedToCreateApplication = <?php echo json_encode($isUserAllowedToCreateApplication); ?>;
			var scope_hasPendingApplication = <?php echo json_encode($hasPendingApplication); ?>;
			var scope_totalPrevApps = <?php echo json_encode($totalPrevApps); ?>;
			var scope_isUserAllowedToSeeApplications = <?php echo json_encode($isUserAllowedToSeeApplications); ?>;
			var scope_isAdmin = <?php echo json_encode($isAdmin); ?>;
			var alert_type = <?php echo json_encode($alertType); ?>;
			var alert_message = <?php echo json_encode($alertMessage); ?>;
		</script>
		<!-- AngularJS Script -->
		<script type="module" src="home.js"></script>
	</head>

	<!-- Page Body -->
	<body ng-app="HIGE-app">
		<!-- Shared Site Banner -->
		<?php include '../../include/site_banner.html'; ?>
	
		<div id="MainContent" role="main">
			<script src="../../include/outdatedbrowser.js"></script> <!-- show site error if outdated -->
			<?php include '../../include/noscript.html'; ?> <!-- show site error if javascript is disabled -->

			<!--AngularJS Controller-->
			<div class="container-fluid" ng-controller="homeCtrl" id="homeCtrl">

				<h1 class="title" ng-if="totalViews > 0">Your Accessible Pages:</h1>
				<h1 class="title" ng-if="totalViews <= 0">You Have No Accessible Pages!</h1>

				<div class="row">
					<div class="col-md-4"></div>
					<div class="col-md-4">
						<ul id="pageList">
							<li ng-if="totalAppsToSign > 0"><a href="../application_list/application_list.php?approval">Approve Applications ({{totalAppsToSign}} to approve)</a></li>
							<li ng-if="totalSignedApps > 0"><a href="../application_list/application_list.php?previousApproval">View Previously Approved Applications ({{totalSignedApps}} approved)</a></li>
							<li ng-if="isUserAllowedToCreateApplication"><a href="../application/application.php">Create Application</a></li>
							<li ng-if="totalPrevApps > 0"><a href="../application_list/application_list.php?previousSubmit">View Previous Applications ({{totalPrevApps}} total)</a></li>
							<li ng-if="isUserAllowedToSeeApplications"><a href="../application_list/application_list.php">List All Applications</a></li>
							<li ng-if="isAdmin"><a href="../administrator/administrator.php">Administrator Page</a></li>
						</ul>	
					</div>
					<div class="col-md-4"></div>
				</div>	
				
				<p ng-if="hasPendingApplication">Your application is pending!<p>
				
				<p>Note: IEFDF recipients must wait at least a full academic year between applications.</p>

				<div class="alert alert-{{alertType}} alert-dismissible fade in" ng-show='alertMessage'>
					<button type="button" class="close" aria-label="Close" ng-click="removeAlert()"><span aria-hidden="true">&times;</span></button>{{alertMessage}}
				</div>
			</div>
		</div>
		<!--BODY-->
	
	</body>
	
</html>
<?php
	$conn = null; //close connection
?>