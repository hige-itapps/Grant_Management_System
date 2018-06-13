<?php
	/*Debug user validation*/
	//include "include/debugAuthentication.php";
	include "include/CAS_login.php";
	
	/*Get DB connection*/
	include "functions/database.php";
	$conn = connection();
	
	/*Verification functions*/
	include "functions/verification.php";
?>
<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<?php include 'include/head_content.html'; ?>
	<body>
	
		<!-- Shared Site Banner -->
		<?php include 'include/site_banner.html'; ?>
	
		<!--BODY-->
		<div id="MainContent" role="main" class="container-fluid">
			<h1>Your Views:</h1>
			<?php
				/*Initialize a counter to track number of available views to this user*/
				$viewCounter = 0;


				/*get number of applications this user needs to sign*/
				//echo "Your email: ".$CASemail;
				$totalSignApps = getNumberOfApplicationsToSign($conn, $CASemail);
				if($totalSignApps > 0)
				{
					$viewCounter++;
			?>
					<p><a href="app_all_list.php?approval">Application Approval (<?php echo $totalSignApps ?> to approve)</a></p>
			<?php 
				}


				/*get number of previously signed applications*/
				$signedApps = getNumberOfSignedApplications($conn, $CASemail);
				if($signedApps > 0)
				{
					$viewCounter++;
			?>
					<p><a href="app_all_list.php?previousApproval">View Previously Approved Applications (<?php echo $signedApps ?> approved)</a></p>
			<?php 
				}

			
				/*Make sure user is allowed to make an application. Check the latest cycle possible*/
				if(isUserAllowedToCreateApplication($conn, $CASbroncoNetId, $CASallPositions, true))
				{ 
					$viewCounter++;
			?>
					<p><a href="application.php">Create Application</a></p>
			<?php 
				}
				
				/*Let the user know they've got a pending application (if they do)*/
				if(hasPendingApplication($conn, $CASbroncoNetId))
				{
					$viewCounter++;
			?>
					<p>Your application is pending!<p>
					<p><strong>Note: IEFDF recipients must wait at least a year between applications.</strong></p>
			<?php
				}
				
				/* Let user see the apps they've submitted, if they have at least 1 */
				$totalPrevApps = getApplications($conn, $CASbroncoNetId);
				if(count($totalPrevApps) > 0)
				{
					$viewCounter++;
			?>
				<p><a href="app_all_list.php?previousSubmit">View Previous Applications (<?php echo count($totalPrevApps) ?> total)</a></p>
			<?php
				}
				
				/*Verify that user is allowed to freely see applications*/
				if(isUserAllowedToSeeApplications($conn, $CASbroncoNetId))
				{
					$viewCounter++;
			?>
					<p><a href="app_all_list.php">Application List (All)</a></p>
			<?php 
				}
				
				/*Verify user as administrator to give link to admin view*/
				if(isAdministrator($conn, $CASbroncoNetId))
				{
					$viewCounter++;
			?>
					<p><a href="administrator.php">Administrator</a></p>
			<?php 
				} 
			
				if($viewCounter == 0) //no views available to this person
				{
					?>
						<p>You do not have access to any views! You must sign in as a faculty member or staff to use this application.</p>
					<?php
				}
			?>
		</div>
		<!--BODY-->
	
	</body>
	
</html>
<?php
	$conn = null; //close connection
?>