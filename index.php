<?php
	/*Debug user validation
	TODO: replace with actual user validation using CAS!!!*/
	include('functions/debugvalidate.php');
	
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
			
				/*Verify user as faculty member in order to make an application*/
				if($position == 'faculty')
				{ 
					$viewCounter++;
			?>
					<p><a href="application_builder.php">Application Builder</a></p>
			<?php } ?>
				
			<?php
				$totalApps = getNumberOfApplicationsToSign($conn, $email);
				if($totalApps > 0)
				{
					$viewCounter++;
			?>
					<p><a href="application_signature.php">Application Signature (<?php echo $totalApps ?> to sign)</a></p>
			<?php } ?>
			
			<?php 
				/*Verify user as Applicant whos application has been approved*/
				if(isApplicationApproved($conn, $broncoNetID))
				{ 
					$viewCounter++;
			?>
					<p><a href="follow_up_report_builder.php">Follow-up Report Builder</a></p>
			<?php } ?>
			
			<?php 
				/*Verify user as application approver to approve applications*/
				if(isApplicationApprover($broncoNetID, $conn))
				{
					$viewCounter++;
			?>
					<p><a href="app_list.php">Application Confirmation</a></p>
			<?php } ?>
			
			<?php 
				/*Verify user as committee member or application approver to see applications*/
				if(isCommitteeMember($broncoNetID, $conn) || isApplicationApprover($broncoNetID, $conn))
				{
					$viewCounter++;
			?>
					<p><a href="application_viewer.php">Application Viewer</a></p>
			<?php } ?>
			
			<?php 
				/*Verify user as follow-up report approver to approve(and see) follow-up reports*/
				if(isFollowUpReportApprover($broncoNetID, $conn))
				{
					$viewCounter++;
			?>
					<p><a href="follow_up_report_confirmation.php">Follow-up Report Confirmation</a></p>
			<?php } ?>
			
			<?php 
				/*Verify user as follow-up report approver or application approver to see follow-up reports*/
				if(isFollowUpReportApprover($broncoNetID, $conn) || isApplicationApprover($broncoNetID, $conn))
				{
					$viewCounter++;
			?>
					<p><a href="follow_up_report_viewer.php">Follow-up Report Viewer</a></p>
			<?php } ?>
			
			<?php 
				/*Verify user as administrator to give link to admin view*/
				if(isAdministrator($broncoNetID, $conn))
				{
					$viewCounter++;
			?>
					<p><a href="administrator.php">Administrator</a></p>
			<?php } 
			
				if($viewCounter == 0) //no views available to this person
				{
					?>
						<p>You do not have access to any views!</p>
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