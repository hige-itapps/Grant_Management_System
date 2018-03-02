<?php
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*Get DB connection*/
	include "functions/database.php";
	$conn = connection();
	
	/*Verification functions*/
	include "functions/verification.php";
	
	/*Document functions*/
	include "functions/documents.php";
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
	
	/*get initial character limits for text fields*/
	$appCharMax = getApplicationsMaxLengths($conn);
	$appBudgetCharMax = getApplicationsBudgetsMaxLengths($conn);
	
	$maxName = $appCharMax[array_search('Name', array_column($appCharMax, 0))][1]; //name char limit
	$maxEmail = $appCharMax[array_search('Email', array_column($appCharMax, 0))][1]; //email char limit
	$maxDep = $appCharMax[array_search('Department', array_column($appCharMax, 0))][1]; //department char limit
	$maxDepEmail = $appCharMax[array_search('DepartmentChairEmail', array_column($appCharMax, 0))][1]; //dep email char limit
	$maxTitle = $appCharMax[array_search('Title', array_column($appCharMax, 0))][1]; //title char limit
	$maxDestination = $appCharMax[array_search('Destination', array_column($appCharMax, 0))][1]; //destination char limit
	$maxOtherEvent = $appCharMax[array_search('IsOtherEventText', array_column($appCharMax, 0))][1]; //other event text char limit
	$maxOtherFunding = $appCharMax[array_search('OtherFunding', array_column($appCharMax, 0))][1]; //other funding char limit
	$maxProposalSummary = $appCharMax[array_search('ProposalSummary', array_column($appCharMax, 0))][1]; //proposal summary char limit
	$maxDepChairSig = $appCharMax[array_search('DepartmentChairSignature', array_column($appCharMax, 0))][1];//signature char limit
	
	$maxBudgetComment = $appBudgetCharMax[array_search('Comment', array_column($appBudgetCharMax, 0))][1]; //budget comment char limit
	
	
	/*Get all user permissions. NOTE- THESE SHOULD PROBABLY BE MUTUALLY EXCLUSIVE. WE WILL NEED TO CHANGE WHAT SOME OF THE FOLLOWING FUNCTIONS CHECK FOR!*/
	$isCreating = isUserAllowedToCreateApplication($conn, $_SESSION['broncoNetID'], $_SESSION['position']); //applicant is creating an application
	$isAdmin = isAdministrator($conn, $_SESSION['broncoNetID']); //admin is viewing page; can edit stuff
	$isCommittee = isUserAllowedToSeeApplications($conn, $_SESSION['broncoNetID']); //committee member; can only view!
	$isChair = isUserAllowedToSignApplication($conn, $_SESSION['email'], $_GET['id']); //chair member; can sign application
	$isApprover = isApplicationApprover($conn, $_SESSION['broncoNetID']); //application approver(director) can write notes, choose awarded amount, and generate email text
	
	/*If application id is set, then the user can't be creating*/
	if(isset($_GET["id"]))
	{
		$isCreating = false;
	}
	
	/*Verify that user is allowed to render application*/
	if($isCreating || $isAdmin || $isCommittee || $isChair || $isApprover)
	{
		$P = "None"; //default document text
		$S = "None"; //default document text
		
		/*Initialize variables if application has already been created*/
		if(!$isCreating)
		{
			$idA = $_GET['id']; //get app ID
			$app = getApplication($conn, $idA); //get application Data
			
			$docs = listDocs($_GET["id"]); //get documents
			for($i = 0; $i < count($docs); $i++)
			{
				if(substr($docs[$i], 0, 1) == 'P')
					$P = "<a href='?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>";
				if(substr($docs[$i], 0, 1) == 'S')
					$S = "<a href='?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>";
			}
		}
		
		/*Check for trying to update application*/
		if($isAdmin && isset($_POST["updateApp"]))
		{
			approveApplication($conn, $idA);
			header('Location: app_list.php'); //redirect to app_list
		}
		
		/*Check for trying to approve application*/
		if($isApprover && isset($_POST["approveApp"]))
		{
			approveApplication($conn, $idA);
			header('Location: index.php'); //redirect to homepage
		}
		
		/*Check for trying to deny application*/
		if($isApprover && isset($_POST["denyApp"]))
		{
			denyApplication($conn, $idA);
			header('Location: index.php'); //redirect to homepage
		}
		
		/*Check for trying to sign application*/
		if($isChair && isset($_POST["signApp"]))
		{
			signApplication($conn, $idA, $_POST["inputDeptCS"]);
			header('Location: index.php'); //redirect to homepage
		}
	?>
	<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
		
			<?php if(!$isCreating){ ?>
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="#">
			<?php }else{ ?>
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="controllers/addApplication.php">
			<?php } ?>
			
				<div ng-controller="appCtrl">
				
				
					<!--APPLICANT INFO-->
					<div class="row">
						<h2 class="title">Applicant Information:</h2>
					</div>
					
					
					
					<div class="row">
					<!--NAME-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating){ //for creating applications?>
									<label for="inputName">Name(up to <?php echo $maxName; ?> characters):</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" required />
								<?php }else if($isAdmin){ ?>
									<label for="inputName">Name:</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" value="<?php echo $app->name; ?>"/>
								<?php }else{ //for viewing applications?>
									<label for="inputName">Name:</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" disabled="true" value="<?php echo $app->name; ?>"/>
								<?php } ?>
							</div>
						</div>
						
						
					<!--EMAIL-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating){ //for creating applications?>
									<label for="inputEmail">Email Address (up to <?php echo $maxEmail; ?> characters):</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" required value="<?php echo $_SESSION['email'] ?>"/>
								<?php }else if($isAdmin){ ?>
									<label for="inputEmail">Email Address:</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" value="<?php echo $app->email; ?>"/>
								<?php }else{ //for viewing applications?>
									<label for="inputEmail">Email Address:</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" disabled="true" value="<?php echo $app->email; ?>"/>
								<?php } ?>
							</div>
						</div>
						
						
					<!--DEPARTMENT-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating){ //for creating applications?>
									<label for="inputDept">Department (up to <?php echo $maxDep; ?> characters):</label>
									<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" required />
								<?php }else if($isAdmin){ ?>
									<label for="inputDept">Department:</label>
									<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" value="<?php echo $app->dept; ?>"/>
								<?php }else{ //for viewing applications?>
									<label for="inputDept">Department:</label>
									<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" disabled="true" value="<?php echo $app->dept; ?>"/>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<div class="row">
					<!--DEPT MAIL STOP-->
						<div class="col-md-6">
							<div class="form-group">
								<?php if($isCreating){ //for creating applications?>
									<label for="inputDeptM">Department Mail Stop (4 digits):</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' required />
								<?php }else if($isAdmin){ ?>
									<label for="inputDeptM">Department Mail Stop:</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" value="<?php echo $app->deptM; ?>" />
								<?php }else{ //for viewing applications?>
									<label for="inputDeptM">Department Mail Stop:</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" disabled="true" value="<?php echo $app->deptM; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-6">
							<div class="form-group">
								<?php if($isCreating){ //for creating applications?>
									<label for="inputDeptCE">Department Chair's Email Address (up to <?php echo $maxDepEmail; ?> characters):</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" required />
								<?php }else if($isAdmin){ ?>
									<label for="inputDeptCE">Department Chair's Email Address:</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" value="<?php echo $app->deptCE; ?>" />
								<?php }else{ //for viewing applications?>
									<label for="inputDeptCE">Department Chair's Email Address:</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" disabled="true" value="<?php echo $app->deptCE; ?>" />
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!-- UPLOADS -->
					<div class="row">
						<div class="col-md-6">
							<?php if($isCreating || $isAdmin){ //for uploading documents; both admins and applicants?>
								<label for="fD">UPLOAD PROPOSAL NARRATIVE:</label><input type="file" accept="application/pdf" name="fD" id="fD"/>
							<?php } //for viewing uploaded documents; ANYONE can?>
							<p class="title">UPLOADED PROPOSAL NARRATIVE: <?php echo $P; ?> </p>
						</div>
						
						
						<div class="col-md-6">
							<?php if($isCreating || $isAdmin){ //for uploading documents; both admins and applicants?>
								<label for="sD">UPLOAD SUPPORTING DOCUMENTS:</label><input type="file" accept="application/pdf" name="sD" id="sD"/>
							<?php } //for viewing uploaded documents; ANYONE can?>
							<p class="title">UPLOADED SUPPORTING DOCUMENTS: <?php echo $S; ?> </p>
						</div>
					</div>
					
					
					<?php if($isCreating){ //for applicant uploading documents?>
						<div class="row">
								<h3>Please ensure to include supporting documentation: Conference letter of acceptance; invitation letter for research or creative activity, etc.</h3> 
						</div>
					<?php } //otherwise, don't show this row?>
					
					
					
					<br><br>
					<div class="row">
					<!--DEPARTMENT CHAIR SIGNATURE-->
						<div class="col-md-3"></div>
						<div class="col-md-6">
							<div class="form-group">
								<?php if($isChair){ //for department chair to sign?>
										<label for="inputDept">Your Signature (up to <?php echo $maxDepChairSig ?> characters):</label>
										<input type="text" class="form-control" id="inputDeptCS" name="inputDeptCS" placeholder="Sign Here" required/>
								<?php }else{ //not department chair
										if(isApplicationSigned($conn, $idA) > 0){ //application is signed, so show signature?>
											<label for="inputDept">Department Chair Signature:</label>
											<input type="text" class="form-control" id="inputDeptCS" name="inputDeptCS" placeholder="Department Chair Signature Here" disabled="true" value="<?php echo $app->deptCS; ?>"/>
									<?php }else{ //application isn't signed, so show default message ?>
											<label for="inputDept">Department Chair Signature:</label>
											<input type="text" class="form-control" id="inputDeptCS" name="inputDeptCS" placeholder="Department Chair Signature Here" disabled="true"/>
								<?php }
									} ?>
							</div>
						</div>
						<div class="col-md-3"></div>
					</div>
					<br><br>
					
					
					
					<div class="row">
						<div class="col-md-4"></div>
						<div class="col-md-2">
							<?php if($isCreating){ ?>
								<input type="submit" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT APPLICATION" />
							<?php }else if($isAdmin){ ?>
								<input type="submit" class="btn btn-success" id="updateApp" name="updateApp" value="UPDATE APPLICATION" />
							<?php }else if($isChair){ ?>
								<input type="submit" class="btn btn-success" id="signApp" name="signApp" value="SIGN APPLICATION" />
							<?php }else if($isApprover){ ?>
								<input type="submit" class="btn btn-success" id="approveApp" name="approveApp" value="APPROVE APPLICATION" />
								<input type="submit" class="btn btn-danger" id="denyApp" name="denyApp" value="DENY APPLICATION" />
							<?php } ?>
						</div>
						<div class="col-md-2">
							<a href="index.php" class="btn btn-info">Leave Page</a>
						</div>
						<div class="col-md-4"></div>
					</div>
				</div>
			</form>
		</div>
		<!--BODY-->
	
	<?php
	}
	else{
	?>
		<h1>You can view this application!</h1>
	<?php
	}
	?>
	</body>
	
	<!-- AngularJS Script -->
	<script>
		
		var myApp = angular.module('HIGE-app', []); //unused at the moment
		
	</script>
	<!-- End Script -->
</html>
<?php
	$conn = null; //close connection
?>