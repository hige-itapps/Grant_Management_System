<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../functions/verification.php");

/************* FOR ADMIN TO ADD A FOLLOW UP APPROVER***************/

$removeReturn = null; //will be true if successful, false if unsuccessful, or otherwise ["error"] will be set

if(isset($_POST["broncoNetID"]) && isset($_POST["name"]))
{
	$broncoNetID = $_POST["broncoNetID"];
	$name = $_POST["name"];

    //must have permission to do this
	if(isAdministrator($conn, $CASbroncoNetID))
	{
        try
        {
            $removeReturn = addFollowUpApprover($conn, $broncoNetID, $name);
        }
        catch(Exception $e)
        {
            $removeReturn["error"] = "Unable to add follow up approver: " . $e->getMessage();
        }
	}
	else
	{
		$removeReturn["error"] = "Permission denied";
	}
}
else
{
	$removeReturn["error"] = "broncoNetID and/or name is not set";
}

$conn = null; //close connection

echo json_encode($removeReturn); //return data!

?>