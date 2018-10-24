<?php
/*This file serves as the project's RESTful API. Simply send a GET request to this file with a specified function name, with additional POST data where necessary. 
Only 1 API function can be called at a time through the GET paramater.*/

/*User validation*/
include_once(dirname(__FILE__) . "/../CAS/CAS_login.php");
	
/*Get DB connection*/
include_once(dirname(__FILE__) . "/../server/DatabaseHelper.php");

/*Document functions*/
include_once(dirname(__FILE__) . "/../server/DocumentsHelper.php");

/*For sending custom emails*/
include_once(dirname(__FILE__) . "/../server/EmailHelper.php");

/*Logger*/
include_once(dirname(__FILE__) . "/../server/Logger.php");

$logger = new Logger(); //for logging to files
$database = new DatabaseHelper($logger); //database helper object used for some verification and insertion
$documentsHelper = new DocumentsHelper($logger); //initialize DocumentsHelper object
$emailHelper = new EmailHelper($logger); //initialize EmailHelper object

$returnVal = []; //initialize return value as empty. If there is an error, it is expected to be set as $returnVal["error"].


//for downloading files, uses GET parameters for appID and filename
if (array_key_exists('download_file', $_GET)) {
    if(isset($_GET["appID"]) && isset($_GET["filename"])){
        $appID = $_GET["appID"];
        $file = $_GET["filename"];

        /*Verify that user is allowed to see this file*/
        if($database->isUserAllowedToSeeApplications($CASbroncoNetID) || $database->doesUserOwnApplication($CASbroncoNetID, $appID) || $database->isUserDepartmentChair($CASemail, $appID, $CASbroncoNetID)){
            $returnVal = $documentsHelper->downloadDoc($appID, $file, $CASbroncoNetID);
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to download files for this application.";
        }
    }
    else{
        $returnVal["error"] = "AppID and/or filename is not set";
    }
}



//for uploading files
else if(array_key_exists('upload_file', $_GET)){
    if(isset($_POST["appID"]) && isset($_FILES)){
        $appID = $_POST["appID"];
        $files = $_FILES;

        /*Verify that user is allowed to upload files*/
        if($database->doesUserOwnApplication($CASbroncoNetID, $appID) || $database->isAdministrator($CASbroncoNetID)){
            $returnVal = $documentsHelper->uploadDocs($appID, $files, $CASbroncoNetID);
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to upload files for this application.";
        }
    }
    else{
        $returnVal["error"] = "AppID and/or uploadFiles is not set";
    }
}



