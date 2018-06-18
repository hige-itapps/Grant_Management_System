<?php
	/*Debug user validation*/
	//include "include/debugAuthentication.php";
	include_once(dirname(__FILE__) . "/include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/functions/verification.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/functions/documents.php");

	/*For sending custom emails*/
	include_once(dirname(__FILE__) . "/functions/customEmail.php");
?>



<?php
	 
	 /*************FOR ADDING OR UPDATING FOLLOW UP REPORT***************/
	ob_start();

	if(isset($_POST["submitApp"]))
	{
		$isAdmin = isAdministrator($conn, $CASbroncoNetID);

		/*Verify that user is allowed to make an application*/
		if(isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetID, $_POST['updateID']) || $isAdmin)
		{
			//echo "User is allowed to create an application!";
			
			try
			{
				//echo "current broncoNetID: ".$_SESSION['broncoNetID'];
				//echo "current broncoNetID: ".$_SESSION['broncoNetID'];
				
				/*Insert data into database - receive the new application id if success, or 0 if failure*/
				/*parameters: DB connection, updating boolean, app ID, travel date from, travel date to, activity date from, activity date to, project summary, and amount awarded spent*/
				if($isAdmin)
				{
					$successAppID = insertFollowUpReport($conn, true, $_POST['updateID'], $_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], 
						$_POST["projs"], $_POST["aAw"]);
				}
				else
				{
					$successAppID = insertFollowUpReport($conn, false, $_POST['updateID'], $_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], 
						$_POST["projs"], $_POST["aAw"]);
				}

				echo "<br>Insert status: ".$successAppID.".<br>";
				
				$successUpload = 0; //initialize value to 0, should be made to something > 0 if upload is successful
				
				if($successAppID > 0) //if insert into DB was successful, continue
				{
					echo "<br>Uploading docs...<br>";
					$successUpload = uploadDocs($successAppID); //upload the documents
					
					echo "<br>Upload status: ".$successUpload.".<br>";
				}
				else
				{
					echo "<br>ERROR: could not insert application, app status: ".$successAppID."!<br>";
				}
				
				if($successUpload > 0) //upload was successful
				{
					header('Location: /');
				}
				else
				{
					echo "<br>ERROR: could not upload application documents, upload status: ".$successUpload."!<br>";
				}
				
			}
			catch(Exception $e)
			{
				echo "Error adding application: " . $e->getMessage();
			}
			
		}
		
		$conn = null; //close connection
	}
?>









