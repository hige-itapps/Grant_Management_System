<?php
	include_once(dirname(__FILE__) . "/../include/classDefinitions.php");
	include_once(dirname(__FILE__) . "/../controllers/customEmail.php");
	include_once('verification.php');

	/* Establishes an sql connection to the database, and returns the object; MAKE SURE TO SET OBJECT TO NULL WHEN FINISHED */
	if(!function_exists('connection')) {
		function connection()
		{
			try 
			{
				/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
				$config_url = dirname(__FILE__).'/../config.ini';

				$settings = parse_ini_file($config_url);
				
				//var_dump($settings);
				$conn = new AtomicPDO("mysql:host=" . $settings["hostname"] . ";dbname=" . $settings["database_name"] . ";charset=utf8", $settings["database_username"], 
					$settings["database_password"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
				// set the PDO error mode to exception
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$conn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
				//echo "Connected successfullyn\n\n\n\n\n"; 
			}
			catch(PDOException $e)
			{
				echo "Connection failed: " . $e->getMessage();
			}
				return $conn;
		}
	}
	
	/* Returns array of all administrators */
	if(!function_exists('getAdministrators')) {
		function getAdministrators($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM administrators");
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/* Returns array of all applicants */
	if(!function_exists('getApplicants')) {
		function getApplicants($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID FROM applicants");
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/*Adds applicant to database IF they don't already exist. Otherwise, just ignore*/
	if(!function_exists('insertApplicantIfNew')) {
		function insertApplicantIfNew($conn, $newID)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select Count(*) FROM applicants WHERE BroncoNetID = :id");
			$sql->bindParam(':id', $newID);
			$sql->execute();
			$res = $sql->fetchAll();
			
			if($res[0][0] == 0)//only triggers if user doesn't already exist
			{
				$sql = $conn->prepare("INSERT INTO applicants VALUES(:id)");
				$sql->bindParam(':id', $newID);
				$sql->execute();
			}
			
			/* Close finished query and connection */
			$sql = null;
		}
	}
	
	/*Returns a single application for a specified application ID*/
	if(!function_exists('getApplication')) {
		function getApplication($conn, $appID)
		{
			$sql = $conn->prepare("Select * FROM applications WHERE ID = :appID");
			$sql->bindParam(':appID', $appID);
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			
			/*create application object*/
			$application = new Application($res[0]);
			
			$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
			$sql->bindParam(':id', $application->id);
			/* run the prepared query */
			$sql->execute();
			$resBudget = $sql->fetchAll();
			
			$application->budget = $resBudget;
			
			/* Close finished query and connection */
			$sql = null;
			
			/* return application */
			return $application;
		}
	}
	
	/*Returns a single follow up report for a specified application ID*/
	if(!function_exists('getFUReport')) {
		function getFUReport($conn, $appID)
		{
			$sql = $conn->prepare("Select * FROM follow_up_reports WHERE ApplicationID = :appID");
			$sql->bindParam(':appID', $appID);
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			
			/*create application object*/
			$FUReport = new FUReport($res[0]);
			
			
			/* Close finished query and connection */
			$sql = null;
			
			/* return FUReport object */
			return $FUReport;
		}
	}
	/* Returns array of all applications that still need to be signed by a specific user(via email address)
	Note: don't include ones that have already been signed, and ones that have the same email as the submitter
	(you shouldn't be able to sign your own application)
	*/
	if(!function_exists('getApplicationsToSign')) {
		function getApplicationsToSign($conn, $email)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select * FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND DepartmentChairEmail != Email AND Approved IS NULL");
				$sql->bindParam(':dEmail', $email);
				
				/* run the prepared query */
				$sql->execute();
				$res = $sql->fetchAll();
				
				$applicationsArray = []; //create new array of applications
				
				/*go through all applications, adding them to the array*/
				for($i = 0; $i < count($res); $i++)
				{
					//echo "i is ".$i.".";
					$application = new Application($res[$i]); //initialize
					
					$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
					$sql->bindParam(':id', $application->id);
					/* run the prepared query */
					$sql->execute();
					$resBudget = $sql->fetchAll();
					
					$application->budget = $resBudget;
					
					/*add application to array*/
					$applicationsArray[$i] = $application;
				}
				
				/* Close finished query and connection */
				$sql = null;
				
				/* return array */
				return $applicationsArray;
			}
		}
	}
	
	/* Returns array of all applications that still need to be approved or denied for a specific user, 
	or ALL users if no ID is provided*/
	if(!function_exists('getPendingApplications')) {
		function getPendingApplications($conn, $bNetID)
		{
			if ($bNetID != "") //valid username
			{
				/* Select only pending applications that this user has has submitted */
				$sql = $conn->prepare("Select * FROM applications WHERE Approved IS NULL AND Applicant = :bNetID");
				$sql->bindParam(':bNetID', $bNetID);
			}
			else //no username
			{
				/* Select all pending applications */
				$sql = $conn->prepare("Select * FROM applications WHERE Approved IS NULL");
			}
			
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			
			$applicationsArray = []; //create new array of applications
			
			/*go through all applications, adding them to the array*/
			for($i = 0; $i < count($res); $i++)
			{
				//echo "i is ".$i.".";
				$application = new Application($res[$i]); //initialize
				
				$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
				$sql->bindParam(':id', $application->id);
				/* run the prepared query */
				$sql->execute();
				$resBudget = $sql->fetchAll();
				
				$application->budget = $resBudget;
				
				/*add application to array*/
				$applicationsArray[$i] = $application;
			}
			
			/* Close finished query and connection */
			$sql = null;
			
			/* return array */
			return $applicationsArray;
		}
	}
	
	/* Returns array of all applications for a specified BroncoNetID, or ALL applications if no ID is provided */
	if(!function_exists('getApplications')) {
		function getApplications($conn, $bNetID)
		{
			if ($bNetID != "") //valid username
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select * FROM applications WHERE Applicant = :bNetID");
				$sql->bindParam(':bNetID', $bNetID);
			}
			else //no username
			{
				/* Select all applications */
				$sql = $conn->prepare("Select * FROM applications");
			}
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			
			$applicationsArray = []; //create new array of applications
			
			/*go through all applications, adding them to the array*/
			for($i = 0; $i < count($res); $i++)
			{
				//echo "i is ".$i.".";
				$application = new Application($res[$i]); //initialize
				
				$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
				$sql->bindParam(':id', $application->id);
				/* run the prepared query */
				$sql->execute();
				$resBudget = $sql->fetchAll();
				
				$application->budget = $resBudget;
				
				/*add application to array*/
				$applicationsArray[$i] = $application;
			}
			
			/* Close finished query and connection */
			$sql = null;
			
			/* return array */
			return $applicationsArray;
		}
	}
	
	/* Returns array of application approvers */
	if(!function_exists('getApplicationApprovers')) {
		function getApplicationApprovers($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM application_approval");
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/* Returns array of committee members */
	if(!function_exists('getCommittee')) {
		function getCommittee($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM committee");
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/* Returns array of follow-up report approvers */
	if(!function_exists('getFollowUpReportApprovers')) {
		function getFollowUpReportApprovers($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM follow_up_approval");
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/* Add an admin to the administrators table */
	if(!function_exists('addAdmin')){
		function addAdmin($conn, $broncoNetID, $name)
		{
			if ($broncoNetID != "" && $name != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("INSERT INTO administrators(BroncoNetID, Name) VALUES(:id, :name)");
				$sql->bindParam(':id', $broncoNetID);
				$sql->bindParam(':name', $name);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/* Add a committee member to the committee table */
	if(!function_exists('addCommittee')){
		function addCommittee($conn, $broncoNetID, $name)
		{
			if ($broncoNetID != "" && $name != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("INSERT INTO committee(BroncoNetID, Name) VALUES(:id, :name)");
				$sql->bindParam(':id', $broncoNetID);
				$sql->bindParam(':name', $name);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/* Add a follow-up approver to the follow_up_approval table */
	if(!function_exists('addFollowUpApprover')){
		function addFollowUpApprover($conn, $broncoNetID, $name)
		{
			if ($broncoNetID != "" && $name != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("INSERT INTO follow_up_approval(BroncoNetID, Name) VALUES(:id, :name)");
				$sql->bindParam(':id', $broncoNetID);
				$sql->bindParam(':name', $name);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/* Add an application approver to the application_approval table */
	if(!function_exists('addApplicationApprover')){
		function addApplicationApprover($conn, $broncoNetID, $name)
		{
			if ($broncoNetID != "" && $name != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("INSERT INTO application_approval(BroncoNetID, Name) VALUES(:id, :name)");
				$sql->bindParam(':id', $broncoNetID);
				$sql->bindParam(':name', $name);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	
	/* Remove an admin to the administrators table */
	if(!function_exists('removeAdmin')){
		function removeAdmin($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("DELETE FROM administrators WHERE BroncoNetID = :id");
				$sql->bindParam(':id', $broncoNetID);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/* Remove a committee member to the committee table */
	if(!function_exists('removeCommittee')){
		function removeCommittee($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("DELETE FROM committee WHERE BroncoNetID = :id");
				$sql->bindParam(':id', $broncoNetID);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/* Remove a follow-up approver to the follow_up_approval table */
	if(!function_exists('removeFollowUpApprover')){
		function removeFollowUpApprover($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("DELETE FROM follow_up_approval WHERE BroncoNetID = :id");
				$sql->bindParam(':id', $broncoNetID);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/* Remove an application approver to the application_approval table */
	if(!function_exists('removeApplicationApprover')){
		function removeApplicationApprover($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("DELETE FROM application_approval WHERE BroncoNetID = :id");
				$sql->bindParam(':id', $broncoNetID);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	
	
	
	/* Returns true if applicant's application has been approved, or false otherwise 
	TODO: make this only work for applications that do not also have an approved follow-up report!*/
	if(!function_exists('isApplicationApproved')){
		function isApplicationApproved($conn, $appID)
		{
			$is = false;
			
			if ($appID != "") //valid Id
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :id AND Approved = true");
				$sql->bindParam(':id', $appID);
				$sql->execute();
				$res = $sql->fetchAll();
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}
	
	/* Returns 1 if applicant's application has been signed, or 0 otherwise */
	if(!function_exists('isApplicationSigned')){
		function isApplicationSigned($conn, $appID)
		{
			
			if ($appID != "") //valid Id
			{
				/* Select only applications that this user has has submitted that have a signature */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :id AND DepartmentChairSignature IS NOT NULL");
				$sql->bindParam(':id', $appID);
				$sql->execute();
				$res = $sql->fetchAll();
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0){ //at least one result
					return 1;
				}else{
					return 0;
				}
			}else
				return 0;
		}
	}
	
	/* Returns true if this applicant has a pending application, or false otherwise */
	if(!function_exists('hasPendingApplication')){
		function hasPendingApplication($conn, $bNetID)
		{
			$is = false;
			
			if ($bNetID != "") //valid username
			{
				/* Select only pending applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Approved IS NULL AND Applicant = :bNetID");
				$sql->bindParam(':bNetID', $bNetID);
				$sql->execute();
				$res = $sql->fetchAll();
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}
	
	/* Returns true if this applicant has a follow up r already created, or false otherwise */
	if(!function_exists('hasFUReport')){
		function hasFUReport($conn, $appID)
		{
			$is = false;
			
			if ($appID != "") //valid username
			{
				/* Select only pending applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM follow_up_reports WHERE ApplicationID = :appID");
				$sql->bindParam(':appID', $appID);
				$sql->execute();
				$res = $sql->fetchAll();
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}
	/* Returns true if this applicant has an approved application from up to a year ago -- OUTDATED!*/
	/*if(!function_exists('hasApprovedApplicationWithinPastYear')){
		function hasApprovedApplicationWithinPastYear($conn, $bNetID)
		{
			$is = false;
			
			if ($bNetID != "") //valid username
			{
				/* Select only approved applications that this user has has submitted within the past year*/
				/*$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Date >= DATE_SUB(NOW(),INTERVAL 1 YEAR) AND Applicant = :bNetID AND Approved = true");
				$sql->bindParam(':bNetID', $bNetID);
				$sql->execute();
				$res = $sql->fetchAll();
				
				/* Close finished query and connection */
				/*$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}*/
	
	/*Get the most recently approved application of a user- return null if none*/
	if(!function_exists('getMostRecentApprovedApplication')){
		function getMostRecentApprovedApplication($conn, $bNetID)
		{
			$mostRecent = null;
			
			if ($bNetID != "") //valid username
			{
				/* Select the most recent from this applicant */
				$sql = $conn->prepare("SELECT * FROM applications WHERE Applicant = :bNetID ORDER BY Date DESC LIMIT 1");
				$sql->bindParam(':bNetID', $bNetID);
				
					/* run the prepared query */
				$sql->execute();
				$res = $sql->fetchAll();
				
				if($res != null)
				{
					//echo "i is ".$i.".";
					$mostRecent = new Application($res[0]); //initialize
					
					$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
					$sql->bindParam(':id', $mostRecent->id);
					/* run the prepared query */
					$sql->execute();
					$resBudget = $sql->fetchAll();
					
					$mostRecent->budget = $resBudget;
				}
				
				
				/* Close finished query and connection */
				$sql = null;
				
				/* return value */
				return $mostRecent;
			}
			
		}
	}
	
	/* Returns number of applications that this user(via email address) needs to sign (for department chairs) */
	if(!function_exists('getNumberOfApplicationsToSign')){
		function getNumberOfApplicationsToSign($conn, $email)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND DepartmentChairEmail != Email AND Approved IS NULL");
				$sql->bindParam(':dEmail', $email);
				$sql->execute();
				$res = $sql->fetchAll();
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				return $res[0][0];
			}
		}
	}
	
	/*return an array of the maximum lengths of every column in the applications table*/
	if(!function_exists('getApplicationsMaxLengths')){
		function getApplicationsMaxLengths($conn)
		{
			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = 'hige' AND table_name = 'applications'");
			$sql->execute();
			$res = $sql->fetchAll();
					
			/* Close finished query and connection */
			$sql = null;
			
			return $res;
		}
	}
	
	/*return an array of the maximum lengths of every column in the applications_budgets table*/
	if(!function_exists('getApplicationsBudgetsMaxLengths')){
		function getApplicationsBudgetsMaxLengths($conn)
		{
			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = 'hige' AND table_name = 'applications_budgets'");
			$sql->execute();
			$res = $sql->fetchAll();
					
			/* Close finished query and connection */
			$sql = null;
			
			return $res;
		}
	}
	
	/*Update an application to be approved*/
	if(!function_exists('approveApplication')){
		function approveApplication($conn, $id, $email, $eb, $amount)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET Approved = 1, AmountAwarded = :aw WHERE ID = :id");
				$sql->bindParam(':aw', $amount);
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
				approvalEmail($email, $eb);
				
			}
		}
	}
	
	
	/*Update a FU Report to be approved*/
	if(!function_exists('approveFU')){
		function approveFU($conn, $id, $email, $eb)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE follow_up_reports SET Approved = 1 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
				approvalEmail($email, $eb);
				
			}
		}
	}
	
	/*Update a FU Report to be denied*/
	if(!function_exists('denyFU')){
		function denyFU($conn, $id, $email, $eb)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE follow_up_reports SET Approved = 0 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
				denialEmail($email, $eb);
			}
		}
	}
	
	
	/*Update an application to be put on hold*/
	if(!function_exists('holdApplication')){
		function holdApplication($conn, $id, $email, $eb)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET OnHold = 1 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
				onHoldEmail($email, $eb);
				
			}
		}
	}
	
	/*Update an application to be signed*/
	if(!function_exists('signApplication')){
		function signApplication($conn, $id, $signature)
		{
			if ($id != "" && $signature != "") //valid application id & sig
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET DepartmentChairSignature = :sig WHERE ID = :id");
				$sql->bindParam(':sig', $signature);
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	
	
	/*Update a follow-up-report to be approved (need application's ID)*/
	if(!function_exists('approveFollowUpReport')){
		function approveFollowUpReport($conn, $id)
		{
			if ($id != "") //valid report ID
			{
				/*Update any follow-up-report with the given id*/
				$sql = $conn->prepare("UPDATE follow_up_reports SET Approved = 1 WHERE ApplicationID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	/*Remove a follow-up report (useful if some information is incorrect) (need application's ID)*/
	if(!function_exists('removeFollowUpReport')){
		function removeFollowUpReport($conn, $id)
		{
			if ($id != "") //valid report ID
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("DELETE FROM follow_up_reports WHERE ApplicationID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				/* Close finished query and connection */
				$sql = null;
			}
		}
	}
	
	
	
	/*
	Insert an application into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use. If $updating is true, update this entry rather than inserting a new one
	Most fields are self-explanatory. It's worth mentioning that $budgetArray is a 2-dimensional array of expenses.
		$budgetArray[i][0] is the name of the expense
		$budgetArray[i][1] is the comment on the expense
		$budgetArray[i][2] is the actual cost
	return new application ID if EVERYTHING was successful, otherwise 0
	*/
	if(!function_exists('insertApplication')){
		function insertApplication($conn, $updating, $broncoNetID, $name, $email, $department, $deptChairEmail, $travelFrom, $travelTo,
			$activityFrom, $activityTo, $title, $destination, $amountRequested, $purpose1, $purpose2, $purpose3,
			$purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetArray)
		{
			//echo "Dates: ".$travelFrom.",".$travelTo.",".$activityFrom.",".$activityTo.".";
			
			if(!$updating)
			{
				//First, add this user to the applicants table IF they don't already exist
				insertApplicantIfNew($conn, $broncoNetID);
			}
			
			
			/*Server-Side validation!*/
			$valid = true; //start valid, turn false if anything is wrong!
			$newAppID = 0; //set this to the new application's ID if successful
			
			/*Sanitize everything*/
			try
			{
				$name = trim(filter_var($name, FILTER_SANITIZE_STRING));
				$email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
				$department = trim(filter_var($department, FILTER_SANITIZE_STRING));
				//$departmentMailStop = filter_var($departmentMailStop, FILTER_SANITIZE_NUMBER_INT);
				$travelFrom = strtotime($travelFrom);
				$travelTo = strtotime($travelTo);
				$title = trim(filter_var($title, FILTER_SANITIZE_STRING));
				$activityFrom = strtotime($activityFrom);
				$activityTo = strtotime($activityTo);
				$destination = trim(filter_var($destination, FILTER_SANITIZE_STRING));
				//$amountRequested = filter_var($amountRequested, FILTER_SANITIZE_NUMBER_INT);//needs to be DECIMAL, NOT INT
				$purpose1 = filter_var($purpose1, FILTER_SANITIZE_NUMBER_INT);
				$purpose2 = filter_var($purpose2, FILTER_SANITIZE_NUMBER_INT);
				$purpose3 = filter_var($purpose3, FILTER_SANITIZE_NUMBER_INT);
				$purpose4Other = trim(filter_var($purpose4Other, FILTER_SANITIZE_STRING));
				$otherFunding = trim(filter_var($otherFunding, FILTER_SANITIZE_STRING));
				$proposalSummary = trim(filter_var($proposalSummary, FILTER_SANITIZE_STRING));
				$goal1 = filter_var($goal1, FILTER_SANITIZE_NUMBER_INT);
				$goal2 = filter_var($goal2, FILTER_SANITIZE_NUMBER_INT);
				$goal3 = filter_var($goal3, FILTER_SANITIZE_NUMBER_INT);
				$goal4 = filter_var($goal4, FILTER_SANITIZE_NUMBER_INT);
				$deptChairEmail = trim(filter_var($deptChairEmail, FILTER_SANITIZE_EMAIL));
				$nextCycle = filter_var($nextCycle, FILTER_SANITIZE_NUMBER_INT);
				
				//echo "Dates: ".$travelFrom.",".$travelTo.",".$activityFrom.",".$activityTo.".";
				
				/*go through budget array*/
				foreach($budgetArray as $i)
				{
					if(!empty($i))
					{
						//echo 'Budget array at ' . $i[0];
						$i[0] = trim(filter_var($i[0], FILTER_SANITIZE_STRING));
						$i[1] = trim(filter_var($i[1], FILTER_SANITIZE_STRING));
					}
				}
				
			}
			catch(Exception $e)
			{
				echo "Application Sanitization Error: " . $e->getMessage();
				$valid = false;
			}
			
			/*Now validate everything that needs it*/
			if($valid)
			{
				list($em, $domain) = explode('@', $deptChairEmail);

				if (!strstr(strtolower($domain), "wmich")) {
					header('Location: ../application.php?error=email');
					$valid = false;
				}
				/*Make sure necessary strings aren't empty*/
				if($name === '' || $email === '' || $department === '' || $title === '' || $proposalSummary === '' || $deptChairEmail === '')
				{
					header('Location: ../application.php?error=emptystring');
					$valid = false;
				}
				/*Make sure dates are acceptable*/
				if($travelTo < $travelFrom || $activityTo < $activityFrom || $activityFrom < $travelFrom || $activityTo > $travelTo)
				{
					header('Location: ../application.php?error=dates');
					$valid = false;
				}
				/*Make sure emails are correct format*/
				if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($deptChairEmail, FILTER_VALIDATE_EMAIL))
				{
					header('Location: ../application.php?error=emailformat');
					$valid = false;
				}
				/*Make sure cycle is allowed*/
				$lastApprovedApp = getMostRecentApprovedApplication($conn, $broncoNetID);
				if($lastApprovedApp != null) //if a previous application exists
				{
					$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateS);
					$lastCycle = getCycleName($lastDate, $lastApprovedApp->nextCycle, false);
					
					$curCycle = getCycleName(DateTime::createFromFormat('Y/m/d', date("Y/m/d")), $nextCycle, false);
					
					$lastApproved = areCyclesFarEnoughApart($lastCycle, $curCycle); //check cycle age

					if(!$lastApproved) 
					{
						header('Location: ../application.php?error=cycle');
						$valid = false;
					}
				}
				
				/*go through budget array*/
				foreach($budgetArray as $i)
				{
					if(!empty($i))
					{
						if($i[0] === '' || $i[1] === '')
						{
							header('Location: ../application.php?error=emptystring');
							$valid = false;
							break;
						}
					}
				}
			}
			
			/*Now insert new application into database*/
			if($valid)
			{
				try
				{
					//Get dates
					$curDate = date("Y/m/d");
					$travelFromDate = date("Y-m-d", $travelFrom);
					$travelToDate = date("Y-m-d", $travelTo);
					$activityFromDate = date("Y-m-d", $activityFrom);
					$activityToDate = date("Y-m-d", $activityTo);
					
					$conn->beginTransaction(); //begin atomic transaction
					//echo "Dates: ".date("Y-m-d",$travelFrom).",".date("Y-m-d",$travelTo).",".date("Y-m-d",$activityFrom).",".date("Y-m-d",$activityTo).".";
					/*Prepare the query*/
					$sql = $conn->prepare("INSERT INTO applications(Applicant, Date, Name, Department, Email, Title, TravelStart, TravelEnd, EventStart, EventEnd, Destination, AmountRequested, 
						IsResearch, IsConference, IsCreativeActivity, IsOtherEventText, OtherFunding, ProposalSummary, FulfillsGoal1, FulfillsGoal2, FulfillsGoal3, FulfillsGoal4, DepartmentChairEmail, NextCycle) 
						VALUES(:applicant, :date, :name, :department, :email, :title, :travelstart, :travelend, :eventstart, :eventend, :destination, :amountrequested, 
						:isresearch, :isconference, :iscreativeactivity, :isothereventtext, :otherfunding, :proposalsummary, :fulfillsgoal1, :fulfillsgoal2, :fulfillsgoal3, :fulfillsgoal4, :departmentchairemail, :nextcycle)");
					$sql->bindParam(':applicant', $broncoNetID);
					$sql->bindParam(':date', $curDate); //create a new date right when inserting to save current time
					$sql->bindParam(':name', $name);
					$sql->bindParam(':department', $department);
					//$sql->bindParam(':mailstop', $departmentMailStop);
					$sql->bindParam(':email', $email);
					$sql->bindParam(':title', $title);
					$sql->bindParam(':travelstart', $travelFromDate);
					$sql->bindParam(':travelend', $travelToDate);
					$sql->bindParam(':eventstart', $activityFromDate);
					$sql->bindParam(':eventend', $activityToDate);
					$sql->bindParam(':destination', $destination);
					$sql->bindParam(':amountrequested', $amountRequested);
					$sql->bindParam(':isresearch', $purpose1);
					$sql->bindParam(':isconference', $purpose2);
					$sql->bindParam(':iscreativeactivity', $purpose3);
					$sql->bindParam(':isothereventtext', $purpose4Other);
					$sql->bindParam(':otherfunding', $otherFunding);
					$sql->bindParam(':proposalsummary', $proposalSummary);
					$sql->bindParam(':fulfillsgoal1', $goal1);
					$sql->bindParam(':fulfillsgoal2', $goal2);
					$sql->bindParam(':fulfillsgoal3', $goal3);
					$sql->bindParam(':fulfillsgoal4', $goal4);
					$sql->bindParam(':departmentchairemail', $deptChairEmail);
					$sql->bindParam(':nextcycle', $nextCycle);
					
					if ($sql->execute() === TRUE) //query executed correctly
					{
						$conn->commit();//commit first part of transaction (we can still rollback if something ahead fails)
						
						/*get the application ID of the just-added application*/
						$sql = $conn->prepare("select max(ID) from applications where Applicant = :applicant LIMIT 1");
						$sql->bindParam(':applicant', $broncoNetID);
						$sql->execute();
						$newAppID = $sql->fetchAll()[0][0];//now we have the current ID!
						
						/*go through budget array*/
						foreach($budgetArray as $i)
						{
							if(!empty($i))
							{
								$sql = $conn->prepare("INSERT INTO applications_budgets(ApplicationID, Name, Cost, Comment) VALUES (:appID, :name, :cost, :comment)");
								$sql->bindParam(':appID', $newAppID);
								$sql->bindParam(':name', $i[0]);
								$sql->bindParam(':cost', $i[2]);
								$sql->bindParam(':comment', $i[1]);
								
								if ($sql->execute() === TRUE) //query executed correctly
								{
									$conn->commit(); //commit next part of transaction
								}
								else //query failed
								{
									$conn->rollBack(); //rollBack the transaction
									$valid = false;
								}
							}
						}
					} 
					else //query failed
					{
						$valid = false;
					}
				}
				catch(Exception $e)
				{
					echo "Error inserting application into database: " . $e->getMessage();
					$valid = false;
				}
			}
			
			if($valid) //if successful, return new application ID
			{
				return $newAppID;
			}
			else //otherwise return 0
			{
				return 0;
			}
		}
	}
	
	
	
	/*
	Insert a follow-up-report into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use.
	Fields: DB connection, application ID, travel start & end dates, activity start & end dates, project summary, and total award spent
	return 1 if insert is successful, 0 otherwise
	*/
	if(!function_exists('insertFollowUpReport')){
		function insertFollowUpReport($conn, $appID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $totalAwardSpent)
		{
			/*Server-Side validation!*/
			$valid = true; //start valid, turn false if anything is wrong!
			
			/*Sanitize everything*/
			try
			{
				$travelFrom = strtotime($travelFrom);
				$travelTo = strtotime($travelTo);
				$activityFrom = strtotime($activityFrom);
				$activityTo = strtotime($activityTo);
				$projectSummary = trim(filter_var($projectSummary, FILTER_SANITIZE_STRING));
				//$totalAwardSpent = filter_var($totalAwardSpent, FILTER_SANITIZE_NUMBER_INT);//needs to be DECIMAL, NOT INT
				
				//echo "Dates: ".$travelFrom.",".$travelTo.",".$activityFrom.",".$activityTo.".";
			}
			catch(Exception $e)
			{
				echo "Follow-Up Report Sanitization Error: " . $e->getMessage();
				$valid = false;
			}
			
			/*Now validate everything that needs it*/
			if($valid)
			{
				/*Make sure necessary strings aren't empty*/
				if($projectSummary === '')
				{
					echo "Follow-Up Report Validation Error: Empty String Given!";
					$valid = false;
				}
				/*Make sure dates are acceptable*/
				if($travelTo < $travelFrom || $activityTo < $activityFrom || $activityFrom < $travelFrom || $activityTo > $travelTo)
				{
					echo "Follow-Up Report Validation Error: Invalid Date Given!";
					$valid = false;
				}
			}
			
			/*Now insert new follow-up report into database*/
			if($valid)
			{
				try
				{
					$conn->beginTransaction(); //begin atomic transaction
					//echo "Dates: ".date("Y-m-d",$travelFrom).",".date("Y-m-d",$travelTo).",".date("Y-m-d",$activityFrom).",".date("Y-m-d",$activityTo).".";
					/*Prepare the query*/
					$sql = $conn->prepare("INSERT INTO follow_up_reports(ApplicationID, TravelStart, TravelEnd, EventStart, EventEnd, ProjectSummary, TotalAwardSpent, Date) 
						VALUES(:applicationid, :travelstart, :travelend, :eventstart, :eventend, :projectsummary, :totalawardspent, :date)");
					$sql->bindParam(':applicationid', $appID);
					$sql->bindParam(':travelstart', date("Y-m-d", $travelFrom));
					$sql->bindParam(':travelend', date("Y-m-d", $travelTo));
					$sql->bindParam(':eventstart', date("Y-m-d", $activityFrom));
					$sql->bindParam(':eventend', date("Y-m-d", $activityTo));
					$sql->bindParam(':projectsummary', $projectSummary);
					$sql->bindParam(':totalawardspent', $totalAwardSpent);
					$sql->bindParam(':date', date("Y/m/d")); //create a new date right when inserting to save current time
					
					if ($sql->execute() === TRUE) //query executed correctly
					{
						$conn->commit();//commit transaction (probably don't need transactions for this since it is only 1 command)
					} 
					else //query failed
					{
						$valid = false;
					}
				}
				catch(Exception $e)
				{
					echo "Error inserting follow-up report into database: " . $e->getMessage();
					$valid = false;
				}
			}
			
			if($valid) //if successful, return 1
			{
				return $appID;
			}
			else //otherwise return 0
			{
				return 0;
			}
		}
	}
	
	
	
	
	/*allow for atomic transactions (to ensure one insert only occurs when another has succeeded, so that either both or neither of them succeed)
	found at http://php.net/manual/en/pdo.begintransaction.php*/
	if(!class_exists('AtomicPDO')){
		class AtomicPDO extends PDO
		{
			protected $transactionCounter = 0;

			public function beginTransaction()
			{
				if (!$this->transactionCounter++) {
					return parent::beginTransaction();
				}
				$this->exec('SAVEPOINT trans'.$this->transactionCounter);
				return $this->transactionCounter >= 0;
			}

			public function commit()
			{
				if (!--$this->transactionCounter) {
					return parent::commit();
				}
				return $this->transactionCounter >= 0;
			}

			public function rollback()
			{
				if (--$this->transactionCounter) {
					$this->exec('ROLLBACK TO trans'.($this->transactionCounter + 1));
					return true;
				}
				return parent::rollback();
			}
			
		}
	}
	
?>