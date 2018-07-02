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

/************* FOR RETRIEVING AN APPLICATION AT ANY TIME - REQUIRES USER TO HAVE PERMISSION TO DO SO ***************/

$approvalReturn = null; //will be the application data if successful. If unsuccessful, approvalReturn["error"] should be set

if(isset($_POST["appID"]) && isset($_POST["status"]))
{
	$appID = $_POST["appID"];
	$status = $_POST["status"];

	/*Verify that user is allowed to approve an application*/
	if(isApplicationApprover($conn, $CASbroncoNetID) || isAdministrator($conn, $CASbroncoNetID))
	{
		try
		{
			if($status === 'approve') 
			{ 
				if(isset($_POST["amount"])) 
				{
					if($_POST["amount"] > 0){ $approvalReturn = approveApplication($conn, $appID, $_POST["amount"]); }
					else {$approvalReturn["error"] = "Amount awarded must be greater than $0";}
				}
				else {$approvalReturn["error"] = "No amount specified";}
			}
			else if($status === 'hold') { $approvalReturn = holdApplication($conn, $appID); }
			else if($status === 'deny') { $approvalReturn = denyApplication($conn, $appID); }
			else { $approvalReturn["error"] = "Invalid status given"; }
		}
		catch(Exception $e)
		{
			$approvalReturn["error"] = "Unable to approve application: " . $e->getMessage();
		}
	}
	else
	{
		$approvalReturn["error"] = "Permission denied";
	}
}
else
{
	$approvalReturn["error"] = "AppID and/or status is not set";
}

$conn = null; //close connection

echo json_encode($approvalReturn); //return data to the application page!

?>