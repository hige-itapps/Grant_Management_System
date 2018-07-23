<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();

/************* FOR ADMIN TO GET FINAL REPORT APPROVERS LIST ***************/

$getReturn = null; //will be the final report approvers list if successful

if(isAdministrator($conn, $CASbroncoNetID))
{
	try
	{
		$getReturn = getFinalReportApprovers($conn);
	}
	catch(Exception $e)
	{
		$getReturn["error"] = "Unable to retrieve final report approvers: " . $e->getMessage();
	}
}
else
{
	$getReturn["error"] = "Permission denied";
}


$conn = null; //close connection

echo json_encode($getReturn); //return data

?>