<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../functions/verification.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/../functions/documents.php");

	/*For sending custom emails*/
	include_once(dirname(__FILE__) . "/../functions/customEmail.php");
?>

<?php

/************* FOR RETRIEVING AN APPLICATION AT ANY TIME - REQUIRES USER TO HAVE PERMISSION TO DO SO ***************/

$getReturn = null; //will be the application data if successful

if(isset($_POST["appID"]))
{
	$appID = $_POST["appID"];

	/*Verify that user is allowed to retrieve an application*/
	if(isUserAllowedToSeeApplications($conn, $CASbroncoNetID) || doesUserOwnApplication($conn, $CASbroncoNetID, $appID) || isUserDepartmentChair($conn, $CASemail, $appID))
	{
		try
		{
			$getReturn = getApplication($conn, $appID); //get application Data
			$getReturn->appStatus = $getReturn->getStatus(); //save the application's current status
		}
		catch(Exception $e)
		{
			$getReturn["error"] = "Unable to retrieve application: " . $e->getMessage();
		}
	}
	else
	{
		$getReturn["error"] = "Permission denied";
	}
}
else
{
	$getReturn["error"] = "AppID is not set!";
}


$conn = null; //close connection

echo json_encode($getReturn); //return data to the application page!

?>