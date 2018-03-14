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
	
	
	/*Initialize all user permissions to false*/
	$isCreating = false; //user is an applicant initially creating application
	$isReviewing = false; //user is an applicant reviewing their already created application
	$isAdmin = false; //user is an administrator
	$isCommittee = false; //user is a committee member
	$isChair = false; //user is the associated department chair
	$isApprover = false; //user is an application approver (director)
	
	$permissionSet = false; //boolean set to true when a permission has been set- used to force only 1 permission at most
	
	/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
	For everything besides application creation, the app ID MUST BE SET*/
	if(isset($_GET["id"]))
	{
		//admin check
		$isAdmin = isAdministrator($conn, $_SESSION['broncoNetID']); //admin is viewing page; can edit stuff
		$permissionSet = $isAdmin;
		
		//application approver check
		if(!$permissionSet)
		{
			$isApprover = isApplicationApprover($conn, $_SESSION['broncoNetID']); //application approver(director) can write notes, choose awarded amount, and generate email text
			$permissionSet = $isApprover;
		}
		
		//department chair check
		if(!$permissionSet)
		{
			$isChair = isUserAllowedToSignApplication($conn, $_SESSION['email'], $_GET['id']); //chair member; can sign application
			$permissionSet = $isChair;
		}
		
		//committee member check
		if(!$permissionSet)
		{
			$isCommittee = isUserAllowedToSeeApplications($conn, $_SESSION['broncoNetID']); //committee member; can only view!
			$permissionSet = $isCommittee;
		}
		
		//applicant reviewing check
		if(!$permissionSet)
		{
			$isReviewing = doesUserOwnApplication($conn, $_SESSION['broncoNetID'], $_GET['id']); //applicant is reviewing their application
			$permissionSet = $isReviewing;
		}
	}
	//applicant creating check. Note- if the app id is set, then by default the application cannot be created
	if(!$permissionSet && !isset($_GET["id"]))
	{
		$isCreating = isUserAllowedToCreateApplication($conn, $_SESSION['broncoNetID'], $_SESSION['position']); //applicant is creating an application
		$permissionSet = $isCreating; //will set to true if user is 
	}
	
	
	
	
	/*Verify that user is allowed to render application*/
	if($permissionSet)
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
			
				/*Check for trying to update application*/
			if($isAdmin && isset($_POST["updateApp"]))
			{
				updateApplication($conn, $idA);
				header('Location: index.php'); //redirect to homepage
			}
			
			/*Check for trying to approve application*/
			if($isAdmin || $isApprover && isset($_POST["approveApp"]))
			{
				approveApplication($conn, $idA);
				header('Location: index.php'); //redirect to homepage
			}
			
			/*Check for trying to deny application*/
			if($isAdmin || $isApprover && isset($_POST["denyApp"]))
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
		}
		
	?>
	<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
		
			<?php if(!$isCreating){ //form for a new application?>
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="#">
			<?php }else{ //form for an existing application?>
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
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputName">Name (up to <?php echo $maxName; ?> characters):</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" required <?php if($isAdmin){echo 'value="'.$app->name.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<label for="inputName">Name:</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" disabled="true" value="<?php echo $app->name; ?>"/>
								<?php } ?>
							</div>
						</div>
						
						
					<!--EMAIL-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputEmail">Email Address (up to <?php echo $maxEmail; ?> characters):</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" required <?php if($isAdmin){echo 'value="'.$app->email.'"';}else{echo 'value="'.$_SESSION['email'].'"';}?> />
								<?php }else{ //for viewing applications?>
									<label for="inputEmail">Email Address:</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" disabled="true" value="<?php echo $app->email; ?>"/>
								<?php } ?>
							</div>
						</div>
						
						
					<!--DEPARTMENT-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputDept">Department (up to <?php echo $maxDep; ?> characters):</label>
									<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" required <?php if($isAdmin){echo 'value="'.$app->dept.'"';} ?>/>
								<?php  }else{ //for viewing applications?>
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
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputDeptM">Department Mail Stop (4 digits):</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' required <?php if($isAdmin){echo 'value="'.$app->deptM.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<label for="inputDeptM">Department Mail Stop:</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" disabled="true" value="<?php echo $app->deptM; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-6">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputDeptCE">Department Chair's Email Address (up to <?php echo $maxDepEmail; ?> characters):</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" required <?php if($isAdmin){echo 'value="'.$app->deptCE.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<label for="inputDeptCE">Department Chair's Email Address:</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" disabled="true" value="<?php echo $app->deptCE; ?>" />
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--RESEARCH INFO-->
					<div class="row">
						<h2 class="title">Research Information:</h2>
					</div>
					
					
					
					<div class="row">
					<!--TRAVEL DATE FROM-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTFrom">Travel Date From:</label>
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" required <?php if($isAdmin){echo 'value="'.$app->tStart.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" disabled="true" value="<?php echo $app->tStart; ?>" />
								<?php } ?>
							</div>
						</div>
						
					
					<!--TRAVEL DATE TO-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTTo">Travel Date To:</label>
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<input type="date" class="form-control" id="inputTTo" name="inputTTo" onchange="TDate()" required <?php if($isAdmin){echo 'value="'.$app->tEnd.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<input type="date" class="form-control" id="inputTTo" name="inputTTo" disabled="true" value="<?php echo $app->tEnd; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--ACTIVITY DATE FROM-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputAFrom">Activity Date From:</label>
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" onchange="ADateF()" required <?php if($isAdmin){echo 'value="'.$app->aStart.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<input type="date" class="form-control" id="inputTTo" name="inputTTo" disabled="true" value="<?php echo $app->aStart; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--ACTIVITY DATE TO-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputATo">Activity Date To:</label>
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<input type="date" class="form-control" id="inputATo" name="inputATo" onchange="ADateT()" required <?php if($isAdmin){echo 'value="'.$app->aEnd.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<input type="date" class="form-control" id="inputTTo" name="inputTTo" disabled="true" value="<?php echo $app->aEnd; ?>" />
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<div class="row">
					<!--TITLE-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputRName">Title of Research (up to <?php echo $maxTitle; ?> characters):</label>
									<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" required <?php if($isAdmin){echo 'value="'.$app->rTitle.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<label for="inputRName">Title of Research:</label>
									<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" disabled="true" value="<?php echo $app->rTitle; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--DESTINATION-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="inputDest">Destination (up to <?php echo $maxDestination; ?> characters):</label>
									<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" required <?php if($isAdmin){echo 'value="'.$app->dest.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<label for="inputDest">Destination:</label>
									<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" disabled="true" value="<?php echo $app->dest; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--AMOUNT REQ-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputAR">Amount Requested($):</label>
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<input type="text" class="form-control" id="inputAR" name="inputAR" placeholder="Enter Amount Requested($)" onkeypress='return (event.which >= 48 && event.which <= 57) 
										|| event.which == 8 || event.which == 46' required <?php if($isAdmin){echo 'value="'.$app->aReq.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<input type="text" class="form-control" id="inputAR" name="inputAR" placeholder="Enter Amount Requested($)" disabled="true" value="<?php echo $app->aReq; ?>" />
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--PURPOSES-->
					<div class="row">
						<label for="purposes">Purpose of Travel:</label>
					</div>
					
					
					
					<!--PURPOSE:RESEARCH-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="purpose1" type="checkbox" value="purpose1">Research</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="purpose1" type="checkbox" value="purpose1" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->pr1 == 1) echo "checked"; ?>>Research</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--PURPOSE:CONFERENCE-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="purpose2" type="checkbox" value="purpose2">Conference</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="purpose2" type="checkbox" value="purpose2" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->pr2 == 1) echo "checked"; ?>>Conference</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--PURPOSE:CREATIVE ACTIVITY-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating  applications?>
									<label><input name="purpose3" type="checkbox" value="purpose3">Creative Activity</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="purpose3" type="checkbox" value="purpose3" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->pr3 == 1) echo "checked"; ?>>Creative Activity</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--PURPOSE:OTHER-->
					<div class="row">
						<div class="col-md-2">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="purposeOtherDummy" id="purposeOtherDummy" type="checkbox" value="purposeOtherDummy">Other, explain.</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="purposeOtherDummy" id="purposeOtherDummy" type="checkbox" value="purposeOtherDummy" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->pr4 != "") echo "checked"; ?>>Other, explain.</label>
								<?php } ?>
							</div>
						</div>
						
						
						<div class="col-md-10">
							<div class="form-group">
								<?php if($isCreating){ //for creating applications?>
									<label for="purposeOtherText">Explain other purpose (up to <?php echo $maxOtherEvent; ?> characters):</label>
									<input type="text" class="form-control" id="purposeOther" name="purposeOther" disabled="true" placeholder="Enter Explanation" />
								<?php }else if($isAdmin){ //for updating applications?>
									<label for="purposeOtherText">Explain other purpose (up to <?php echo $maxOtherEvent; ?> characters):</label>
									<input type="text" class="form-control" id="purposeOther" name="purposeOther" disabled="true" placeholder="Enter Explanation" disabled="true" value="<?php echo $app->pr4; ?>"/>
								<?php }else{ //for viewing applications?>
									<label for="purposeOtherText">Explain other purpose:</label>
									<input type="text" class="form-control" id="purposeOther" name="purposeOther" disabled="true" placeholder="Enter Explanation" disabled="true" value="<?php echo $app->pr4; ?>"/>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--OTHER FUNDING-->
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="eS">Are you receiving other funding? Who is providing the funds? How much? (up to <?php echo $maxOtherFunding; ?> characters):</label>
									<input type="text" class="form-control" id="eS" name="eS" placeholder="Explain here" <?php if($isAdmin){echo 'value="'.$app->oF.'"';} ?>/>
								<?php }else{ //for viewing applications?>
									<label for="eS">Are you receiving other funding? Who is providing the funds? How much?:</label>
									<input type="text" class="form-control" id="eS" name="eS" placeholder="Explain here" disabled="true" value="<?php echo $app->oF; ?>"/>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--PROPOSAL SUMMARY-->
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
									<label for="props">Proposal Summary (up to <?php echo $maxProposalSummary; ?> characters) (We recommend up to 150 words):</label>
									<textarea class="form-control" id="props" name="props" placeholder="Enter Proposal Summary" rows=10 required /><?php if($isAdmin){echo $app->pS;} ?></textarea>
								<?php }else{ //for viewing applications?>
									<label for="props">Proposal Summary:</label>
									<textarea class="form-control" id="props" name="props" placeholder="Enter Proposal Summary" rows=10 disabled="true" required /><?php echo $app->pS; ?></textarea>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--GOALS-->
					<div class="row">
						<label for="goals">Please indicate which of the prioritized goals of the IEFDF this proposal fulfills:</label>
					</div>
					
					
					
					<!--GOAL 1-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="goal1" type="checkbox" value="goal1">
									Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="goal1" type="checkbox" value="goal1" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->fg1 == 1) echo "checked"; ?>>
									Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--GOAL 2-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="goal2" type="checkbox" value="goal2">
									Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="goal2" type="checkbox" value="goal2" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->fg2 == 1) echo "checked"; ?>>
									Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--GOAL 3-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="goal3" type="checkbox" value="goal3">
									Support for attendance at international conferences, seminars or workshops.</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="goal3" type="checkbox" value="goal3" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->fg3 == 1) echo "checked"; ?>>
									Support for attendance at international conferences, seminars or workshops.</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--GOAL 4-->
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<?php if($isCreating){ //for creating applications?>
									<label><input name="goal4" type="checkbox" value="goal4">
									Support for scholarly international travel in order to enrich international knowledge, which will directly
									contribute to the internationalization of the WMU curricula.</label>
								<?php }else{ //for viewing or updating applications?>
									<label><input name="goal4" type="checkbox" value="goal4" <?php if(!$isAdmin){echo 'disabled="true"';} ?> <?php if($app->fg4 == 1) echo "checked"; ?>>
									Support for scholarly international travel in order to enrich international knowledge, which will directly
									contribute to the internationalization of the WMU curricula.</label>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--BUDGET-->
					<div class="row">
						<h2>Budget: (please separate room and board calculating per diem)</h2>
					</div>
					
					
					
					<div class="row">
						<div class="col-md-12">
							<table id="budgetList" class="table table-sm">
							<!--BUDGET:TABLE HEAD-->
								<thead>
									<tr>
										<th>Expense:</th>
										
										<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
											<th>Comments (up to <?php echo $maxBudgetComment; ?> characters):</th>
										<?php }else{ //for viewing applications?>
											<th>Comments:</th>
										<?php } ?>
										
										<th>Amount($):</th>
									</tr>
								</thead>
								
								
							<!--BUDGET:TABLE BODY-->
								<tbody>
									<tr class="row" ng-repeat="bitem in bitems">
									<!--BUDGET:EXPENSE-->
										<td>
											<div class="form-group">
												<?php if($isCreating){ //for creating applications?>
													<select ng-model="bitem.ex" class="form-control" name="{{bitem.exN}}" value="{{bitem.ex}}" required>
														<option ng-repeat="o in options" value="{{o.name}}">{{o.name}}</option>
													</select>
												<?php }else{ //for viewing or updating applications?>
													<select ng-model="bitem.ex" class="form-control" name="{{bitem.exN}}" value="{{bitem.ex}}" <?php if(!$isAdmin){echo 'disabled';} ?>>
														<option ng-repeat="o in options" value="{{o.name}}">{{o.name}}</option>
													</select>
												<?php } ?>
											</div>
										</td>
							
							
									
									<!--BUDGET:COMMENTS-->
										<td>
											<div class="form-group">
												<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
													<input type="text" class="form-control" name="{{bitem.comN}}" placeholder="Explain..." required />
												<?php }else{ //for viewing applications?>
													<input type="text" class="form-control" name="{{bitem.comN}}" disabled="true" value="{{bitem.com}}" />
												<?php } ?>
											</div>
										</td>
										
										
									<!--BUDGET:AMOUNT-->
										<td>
											<div class="form-group">
												<?php if($isCreating || $isAdmin){ //for creating or updating applications?>
													<input type="text" class="form-control" name="{{bitem.amN}}" ng-model="bitem.am" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' required />
												<?php }else{ //for viewing applications?>
													<input type="text" class="form-control" name="{{bitem.amN}}" ng-model="bitem.am" disabled="true" value="{{bitem.am}}" />
												<?php } ?>
											</div>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					
					
					
					<!--BUDGET:ADD OR REMOVE-->
					<?php if($isCreating || $isAdmin){ //for creating or updating applications only?>
						<div class="row">
							<div class="col-md-5"></div>
							<div id="budgetButtons" class="col-md-2">
								<p id="addBudget"><i class="fa fa-plus-circle fa-2x" aria-hidden="true" ng-click="addInput()"></i></p>
								<p id="removeBudget"><i class="fa fa-minus-circle fa-2x" aria-hidden="true" ng-click="remInput()"></i></p>
							</div>
							<div class="col-md-5"></div>
						</div>
					<?php }?>
					
					
					
					<!--BUDGET:TOTAL-->
					<div class="row">
						<div class="col-md-5"></div>
						<div class="col-md-2">
							<h3>Total: ${{ getTotal() }}</h3>
						</div>
						<div class="col-md-5"></div>
					</div>
					
					
					
					<!--UPLOADS-->
					<div class="row">
						<div class="col-md-6">
							<?php if($isCreating || $isReviewing || $isAdmin){ //for uploading documents; both admins and applicants?>
								<label for="fD">UPLOAD PROPOSAL NARRATIVE:</label><input type="file" accept="application/pdf" name="fD" id="fD"/>
							<?php } //for viewing uploaded documents; ANYONE can?>
							<p class="title">UPLOADED PROPOSAL NARRATIVE: <?php echo $P; ?> </p>
						</div>
						
						
						<div class="col-md-6">
							<?php if($isCreating || $isReviewing || $isAdmin){ //for uploading documents; both admins and applicants?>
								<label for="sD">UPLOAD SUPPORTING DOCUMENTS:</label><input type="file" accept="application/pdf" name="sD" id="sD"/>
							<?php } //for viewing uploaded documents; ANYONE can?>
							<p class="title">UPLOADED SUPPORTING DOCUMENTS: <?php echo $S; ?> </p>
						</div>
					</div>
					
					
				
					<!--UPLOADS NOTE-->
					<div class="row">
						<h3>Please ensure to include supporting documentation: Conference letter of acceptance; invitation letter for research or creative activity, etc.</h3> 
					</div>
					
					
					
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
						<div class="col-md-2"></div>
					<!--SUBMIT BUTTONS-->
						<div class="col-md-6">
							<?php if($isCreating){ //show submit application button if creating?>
								<input type="submit" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT APPLICATION" />
							<?php }else if($isAdmin){ //show update, approve, and deny buttons if admin?>
								<input type="submit" class="btn btn-success" id="updateApp" name="updateApp" value="UPDATE APPLICATION" />
								<input type="submit" class="btn btn-warning" id="approveApp" name="approveApp" value="APPROVE APPLICATION" />
								<input type="submit" class="btn btn-danger" id="denyApp" name="denyApp" value="DENY APPLICATION" />
							<?php }else if($isChair){ //show sign button if dep chair?>
								<input type="submit" class="btn btn-success" id="signApp" name="signApp" value="SIGN APPLICATION" />
							<?php }else if($isApprover){ //show approve, hold, and deny buttons if approver?>
								<input type="submit" class="btn btn-success" id="approveApp" name="approveApp" value="APPROVE APPLICATION" />
								<input type="submit" class="btn btn-primary" id="holdApp" name="holdApp" value="PLACE APPLICATION ON HOLD" />
								<input type="submit" class="btn btn-danger" id="denyApp" name="denyApp" value="DENY APPLICATION" />
							<?php } ?>
						</div>
						<div class="col-md-2">
							<a href="index.php" class="btn btn-info">LEAVE PAGE</a>
						</div>
						<div class="col-md-2"></div>
					</div>
				</div>
			</form>
		</div>
		<!--BODY-->
	
	<?php
	}
	else{
	?>
		<h1>You do not have permission to access an application!</h1>
	<?php
	}
	?>
	</body>
	
	<!-- AngularJS Script -->
	<script>
		
		var myApp = angular.module('HIGE-app', []);
		
		myApp.controller('appCtrl', ['$scope', function($scope){
			$scope.bitems = [];
			$scope.options = [{ name: "Air Travel"}, 
								{ name: "Ground Travel"},
								{ name: "Hotel"},
								{ name: "Registration Fee"},
								{ name: "Per Diem"},
								{ name: "Other"}];
			$scope.addInput = function(expense, comment, amount) {
				expensesName = 'expense' + ($scope.bitems.length + 1);
				comName = 'comm' + ($scope.bitems.length + 1);
				amountsName = 'amount' + ($scope.bitems.length + 1);    
				if(typeof expense === 'undefined'){expense = "Other";}
				if(typeof comment === 'undefined'){comment = "";}
				if(typeof amount === 'undefined'){amount = 0;}
				$scope.bitems.push({
					exN: expensesName,
					comN: comName,
					amN: amountsName,
					ex: expense,
					com: comment,
					am: amount
				})       
			}
			$scope.remInput = function() {
				$scope.bitems.splice($scope.bitems.length - 1, 1);
			}
			$scope.getTotal = function(){
				var total = 0;
				for(var i = 0; i < $scope.bitems.length; i++){
					total += parseFloat($scope.bitems[i]["am"]);
				}
				return (total).toFixed(2);
			}
			
			//add a few empty budget items in by default if creating application. Otherwise, load all the existing items
			<?php if($isCreating){?>
				$scope.addInput();
				$scope.addInput();
				$scope.addInput();
			<?php }else{ 
				for($i = 0; $i < count($app->budget); $i++) { ?>
					$scope.addInput("<?php echo $app->budget[$i][2]; ?>" , "<?php echo $app->budget[$i][4]; ?>" , <?php echo $app->budget[$i][3]; ?>);
				<?php }
			 } ?>
		}]);
		
		/*Messages disabled*/
		/*var c = 6;
		setInterval(function() {
			if(c != 0)
				c--;
			if(c == 0)
				if(document.getElementById("smsg") != null)
					$("#smsg").remove();
		}, 1000);*/
		
		/* FIN AJAX */
		/*TRAVEL DATE*/
		function TDate() {
			var ToDate = document.getElementById("inputTTo").value;
			var FromDate = document.getElementById("inputTFrom").value;

			if (new Date(ToDate).getTime() < new Date(FromDate).getTime()) {
				$('#inputTTo').val("");
				return false;
			}
			return true;
		}
		/*ACTIVITY DATE*/
		function ADateF() {
			var ToDate = document.getElementById("inputAFrom").value;
			var FromDate = document.getElementById("inputTFrom").value;

			if (new Date(ToDate).getTime() < new Date(FromDate).getTime()) {
				$('#inputAFrom').val("");
				return false;
			}
			return true;
		}
		function ADateT() {
			var ToDate = document.getElementById("inputATo").value;
			var FromDate = document.getElementById("inputTTo").value;

			if (new Date(ToDate).getTime() > new Date(FromDate).getTime()) {
				$('#inputATo').val("");
				return false;
			}
			return true;
		}
		/*FIN DATES*/
		/*OTHER ACTIVITY CHECK*/
		/*activate 'other purpose' box when corresponding checkbox is checked*/
		document.getElementById('purposeOtherDummy').onchange = function() {
			document.getElementById('purposeOther').disabled = !this.checked;
		};
		
	</script>
	<!-- End Script -->
</html>
<?php
	$conn = null; //close connection
?>