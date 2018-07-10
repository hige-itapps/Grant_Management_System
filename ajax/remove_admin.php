<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();

/************* FOR ADMIN TO REMOVE OTHER ADMINS ***************/

$removeReturn = null; //will be true if successful, false if unsuccessful, or otherwise ["error"] will be set

if(isset($_POST["broncoNetID"]))
{
	$broncoNetID = $_POST["broncoNetID"];

    //must have permission to do this
	if(isAdministrator($conn, $CASbroncoNetID))
	{
        if($CASbroncoNetID !== $broncoNetID) //not trying to remove self
        {
            try
            {
                $removeReturn = removeAdmin($conn, $broncoNetID);
            }
            catch(Exception $e)
            {
                $removeReturn["error"] = "Unable to remove admin: " . $e->getMessage();
            }
        }
        else
        {
            $removeReturn["error"] = "Admins cannot remove themselves";
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