<!DOCTYPE html>
<html lang="en">
	
	<!-- Page Head -->
	<?php include 'include/head_content.html'; ?>
	<body>
	
		<!-- Shared Site Banner -->
		<?php include 'include/site_banner.html'; ?>

	<div id="MainContent" role="main">

		<?php
		
		/*get initial character limits for text fields*/
		$reportCharMax = getFollowUpReportsMaxLengths($conn);

		$maxProjectSummary = $reportCharMax[array_search('ProjectSummary', array_column($reportCharMax, 0))][1]; //project summary char limit
		
		
		/*Initialize all user permissions to false*/
		$isCreating = false; //user is an applicant initially creating report
		$isReviewing = false; //user is an applicant reviewing their already created report
		$isAdmin = false; //user is an administrator
		$isAdminUpdating = false; //user is an administrator who is updating the report
		$isCommittee = false; //user is a committee member
		$isChairReviewing = false; //user is the associated department chair, but cannot do anything (just for reviewing purposes)
		$isFollowUpReportApprover = false; //user is a follow-up report approver
		
		$permissionSet = false; //boolean set to true when a permission has been set- used to force only 1 permission at most
		
		/*Get all user permissions. THESE ARE TREATED AS IF THEY ARE MUTUALLY EXCLUSIVE; ONLY ONE CAN BE TRUE!
		No matter what, the app ID MUST BE SET*/
		if(isset($_GET["id"]))
		{
			//admin updating check
			$isAdminUpdating = (isAdministrator($conn, $CASbroncoNetID) && isset($_GET["updating"])); //admin is viewing page; can edit stuff
			$permissionSet = $isAdminUpdating;

			//admin check
			if(!$permissionSet)
			{
				$isAdmin = isAdministrator($conn, $CASbroncoNetID); //admin is viewing page
				$permissionSet = $isAdmin;
			}
			
			//application approver check
			if(!$permissionSet)
			{
				$isFollowUpReportApprover = isFollowUpReportApprover($conn, $CASbroncoNetID); //application approver(director) can write notes, choose awarded amount, and generate email text
				$permissionSet = $isFollowUpReportApprover;
			}

			//department chair reviewing check
			if(!$permissionSet)
			{
				$isChairReviewing = isUserDepartmentChair($conn, $CASemail, $_GET['id']); //chair member; can view the application, but cannot sign
				$permissionSet = $isChairReviewing;
			}
			
			//committee member check
			if(!$permissionSet)
			{
				$isCommittee = isUserAllowedToSeeApplications($conn, $CASbroncoNetID); //committee member; can only view!
				$permissionSet = $isCommittee;
			}
			
			//applicant creating check
			if(!$permissionSet)
			{
				$isCreating = isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetID, $_GET['id']); //applicant is creating a Follow-Up R
				$permissionSet = $isCreating; //will set to true if user is 
			}
			
			//applicant reviewing check
			if(!$permissionSet)
			{
				$isReviewing = (doesUserOwnApplication($conn, $CASbroncoNetID, $_GET['id']) && getFollowUpReport($conn, $_GET['id'])); //applicant is reviewing their application
				$permissionSet = $isReviewing;
			}

		}
		
		
		/*Verify that user is allowed to render application*/
		if($permissionSet)
		{
			$idA = $_GET["id"];


			/*User is trying to download a document*/
			if(isset($_GET["doc"]))
			{
				downloadDocs($_GET["id"], $_GET["doc"]);
			}
			/*User is trying to upload a document (only allowed to if has certain permissions)*/
			if($isCreating || $isReviewing || $isAdminUpdating){
				if(isset($_REQUEST["uploadDocs"]) || isset($_REQUEST["uploadDocsF"]))
				{
					uploadDocs($_REQUEST["updateID"]);
				}
			}

			$P = array();
			$app = getApplication($conn, $idA); //get application Data
			/*Initialize variables if application has already been created*/
			if(!$isCreating)
			{
				$fR = getFollowUpReport($conn, $idA);
				$docs = listDocs($idA); //get documents

				for($i = 0; $i < count($docs); $i++)
				{
					if(substr($docs[$i], 0, 1) == 'F')
						array_push($P, "<a href='?id=" . $idA . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>");
				}
				
				/*Admin wants to update report*/
				if($isAdminUpdating && isset($_POST["cancelUpdateApp"]))
				{
					header('Location: ?id=' . $idA); //reload page as admin
				}

				/*Admin wants to cancel updating this report*/
				if($isAdmin && isset($_POST["updateApp"]))
				{
					header('Location: ?id=' . $idA . '&updating'); //reload page as admin updating
				}
				
				/*User wants to approve this report*/
				if(isset($_POST["approveA"]))
				{
					if(approveFollowUpReport($conn, $idA))
					{
						customEmail(trim($app->email), nl2br($_POST["finalE"]), "");
						header('Location: index.php'); //redirect to homepage
					}
				}
				
				/*User wants to deny this report*/
				if(isset($_POST["denyA"]))
				{
					if(denyFollowUpReport($conn, $idA))
					{
						customEmail(trim($app->email), nl2br($_POST["finalE"]), "");
						header('Location: index.php'); //redirect to hommepage
					}
				}
				
			} 
			
		?>
		<!--HEADER-->
		
			<!--BODY-->
			<div class="container-fluid">


				<?php if($isAdmin){ //form for admin updates- start ?>
					<form enctype="multipart/form-data" class="form-horizontal" id="updateForm" name="updateForm" method="POST" action="#">
						<input type="submit" onclick="return confirm ('Are you sure you want to enter update mode? Any unsaved data will be lost.')" class="btn btn-warning" id="updateApp" name="updateApp" value="---UPDATE MODE---" />
					</form>
				<?php }else if($isAdminUpdating){ //form for admin updates- end ?>
					<form enctype="multipart/form-data" class="form-horizontal" id="updateForm" name="updateForm" method="POST" action="#">
						<input type="submit" onclick="return confirm ('Are you sure you want to cancel updating? Any unsaved data will be lost.')" class="btn btn-warning" id="cancelUpdateApp" name="cancelUpdateApp" value="---CANCEL EDITS---" />
					</form>
				<?php } ?>

			
				<!-- follow-up form -->
				<form enctype="multipart/form-data" class="form-horizontal" id="applicationForm" name="applicationForm" method="POST" action="#">

					
				<input type="hidden" name="updateID" value="<?php echo $_GET["id"]; ?>" />
				
					<div>


					
						<div class="row">
							<h1 class="title">FOLLOW-UP REPORT:</h1>
						</div>
					
						<!--APPLICANT INFO-->
						<div class="row">
							<h2 class="title">Applicant Information:</h2>
						</div>
						
						
						
						<div class="row">
						<!--NAME-->
							<div class="col-md-5">
								<div class="form-group">
									<label for="inputName">Name:</label>
									<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" disabled="true" value="<?php echo $app->name; ?>"/>
								</div>
							</div>
							
							
						<!--EMAIL-->
							<div class="col-md-7">
								<div class="form-group">
									<label for="inputEmail">Email Address:</label>
									<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" disabled="true" value="<?php echo $app->email; ?>"/>
								</div>
							</div>
							
						</div>
						
						
						
						<div class="row">
						<!--DEPARTMENT-->
							<div class="col-md-5">
								<div class="form-group">
									<label for="inputDept">Department:</label>
									<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" disabled="true" value="<?php echo $app->department; ?>"/>
								</div>
							</div>
							
						<!--DEPT CHAIR EMAIL-->
							<div class="col-md-7">
								<div class="form-group">
									<label for="inputDeptCE">Department Chair's Email Address:</label>
									<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" disabled="true" value="<?php echo $app->deptChairEmail; ?>" />
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
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" <?php if($isAdminUpdating){echo 'value="' . $fR->travelFrom . '"';} ?> required />
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" <?php echo 'value="' . $fR->travelFrom .  '"'; ?> disabled="true"/>
									<?php } ?>
								</div>
							</div>
							
						
						<!--TRAVEL DATE TO-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="inputTTo">Travel Date To:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" id="inputTTo" name="inputTTo" <?php if($isAdminUpdating){echo 'value="' . $fR->travelTo . '"';} ?> required />
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" id="inputTTo" name="inputTTo" <?php echo 'value="' . $fR->travelTo . '"'; ?> disabled="true"/>
									<?php } ?>
								</div>
							</div>
							
							
						<!--ACTIVITY DATE FROM-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="inputAFrom">Activity Date From:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" <?php if($isAdminUpdating){echo 'value="' . $fR->activityFrom . '"';} ?> required />
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" <?php echo 'value="' . $fR->activityFrom . '"'; ?> disabled="true"/>
									<?php } ?>
								</div>
							</div>
							
							
						<!--ACTIVITY DATE TO-->
							<div class="col-md-3">
								<div class="form-group">
									<label for="inputATo">Activity Date To:</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<input type="date" class="form-control" id="inputATo" name="inputATo" <?php if($isAdminUpdating){echo 'value="' . $fR->activityTo . '"';} ?> required />
									<?php }else{ //for viewing applications ?>
										<input type="date" class="form-control" id="inputATo" name="inputATo" <?php echo 'value="' . $fR->activityTo . '"'; ?> disabled="true"/>
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<div class="row">
							<div class="col-md-2"></div>
						<!--TITLE-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="inputRName">Project Title:</label>
									<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" disabled="true" value="<?php echo $app->title; ?>" />
								</div>
							</div>
							
							
						<!--DESTINATION-->
							<div class="col-md-4">
								<div class="form-group">
									<label for="inputDest">Destination:</label>
									<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" disabled="true" value="<?php echo $app->destination; ?>" />
								</div>
							</div>
						
							<div class="col-md-2"></div>
						
						<div class="row">
						<!--AMOUNT AWARDED SPENT-->
							<div class="col-md-12">
								<div class="form-group">
									<label for="aAw">Awarded Amount Spent($):</label>
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
									<input type="text" class="form-control" id="aAw" name="aAw" placeholder="Enter Awarded Amount Spent" onkeypress='return (event.which >= 48 && event.which <= 57) 
									|| event.which == 8 || event.which == 46' <?php if($isAdminUpdating){echo 'value="'.$fR->amountAwardedSpent.'"'; } ?> />
									<?php }else{ //for viewing applications ?>
									<input type="text" class="form-control" id="aAw" name="aAw" placeholder="Enter Awarded Amount Spent" onkeypress='return (event.which >= 48 && event.which <= 57) 
									|| event.which == 8 || event.which == 46' disabled="true" <?php echo 'value="'.$fR->amountAwardedSpent.'"'; ?>/>
									<?php } ?>
								</div>
							</div>
						</div>
						
						<!--PROJECT SUMMARY-->
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<?php if($isCreating || $isAdminUpdating){ //for creating or updating applications ?>
										<label for="projs">Project Summary (up to <?php echo $maxProjectSummary; ?> characters):</label>
										<textarea class="form-control" id="projs" name="projs" placeholder="Enter Project Summary" rows=10 required /><?php if($isAdminUpdating){echo $fR->projectSummary;} ?></textarea>
									<?php }else{ //for viewing applications ?>
										<label for="projs">Project Summary:</label>
										<textarea class="form-control" id="projs" name="projs" placeholder="Enter Project Summary" rows=10 required disabled="true" /><?php echo $fR->projectSummary; ?></textarea>
									<?php } ?>
								</div>
							</div>
						</div>
						
						
						
						<!--UPLOAD INFO-->
						<div class="row">
							<h2 class="title">Attachments:</h2>
							<h3>Please Upload Documentation (Travel Expense Vouchers, Receipts, Travel Authorization Forms, Etc.)</h3>
						</div>


						
						<!--UPLOADS-->
						<div class="row">
							<?php if($isCreating || $isReviewing || $isAdminUpdating){ //for uploading documents; both admins and applicants ?>
								<label for="fD">UPLOAD DOCUMENTS:</label><input type="file" name="followD[]" id="followD" accept=".txt, .rtf, .doc, .docx, 
									.xls, .xlsx, .ppt, .pptx, .pdf, .jpg, .png, .bmp, .tif" multiple />
								<?php } //for viewing uploaded documents; ANYONE can ?>
								<p class="title">UPLOADED DOCUMENTS: <?php if(count($P > 0)) { echo "<table>"; foreach($P as $ip) { echo "<tr><td>" . $ip . "</td></tr>"; } echo "</table>"; } else echo "none"; ?> </p>
							</div>
						
						
						
						<br><br>
						<?php if($isFollowUpReportApprover || $isAdmin) { ?>
						<div class="row">
						<!--EMAIL EDIT-->
							<div class="col-md-12">
								<div class="form-group">
									<label for="finalE">EMAIL TO BE SENT:</label>
									<textarea class="form-control" id="finalE" name="finalE" placeholder="Enter email body, with greetings." rows=20 /></textarea>
								</div>
							</div>
						</div>
						<?php } ?>
						<br><br>
						
						<div class="row">
							<div class="col-md-2"></div>
						<!--SUBMIT BUTTONS-->
							<div class="col-md-6">
							<?php if($isCreating){ //show submit application button if creating ?>
									<input type="submit" onclick="return confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT FOLLOW-UP REPORT" />
								<?php }else if($isAdminUpdating){ //show submit edits button and cancel button if editing?>
									<input type="submit" onclick="return confirm ('By submitting, I affirm that this work meets university requirements for compliance with all research protocols.')" class="btn btn-success" id="submitApp" name="submitApp" value="SUBMIT EDITS" />
								<?php }else if($isAdmin || $isFollowUpReportApprover){ //show update, approve, and deny buttons if admin or approver ?>
									<input type="submit" onclick="return confirm ('By confirming, your email will be sent to the applicant! Are you sure you want to approve this follow-up report?')" class="btn btn-success" id="approveApp" name="approveA" value="APPROVE FOLLOW-UP REPORT" />
									<input type="submit" onclick="return confirm ('By confirming, your email will be sent to the applicant! Are you sure you want to deny this follow-up report?')" class="btn btn-danger" id="denyApp" name="denyA" value="DENY FOLLOW-UP REPORT" />
								<?php }else if($isReviewing){ ?>
									<input type="submit" class="btn btn-primary" id="uploadDocsF" name="uploadDocsF" value="UPLOAD MORE DOCUMENTS" />
								<?php } ?>
							</div>
							<div class="col-md-2">
								<a href="index.php" <?php if($isCreating || $isAdminUpdating || $isAdmin || $isFollowUpReportApprover){ ?> onclick="return confirm ('Are you sure you want to leave this page? Any unsaved data will be lost.')" <?php } ?> class="btn btn-info">LEAVE PAGE</a>
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
			<h1>You do not have permission to access a follow-up report!</h1>
		<?php
		}
		?>
	</div>
	</body>
</html>