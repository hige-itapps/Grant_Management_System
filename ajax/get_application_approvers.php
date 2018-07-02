<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../functions/verification.php");

/************* FOR ADMIN TO GET APPLICATION APPROVERS LIST ***************/

$getReturn = null; //will be the application approvers list if successful

if(isAdministrator($conn, $CASbroncoNetID))
{
	try
	{
		$getReturn = getApplicationApprovers($conn);
	}
	catch(Exception $e)
	{
		$getReturn["error"] = "Unable to retrieve application approvers: " . $e->getMessage();
	}
}
else
{
	$getReturn["error"] = "Permission denied";
}


$conn = null; //close connection

echo json_encode($getReturn); //return data

?>