<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/../functions/documents.php");

/************* FOR RETRIEVING A FINAL REPORT AT ANY TIME - REQUIRES USER TO HAVE PERMISSION TO DO SO ***************/

$getReturn = null; //will be the report data if successful

if(isset($_POST["appID"]))
{
	$appID = $_POST["appID"];

	/*Verify that user is allowed to retrieve a report*/
	if(isUserAllowedToSeeApplications($conn, $CASbroncoNetID) || doesUserOwnApplication($conn, $CASbroncoNetID, $appID) || isUserDepartmentChair($conn, $CASemail, $appID))
	{
		try
		{
			$getReturn = getFinalReport($conn, $appID); //get report Data
			$getReturn->reportFiles = getFileNames($appID); //get the list of file names associated with this application
			$getReturn->reportEmails = getEmails($conn, $appID); //get associated emails
		}
		catch(Exception $e)
		{
			$getReturn["error"] = "Unable to retrieve final report: " . $e->getMessage();
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