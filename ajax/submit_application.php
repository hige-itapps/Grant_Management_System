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


	/*for dept. chair email message*/
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
?>

<?php
/************* FOR ADDING OR UPDATING APPLICATION VIA SUMBISSION ***************/

$insertReturn = null; //will be an array with return code and status, or an array of errors
//$data = array();    // array to pass back data


if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/../PHPMAILER/src/Exception.php';
if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/../PHPMAILER/src/PHPMailer.php';
if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/../PHPMAILER/src/SMTP.php';

// Please specify your Mail Server - Example: mail.example.com.
ini_set("SMTP","mail.example.com");

// Please specify an SMTP Number 25 and 8889 are valid SMTP Ports.
ini_set("smtp_port","25");

// Please specify the return address to use
ini_set('sendmail_from', 'info@hige.com');

$isAdmin = isAdministrator($conn, $CASbroncoNetID);

/*Verify that user is allowed to make an application*/
if(isUserAllowedToCreateApplication($conn, $CASbroncoNetID, $CASallPositions, true) || $isAdmin)
{
    //echo "User is allowed to create an application!";
    //print_r($_POST);
    
    try
    {
        /*Get the budget items*/
        $budgetItems = null;
        if(isset($_POST["budgetItems"])){$budgetItems = $_POST["budgetItems"];}
        
        /*get the 4 purposes and 4 goals*/
        $purpose1 = 0; $purpose2 = 0; $purpose3 = 0; $purpose4Other = ""; 
        $goal1 = 0; $goal2 = 0; $goal3 = 0; $goal4 = 0;
        if(isset($_POST["purpose1"])){$purpose1 = 1;}
        if(isset($_POST["purpose2"])){$purpose2 = 1;}
        if(isset($_POST["purpose3"])){$purpose3 = 1;}
        if(isset($_POST["purpose4Other"])){$purpose4Other = $_POST["purpose4Other"];}
        if(isset($_POST["goal1"])){$goal1 = 1;}
        if(isset($_POST["goal2"])){$goal2 = 1;}
        if(isset($_POST["goal3"])){$goal3 = 1;}
        if(isset($_POST["goal4"])){$goal4 = 1;}

        /*get other data*/
        $updateID = null; $name = null; $email = null; $department = null; $deptChairEmail = null;
        $travelFrom = null; $travelTo = null; $activityFrom = null; $activityTo = null; $title = null;
        $destination = null; $amountRequested = null; $otherFunding = null; $proposalSummary = null;
        if(isset($_POST["updateID"])){$updateID = $_POST["updateID"];}
        if(isset($_POST["name"])){$name = $_POST["name"];}
        if(isset($_POST["email"])){$email = $_POST["email"];}
        if(isset($_POST["department"])){$department = $_POST["department"];}
        if(isset($_POST["deptChairEmail"])){$deptChairEmail = $_POST["deptChairEmail"];}
        if(isset($_POST["travelFrom"])){$travelFrom = $_POST["travelFrom"];}
        if(isset($_POST["travelTo"])){$travelTo = $_POST["travelTo"];}
        if(isset($_POST["activityFrom"])){$activityFrom = $_POST["activityFrom"];}
        if(isset($_POST["activityTo"])){$activityTo = $_POST["activityTo"];}
        if(isset($_POST["title"])){$title = $_POST["title"];}
        if(isset($_POST["destination"])){$destination = $_POST["destination"];}
        if(isset($_POST["amountRequested"])){$amountRequested = $_POST["amountRequested"];}
        if(isset($_POST["otherFunding"])){$otherFunding = $_POST["otherFunding"];}
        if(isset($_POST["proposalSummary"])){$proposalSummary = $_POST["proposalSummary"];}


        /*echo "application_form Travel From:";
        var_dump($travelFrom);*/
        
        //split unnecessary day of the week from rest of strings
        /*$travelFromParts = explode(' ', $travelFrom, 2);
        $travelToParts = explode(' ', $travelTo, 2);
        $activityFromParts = explode(' ', $activityFrom, 2);
        $activityToParts = explode(' ', $activityTo, 2);

        //remove unnecessary day of week off the front of the string
        $travelFrom = $travelFromParts[1];
        $travelTo = $travelToParts[1];
        $activityFrom = $activityFromParts[1];
        $activityTo = $activityToParts[1];

        echo "2: application_form Travel From:";
        var_dump($travelFrom);*/

        /*get nextCycle or currentCycle*/
        $nextCycle = null;

        if(isset($_POST["cycleChoice"]))
        {
            if(strcmp($_POST["cycleChoice"], "next") == 0) //user chose to submit next cycle
            {$nextCycle = 1;}
            else if (strcmp($_POST["cycleChoice"], "this") == 0) //user chose to submit this cycle
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
        
        /*echo "<br>Insert status: ".$insertReturn[1].".<br>";
        
        $successUpload = 0; //initialize value to 0, should be made to something > 0 if upload is successful
        
        if($insertReturn[0] > 0) //if insert into DB was successful, continue
        {
            echo "<br>Uploading docs...<br>";
            $successUpload = uploadDocs($insertReturn[0]); //upload the documents
            
            echo "<br>Upload status: ".$successUpload.".<br>";
        }
        else
        {
            echo "<br>ERROR: could not insert application, app status: ".$insertReturn[0]."!<br>";
        }
        
        if($successUpload > 0 && !$isAdmin) //upload was successful- send email to department chair if not administrator
        {
            chairApprovalEmail($_POST["inputDeptCE"], $_POST["inputName"], $_POST["inputEmail"]); //send the email
            
            header('Location: ../index.php'); //redirect back to homepage
        }
        else if($successUpload > 0 && $isAdmin) //upload was successful for admin, so reload page
        {
            header('Location: ?id=' . $_POST["updateID"]); //reload page as admin
        }
        else
        {
            echo "<br>ERROR: could not upload application documents, upload status: ".$successUpload."!<br>";
        }*/
        
    }
    catch(Exception $e)
    {
        $insertReturn = "Error adding application: " . $e->getMessage();
    }
    
}

$conn = null; //close connection

echo json_encode($insertReturn); //return data to the application page!

?>