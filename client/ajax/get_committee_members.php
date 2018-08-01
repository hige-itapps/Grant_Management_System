<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();

/************* FOR ADMIN TO GET COMMITTEE LIST ***************/

$getReturn = null; //will be the committee members list if successful

if(isAdministrator($conn, $CASbroncoNetID))
{
	try
	{
		$getReturn = getCommittee($conn);
	}
	catch(Exception $e)
	{
		$getReturn["error"] = "Unable to retrieve committee members: " . $e->getMessage();
	}
}
else
{
	$getReturn["error"] = "Permission denied";
}


$conn = null; //close connection

echo json_encode($getReturn); //return data

?>