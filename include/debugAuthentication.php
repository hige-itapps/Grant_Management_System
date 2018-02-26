<?php
	session_start(); //make sure session is running
	
	/*Check if POST data for user variables are being set*/
	if(isset($_POST["inputBroncoNetID"]) && isset($_POST["inputPosition"]) && isset($_POST["inputEmail"])) //if all variables are being set
	{
		//echo "Got user vars!";
		$_SESSION['broncoNetID'] = $_POST["inputBroncoNetID"];
		$_SESSION['position'] = $_POST["inputPosition"];
		$_SESSION['email'] = $_POST["inputEmail"];
		//echo "User saved with session vars ".$_SESSION['broncoNetID'].", ".$_SESSION['position'].", and ".$_SESSION['email'].".";
	}

	/*Force user to login with session if any important session variables aren't set*/
	if (!isset($_SESSION['broncoNetID']) || !isset($_SESSION['position']) || !isset($_SESSION['email']))
	{
		?>
		<!DOCTYPE html>
			<html lang="en">
				<head>
					<title>login</title>
				</head>
				<body>
					<h1>Please login to use our site!</h1>
					<div class="container-fluid">
						<form enctype="multipart/form-data" class="form-horizontal" id="loginForm" name="loginForm" method="POST" action="#">
							<div class="row">
							<!--BroncoNetID-->
								<div class="form-group">
									<label for="inputBroncoNetID">BroncoNetID:</label>
									<input type="text" class="form-control" id="inputBroncoNetID" name="inputBroncoNetID" placeholder="Enter BroncoNetID" required />
								</div>
							</div>
							<div class="row">
							<!--Email-->
								<div class="form-group">
									<label for="inputEmail">Email:</label>
									<input type="text" class="form-control" id="inputEmail" name="inputEmail" placeholder="Enter Email Address" required />
								</div>
							</div>
							<div class="row">
								<label for="positions">Position:</label>
							</div>
							<div class="row">
							<!--Position-->
								<div class="form-group">
									<label for="positionFaculty">Faculty</label>
									<input type="radio" id="positionFaculty" name="inputPosition" value="faculty" required>
									<label for="positionStaff">Staff</label>
									<input type="radio" id="positionStaff" name="inputPosition" value="staff">
									<label for="positionStudent">Student</label>
									<input type="radio" id="positionStudent" name="inputPosition" value="student">
								</div>
							</div>
							<div class="row">
								<div class="col-md-5"></div>
								<div class="col-md-2">
									<input type="submit" class="styled-button-3" id="loginButton" name="loginButton" value="LOGIN" />
								</div>
								<div class="col-md-5"></div>
							</div>
						</form>
					</div>
				</body>
			</html>
		<?php
		exit();//don't load rest of page
	}
?>