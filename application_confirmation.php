<?php
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*include documents functions*/
	include "functions/documents.php";
	
	/*database functions*/
	include "functions/database.php";
	$conn = connection();
	
	if(isApplicationApprover($conn, $_SESSION['broncoNetID']))
	{
		$app = getApplication($conn, $_GET['id']);
		
		/*User is trying to download a document*/
		if(isset($_GET["doc"]))
		{
			downloadDocs($_GET["id"], $_GET["doc"]);
		}
		
		/*User wants to approve this application*/
		if(isset($_POST["approveA"]))
		{
			approveApplication($conn, $_POST["appID"]);
			header('Location: app_list.php'); //redirect to app_list
		}
		
		/*User wants to deny this application*/
		if(isset($_POST["denyA"]))
		{
			denyApplication($conn, $_POST["appID"]);
			header('Location: app_list.php'); //redirect to app_list
		}
	}
		
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
			
			if(isApplicationApprover($conn, $_SESSION['broncoNetID']))
			{
		?>
		<!--HEADER-->
		
		<!--BODY-->
		<div class="container-fluid">
			<form class="form-horizontal" id="approvalF" name="approvalF" method="POST" action="#">
				<div ng-controller="budget">
					<!--APPLICANT INFO-->
					<div class="row">
						<h2 class="title">Applicant Information<? if(isSigned($conn, $idA) == 0) echo "(NOT YET SIGNED BY CHAIR)"; ?>:</h2>
					</div>
					<div class="row">
					<!--NAME-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputName">Name:</label>
								<input type="text" class="form-control" id="inputName" name="inputName" placeholder="Enter Name" disabled="true" value="<?php echo $app->name; ?>" required />
							</div>
						</div>
					<!--EMAIL-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputEmail">Email Address:</label>
								<input type="email" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" disabled="true" value="<?php echo $app->email; ?>" required />
							</div>
						</div>
					<!--DEPARTMENT-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputDept">Department:</label>
								<input type="text" class="form-control" id="inputDept" name="inputDept" placeholder="Enter Department" disabled="true" value="<?php echo $app->dept; ?>" required />
							</div>
						</div>
					</div>
					<div class="row">
					<!--DEPT MAIL STOP-->
						<div class="col-md-6">
							<div class="form-group">
								<label for="inputDeptM">Department Mail Stop:</label>
								<input type="text" class="form-control" id="inputDeptM" name="inputDeptM" placeholder="Enter Department Mail Stop" maxlength="4" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' disabled="true" value="<?php echo $app->deptM; ?>" required />
							</div>
						</div>
					<!--DEPT CHAIR EMAIL-->
						<div class="col-md-6">
							<div class="form-group">
								<label for="inputDeptCE">Department Chair's Email Address:</label>
								<input type="email" class="form-control" id="inputDeptCE" name="inputDeptCE" placeholder="Enter Department Chair's Email Address" disabled="true" value="<?php echo $app->deptCE; ?>" required />
							</div>
						</div>
					</div>
					<!--RESEARCH INFO-->
					<div class="row">
						<h2 class="title">Research Information:</h2>
					</div>
					<div class="row">
					<!--TRAVEL DATES-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTFrom">Travel Date From:</label>
								<input type="date" class="form-control" id="inputTFrom" name="inputTFrom" disabled="true" value="<?php echo $app->tStart; ?>" required />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputTTo">Travel Date To:</label>
								<input type="date" class="form-control" id="inputTTo" name="inputTTo" onchange="TDate()" disabled="true" value="<?php echo $app->tEnd; ?>" required />
							</div>
						</div>
					<!--ACTIVITY DATES-->
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputAFrom">Activity Date From:</label>
								<input type="date" class="form-control" id="inputAFrom" name="inputAFrom" onchange="ADateF()" disabled="true" value="<?php echo $app->aStart; ?>" required />
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label for="inputATo">Activity Date To:</label>
								<input type="date" class="form-control" id="inputATo" name="inputATo" onchange="ADateT()" disabled="true" value="<?php echo $app->aEnd; ?>" required />
							</div>
						</div>
					</div>
					<div class="row">
					<!--TITLE-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputRName">Title of Research:</label>
								<input type="text" class="form-control" id="inputRName" name="inputRName" placeholder="Enter Title of Research" disabled="true" value="<?php echo $app->rTitle; ?>" required />
							</div>
						</div>
					<!--DESTINATION-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputDest">Destination:</label>
								<input type="text" class="form-control" id="inputDest" name="inputDest" placeholder="Enter Destination" disabled="true" value="<?php echo $app->dest; ?>" required />
							</div>
						</div>
					<!--AMOUNT REQ-->
						<div class="col-md-4">
							<div class="form-group">
								<label for="inputAR">Amount Requested($):</label>
								<input type="text" class="form-control" id="inputAR" name="inputAR" placeholder="Enter Amount Requested($)" onkeypress='return (event.which >= 48 && event.which <= 57) 
								|| event.which == 8 || event.which == 46' disabled="true" value="<?php echo $app->aReq; ?>" required />
							</div>
						</div>
					</div>
					<!--PURPOSE-->
					<div class="row">
						<label for="purposes">Purpose of Travel:</label>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="purpose1" type="checkbox" value="purpose1" disabled="true" <?php if($app->pr1 == 1) echo "checked"; ?>>Research</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="purpose2" type="checkbox" value="purpose2" disabled="true" <?php if($app->pr2 == 1) echo "checked"; ?>>Conference</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="purpose3" type="checkbox" value="purpose3" disabled="true" <?php if($app->pr3 == 1) echo "checked"; ?>>Creative Activity</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2">
							<div class="checkbox">
								<label><input name="purposeOtherDummy" id="purposeOtherDummy" type="checkbox" value="purposeOtherDummy" disabled="true" <?php if($app->pr4 != "") echo "checked"; ?>>Other, explain.</label>
							</div>
						</div>
						<div class="col-md-10">
							<div class="form-group">
								<label for="purposeOtherText">Explain other purpose:</label>
								<input type="text" class="form-control" id="purposeOther" name="purposeOther" disabled="true" placeholder="Enter Explanation" disabled="true" value="<?php echo $app->pr4; ?>"/>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="eS">Are you receiving other funding? Who is providing the funds? How much?:</label>
								<input type="text" class="form-control" id="eS" name="eS" placeholder="Explain here" disabled="true" value="<?php echo $app->oF; ?>"/>
							</div>
						</div>
					</div>
					<div class="row">
					<!--PROPOSAL SUMMARY-->
						<div class="col-md-12">
							<div class="form-group">
								<label for="props">Proposal Summary:</label>
								<textarea class="form-control" id="props" name="props" placeholder="Enter Proposal Summary" rows=10 disabled="true" required /><?php echo $app->pS; ?></textarea>
							</div>
						</div>
					</div>
					<div class="row">
						<label for="goals">Please indicate which of the prioritized goals of the IEFDF this proposal fulfills:</label>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal1" type="checkbox" value="goal1" disabled="true" <?php if($app->fg1 == 1) echo "checked"; ?>>
								Support for international collaborative research and creative activities, or for international research, including archival and field work.</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal2" type="checkbox" value="goal2" disabled="true" <?php if($app->fg2 == 1) echo "checked"; ?>>
								Support for presentation at international conferences, seminars or workshops (presentation of papers will have priority over posters)</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal3" type="checkbox" value="goal3" disabled="true" <?php if($app->fg3 == 1) echo "checked"; ?>>
								Support for attendance at international conferences, seminars or workshops.</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="checkbox">
								<label><input name="goal4" type="checkbox" value="goal4" disabled="true" <?php if($app->fg4 == 1) echo "checked"; ?>>
								Support for scholarly international travel in order to enrich international knowledge, which will directly
								contribute to the internationalization of the WMU curricula.</label>
							</div>
						</div>
					</div>
					<div class="row">
						<h2>Budget:(please separate room and board calculating per diem)</h2>
					</div>
					<div class="row">
						<div class="col-md-4">
							<h3>Expense:</h3>
						</div>
						<div class="col-md-4">
							<h3>Comments:</h3>
						</div>
						<div class="col-md-4">
							<h3>Amount($):</h3>
						</div>
					</div>
					<?php
						for($i = 0; $i < count($app->budget); $i++) {
							echo '<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<input type="text" disabled="true" class="form-control" value="' . $app->budget[$i][2] . '" />
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<input type="text" disabled="true" class="form-control" value="' . $app->budget[$i][4] . '" />
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<input type="text" disabled="true" class="form-control" value="' . $app->budget[$i][3] . '" />
										</div>
									</div>
								</div>';
						}
					?>
					<div class="row">
						<div class="col-md-5"></div>
						<div class="col-md-2">
							<h3>Total: $<?php $sum = 0; for($i = 0; $i < count($app->budget); $i++) $sum += $app->budget[$i][3]; echo $sum; ?></h3>
						</div>
						<div class="col-md-5"></div>
					</div>
					<!--UPLOAD DOCS FORM-->
					<?php
						$docs = listDocs($_GET["id"]);
						$P = "None";
						$S = "None";
						for($i = 0; $i < count($docs); $i++)
						{
							if(substr($docs[$i], 0, 1) == 'P')
								$P = "<a href='?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>";
							if(substr($docs[$i], 0, 1) == 'S')
								$S = "<a href='?id=" . $_GET["id"] . "&doc=" . $docs[$i] . "' target='_blank'>" . $docs[$i] . "</a>";
						}
					?>
					<div class="row">
							<h2>Uploads</h2> 
					</div>
					<div class="row">
						<div class="col-md-6">
								<p class="title">PROPOSAL NARRATIVE: <?php echo $P; ?> </p>
						</div>
						<div class="col-md-6">
							<p class="title">SUPPORTING DOCUMENTS: <?php echo $S; ?> </p>
						</div>
					</div>
					<br><br>
					<div class="row">
						<div class="col-md-3"></div>
						<center>
						<div class="col-md-3">
							<input type="hidden" name="appID" value="<?php echo $app->id; ?>" />
							<input type="submit" class="styled-button-3" <? if(isSigned($conn, $idA) == 0) echo 'disabled="true" style="background-color: gray; border-color: gray; margin-top: 10px;" '; ?> id="approveA" name="approveA" style="background-color: green !important; border-color: green !important; margin-top: 10px;" value="APPROVE APPLICATION" />
						</div>
						<div class="col-md-3">
							<input type="submit" class="styled-button-3" id="denyA" name="denyA" style="background-color: red !important; border-color: red !important; margin-top: 10px;" value="DENY APPLICATION" />
						</div>
						</center>
						<div class="col-md-3"></div>
					</div>
				</div>
			</form>
		</div>
		<!--BODY-->
		<?php
			}
			else{
			?>
				<h1>You are not allowed to confirm applications!</h1>
			<?php
			}
			?>
	</body>
</html>
<?php
	$conn = null; //close connection
?>