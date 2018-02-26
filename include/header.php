<?php
/* header to be included in every page on the site */

/*If POST data contains a logout request, logout the user by destroying session!*/
if(isset($_POST["logoutUser"]))
{
	// remove all session variables
	session_unset(); 
	// destroy the session 
	session_destroy(); 
	// reload page to enforce login system to start
	header("Refresh:0");
	exit(); //make sure the remaining part of the page doesn't load!
}
?>

<div class="page-header container-fluid">
	
	<div class="row">
		<div class="col-md-4">
			<img src="images/WMU.png" alt="WMU Logo" class="logo" />
			<a href="/" id="HomeLink">Haenicke Institute for Global Education</a>
		</div>
		<div class="col-md-4"> 
		</div>
		<div class="col-md-4">
			<form id="logoutForm" method="post" action="#">
				<input type="hidden" name="logoutUser" value="logout" /> 
				<input type="submit" class="btn btn-info" id="logoutSub" name="logoutSub" value="Logout" />
			</form>
		</div>
	</div>
	
</div>