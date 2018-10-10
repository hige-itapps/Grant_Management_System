<?php
	/*User validation*/
	include_once(dirname(__FILE__) . "/../../include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../../functions/database.php");
	$conn = connection();
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/../../functions/documents.php");

	/*For sending custom emails*/
	include_once(dirname(__FILE__) . "/../../functions/customEmail.php");


	/*for dept. chair email message*/
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

/************* FOR ADDING OR UPDATING APPLICATION VIA SUMBISSION ***************/

$insertReturn = null; //will be an array with return code and status, or an array of errors


if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/../../PHPMAILER/src/Exception.php';
if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/../../PHPMAILER/src/PHPMailer.php';
if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/../../PHPMAILER/src/SMTP.php';

// Please specify your Mail Server - Example: mail.example.com.
ini_set("SMTP","mail.example.com");

// Please specify an SMTP Number 25 and 8889 are valid SMTP Ports.
ini_set("smtp_port","25");

// Please specify the return address to use
ini_set('sendmail_from', 'info@hige.com');

$isAdmin = isAdministrator($conn, $CASbroncoNetID);

/*Verify that user is allowed to make an application. The specific cycle is checked in the insertApplication() function, so just pass in true for now.*/
if(isUserAllowedToCreateApplication($conn, $CASbroncoNetID, true) || $isAdmin)
{
    //echo "User is allowed to create an application!";
   // echo var_dump($_POST);
    
    try
    {
        $files = null; //get files to upload, if any
        if(isset($_FILES)){$files = $_FILES;}
        /*Get the budget items*/
        $budgetItems = null;
        if(isset($_POST["budgetItems"])){$budgetItems = json_decode($_POST["budgetItems"], true);}
        
        /*get the 4 purposes and 4 goals, set to 1 or 0*/
        $purpose1 = 0; $purpose2 = 0; $purpose3 = 0; $purpose4Other = ""; 
        $goal1 = 0; $goal2 = 0; $goal3 = 0; $goal4 = 0;
        if(isset($_POST["purpose1"])){$purpose1 = $_POST["purpose1"] === "true" ? 1 : 0;}
        if(isset($_POST["purpose2"])){$purpose2 = $_POST["purpose2"] === "true" ? 1 : 0;}
        if(isset($_POST["purpose3"])){$purpose3 = $_POST["purpose3"] === "true" ? 1 : 0;}
        if(isset($_POST["purpose4Other"])){$purpose4Other = json_decode($_POST["purpose4Other"]);}
        if(isset($_POST["goal1"])){$goal1 = $_POST["goal1"] === "true" ? 1 : 0;}
        if(isset($_POST["goal2"])){$goal2 = $_POST["goal2"] === "true" ? 1 : 0;}
        if(isset($_POST["goal3"])){$goal3 = $_POST["goal3"] === "true" ? 1 : 0;}
        if(isset($_POST["goal4"])){$goal4 = $_POST["goal4"] === "true" ? 1 : 0;}

        /*Get the dates & properly format*/
        $travelFrom = null; $travelTo = null; $activityFrom = null; $activityTo = null;
        if(isset($_POST["travelFrom"])){$travelFrom = date('Y-m-d h:i:s', $_POST["travelFrom"]);}
        if(isset($_POST["travelTo"])){$travelTo = date('Y-m-d h:i:s', $_POST["travelTo"]);}
        if(isset($_POST["activityFrom"])){$activityFrom = date('Y-m-d h:i:s', $_POST["activityFrom"]);}
        if(isset($_POST["activityTo"])){$activityTo = date('Y-m-d h:i:s', $_POST["activityTo"]);}

        /*get other data*/
        $updateID = null; $name = null; $email = null; $department = null; $deptChairEmail = null; $title = null;
        $destination = null; $amountRequested = null; $otherFunding = null; $proposalSummary = null;
        if(isset($_POST["updateID"])){$updateID = json_decode($_POST["updateID"]);}
        if(isset($_POST["name"])){$name = json_decode($_POST["name"]);}
        if(isset($_POST["email"])){$email = json_decode($_POST["email"]);}
        if(isset($_POST["department"])){$department = json_decode($_POST["department"]);}
        if(isset($_POST["deptChairEmail"])){$deptChairEmail = json_decode($_POST["deptChairEmail"]);}
        if(isset($_POST["title"])){$title = json_decode($_POST["title"]);}
        if(isset($_POST["destination"])){$destination = json_decode($_POST["destination"]);}
        if(isset($_POST["amountRequested"])){$amountRequested = json_decode($_POST["amountRequested"]);}
        if(isset($_POST["otherFunding"])){$otherFunding = json_decode($_POST["otherFunding"]);}
        if(isset($_POST["proposalSummary"])){$proposalSummary = json_decode($_POST["proposalSummary"]);}

        /*get nextCycle or currentCycle*/
        $nextCycle = null;

        if(isset($_POST["cycleChoice"]))
        {
            $chosen = json_decode($_POST["cycleChoice"]);
            if($chosen === "next") //user chose to submit next cycle
            {$nextCycle = 1;}
            else if ($chosen === "this") //user chose to submit this cycle
            {$nextCycle = 0;}
        }
        
        /*Insert data into database - receive the new application id if success, or 0 if failure*/
        /*parameters: DB connection, updating boolean, app ID (if exists), broncoNetID, name, email, department, dep. chair email, travel from, travel to, activity from, activity to, title, 
        destination, amount requested, purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, next cycle boolean, budgetItems*/
        if($isAdmin)
        {
            $insertReturn = insertApplication($conn, true, $updateID, $CASbroncoNetID, $name, $email, $department, $deptChairEmail, 
                $travelFrom, $travelTo, $activityFrom, $activityTo, $title, $destination, $amountRequested, 
                $purpose1, $purpose2, $purpose3, $purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetItems);
        }
        else
        {
            $insertReturn = insertApplication($conn, false, null, $CASbroncoNetID, $name, $email, $department, $deptChairEmail, 
                $travelFrom, $travelTo, $activityFrom, $activityTo, $title, $destination, $amountRequested, 
                $purpose1, $purpose2, $purpose3, $purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetItems);
        }
        
        if(isset($insertReturn["success"]))//returned normally
        {
            if($insertReturn["success"] === true)//if it was successful; try to upload all files and send an email to the department chair
            {
                $appID = $insertReturn["appID"]; //get the new application ID
                $insertReturn["fileSuccess"] = null; //variable to tell whether file upload was successful. If not, this will be set to false, and ["fileError"] will hold a detailed error message

                if($files != null) //user is uploading files as well
                {
                    $uploadReturn = uploadDocs($appID, $files);

                    if($uploadReturn !== true)//if there was an error with the upload
                    {
                        $insertReturn["fileSuccess"] = false; 
                        $insertReturn["fileError"] = $uploadReturn["error"];
                    }
                    else {$insertReturn["fileSuccess"] = true;} //it was successful
                }
                else {$insertReturn["fileSuccess"] = true;} //not uploading files

                //now try to email department chair IF creating for the first time
                if(!$isAdmin){$insertReturn["email"] = chairApprovalEmail($appID, $deptChairEmail, $name, $email);} //get results of trying to save/send email message
            }
            else {$insertReturn["fileSuccess"] = true;} //not uploading files
        }
    }
    catch(Exception $e)
    {
        $insertReturn = "Error adding application: " . $e->getMessage();
    }
    
}
else
{
    $insertReturn = "Permission denied";
}

$conn = null; //close connection

echo json_encode($insertReturn); //return data to the application page!

?>