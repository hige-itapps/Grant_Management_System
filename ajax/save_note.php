<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../functions/verification.php");

/************* FOR AN ADMIN, APPROVER, OR FOLLOW UP APPROVER TO SAVE APPLICATION NOTES ***************/

$saveReturn = null; //will be the application data if successful. If unsuccessful, saveReturn["error"] should be set

if(isset($_POST["appID"]) && isset($_POST["note"]))
{
	$appID = $_POST["appID"];
	$note = $_POST["note"];

	/*Verify that user is allowed to save this note*/
	if(isAdministrator($conn, $CASbroncoNetID) || isApplicationApprover($conn, $CASbroncoNetID) || isFollowUpReportApprover($conn, $CASbroncoNetID))
	{
		try
		{
			$note = trim($note);
			$saveReturn = saveStaffNotes($conn, $appID, $note);
		}
		catch(Exception $e)
		{
			$saveReturn["error"] = "Unable to save note: " . $e->getMessage();
		}
	}
	else
	{
		$saveReturn["error"] = "Permission denied";
	}
}
else
{
	$saveReturn["error"] = "AppID and/or note is not set";
}

$conn = null; //close connection

echo json_encode($saveReturn); //return data to the application page!

?>