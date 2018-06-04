<?php
//For AJAX access
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

	/*Debug user validation*/
	//include "include/debugAuthentication.php";
	include_once(dirname(__FILE__) . "/include/CAS_login.php");
	
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/functions/database.php");
	$conn = connection();
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/functions/verification.php");
	
	/*Document functions*/
	include_once(dirname(__FILE__) . "/functions/documents.php");

	/*For sending custom emails*/
	include_once(dirname(__FILE__) . "/functions/customEmail.php");


	/*for dept. chair email message*/
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
?>

<?php
//ob_start();

/*************FOR ADDING OR UPDATING APPLICATION VIA SUMBISSION***************/

$insertReturn = null; //will be an array with return code and status, assuming no unexpected errors
//$data = array();    // array to pass back data


if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/PHPMAILER/src/Exception.php';
if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/PHPMAILER/src/PHPMailer.php';
if (!class_exists('PHPMailer'))
    require_once dirname(__FILE__) . '/PHPMAILER/src/SMTP.php';

// Please specify your Mail Server - Example: mail.example.com.
ini_set("SMTP","mail.example.com");

// Please specify an SMTP Number 25 and 8889 are valid SMTP Ports.
ini_set("smtp_port","25");

// Please specify the return address to use
ini_set('sendmail_from', 'info@hige.com');

$isAdmin = isAdministrator($conn, $CASbroncoNetId);

/*Verify that user is allowed to make an application*/
if(isUserAllowedToCreateApplication($conn, $CASbroncoNetId, $CASallPositions, true) || $isAdmin)
{
    //echo "User is allowed to create an application!";
    
    try
    {
        /*Set budgetArray*/
        $budgetArray = [[]];
        $count = 0; //index. Use this +1 to find name of current index (see below)
        
        while(true) //loop until no more budget items remaining
        {
            if(isset($_POST["amount" . ($count+1)])) {//make sure this index is used
                $budgetArray[$count][0] = $_POST["expense" . ($count+1)];
                $budgetArray[$count][1] = $_POST["comm" . ($count+1)];
                $budgetArray[$count][2] = $_POST["amount" . ($count+1)];
            }else{
                break;
            }
            $count++;
        }
        
        /*get the 4 purposes and 4 goals*/
        $pr1 = 0; $pr2 = 0; $pr3 = 0; $pr4 = ""; 
        $pg1 = 0; $pg2 = 0; $pg3 = 0; $pg4 = 0;
        if(isset($_POST["purpose1"])){$pr1 = 1;}
        if(isset($_POST["purpose2"])){$pr2 = 1;}
        if(isset($_POST["purpose3"])){$pr3 = 1;}
        if(isset($_POST["purposeOther"])){$pr4 = $_POST["purposeOther"];}
        if(isset($_POST["goal1"])){$pg1 = 1;}
        if(isset($_POST["goal2"])){$pg2 = 1;}
        if(isset($_POST["goal3"])){$pg3 = 1;}
        if(isset($_POST["goal4"])){$pg4 = 1;}

        /*get other data*/
        $updateID = null; $inputName = null; $inputEmail = null; $inputDept = null; $inputDeptCE = null;
        $inputTFrom = null; $inputTTo = null; $inputAFrom = null; $inputATo = null; $inputRName = null;
        $inputDest = null; $inputAR = null; $eS = null; $props = null;
        if(isset($_POST["updateID"])){$updateID = $_POST["updateID"];}
        if(isset($_POST["inputName"])){$inputName = $_POST["inputName"];}
        if(isset($_POST["inputEmail"])){$inputEmail = $_POST["inputEmail"];}
        if(isset($_POST["inputDept"])){$inputDept = $_POST["inputDept"];}
        if(isset($_POST["inputDeptCE"])){$inputDeptCE = $_POST["inputDeptCE"];}
        if(isset($_POST["inputTFrom"])){$inputTFrom = $_POST["inputTFrom"];}
        if(isset($_POST["inputTTo"])){$inputTTo = $_POST["inputTTo"];}
        if(isset($_POST["inputAFrom"])){$inputAFrom = $_POST["inputAFrom"];}
        if(isset($_POST["inputATo"])){$inputATo = $_POST["inputATo"];}
        if(isset($_POST["inputRName"])){$inputRName = $_POST["inputRName"];}
        if(isset($_POST["inputDest"])){$inputDest = $_POST["inputDest"];}
        if(isset($_POST["inputAR"])){$inputAR = $_POST["inputAR"];}
        if(isset($_POST["eS"])){$eS = $_POST["eS"];}
        if(isset($_POST["props"])){$props = $_POST["props"];}


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
        destination, amount requested, purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, next cycle boolean, budgetArray*/
        if($isAdmin)
        {
            $insertReturn = insertApplication($conn, true, $updateID, $CASbroncoNetId, $inputName, $inputEmail, $inputDept, $inputDeptCE, 
                $inputTFrom, $inputTTo, $inputAFrom, $inputATo, $inputRName, $inputDest, $inputAR, 
                $pr1, $pr2, $pr3, $pr4, $eS, $props, $pg1, $pg2, $pg3, $pg4, $nextCycle, $budgetArray);
        }
        else
        {
            $insertReturn = insertApplication($conn, false, null, $CASbroncoNetId, $inputName, $inputEmail, $inputDept, $inputDeptCE, 
                $inputTFrom, $inputTTo, $inputAFrom, $inputATo, $inputRName, $inputDest, $inputAR, 
                $pr1, $pr2, $pr3, $pr4, $eS, $props, $pg1, $pg2, $pg3, $pg4, $nextCycle, $budgetArray);
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
        //echo "Error adding application: " . $e->getMessage();
    }
    
}

$conn = null; //close connection

echo json_encode($insertReturn); //return data

?>