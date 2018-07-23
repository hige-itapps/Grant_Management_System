<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();

/************* FOR ADMIN TO REMOVE FINAL REPORT APPROVERS ***************/

$removeReturn = null; //will be true if successful, false if unsuccessful, or otherwise ["error"] will be set

if(isset($_POST["broncoNetID"]))
{
	$broncoNetID = $_POST["broncoNetID"];

    //must have permission to do this
	if(isAdministrator($conn, $CASbroncoNetID))
	{
        try
        {
            $removeReturn = removeFinalReportApprover($conn, $broncoNetID);
        }
        catch(Exception $e)
        {
            $removeReturn["error"] = "Unable to remove final report approver: " . $e->getMessage();
        }
	}
	else
	{
		$removeReturn["error"] = "Permission denied";
	}
}
else
{
	$removeReturn["error"] = "broncoNetID is not set";
}

$conn = null; //close connection

echo json_encode($removeReturn); //return data!

?>