//for saving staff notes
else if(array_key_exists('save_note', $_GET)){
    if(isset($_POST["appID"]) && isset($_POST["note"])){
        $appID = $_POST["appID"];
        $note = $_POST["note"];

        /*Verify that user is allowed to save this note*/
        if($database->isAdministrator($CASbroncoNetID) || $database->isApplicationApprover($CASbroncoNetID) || $database->isFinalReportApprover($CASbroncoNetID)){
            try{
                $note = trim($note);
                $returnVal = $database->saveStaffNotes($appID, $note, $CASbroncoNetID);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to save note due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to save note due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to save notes for this application.";
        }
    }
    else{
        $returnVal["error"] = "AppID and/or note is not set";
    }
}



//for submitting applications
else if(array_key_exists('submit_application', $_GET)){
    $isAdmin = $database->isAdministrator($CASbroncoNetID); //check if user is an admin

    /*Verify that user is allowed to make an application. The specific cycle is checked in the insertApplication() function, so just pass in true for now.*/
    if($database->isUserAllowedToCreateApplication($CASbroncoNetID, true) || $isAdmin){
        try{
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

            if(isset($_POST["cycleChoice"])){
                $chosen = json_decode($_POST["cycleChoice"]);
                if($chosen === "next") //user chose to submit next cycle
                {$nextCycle = 1;}
                else if ($chosen === "this") //user chose to submit this cycle
                {$nextCycle = 0;}
            }
            
            /*Insert data into database - receive the new application id if success, or 0 if failure*/
            if($isAdmin){//updating
                $returnVal["insert"] = $database->insertApplication(true, $updateID, $CASbroncoNetID, $name, $email, $department, $deptChairEmail, 
                    $travelFrom, $travelTo, $activityFrom, $activityTo, $title, $destination, $amountRequested, 
                    $purpose1, $purpose2, $purpose3, $purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetItems);
            }
            else{//new insert
                $returnVal["insert"] = $database->insertApplication(false, null, $CASbroncoNetID, $name, $email, $department, $deptChairEmail, 
                    $travelFrom, $travelTo, $activityFrom, $activityTo, $title, $destination, $amountRequested, 
                    $purpose1, $purpose2, $purpose3, $purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetItems);
            }
            
            if(isset($returnVal["insert"]["success"])){//returned normally
                if($returnVal["insert"]["success"] === true){//if it was successful; try to upload all files and send an email to the department chair
                    $appID = $returnVal["insert"]["appID"]; //get the new application ID
                    $returnVal["fileSuccess"] = null; //variable to tell whether file upload was successful. If not, this will be set to false, and ["fileError"] will hold a detailed error message

                    if($files != null){ //user is uploading files as well
                        $uploadReturn = $documentsHelper->uploadDocs($appID, $files, $CASbroncoNetID);

                        if(isset($uploadReturn["error"])){//if there was an error with the upload
                            $returnVal["fileSuccess"] = false; 
                            $returnVal["fileError"] = $uploadReturn["error"];
                        }
                        else {$returnVal["fileSuccess"] = true;} //it was successful
                    }
                    else {$returnVal["fileSuccess"] = true;} //not uploading files

                    //now try to email department chair IF creating for the first time
                    if(!$isAdmin){$returnVal["email"] = $emailHelper->chairApprovalEmail($appID, $deptChairEmail, $name, $email, $CASbroncoNetID);} //get results of trying to save/send email message
                }
            }
        }
        catch(Exception $e){
            $errorMessage = $logger->logError("Unable to insert application and/or upload files due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			$returnVal["error"] = "Error: Unable to insert application and/or upload files due to an internal exception. ".$errorMessage;
        }     
    }
    else{
        $returnVal["error"] = "Permission denied, you are not permitted to create an application at this time.";
    }
}



//for submitting final reports
else if(array_key_exists('submit_final_report', $_GET)){
    $isAdmin = $database->isAdministrator($CASbroncoNetID);

    if(isset($_POST["appID"])){
        $appID = json_decode($_POST["appID"]);

        /*Verify that user is allowed to make an application*/
        if($database->isUserAllowedToCreateFinalReport($CASbroncoNetID, $appID) || $isAdmin){
            try{
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
                if($isAdmin){
                    $returnVal["insert"] = $database->insertFinalReport(true, $appID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $amountAwardedSpent, $CASbroncoNetID);
                }
                else{
                    $returnVal["insert"] = $database->insertFinalReport(false, $appID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $amountAwardedSpent, $CASbroncoNetID);
                }
                
                if(isset($returnVal["insert"]["success"])){//returned normally
                    if($returnVal["insert"]["success"] === true){//if it was successful
                        $returnVal["fileSuccess"] = null; //variable to tell whether file upload was successful. If not, this will be set to false, and ["fileError"] will hold a detailed error message

                        if($files != null){ //user is uploading files as well
                            $uploadReturn = $documentsHelper->uploadDocs($appID, $files, $CASbroncoNetID);

                            if(isset($uploadReturn["error"])){//if there was an error with the upload
                                $returnVal["fileSuccess"] = false; 
                                $returnVal["fileError"] = $$uploadReturn["error"];
                            }
                            else {$returnVal["fileSuccess"] = true;} //it was successful
                        }
                        else {$returnVal["fileSuccess"] = true;} //not uploading files
                    }
                    else {$returnVal["fileSuccess"] = true;} //not uploading files
                }
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to insert final report and/or upload files due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to insert final report and/or upload files due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to create a final report at this time.";
        }
    }
    else{
        $returnVal["error"] = "Error: AppID is not set!";
    }
}



//for adding administrators
else if(array_key_exists('add_admin', $_GET)){
    if(isset($_POST["broncoNetID"]) && isset($_POST["name"])){
        $broncoNetID = $_POST["broncoNetID"];
        $name = $_POST["name"];

        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->addAdmin($broncoNetID, $name);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to insert administrator due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to insert administrator due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to add administrators.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID and/or name is not set";
    }
}



//for adding application approvers
else if(array_key_exists('add_application_approver', $_GET)){
    if(isset($_POST["broncoNetID"]) && isset($_POST["name"])){
        $broncoNetID = $_POST["broncoNetID"];
        $name = $_POST["name"];

        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->addApplicationApprover($broncoNetID, $name);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to insert application approver due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to insert application approver due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to add application approvers.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID and/or name is not set";
    }
}



//for adding final report approvers
else if(array_key_exists('add_final_report_approver', $_GET)){
    if(isset($_POST["broncoNetID"]) && isset($_POST["name"])){
        $broncoNetID = $_POST["broncoNetID"];
        $name = $_POST["name"];

        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->addFinalReportApprover($broncoNetID, $name);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to insert final report approver due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to insert final report approver due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to add final report approvers.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID and/or name is not set";
    }
}



//for adding committee members
else if(array_key_exists('add_committee_member', $_GET)){
    if(isset($_POST["broncoNetID"]) && isset($_POST["name"])){
        $broncoNetID = $_POST["broncoNetID"];
        $name = $_POST["name"];

        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->addCommittee($broncoNetID, $name);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to insert committee member due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to insert committee member due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to add committee members.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID and/or name is not set";
    }
}



//for a department chair to approve an application
else if(array_key_exists('chair_approval', $_GET)){
    if(isset($_POST["appID"]) && isset($_POST["deptChairApproval"])){
        $appID = $_POST["appID"];
        $deptChairApproval = $_POST["deptChairApproval"];

        /*Verify that user is allowed to approve an application*/
        if($database->isUserAllowedToSignApplication($CASemail, $appID, $CASbroncoNetID)){
            try{
                $deptChairApproval = trim($deptChairApproval);
                if($deptChairApproval !== '') {
                    $returnVal = $database->signApplication($appID, $deptChairApproval, $CASbroncoNetID);
                }
                else {$returnVal["error"] = "No name specified, you must type your name to approve this application";}
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to approve application as the department chair due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to approve application as the department chair due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to approve this application as the department chair.";
        }
    }
    else{
        $returnVal["error"] = "AppID and/or status is not set";
    }
}



//for HIGE staff to approve an application
else if(array_key_exists('approve_application', $_GET)){
    if(isset($_POST["appID"]) && isset($_POST["status"]) && isset($_POST["emailAddress"]) && isset($_POST["emailMessage"]))
    {
        $appID = $_POST["appID"];
        $status = $_POST["status"];
        $emailAddress = $_POST["emailAddress"];
        $emailMessage = $_POST["emailMessage"];
    
        if(trim($emailMessage) === '' || $emailMessage == null) {$returnVal["error"] = "Email message must not be empty!";}
        else
        {
            /*Verify that user is allowed to approve an application*/
            if($database->isApplicationApprover($CASbroncoNetID) || $database->isAdministrator($CASbroncoNetID))
            {
                try{
                    if($status === 'Approved'){ 
                        if(isset($_POST["amount"])){
                            if($_POST["amount"] > 0){ $returnVal["success"] = $database->approveApplication($appID, $_POST["amount"], $CASbroncoNetID); }
                            else {$returnVal["error"] = "Amount awarded must be greater than $0";}
                        }
                        else {$returnVal["error"] = "No amount specified";}
                    }
                    else if($status === 'Hold') { $returnVal["success"] = $database->holdApplication($appID, $CASbroncoNetID); }
                    else if($status === 'Denied') { $returnVal["success"] = $database->denyApplication($appID, $CASbroncoNetID); }
                    else { $returnVal["error"] = "Invalid status given"; }
    
                    //if everything has been successful so far, send off the email as well
                    if(!isset($returnVal["error"])){
                        $returnVal["email"] = $emailHelper->customEmail($appID, $emailAddress, $emailMessage, null, $CASbroncoNetID); //get results of trying to save/send email message
                    }
                }
                catch(Exception $e){
                    $errorMessage = $logger->logError("Unable to approve application due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			        $returnVal["error"] = "Error: Unable to approve application due to an internal exception. ".$errorMessage;
                }
            }
            else{
                $returnVal["error"] = "Permission denied, you are not permitted to approve this application.";
            }
        }
    }
    else{
        $returnVal["error"] = "AppID, status, and/or email is not set";
    }
}



//for HIGE staff to approve a final report
else if(array_key_exists('approve_final_report', $_GET)){
    if(isset($_POST["appID"]) && isset($_POST["status"]) && isset($_POST["emailAddress"]) && isset($_POST["emailMessage"])){
        $appID = $_POST["appID"];
        $status = $_POST["status"];
        $emailAddress = $_POST["emailAddress"];
        $emailMessage = $_POST["emailMessage"];
    
    
        if(trim($emailMessage) === '' || $emailMessage == null) {$returnVal["error"] = "Email message must not be empty!";}
        else{
            /*Verify that user is allowed to approve a report*/
            if($database->isFinalReportApprover($CASbroncoNetID) || $database->isAdministrator($CASbroncoNetID)){
                try{
                    if($status === 'Approved') { $returnVal["success"] = $database->approveFinalReport($appID, $CASbroncoNetID); }
                    else if($status === 'Hold') { $returnVal["success"] = $database->holdFinalReport($appID, $CASbroncoNetID); }
                    else { $returnVal["error"] = "Invalid status given"; }
    
                    //if everything has been successful so far, send off the email as well
                    if(!isset($returnVal["error"])){
                        $returnVal["email"] = $emailHelper->customEmail($appID, $emailAddress, $emailMessage, null, $CASbroncoNetID); //get results of trying to save/send email message
                    }
                }
                catch(Exception $e){
                    $errorMessage = $logger->logError("Unable to approve final report due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			        $returnVal["error"] = "Error: Unable to approve final report due to an internal exception. ".$errorMessage;
                }
            }
            else{
                $returnVal["error"] = "Permission denied, you are not permitted to approve this final report.";
            }
        }
    }
    else{
        $returnVal["error"] = "AppID, status, and/or email is not set";
    }
}



//for getting admins
else if(array_key_exists('get_admins', $_GET)){
    if($database->isAdministrator($CASbroncoNetID)){
        try{
            $returnVal = $database->getAdministrators();
        }
        catch(Exception $e){
            $errorMessage = $logger->logError("Unable to retrieve administrator due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			$returnVal["error"] = "Error: Unable to retrieve administrator due to an internal exception. ".$errorMessage;
        }
    }
    else{
        $returnVal["error"] = "Permission denied, you are not permitted to retrieve the administrators list.";
    }
}



//for getting application approvers
else if(array_key_exists('get_application_approvers', $_GET)){
    if($database->isAdministrator($CASbroncoNetID)){
        try{
            $returnVal = $database->getApplicationApprovers();
        }
        catch(Exception $e){
            $errorMessage = $logger->logError("Unable to retrieve application approvers due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			$returnVal["error"] = "Error: Unable to retrieve application approvers due to an internal exception. ".$errorMessage;
        }
    }
    else{
        $returnVal["error"] = "Permission denied, you are not permitted to retrieve the application approvers list.";
    }
}



//for getting final report approvers
else if(array_key_exists('get_final_report_approvers', $_GET)){
    if($database->isAdministrator($CASbroncoNetID)){
        try{
            $returnVal = $database->getFinalReportApprovers();
        }
        catch(Exception $e){
            $errorMessage = $logger->logError("Unable to retrieve final report approvers due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			$returnVal["error"] = "Error: Unable to retrieve final report approvers due to an internal exception. ".$errorMessage;
        }
    }
    else{
        $returnVal["error"] = "Permission denied, you are not permitted to retrieve the final report approvers list.";
    }
}



//for getting committee members
else if(array_key_exists('get_committee_members', $_GET)){
    if($database->isAdministrator($CASbroncoNetID)){
        try{
            $returnVal = $database->getCommittee();
        }
        catch(Exception $e){
            $errorMessage = $logger->logError("Unable to retrieve committee members due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			$returnVal["error"] = "Error: Unable to retrieve committee members due to an internal exception. ".$errorMessage;
        }
    }
    else{
        $returnVal["error"] = "Permission denied, you are not permitted to retrieve the committee members list.";
    }
}



//for getting applications
else if(array_key_exists('get_application', $_GET)){
    if(isset($_POST["appID"])){
        $appID = $_POST["appID"];
    
        /*Verify that user is allowed to retrieve an application*/
        if($database->isUserAllowedToSeeApplications($CASbroncoNetID) || $database->doesUserOwnApplication($CASbroncoNetID, $appID) || $database->isUserDepartmentChair($CASemail, $appID, $CASbroncoNetID)){
            try{
                $returnVal = $database->getApplication($appID); //get application Data
                $returnVal->appFiles = $documentsHelper->getFileNames($appID); //get the list of file names associated with this application
                $returnVal->appEmails = $database->getEmails($appID); //get associated emails
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to retrieve application due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to retrieve application due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to retrieve this application.";
        }
    }
    else{
        $returnVal["error"] = "AppID is not set!";
    }
}



//for getting final reports
else if(array_key_exists('get_final_report', $_GET)){
    if(isset($_POST["appID"])){
        $appID = $_POST["appID"];
    
        /*Verify that user is allowed to retrieve a report*/
        if($database->isUserAllowedToSeeApplications($CASbroncoNetID) || $database->doesUserOwnApplication($CASbroncoNetID, $appID) || $database->isUserDepartmentChair($CASemail, $appID)){
            try{
                $returnVal = $database->getFinalReport($appID); //get report Data
                $returnVal->reportFiles = $documentsHelper->getFileNames($appID); //get the list of file names associated with this application
                $returnVal->reportEmails = $database->getEmails($appID); //get associated emails
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to retrieve final report due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to retrieve final report due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to retrieve this final report";
        }
    }
    else{
        $returnVal["error"] = "AppID is not set!";
    }
}



//for removing an admin
else if(array_key_exists('remove_admin', $_GET)){
    if(isset($_POST["broncoNetID"])){
        $broncoNetID = $_POST["broncoNetID"];
    
        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            if($CASbroncoNetID !== $broncoNetID){ //not trying to remove self
                try{
                    $returnVal = $database->removeAdmin($broncoNetID);
                }
                catch(Exception $e){
                    $errorMessage = $logger->logError("Unable to remove administrator due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			        $returnVal["error"] = "Error: Unable to remove administrator due to an internal exception. ".$errorMessage;
                }
            }
            else{
                $returnVal["error"] = "Admins cannot remove themselves";
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to remove administrators.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID is not set";
    }
}



//for removing an application approver
else if(array_key_exists('remove_application_approver', $_GET)){
    if(isset($_POST["broncoNetID"])){
        $broncoNetID = $_POST["broncoNetID"];
    
        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->removeApplicationApprover($broncoNetID);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to remove application approver due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to remove application approver due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to remove application approvers.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID is not set";
    }
}



//for removing a final report approver
else if(array_key_exists('remove_final_report_approver', $_GET)){
    if(isset($_POST["broncoNetID"])){
        $broncoNetID = $_POST["broncoNetID"];
    
        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->removeFinalReportApprover($broncoNetID);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to remove final report approver due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to remove final report approver due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to remove final report approvers.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID is not set";
    }
}



//for removing a committee member
else if(array_key_exists('remove_committee_member', $_GET)){
    if(isset($_POST["broncoNetID"])){
        $broncoNetID = $_POST["broncoNetID"];
    
        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->removeCommittee($broncoNetID);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to remove committee member due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to remove committee member due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to remove committee members.";
        }
    }
    else{
        $returnVal["error"] = "broncoNetID is not set";
    }
}



//for removing an application
else if(array_key_exists('remove_application', $_GET)){
    if(isset($_POST["appID"])){
        $appID = $_POST["appID"];
    
        //must have permission to do this
        if($database->isAdministrator($CASbroncoNetID)){
            try{
                $returnVal = $database->removeApplication($appID, $CASbroncoNetID);
            }
            catch(Exception $e){
                $errorMessage = $logger->logError("Unable to remove application due to an internal exception: ".$e->getMessage(), $CASbroncoNetID, dirname(__FILE__), true);
			    $returnVal["error"] = "Error: Unable to remove application due to an internal exception. ".$errorMessage;
            }
        }
        else{
            $returnVal["error"] = "Permission denied, you are not permitted to remove applications.";
        }
    }
    else{
        $returnVal["error"] = "appID is not set";
    }
}



//no appropriate function called
else{
    $returnVal = json_encode("No function called");
}



$database->close(); //close database connections

echo json_encode($returnVal); //return results

?>