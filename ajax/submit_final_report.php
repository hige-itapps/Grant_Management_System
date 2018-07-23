<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection();
	
	/*Document functions*/
    include_once(dirname(__FILE__) . "/../functions/documents.php");
    
/************* FOR ADDING OR UPDATING FINAL REPORT VIA SUMBISSION ***************/

$insertReturn = null; //will be an array with return code and status, or an array of errors
//$data = array();    // array to pass back data

$isAdmin = isAdministrator($conn, $CASbroncoNetID);

if(isset($_POST["appID"]))
{
    $appID = json_decode($_POST["appID"]);

    /*Verify that user is allowed to make an application*/
    if(isUserAllowedToCreateFinalReport($conn, $CASbroncoNetID, $appID) || $isAdmin)
    {

        try
        {
            $files = null; //get files to upload, if any
            if(isset($_FILES)){$files = $_FILES;}

            /*Get the dates & properly format*/
            $travelFrom = null; $travelTo = null; $activityFrom = null; $activityTo = null;
            if(isset($_POST["travelFrom"])){$travelFrom = date('Y-m-d h:i:s', $_POST["travelFrom"]);}
            if(isset($_POST["travelTo"])){$travelTo = date('Y-m-d h:i:s', $_POST["travelTo"]);}
            if(isset($_POST["activityFrom"])){$activityFrom = date('Y-m-d h:i:s', $_POST["activityFrom"]);}
            if(isset($_POST["activityTo"])){$activityTo = date('Y-m-d h:i:s', $_POST["activityTo"]);}

            /*get other data*/
            $amountAwardedSpent = null; $projectSummary = null;
            if(isset($_POST["amountAwardedSpent"])){$amountAwardedSpent = json_decode($_POST["amountAwardedSpent"]);}
            if(isset($_POST["projectSummary"])){$projectSummary = json_decode($_POST["projectSummary"]);}


            /*Insert data into database - receive a success message if successful, or else not*/
            /*parameters: DB connection, updating boolean, app ID (if exists), travel from, travel to, activity from, activity to, project summary, amount awarded spent*/
            if($isAdmin)
            {
                $insertReturn = insertFinalReport($conn, true, $appID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $amountAwardedSpent);
            }
            else
            {
                $insertReturn = insertFinalReport($conn, false, $appID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $amountAwardedSpent);
            }
            
            if(isset($insertReturn["success"]))//returned normally
            {
                if($insertReturn["success"] === true)//if it was successful
                {
                    $insertReturn["fileSuccess"] = null; //variable to tell whether file upload was successful. If not, this will be set to false, and ["fileError"] will hold a detailed error message

                    if($files != null) //user is uploading files as well
                    {
                        $uploadReturn = uploadDocs($appID, $files);

                        if($uploadReturn !== true)//if there was an error with the upload
                        {
                            $insertReturn["fileSuccess"] = false; 
                            $insertReturn["fileError"] = $$uploadReturn["error"];
                        }
                        else {$insertReturn["fileSuccess"] = true;} //it was successful
                    }
                    else {$insertReturn["fileSuccess"] = true;} //not uploading files
                }
                else {$insertReturn["fileSuccess"] = true;} //not uploading files
            }
        }
        catch(Exception $e)
        {
            $insertReturn = "Error adding final report: " . $e->getMessage();
        }
    }
    else
    {
        $insertReturn = "Permission denied";
    }
}
else
{
	$insertReturn = "Error: AppID is not set!";
}


$conn = null; //close connection

echo json_encode($insertReturn); //return data to the application page!

?>