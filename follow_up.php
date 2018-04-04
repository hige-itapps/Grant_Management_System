<?php
	/*Debug user validation*/
	//include "include/debugAuthentication.php";
	include "include/CAS_login.php";
	
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
	
	/*User is trying to download a document*/
	if(isset($_GET["doc"]))
	{
		downloadDocs($_GET["id"], $_GET["doc"]);
	}
	/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
	For everything besides application creation, the app ID MUST BE SET*/
	if(isset($_GET["id"]))
	{
		//admin check
		$isAdmin = isAdministrator($conn, $CASbroncoNetId); //admin is viewing page; can edit stuff
		$permissionSet = $isAdmin;
		
		//isFollowUpReportApprover
		if(!$permissionSet)
		{
			$isFUApprover = isFollowUpReportApprover($conn, $$CASbroncoNetId); //application approver(director) can write notes, choose awarded amount, and generate email text
			$permissionSet = $isFUApprover;
		}
		
		//committee member check
		if(!$permissionSet)
		{
			$isCommittee = isUserAllowedToSeeApplications($conn, $$CASbroncoNetId); //committee member; can only view!
			$permissionSet = $isCommittee;
		}
		
		//applicant reviewing check
		if(!$permissionSet)
		{
			$isReviewing = doesUserOwnApplication($conn, $$CASbroncoNetId, $_GET['id']) && hasFUReport($conn, $_GET["id"]); //applicant is reviewing their application
			$permissionSet = $isReviewing;
		}
	}
	//applicant creating check. Note- if the app id is set, then by default the application cannot be created
	if(!$permissionSet)
	{
		$isCreating = !hasFUReport($conn, $_GET["id"]); //applicant is creating a Follow-Up R
		$permissionSet = $isCreating; //will set to true if user is 
	}
	
	$idA = $_GET["id"];
	
	
	/*Verify that user is allowed to render application*/
	if($permissionSet)
	{
		$P = array();
		$app = getApplication($conn, $_GET['id']); //get application Data
		/*Initialize variables if application has already been created*/
		if(!$isCreating)
		{
			
			
			$fR = getFUReport($conn, $_GET["id"]);
			$docs = listDocs($_GET["id"]); //get documents
			for($i = 0; $i < count($docs); $i++)
			{
				if(substr($docs[$i], 0, 1) == 'F')
					array_push($P, "<a href='?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>");
			}
			
				/*Check for trying to update application*/
			if($isAdmin && isset($_POST["updateApp"]))
			{
				updateApplication($conn, $idA);
				header('Location: index.php'); //redirect to homepage
			}
			
			/*User wants to approve this application*/
			if(isset($_POST["approveA"]))
			{
				approveFU($conn, $_GET["id"], trim($app->email), $_POST["finalE"]);
				header('Location: app_list.php'); //redirect to app_list
			}
			
			/*User wants to deny this application*/
			if(isset($_POST["denyA"]))
			{
				denyFU($conn, $_GET["id"], trim($app->email), $_POST["finalE"]);
				header('Location: app_list.php'); //redirect to app_list
			}
			/*User wants to HOLD this application*/
			/*if(isset($_POST["holdA"]))
			{
				holdApplication($conn, $_GET["id"], trim($app->email), $_POST["finalE"]);
				header('Location: app_list.php'); //redirect to app_list
			}*/
			
		} 
		
	?>
	<!--HEADER-->
	
		<!--BODY-->
		<div class="container-fluid">
		
			<?php if($isReviewing){ //form for updating an application ?>
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="functions/documents.php">
			<?php }else if($isCreating){ //form for a new application ?>
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="controllers/addFUReport.php">
			<?php }else{ //default form ?>
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="#">
			<?php } ?>
			
				<div ng-controller="appCtrl">
				
					<div class="row">
						<h1 class="title">FOLLOW-UP REPORT:</h1>
					</div>
				
					<!--APPLICANT INFO-->
					<div class="row">
						<h2 class="title">Applicant Information:</h2>
					</div>
					
					
					
					<div class="row">
					<!--NAME-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if(/*$isCreating || */$isAdmin){ //for creating or updating applications ?>
									<label for="inputName">Name (up to <?php echo $maxName; ?> characters):</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" required <?php if($isAdmin){echo 'value="'.$app->name.'"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<label for="inputName">Name:</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" disabled="true" value="<?php echo $app->name; ?>"/>
								<?php } ?>
							</div>
						</div>
						
						
					<!--EMAIL-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if(/*$isCreating || */$isAdmin){ //for creating or updating applications ?>
									<label for="inputEmail">Email Address (up to <?php echo $maxEmail; ?> characters):</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" required <?php if($isAdmin){echo 'value="'.$app->email.'"';}else{echo 'value="'.$CASemail.'"';}?> />
								<?php }else{ //for viewing applications ?>
									<label for="inputEmail">Email Address:</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" disabled="true" value="<?php echo $app->email; ?>"/>
								<?php } ?>
							</div>
						</div>
						
						
					<!--DEPARTMENT-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if(/*$isCreating || */$isAdmin){ //for creating or updating applications ?>
									<label for="inputDept">Department (up to <?php echo $maxDep; ?> characters):</label>
									<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" required <?php if($isAdmin){echo 'value="'.$app->dept.'"';} ?>/>
								<?php  }else{ //for viewing applications ?>
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
								<?php if(/*$isCreating ||*/ $isAdmin){ //for creating or updating applications ?>
									<label for="inputDeptM">Department Mail Stop (4 digits):</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) || event.which == 8 || event.which == 46' required <?php if($isAdmin){echo 'value="'.$app->deptM.'"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<label for="inputDeptM">Department Mail Stop:</label>
									<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" disabled="true" value="<?php echo $app->deptM; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-6">
							<div class="form-group">
								<?php if(/*$isCreating || */$isAdmin){ //for creating or updating applications ?>
									<label for="inputDeptCE">Department Chair's Email Address (up to <?php echo $maxDepEmail; ?> characters):</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" required <?php if($isAdmin){echo 'value="'.$app->deptCE.'"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<label for="inputDeptCE">Department Chair's Email Address:</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" disabled="true" value="<?php echo $app->deptCE; ?>" />
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--RESEARCH INFO-->
					<div class="row">
						<p><h2 class="title">Follow-Up Information:</h2></p>
					</div>
					
					
					
					<div class="row">
					<!--TRAVEL DATE FROM-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTFrom">Travel Date From:</label>
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" required <?php if($isAdmin){echo 'value="'.$fR->tStart.'"'; } else { echo 'value="'.$fR->tStart.'" disabled="true"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" />
								<?php } ?>
							</div>
						</div>
						
					
					<!--TRAVEL DATE TO-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTTo">Travel Date To:</label>
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<input type="date" class="form-control" id="inputTTo" name="inputTTo" required <?php if($isAdmin){echo 'value="'.$fR->tEnd.'"'; } else { echo 'value="'.$fR->tEnd.'" disabled="true"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<input type="date" class="form-control" id="inputTTo" name="inputTTo" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--ACTIVITY DATE FROM-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputAFrom">Activity Date From:</label>
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" required <?php if($isAdmin){echo 'value="'.$fR->aStart.'"'; } else { echo 'value="'.$fR->aStart.'" disabled="true"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--ACTIVITY DATE TO-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputATo">Activity Date To:</label>
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<input type="date" class="form-control" id="inputATo" name="inputATo" required <?php if($isAdmin){echo 'value="'.$fR->aEnd.'"'; } else { echo 'value="'.$fR->aEnd.'" disabled="true"';} ?>/>
								<?php }else{ //for viewing applications ?>
									<input type="date" class="form-control" id="inputATo" name="inputATo" />
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<div class="row">
						<div class="col-md-2"></div>
					<!--TITLE-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<label for="inputRName">Title of Research (up to <?php echo $maxTitle; ?> characters):</label>
									<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" disabled="true" required <?php echo 'value="'.$app->rTitle.'"'; ?>/>
								<?php }else{ //for viewing applications ?>
									<label for="inputRName">Title of Research:</label>
									<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" disabled="true" value="<?php echo $app->rTitle; ?>" />
								<?php } ?>
							</div>
						</div>
						
						
					<!--DESTINATION-->
						<div class="col-md-4">
							<div class="form-group">
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<label for="inputDest">Destination (up to <?php echo $maxDestination; ?> characters):</label>
									<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" disabled="true" required <?php echo 'value="'.$app->dest.'"'; ?>/>
								<?php }else{ //for viewing applications ?>
									<label for="inputDest">Destination:</label>
									<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" disabled="true" value="<?php echo $app->dest; ?>" />
								<?php } ?>
							</div>
						</div>
					
						<div class="col-md-2"></div>
					
					<div class="row">
					<!--AMOUNT AWARDED SPENT-->
						<div class="col-md-12">
							<div class="form-group">
								<label for="finalE">AMOUNT AWARDED SPENT:</label>
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
								<input type="text" class="form-control" id="aAw" name="aAw" placeholder="AMOUNT AWARDED SPENT" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' <?php if($isAdmin){echo 'value="'.$fR->awardedS.'"'; } else { echo 'value="'.$fR->awardedS.'" disabled="true"';} ?> />
								<?php }else{ //for viewing applications ?>
								<input type="text" class="form-control" id="aAw" name="aAw" placeholder="AMOUNT AWARDED SPENT" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' />
								<?php } ?>
							</div>
						</div>
					</div>
					
					<!--PROPOSAL SUMMARY-->
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<?php if($isReviewing || $isFUApprover || $isAdmin){ //for creating or updating applications ?>
									<label for="props">Proposal Summary (up to <?php echo $maxProposalSummary; ?> characters) (We recommend up to 150 words):</label>
									<textarea class="form-control" id="projs" name="projs" placeholder="Enter Proposal Summary" rows=10 required <?php if($isReviewing || $isFUApprover) echo 'disabled="true"'; ?>/><?php echo $fR->pS; ?></textarea>
								<?php }else{ //for viewing applications ?>
									<label for="props">Proposal Summary:</label>
									<textarea class="form-control" id="projs" name="projs" placeholder="Enter Project Summary" rows=10 required /></textarea>
								<?php } ?>
							</div>
						</div>
					</div>
					
					
					
					<!--UPLOAD INFO-->
					<div class="row">
						<h2 class="title">UPLOAD DOCUMENTS:</h2>
					</div>
					
					<!--UPLOADS-->
					<div class="row">
						<center>
							<?php if($isCreating || $isReviewing || $isAdmin){ //for uploading documents; both admins and applicants ?>
								<label for="followD"><h3>Click to Upload:</h3></label><input type="file" name="followD[]" id="followD" multiple />
							<?php } //for viewing uploaded documents; ANYONE can ?>
							<p class="title">UPLOADED TRAVEL RECEIPTS: <?php if(count($P > 0)) { foreach($P as $ip) echo $ip . " "; } else { echo "none"; } ?> </p>
						</center>
					</div>
					
					
				
					<!--UPLOADS NOTE-->
					<div class="row">
						<h3>Please upload travel expense vouchers, receipts, and travel authorization forms.</h3> 
					</div>
					
					
					
					<br><br>
					<?php if($isFUApprover) { ?>
					<div class="row">
					<!--EMAIL EDIT-->
						<div class="col-md-12">
							<div class="form-group">
								<label for="finalE">EMAIL TO BE SENT:</label>
								<textarea class="form-control" id="finalE" name="finalE" placeholder="Enter email body, with greetings." rows=20 required /></textarea>
							</div>
						</div>
					</div>
					<?php } ?>
					<br><br>
					
					<?php/* if($isReviewing || $isAdmin){ //show submit application button if creating*/ ?>
						<input type="hidden" name="appID" value="<?php echo $app->id; ?>" />
					<?php /* } */ ?>	
					
					<div class="row">
						<div class="col-md-2"></div>
					<!--SUBMIT BUTTONS-->
						<div class="col-md-6">
							<?php if($isCreating){ //show submit application button if creating ?>
								<input type="submit" class="btn btn-success" id="submitApp" name="submitApp" value="Submit Follow-Up Report" />
							<?php }else if($isAdmin){ //show update, approve, and deny buttons if admin ?>
								<input type="submit" class="btn btn-success" id="updateApp" name="updateApp" value="UPDATE FOLLOW-UP REPORT" />
								<input type="submit" class="btn btn-warning" id="approveApp" name="approveA" value="APPROVE FOLLOW-UP REPORT" />
								<input type="submit" class="btn btn-danger" id="denyApp" name="denyA" value="DENY FOLLOW-UP REPORT" />
							<?php }else if($isFUApprover){ //show approve, hold, and deny buttons if approver ?>
								<input type="submit" class="btn btn-success" id="approveApp" name="approveA" value="APPROVE FOLLOW-UP REPORT" />
								<!--<input type="submit" class="btn btn-primary" id="holdApp" name="holdA" value="PLACE APPLICATION ON HOLD" />-->
								<input type="submit" class="btn btn-danger" id="denyApp" name="denyA" value="DENY FOLLOW-UP REPORT" />
							<?php }else if($isReviewing){ ?>
								<input type="submit" class="btn btn-primary" id="uploadDocsF" name="uploadDocsF" value="Update Follow-Up Report" />
							<?php } ?>
						</div>
						<div class="col-md-2">
							<a href="index.php" onclick="return confirm ('Are you sure you want to leave this page? Any unsaved data will be lost.')" class="btn btn-info">LEAVE PAGE</a>
						</div>
						<div class="col-md-2"></div>
					</div>
				</div>
			</form>
		</div>
		<!--BODY-->
	
	<?php
	 }else{ 
	    echo "<h1>You do not have permission to access an application!</h1>";
	 } 
	?>
	</body>
</html>
<?php
	 $conn = null; //close connection 
?>