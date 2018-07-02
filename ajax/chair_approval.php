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

/************* FOR A DEPARTMENT CHAIR CHAIR TO APPROVE AN APPLICATION AT ANY TIME - REQUIRES USER TO HAVE PERMISSION TO DO SO ***************/

$chairReturn = null; //will be the application data if successful. If unsuccessful, chairReturn["error"] should be set

if(isset($_POST["appID"]) && isset($_POST["deptChairApproval"]))
{
	$appID = $_POST["appID"];
	$deptChairApproval = $_POST["deptChairApproval"];

	/*Verify that user is allowed to approve an application*/
	if(isUserAllowedToSignApplication($conn, $CASemail, $appID))
	{
		try
		{
			$deptChairApproval = trim($deptChairApproval);
			if($deptChairApproval !== '') 
			{
				$chairReturn = signApplication($conn, $appID, $deptChairApproval);
			}
			else {$chairReturn["error"] = "No name specified, you must type your name to approve this application";}
		}
		catch(Exception $e)
		{
			$chairReturn["error"] = "Unable to approve application: " . $e->getMessage();
		}
	}
	else
	{
		$chairReturn["error"] = "Permission denied";
	}
}
else
{
	$chairReturn["error"] = "AppID and/or status is not set";
}

$conn = null; //close connection

echo json_encode($chairReturn); //return data to the application page!

?>