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

/************* FOR AN APPLICANT OR ADMINISTRATOR TO UPLOAD FILES AT ANY TIME - REQUIRES USER TO HAVE PERMISSION TO DO SO ***************/

$uploadReturn = null; //will be the application data if successful. If unsuccessful, uploadReturn["error"] should be set

if(isset($_POST["appID"]) && isset($_FILES))
{
	$appID = $_POST["appID"];
	$files = $_FILES;

	/*Verify that user is allowed to upload files*/
	if(doesUserOwnApplication($conn, $CASbroncoNetID, $appID) || isAdministrator($conn, $CASbroncoNetID))
	{
		$uploadReturn = uploadDocs($appID, $files);
	}
	else
	{
		$uploadReturn["error"] = "Permission denied";
	}
}
else
{
	$uploadReturn["error"] = "AppID and/or uploadFiles is not set";
}

$conn = null; //close connection

echo json_encode($uploadReturn); //return data to the application page!

?>