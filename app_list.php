<?php
	ob_start();
	
	set_include_path('/home/egf897jck0fu/public_html/');
	/*get database connection*/
	include "functions/database.php";
	$conn = connection();
	
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
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
	<body>
	
		<!--HEADER-->
		<?php
			include 'include/header.php';
			
			if(isUserAllowedToSeeApplications($conn, $_SESSION['broncoNetID']))
			{
				$apps = getPendingApplications($conn, "");//get all pending applications
		?>
		<!--HEADER-->
		<div class="container-fluid">
			<div class="row">
				<center><h2 class="title">Pending Applications</h2></center>
			</div>
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<table class="table">
						<thead>
							<tr>
								<th>Name</th>
								<th>Date Submitted</th>
							</tr>
						</thead>
						<tbody>
							<?php
								if(count($apps) == 0)
									echo "<tr><td align='center' colspan='3'><h3 class='title'>NO PENDING APPLICATIONS.</h3></td></tr>";
								else
									for($i = 0; $i < count($apps); $i++) {
										echo "<tr>";
										echo "<td><a href=application_confirmation.php?id=" . $apps[$i]->id . ">" . $apps[$i]->name . "</a></td>";
										echo "<td>" . $apps[$i]->dateS . "</td>";
										echo "</tr>";
									}
							?>
						</tbody>
					</table>
				</div>
				<div class="col-md-3"></div>
			</div>
		</div>
		<?php
			}
			else{
			?>
				<h1>You are not allowed to view applications!</h1>
			<?php
			}
		?>
	</body>
</html>
<?php
	$conn = null; //close connection
?>