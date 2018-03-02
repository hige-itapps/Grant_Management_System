<?php
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*Get DB connection*/
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
		<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
			<p>Your Views:</p>
			<?php
				/*Initialize a counter to track number of available views to this user*/
				$viewCounter = 0;
			
				/*Make sure user is allowed to make an application*/
				if(isUserAllowedToCreateApplication($conn, $_SESSION['broncoNetID'], $_SESSION['position']))
				{ 
					$viewCounter++;
			?>
					<p><a href="application_builder.php">Application Builder</a></p>
			<?php 
				}
				
				/*Let the user know they've got a pending application (if they do)*/
				if(hasPendingApplication($conn, $_SESSION['broncoNetID']))
				{
					$viewCounter++;
			?>
					<p>Your application is pending!<p>
					<p><b>Note: IEFDF recipients must wait at least a year between applications.</b></p>
			<?php
				}
				
				/* Let user see the apps they've submitted, if they have at least 1 */
				$totalPrevApps = getApplications($conn, $_SESSION['broncoNetID']);
				if(count($totalPrevApps) > 0)
				{
			?>
				<p><a href="app_prev_list.php">View Previous Applications (<?php echo count($totalPrevApps) ?> total)</a></p>
			<?php
				}
				
				/*get number of applications this user needs to sign*/
				$totalSignApps = getNumberOfApplicationsToSign($conn, $_SESSION['email']);
				if($totalSignApps > 0)
				{
					$viewCounter++;
			?>
					<p><a href="app_sig_list.php">Application Signature (<?php echo $totalSignApps ?> to sign)</a></p>
			<?php 
				}
			
				/*Verify user as Applicant whos application has been approved*/
				if(isApplicationApproved($conn, $_SESSION['broncoNetID']))
				{ 
					$viewCounter++;
			?>
					<p><a href="follow_up_report_builder.php">Follow-up Report Builder</a></p>
			<?php 
				}
				
				/*Verify that user is allowed to approve applications in order to access pending ones*/
				if(isApplicationApprover($conn, $_SESSION['broncoNetID']))
				{
			?>
					<p><a href="app_list.php">Pending Applications</a></p>
			<?php
				}
				
				/*Verify that user is allowed to freely see applications*/
				if(isUserAllowedToSeeApplications($conn, $_SESSION['broncoNetID']))
				{
					$viewCounter++;
			?>
					<p><a href="app_all_list.php">Application List</a></p>
			<?php 
				}
				
				/*Verify user as follow-up report approver to approve follow-up reports*/
				if(isFollowUpReportApprover($conn, $_SESSION['broncoNetID']))
				{
					$viewCounter++;
			?>
					<p><a href="follow_up_report_confirmation.php">Follow-up Report Confirmation</a></p>
			<?php 
				}
				
				/*Verify that user is allowed to freely see follow-up reports*/
				if(isUserAllowedToSeeApplications($conn, $_SESSION['broncoNetID']))
				{
					$viewCounter++;
			?>
					<p><a href="follow_up_report_viewer.php">Follow-up Report Viewer</a></p>
			<?php 
				}
				
				/*Verify user as administrator to give link to admin view*/
				if(isAdministrator($conn, $_SESSION['broncoNetID']))
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