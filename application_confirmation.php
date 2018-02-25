<?php
	/*database functions*/
	include "functions/database.php";
	$conn = connection();
	$idA = $_GET['id'];
	$app = getApplications($conn, $idA);
	
	/*include documents functions*/
	include "functions/documents.php";
	//print_r($app);
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
			<form class="form-horizontal" id="approvalF" name="approvalF" method="POST" action="application_confirmation.php">
				<div ng-controller="budget">
					<!--APPLICANT INFO-->
					<div class="row">
						<center>
						<h2 class="title">Applicant Information<? if(isSigned($conn, $idA) == 0) echo "(NOT YET SIGNED BY CHAIR)"; ?>:</h2>
						</center>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
					<!--NAME-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputName"><p class="title" style="font-size: 18px !important;">Name:</p></label>
								<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" disabled="true" value="<?php echo $app[0]->name; ?>" required />
							</div>
						</div>
					<!--EMAIL-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputEmail"><p class="title" style="font-size: 18px !important;">Email Address:</p></label>
								<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email" disabled="true" value="<?php echo $app[0]->email; ?>" required />
							</div>
						</div>
					<!--DEPARTMENT-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputDept"><p class="title" style="font-size: 18px !important;">Department:</p></label>
								<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Department" disabled="true" value="<?php echo $app[0]->dept; ?>" required />
							</div>
						</div>
					<!--DEPT MAIL STOP-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputDeptM"><p class="title" style="font-size: 18px !important;">Department Mail Stop:</p></label>
								<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Department Mail Stop" disabled="true" value="<?php echo $app[0]->deptM; ?>" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
					<!--TRAVEL DATES-->
						<div class="col-md-1">
							<div class="form-group">
								<label for="inputFrom"><p class="title" style="font-size: 18px !important;">Travel From:</p></label>
								<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" disabled="true" value="<?php echo $app[0]->tStart; ?>" required />
							</div>
						</div>
						<div class="col-md-1">
							<div class="form-group">
								<label for="inputFrom"><p class="title" style="font-size: 18px !important;">To:</p></label>
								<input type="date" class="form-control" id="inputTTo" name="inputTTo" disabled="true" value="<?php echo $app[0]->tEnd; ?>" onchange="TDate()" required />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<br><br>
					<!--RESEARCH INFO-->
					<div class="row">
						<center>
						<p class="title">Research Information:</p>
						</center>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
					<!--TITLE-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputRName"><p class="title" style="font-size: 18px !important;">Title of Research:</p></label>
								<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title" disabled="true" value="<?php echo $app[0]->rTitle; ?>" required />
							</div>
						</div>
					<!--ACTIVITY DATES-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputFrom"><p class="title" style="font-size: 18px !important;">Activity Date From:</p></label>
								<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" onchange="ADateF()" disabled="true" value="<?php echo $app[0]->aStart; ?>"  required />
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputTo"><p class="title" style="font-size: 18px !important;">Activity Date To:</p></label>
								<input type="date" class="form-control" id="inputATo" name="inputATo" onchange="ADateT()" disabled="true" value="<?php echo $app[0]->aEnd; ?>"  required />
							</div>
						</div>
					<!--DESTINATION-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputDest"><p class="title" style="font-size: 18px !important;">Destination:</p></label>
								<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" disabled="true" value="<?php echo $app[0]->dest; ?>" required />
							</div>
						</div>
					<!--AMOUNT REQ-->
						<div class="col-md-2">
							<div class="form-group">
								<label for="inputAR"><p class="title" style="font-size: 18px !important;">Amount Requested:</p></label>
								<input type="text" class="form-control" id="inputAR" name="inputAR" placeholder="Amount Requested" disabled="true" value="<?php echo $app[0]->aReq; ?>" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' required />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
					<!--PURPOSE-->
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="form-group">
								<label for="inputAR<p class="title" style="font-size: 18px !important;">Purpose of Travel:</p>
							</div>
						</div>
						
					</div><div class="row"><div class="col-md-1"></div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr1" type="checkbox" disabled="true" <?php if($app[0]->pr1 == 1) echo "checked"; ?> value="Research">Research</label></p>
							</div>
						</div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr2" type="checkbox" disabled="true" <?php if($app[0]->pr2 == 1) echo "checked"; ?> value="Conference">Conference</label></p>
							</div>
						</div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr3" type="checkbox" disabled="true" <?php if($app[0]->pr3 == 1) echo "checked"; ?> value="Creative Activity">Creative Activity</label></p>
							</div>
						</div>
						<div class="col-md-1">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pr4dummy" id="pr4dummy" type="checkbox" disabled="true"<?php if($app[0]->pr4 != "") echo "checked"; ?> value="">Other, explain.</label></p>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<input type="text" class="form-control" id="pr4" name="pr4" disabled="true" value="<?php echo $app[0]->pr4; ?>" placeholder="Other, explain..." />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row"><div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="form-group">
								<input type="text" class="form-control" id="eS" name="eS" placeholder="Are you receiving other funding? Who is providing the funds? How much?" disabled="true" value="<?php echo $app[0]->oF; ?>" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' />
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
					<div class="col-md-1"></div>
						<div class="col-md-10">
						<center>
							<p class="title" style="font-size: 14px !important;">Please ensure to include supporting documentation: Conference letter of acceptance; invitation letter for research or creative activity, etc. in 1 PDF, using the upload button below.</p> 
						</center>
						</div>
					<div class="col-md-1"></div>
					</div>
					<div class="row">
					<!--PROPOSAL SUMMARY-->
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="form-group">
								<textarea class="form-control" id="props" name="props" placeholder="Proposal summary, MAX 150 words" disabled="true" rows=10 required /><?php echo $app[0]->pS; ?></textarea>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row"><div class="col-md-1"></div>
						<p class="title" style="font-size: 18px !important;">Please indicate which of the prioritized goals of the IEFDF this proposal fulfills:</p>
					<div class="col-md-1"></div></div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg1" type="checkbox" disabled="true" <?php if($app[0]->fg1 == 1) echo "checked"; ?> value="">
								Support for international collaborative research and creative activities, or for international research, including archival and field work.</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg2" type="checkbox" disabled="true" <?php if($app[0]->fg2 == 1) echo "checked"; ?> value="">
								Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg3" type="checkbox" disabled="true" <?php if($app[0]->fg3 == 1) echo "checked"; ?> value="">
								Support for attendance at international conferences, seminars or workshops.</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-10">
							<div class="checkbox">
								<p class="title" style="font-size: 14px !important;"><label><input name="pg4" type="checkbox" disabled="true" <?php if($app[0]->fg4 == 1) echo "checked"; ?> value="">
								Support for scholarly international travel in order to enrich international knowledge, which will directly
								contribute to the internationalization of the WMU curricula.</label></p>
							</div>
						</div>
						<div class="col-md-1"></div>
					</div>
					<div class="row"><div class="col-md-1"></div>
						<p class="title" style="font-size: 18px !important;">Budget:</p>
					<div class="col-md-1"></div></div>
					<div class="row"><div class="col-md-3"></div>
						<div class="col-md-2">
							<p class="title" style="font-size: 16px !important;">Expense:</p>
						</div>
						<div class="col-md-2">
							<p class="title" style="font-size: 16px !important;">Comments:</p>
						</div>
						<div class="col-md-2">
							<p class="title" style="font-size: 16px !important;">Amount:</p>
						</div>
						<div class="col-md-3"></div>
					</div>
					<?php
						for($i = 0; $i < count($app[0]->budget); $i++) {
							echo '<div class="row">
									<div class="col-md-3"></div>
									<div class="col-md-2">
										<div class="form-group">
											<input type="text" disabled="true" class="form-control" value="' . $app[0]->budget[$i][2] . '" />
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<input type="text" disabled="true" class="form-control" value="' . $app[0]->budget[$i][4] . '" />
										</div>
									</div>
									<div class="col-md-2">
										<div class="form-group">
											<input type="text" disabled="true" class="form-control" value="' . $app[0]->budget[$i][3] . '" />
										</div>
									</div>
									<div class="col-md-3"></div>
								</div>';
						}
					?>
					<div class="row"><div class="col-md-5"></div>
						<div class="col-md-1">
							<p class="title" style="font-size: 16px !important;">Total:</p>
						</div>
						<div class="col-md-1">
							<p class="title" style="font-size: 16px !important;"><?php $sum = 0; for($i = 0; $i < count($app[0]->budget); $i++) $sum += $app[0]->budget[$i][3]; echo $sum; ?></p>
						</div>
						<div class="col-md-5"></div>
					</div>
					<br><br>
					<!--DOCS-->
					<?php
						$docs = listDocs($_GET["id"]);
						$P = "None";
						$S = "None";
						for($i = 0; $i < count($docs); $i++)
						{
							if(substr($docs[$i], 0, 1) == 'P')
								$P = "<a href='functions/documents.php?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>";
							if(substr($docs[$i], 0, 1) == 'S')
								$S = "<a href='functions/documents.php?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>";
						}
					?>
					<div class="row">
						<div class="col-md-1"></div>
						<div class="col-md-5">
							<p class="title">Proposal: <?php echo $P; ?> </p>
						</div>
						<div class="col-md-5">
							<p class="title">Supporting Docs:  <?php echo $S; ?> </p>
						</div>
						<div class="col-md-1"></div>
					</div>
					<br><br>
					<div class="row">
						<div class="col-md-3"></div>
						<center>
						<div class="col-md-3">
							<input type="hidden" name="appID" value="<?php echo $app[0]->id; ?>" />
							<input type="submit" class="styled-button-3" <? if(isSigned($conn, $idA) == 0) echo 'disabled="true" style="background-color: gray; border-color: gray; margin-top: 10px;" '; ?> id="approveA" name="approveA" style="background-color: green !important; border-color: green !important; margin-top: 10px;" value="APPROVE APPLICATION" />
						</div>
						<div class="col-md-3">
							<input type="submit" class="styled-button-3" id="denyA" name="denyA" style="background-color: red !important; border-color: red !important; margin-top: 10px;" value="DENY APPLICATION" />
						</div>
						</center>
						<div class="col-md-3"></div>
					</div>
				</div>
				<?php
			
					//DO NOT TOUCH.
					if(isset($_POST["approveA"]))
						approveApplication($conn, $_POST["appID"]);
					if(isset($_POST["denyA"]))
						denyApplication($conn, $_POST["appID"]);
				?>
			</form>
			<br><br>
		</div>
		<!--BODY-->
	</body>
</html>