<?php
	include_once(dirname(__FILE__) . "/../include/classDefinitions.php");
	include_once('cycles.php');

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

	/*Save a new email message to the database, return true if successful or false otherwise*/
	if(!function_exists('saveEmail')) {
		function saveEmail($conn, $appID, $subject, $message)
		{
			$curTime = date('Y-m-d H:i:s');//get current timestamp
						
			/*Prepare the query*/
			$sql = $conn->prepare("INSERT INTO emails(ApplicationID, Subject, Message, Time) VALUES(:applicationid, :subject, :message, :time)");
			$sql->bindParam(':applicationid', $appID);
			$sql->bindParam(':subject', $subject);
			$sql->bindParam(':message', $message);
			$sql->bindParam(':time', $curTime);
			
			$res = $sql->execute();
			/* Close finished query and connection */
			$sql = null;
			/*return confirmation*/
			return $res;
		}
	}

	/*Return all emails for a given application*/
	if(!function_exists('getEmails')) {
		function getEmails($conn, $appID)
		{
			$curTime = date('Y-m-d H:i:s');//get current timestamp
						
			/*Prepare the query*/
			$sql = $conn->prepare("Select * FROM emails WHERE ApplicationID = :applicationid");
			$sql->bindParam(':applicationid', $appID);
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			/* Close finished query and connection */
			$sql = null;
			/*return confirmation*/
			return $res;
		}
	}

	/*Return staff notes for a given application*/
	if(!function_exists('getStaffNotes')) {
		function getStaffNotes($conn, $appID)
		{
			/*Prepare the query*/
			$sql = $conn->prepare("Select * FROM notes WHERE ApplicationID = :applicationid");
			$sql->bindParam(':applicationid', $appID);
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			/* Close finished query and connection */
			$sql = null;
			/*return confirmation*/
			if(!empty($res)){return $res[0];}
			else{return null;}
		}
	}

	/*Save staff notes for a certain application*/
	if(!function_exists('saveStaffNotes')){
		function saveStaffNotes($conn, $appID, $note)
		{
			if ($appID != "") //valid app id
			{
				/*Update note or insert if new*/
				$sql = $conn->prepare("INSERT INTO notes(ApplicationID, Note) VALUES(:applicationid, :note) ON DUPLICATE KEY UPDATE Note = :updatenote");
				$sql->bindParam(':applicationid', $appID);
				$sql->bindParam(':note', $note);
				$sql->bindParam(':updatenote', $note); //have to bind to different name despite being the same variable
				$sql->execute();

				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
			else{return null;}
		}
	}
	
	/* Returns array of all administrators */
	if(!function_exists('getAdministrators')) {
		function getAdministrators($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM administrators");
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
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
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
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
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
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
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys

			if(!empty($res))
			{
				/*create application object*/
				$application = new Application($res[0]);
				
				$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
				$sql->bindParam(':id', $application->id);
				/* run the prepared query */
				$sql->execute();
				$resBudget = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				$application->budget = $resBudget;
				
				/* Close finished query and connection */
				$sql = null;
				
				/* return application */
				return $application;
			}
			else
			{
				return null;
			}
		}
	}
	
	/*Returns a single final report for a specified application ID*/
	if(!function_exists('getFinalReport')) {
		function getFinalReport($conn, $appID)
		{
			$sql = $conn->prepare("Select * FROM final_reports WHERE ApplicationID = :appID");
			$sql->bindParam(':appID', $appID);
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if(!empty($res))
			{
				/*create application object*/
				$FinalReport = new FinalReport($res[0]);
				
				
				/* Close finished query and connection */
				$sql = null;
				
				/* return FinalReport object */
				return $FinalReport;
			}
			else
			{
				return null;
			}
			
		}
	}
	/* Returns array of all applications that still need to be signed by a specific user(via email address)
	Note: don't include ones that have already been signed, and ones that have the same broncoNetID as the submitter
	(you shouldn't be able to sign your own application)
	*/
	if(!function_exists('getApplicationsToSign')) {
		function getApplicationsToSign($conn, $email, $broncoNetID)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select * FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND Applicant != :broncoNetID AND Status = 'Pending'");
				$sql->bindParam(':dEmail', $email);
				$sql->bindParam(':broncoNetID', $broncoNetID);
				
				/* run the prepared query */
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
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
					$resBudget = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
					
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
	/* Returns array of all applications signed by this email address's owner*/
	if(!function_exists('getSignedApplications')) {
		function getSignedApplications($conn, $email)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select * FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NOT NULL AND DepartmentChairEmail != Email");
				$sql->bindParam(':dEmail', $email);
				
				/* run the prepared query */
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
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
					$resBudget = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
					
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

	/* Returns number of applications that this user(via email address) needs to sign (for department chairs) */
	if(!function_exists('getNumberOfApplicationsToSign')){
		function getNumberOfApplicationsToSign($conn, $email, $broncoNetID)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND Applicant != :broncoNetID AND Status = 'Pending'");
				$sql->bindParam(':dEmail', $email);
				$sql->bindParam(':broncoNetID', $broncoNetID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				return $res[0][0];
			}
		}
	}

	/* Returns number of previously signed applications from this dept. chair's email address*/
	if(!function_exists('getNumberOfSignedApplications')){
		function getNumberOfSignedApplications($conn, $email)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NOT NULL AND DepartmentChairEmail != Email");
				$sql->bindParam(':dEmail', $email);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				return $res[0][0];
			}
		}
	}

	
	/* Returns array of all applications for a specified BroncoNetID, or ALL applications if no ID is provided */
	if(!function_exists('getApplications')) {
		function getApplications($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid username
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select * FROM applications WHERE Applicant = :broncoNetID");
				$sql->bindParam(':broncoNetID', $broncoNetID);
			}
			else //no username
			{
				/* Select all applications */
				$sql = $conn->prepare("Select * FROM applications");
			}
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
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
				$resBudget = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
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
	/* Returns number of all applications for a specified BroncoNetID, or number of ALL applications if no ID is provided */
	if(!function_exists('getNumberOfApplications')) {
		function getNumberOfApplications($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid username
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Applicant = :broncoNetID");
				$sql->bindParam(':broncoNetID', $broncoNetID);
			}
			else //no username
			{
				/* Select all applications */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications");
			}
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			/* Close finished query and connection */
			$sql = null;
			
			return $res[0][0];
		}
	}
	
	/* Returns array of application approvers */
	if(!function_exists('getApplicationApprovers')) {
		function getApplicationApprovers($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM application_approval");
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
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
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/* Returns array of final report approvers */
	if(!function_exists('getFinalReportApprovers')) {
		function getFinalReportApprovers($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM final_report_approval");
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
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
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
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
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
			}
		}
	}
	
	/* Add a final report approver to the final_report_approval table */
	if(!function_exists('addFinalReportApprover')){
		function addFinalReportApprover($conn, $broncoNetID, $name)
		{
			if ($broncoNetID != "" && $name != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("INSERT INTO final_report_approval(BroncoNetID, Name) VALUES(:id, :name)");
				$sql->bindParam(':id', $broncoNetID);
				$sql->bindParam(':name', $name);
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
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
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
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
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
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
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
			}
		}
	}
	
	/* Remove a final report approver to the final_report_approval table */
	if(!function_exists('removeFinalReportApprover')){
		function removeFinalReportApprover($conn, $broncoNetID)
		{
			if ($broncoNetID != "") //valid params
			{
				/* Prepare & run the query */
				$sql = $conn->prepare("DELETE FROM final_report_approval WHERE BroncoNetID = :id");
				$sql->bindParam(':id', $broncoNetID);
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
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
				$ret = $sql->execute();
				/* Close finished query and connection */
				$sql = null;
				return $ret;//return result
			}
		}
	}
	



	/*Checks if a user is an administrator-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	if(!function_exists('isAdministrator')) {
		function isAdministrator($conn, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			$adminList = getAdministrators($conn);//grab admin list
			
			foreach($adminList as $i) //loop through admins
			{
				$newID = $i[0];
				if(strcmp($newID, $broncoNetID) == 0)
				{
					$is = true;
					break; //no need to continue loop
				}
			}
			return $is;
		}
	}
	
	/*Checks if a user is an application approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	if(!function_exists('isApplicationApprover')) {
		function isApplicationApprover($conn, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			$approverList = getApplicationApprovers($conn);//grab application approver list
			
			foreach($approverList as $i) //loop through approvers
			{
				$newID = $i[0];
				if($newID == $broncoNetID)
				{
					$is = true;
					break; //no need to continue loop
				}
			}
			return $is;
		}
	}
	
	/*Checks if a user is a final report approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	if(!function_exists('isFinalReportApprover')) {
		function isFinalReportApprover($conn, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			$approverList = getFinalReportApprovers($conn);//grab final report approver list
			
			foreach($approverList as $i) //loop through approvers
			{
				$newID = $i[0];
				if($newID == $broncoNetID)
				{
					$is = true;
					break; //no need to continue loop
				}
			}
			return $is;
		}
	}
	
	/*Checks if a user is a committee member-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	if(!function_exists('isCommitteeMember')) {
		function isCommitteeMember($conn, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			$committeeList = getCommittee($conn);//grab committee member list
			
			foreach($committeeList as $i) //loop through committee members
			{
				$newID = $i[0];
				if($newID == $broncoNetID)
				{
					$is = true;
					break; //no need to continue loop
				}
			}
			return $is;
		}
	}
	
	/*Checks if a user is allowed to create an application
	Rules:
	2. Must not have a pending application
	3. Must not have received funding within the past year (use rules from cycles.php)
	4. Must not be an admin, committee member, application approver, or final report approver (HIGE Staff)
	&nextCycle = false if checking current cycle, or true if checking next cycle*/
	if(!function_exists('isUserAllowedToCreateApplication')) {
		function isUserAllowedToCreateApplication($conn, $broncoNetID, $nextCycle)
		{
			//make sure user is not part of HIGE staff
			if(!isCommitteeMember($conn, $broncoNetID) && !isApplicationApprover($conn, $broncoNetID) && !isAdministrator($conn, $broncoNetID) && !isFinalReportApprover($conn, $broncoNetID))
			{
				$lastApproved = false; //set to true if last approved application was long enough ago
				$lastApprovedApp = getMostRecentApprovedApplication($conn, $broncoNetID);

				//echo "Last approved: " .$lastApprovedApp->id;
				
				if($lastApprovedApp != null) //if a previous application exists
				{
					$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateSubmitted);
					$lastCycle = getCycleName($lastDate, $lastApprovedApp->nextCycle, false);
					
					$curCycle = getCycleName(DateTime::createFromFormat('Y/m/d', date("Y/m/d")), $nextCycle, false);

					//echo "cycles: ".$lastCycle.", ".$curCycle;
					
					$lastApproved = areCyclesFarEnoughApart($lastCycle, $curCycle); //check cycles in function down below

					//echo "last approved: " .$lastApproved;
				}
				else //no previous application
				{
					$lastApproved = true;
				}
				
				if(!hasPendingApplication($conn, $broncoNetID) && !hasApplicationOnHold($conn, $broncoNetID) && $lastApproved)
				{
					return true;
				}
				else 
				{
					return false; //necessary to specify true/false because of dumb php rules :(
				}
			}
			else //user is part of HIGE staff, so they cannot apply
			{
				return false;
			}
		}
	}

	/*Checks if a user is allowed to create a final report for a specified appID
	Rules:
	Application must not already have a final report
	Application must belong to the user
	Application must have 'Approved' status*/
	if(!function_exists('isUserAllowedToCreateFinalReport')) {
		function isUserAllowedToCreateFinalReport($conn, $broncoNetID, $appID)
		{
			if(doesUserOwnApplication($conn, $broncoNetID, $appID) && !getFinalReport($conn, $appID) && isApplicationApproved($conn, $appID))
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
	}
	
	/*Checks if a user is allowed to freely see applications. ALSO USED FOR VIEWING FINAL REPORTS
	Rules: Must be an application approver, final report approver, administrator, or a committee member*/
	if(!function_exists('isUserAllowedToSeeApplications')) {
		function isUserAllowedToSeeApplications($conn, $broncoNetID)
		{
				
			if(isCommitteeMember($conn, $broncoNetID) || isApplicationApprover($conn, $broncoNetID) || isAdministrator($conn, $broncoNetID) || isFinalReportApprover($conn, $broncoNetID))
			{
				return true;
			}
			else 
			{
				return false; //necessary to specify true/false because of dumb php rules :(
			}
		}
	}




	/* Returns true if the given email == the deptChairEmail for a given app ID, or false otherwise */
	if(!function_exists('isUserAllowedToSignApplication')){
		function isUserAllowedToSignApplication($conn, $email, $appID, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			
			if ($email != "" && $appID != "") //valid email & ID
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's broncoNetID == this broncoNetID*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND Applicant != :broncoNetID");
				$sql->bindParam(':dEmail', $email);
				$sql->bindParam(':appID', $appID);
				$sql->bindParam(':broncoNetID', $broncoNetID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				if($res[0][0] > 0)//this is the correct user to sign
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}

	/* Returns true if this user signed a given application */
	if(!function_exists('hasUserSignedApplication')){
		function hasUserSignedApplication($conn, $email, $appID)
		{
			$is = false; //initialize boolean to false
			
			if ($email != "" && $appID != "") //valid email & ID
			{
				/* Only count applications meant for this person that HAVE already been signed */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NOT NULL");
				$sql->bindParam(':dEmail', $email);
				$sql->bindParam(':appID', $appID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				if($res[0][0] > 0)//this is the correct user to sign
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}

	/* Returns true if this user is the department chair specified by an application */
	if(!function_exists('isUserDepartmentChair')){
		function isUserDepartmentChair($conn, $email, $appID, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			
			if ($email != "" && $appID != "") //valid email & ID
			{
				/* Only count applications meant for this person */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND Applicant != :broncoNetID");
				$sql->bindParam(':dEmail', $email);
				$sql->bindParam(':appID', $appID);
				$sql->bindParam(':broncoNetID', $broncoNetID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				if($res[0][0] > 0)//this is the correct user to sign
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}

	/*returns true if the user owns this application, or false if they don't*/
	if(!function_exists('doesUserOwnApplication')){
		function doesUserOwnApplication($conn, $broncoNetID, $appID)
		{
			$is = false; //initialize boolean to false
			
			if ($broncoNetID != "" && $appID != "") //valid broncoNetID & appID
			{
				/* Only count applications with the right ID that this user has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND Applicant = :broncoNetID");
				$sql->bindParam(':appID', $appID);
				$sql->bindParam(':broncoNetID', $broncoNetID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				if($res[0][0] > 0)//this is the correct user to sign
				{
					$is = true;
				}
			}
			
			return $is;
		}
	}

	
	
	
	/* Returns true if applicant's application has been approved, or false otherwise 
	TODO: make this only work for applications that do not also have an approved final report!*/
	if(!function_exists('isApplicationApproved')){
		function isApplicationApproved($conn, $appID)
		{
			$is = false;
			
			if ($appID != "") //valid Id
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :id AND Status = 'Approved'");
				$sql->bindParam(':id', $appID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
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
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
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
		function hasPendingApplication($conn, $broncoNetID)
		{
			$ret = false;
			
			if ($broncoNetID != "") //valid username
			{
				/* Select only pending applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Status = 'Pending' AND Applicant = :broncoNetID");
				$sql->bindParam(':broncoNetID', $broncoNetID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$ret = true;
				}
			}
			
			return $ret;
		}
	}

	/* Returns true if this applicant has an application on hold, or false otherwise */
	if(!function_exists('hasApplicationOnHold')){
		function hasApplicationOnHold($conn, $broncoNetID)
		{
			$ret = false;
			
			if ($broncoNetID != "") //valid username
			{
				/* Select only pending applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Status = 'Hold' AND Applicant = :broncoNetID");
				$sql->bindParam(':broncoNetID', $broncoNetID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$ret = true;
				}
			}
			
			return $ret;
		}
	}
	
	/* Returns true if this applicant has a final report already created, or false otherwise */
	if(!function_exists('hasFinalReport')){
		function hasFinalReport($conn, $appID)
		{
			$ret = false;
			
			if ($appID != "") //valid username
			{
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM final_reports WHERE ApplicationID = :appID");
				$sql->bindParam(':appID', $appID);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
				if($res[0][0] > 0) //at least one result
				{
					$ret = true;
				}
			}
			
			return $ret;
		}
	}
	
	/*Get the most recently approved application of a user- return null if none*/
	if(!function_exists('getMostRecentApprovedApplication')){
		function getMostRecentApprovedApplication($conn, $broncoNetID)
		{
			$mostRecent = null;
			
			if ($broncoNetID != "") //valid username
			{
				/* Select the most recent from this applicant */
				$sql = $conn->prepare("SELECT * FROM applications WHERE Applicant = :broncoNetID AND Status = 'Approved' ORDER BY Date DESC LIMIT 1");
				$sql->bindParam(':broncoNetID', $broncoNetID);
				
					/* run the prepared query */
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				//echo "result: " .$res[0][0];

				if($res != null)
				{
					//echo "i is ".$i.".";
					$mostRecent = new Application($res[0]); //initialize

					//echo "result: " .$mostRecent->id;
					
					$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
					$sql->bindParam(':id', $mostRecent->id);
					/* run the prepared query */
					$sql->execute();
					$resBudget = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
					
					$mostRecent->budget = $resBudget;
				}
				
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo "Final res: ".$mostRecent->id;

				/* return value */
				return $mostRecent;
			}
			
		}
	}

	/*Get all past approved cycles for this user- return null if none*/
	if(!function_exists('getPastApprovedCycles')){
		function getPastApprovedCycles($conn, $broncoNetID)
		{
			$pastCycles = null;
			
			if ($broncoNetID != "") //valid username
			{
				/* Select all dates from past approved applications */
				$sql = $conn->prepare("SELECT Date, NextCycle FROM applications WHERE Applicant = :broncoNetID AND Status = 'Approved'");
				$sql->bindParam(':broncoNetID', $broncoNetID);
				
					/* run the prepared query */
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys

				/* Close finished query and connection */
				$sql = null;
				

				if($res != null)
				{
					$pastCycles = []; //initialize array

					foreach($res as $i) //go through all the dates, grab the cycles
					{
						if(!empty($i))
						{
							array_push($pastCycles, getCycleName(DateTime::createFromFormat('Y-m-d', $i[0]), $i[1], false)); //push the cycle name to the array
						}
					}

					$pastCycles = sortCycles($pastCycles); //sort cycles in descending order
				}
				
				
				//echo "Final res: ".$pastCycles->id;

				/* return value */
				return $pastCycles;
			}
			
		}
	}
	
	/*return an array of the maximum lengths of every column in the applications table*/
	if(!function_exists('getApplicationsMaxLengths')){
		function getApplicationsMaxLengths($conn)
		{
			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';
			$settings = parse_ini_file($config_url);

			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = '" . $settings["database_name"] . "' AND table_name = 'applications'");
			$sql->execute();
			$res = $sql->fetchAll(); //return indexes AND names as keys
					
			/* Close finished query and connection */
			$sql = null;
			
			return $res;
		}
	}
	
	/*return an array of the maximum lengths of every column in the applications_budgets table*/
	if(!function_exists('getApplicationsBudgetsMaxLengths')){
		function getApplicationsBudgetsMaxLengths($conn)
		{
			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';
			$settings = parse_ini_file($config_url);

			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = '" . $settings["database_name"] . "' AND table_name = 'applications_budgets'");
			$sql->execute();
			$res = $sql->fetchAll(); //return indexes AND names as keys
					
			/* Close finished query and connection */
			$sql = null;
			
			return $res;
		}
	}

	/*return an array of the maximum lengths of every column in the final_reports table*/
	if(!function_exists('getFinalReportsMaxLengths')){
		function getFinalReportsMaxLengths($conn)
		{
			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';
			$settings = parse_ini_file($config_url);
			
			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = '" . $settings["database_name"] . "' AND table_name = 'final_reports'");
			$sql->execute();
			$res = $sql->fetchAll(); //return indexes AND names as keys
					
			/* Close finished query and connection */
			$sql = null;
			
			return $res;
		}
	}
	
	/*Update an application to be approved*/
	if(!function_exists('approveApplication')){
		function approveApplication($conn, $id, $amount)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET Status = 'Approved', AmountAwarded = :aw WHERE ID = :id");
				$sql->bindParam(':aw', $amount);
				$sql->bindParam(':id', $id);
				$sql->execute();

				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
		}
	}
	
	/*Update an application to be denied*/
	if(!function_exists('denyApplication')){
		function denyApplication($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET Status = 'Denied', AmountAwarded = 0 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();

				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
		}
	}
	
	
	/*Update an application to be put on hold*/
	if(!function_exists('holdApplication')){
		function holdApplication($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET Status = 'Hold', AmountAwarded = 0 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();

				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				/* Close finished query and connection */
				$sql = null;
				return $ret;
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

				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
		}
	}
	
	

	/*Update a Final Report to be approved*/
	if(!function_exists('approveFinalReport')){
		function approveFinalReport($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE final_reports SET Status = 'Approved' WHERE ApplicationID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
		}
	}

	/*Update a Final Report to be on hold*/
	if(!function_exists('holdFinalReport')){
		function holdFinalReport($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE final_reports SET Status = 'Hold' WHERE ApplicationID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
		}
	}
	
	
	/*
	Insert an application into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use. If $updating is true, update this entry rather than inserting a new one.
	Most fields are self-explanatory. It's worth mentioning that $budgetArray is a 2-dimensional array of expenses.
		$budgetArray[i][0] is the name of the expense
		$budgetArray[i][1] is the details on the expense
		$budgetArray[i][2] is the actual cost

	This function returns a data array; If the application is successfully inserted or updated, then data["success"] is set to true, and data["message"] is set to a confirmation message.
	Otherwise, data["success"] is set to false, and data["errors"] is set to an array of errors following the format of ["field", "message"], where field corresponds to one of the application's fields.
	
	*/
	if(!function_exists('insertApplication')){
		function insertApplication($conn, $updating, $updateID, $broncoNetID, $name, $email, $department, $deptChairEmail, $travelFrom, $travelTo,
			$activityFrom, $activityTo, $title, $destination, $amountRequested, $purpose1, $purpose2, $purpose3,
			$purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetArray)
		{

			$errors = array(); // array to hold validation errors
			$data = array(); // array to pass back data

			$newAppID = 0; //set this to the new application's ID if successful

			if(!$updating)
			{
				//First, add this user to the applicants table IF they don't already exist
				insertApplicantIfNew($conn, $broncoNetID);
			}
			
			/*Sanitize everything - keep quotes. This may be a bit redundant since PDO already sanitizes everything in prepared statements*/
			try
			{
				$name = trim(filter_var($name, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$email = trim(filter_var($email, FILTER_SANITIZE_EMAIL, FILTER_FLAG_NO_ENCODE_QUOTES));
				$department = trim(filter_var($department, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$title = trim(filter_var($title, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$destination = trim(filter_var($destination, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$purpose1 = filter_var($purpose1, FILTER_SANITIZE_NUMBER_INT);
				$purpose2 = filter_var($purpose2, FILTER_SANITIZE_NUMBER_INT);
				$purpose3 = filter_var($purpose3, FILTER_SANITIZE_NUMBER_INT);
				$purpose4Other = trim(filter_var($purpose4Other, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$otherFunding = trim(filter_var($otherFunding, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$proposalSummary = trim(filter_var($proposalSummary, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
				$goal1 = filter_var($goal1, FILTER_SANITIZE_NUMBER_INT);
				$goal2 = filter_var($goal2, FILTER_SANITIZE_NUMBER_INT);
				$goal3 = filter_var($goal3, FILTER_SANITIZE_NUMBER_INT);
				$goal4 = filter_var($goal4, FILTER_SANITIZE_NUMBER_INT);
				$deptChairEmail = trim(filter_var($deptChairEmail, FILTER_SANITIZE_EMAIL, FILTER_FLAG_NO_ENCODE_QUOTES));
				$nextCycle = filter_var($nextCycle, FILTER_SANITIZE_NUMBER_INT);
				
				//echo "Dates: ".$travelFrom.",".$travelTo.",".$activityFrom.",".$activityTo.".";
				
				/*go through budget array*/
				foreach($budgetArray as $i)
				{
					if(!empty($i))
					{
						//echo 'Budget array at ' . $i[0];
						if(isset($i["expense"])){ $i["expense"] = trim(filter_var($i["expense"], FILTER_SANITIZE_STRING)); }
						if(isset($i["details"])){ $i["details"] = trim(filter_var($i["details"], FILTER_SANITIZE_STRING)); }
					}
				}
				
			}
			catch(Exception $e)
			{
				$errors["other"] = "Exception when sanitizing fields!";
			}

			/*echo "Travel From:";
			var_dump($travelFrom);*/
			
			/*Now validate everything that needs it*/
			if(empty($errors)) //no errors yet
			{

				/*Make sure necessary strings aren't empty*/
				if($name === '')
				{
					$errors["name"] = "Name field is required.";
				}

				if($email === '')
				{
					$errors["email"] = "Email field is required.";
				}
				else if(!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					$errors["email"] = "Email address is invalid (invalid email format).";
				}

				if($department === '')
				{
					$errors["department"] = "Department field is required.";
				}
				if($title === '')
				{
					$errors["title"] = "Title field is required.";
				}
				if($destination === '')
				{
					$errors["destination"] = "Destination field is required.";
				}
				if($proposalSummary === '')
				{
					$errors["proposalSummary"] = "Proposal Summary field is required.";
				}

				if($deptChairEmail === '')
				{
					$errors["deptChairEmail"] = "Department Chair Email field is required.";
				}
				else if(!filter_var($deptChairEmail, FILTER_VALIDATE_EMAIL))
				{
					$errors["deptChairEmail"] = "Department chair's email address is invalid (invalid email format).";
				}
				else
				{
					list($em, $domain) = explode('@', $deptChairEmail);
					/*Make sure department chair's email address is a wmich address*/
					if (!strstr(strtolower($domain), "wmich.edu")) 
					{
						$errors["deptChairEmail"] = "Department chair's email address must be a wmich.edu address.";
					}
				}

				if($travelFrom == null || $travelFrom === '')
				{
					$errors["travelFrom"] = "Travel From field is required.";
				}
				if($travelTo == null || $travelTo === '')
				{
					$errors["travelTo"] = "Travel To field is required.";
				}
				if($activityFrom == null || $activityFrom === '')
				{
					$errors["activityFrom"] = "Activity From field is required.";
				}
				if($activityTo == null || $activityTo === '')
				{
					$errors["activityTo"] = "Activity To field is required.";
				}


				if($nextCycle === '')
				{
					$errors["cycleChoice"] = "Must choose a cycle.";
				}


				/*Make sure dates are acceptable*/
				if(!isset($errors["travelFrom"]) && !isset($errors["activityFrom"]))//making sure dates were set
				{
					if($travelFrom > $activityFrom)
					{
						$errors["travelFrom"] = "Travel dates are impossible (activity cannot start before travel!).";
					}
				}	
				if(!isset($errors["activityFrom"]) && !isset($errors["activityTo"]))//making sure dates were set
				{
					if($activityFrom > $activityTo)
					{
						$errors["activityFrom"] = "Travel dates are impossible (activity cannot end before it begins!).";
					}
				}
				if(!isset($errors["activityTo"]) && !isset($errors["travelTo"]))//making sure dates were set
				{
					if($activityTo > $travelTo)
					{
						$errors["activityTo"] = "Travel dates are impossible (travel cannot end before activity ends!).";
					}
				}

				/*Make sure at least one purpose is chosen*/
				if($purpose1 == 0 && $purpose2 == 0 && $purpose3 == 0 && $purpose4Other === '')
				{
					$errors["purpose"] = "At least one purpose must be selected.";
				}
				/*Make sure at least one goal is chosen*/
				if($goal1 == 0 && $goal2 == 0 && $goal3 == 0 && $goal4 == 0)
				{
					$errors["goal"] = "At least one goal must be selected.";
				}
				if ($amountRequested <= 0)
				{
					$errors["amountRequested"] = "Amount requested must be greater than $0.";
				}
			}

			/*Make sure cycle is allowed; ignore this check if an admin is updating*/
			if(!$updating)
			{
				$lastApprovedApp = getMostRecentApprovedApplication($conn, $broncoNetID);
				if($lastApprovedApp != null) //if a previous application exists
				{
					$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateSubmitted);
					$lastCycle = getCycleName($lastDate, $lastApprovedApp->nextCycle, false);
					
					$curCycle = getCycleName(DateTime::createFromFormat('Y/m/d', date("Y/m/d")), $nextCycle, false);
					
					$lastApproved = areCyclesFarEnoughApart($lastCycle, $curCycle); //check cycle age

					if(!$lastApproved) 
					{
						$errors["other"] = "Applicant is not allowed to apply for the specified cycle (not enough cycles have passed since last approved application).";
					}
				}
			}

			if(sizeof($budgetArray) != 0) //at least one budget item
			{
				/*go through budget array*/
				$index = 0; //index of budget item
				foreach($budgetArray as $item)
				{
					/*echo "Budget item: ";
					print_r($i);
					var_dump($i);*/

					if(!empty($item))
					{
						if(!isset($item["expense"]))
						{
							$errors["budgetArray ".($index+1)." expense"] = "Budget expense is required.";
						}
						else if($item["expense"] === '')
						{
							$errors["budgetArray ".($index+1)." expense"] = "Budget expense is required.";
						}

						if(!isset($item["details"]))
						{
							$errors["budgetArray ".($index+1)." details"] = "Budget details are required.";
						}
						else if($item["details"] === '')
						{
							$errors["budgetArray ".($index+1)." details"] = "Budget details are required.";
						}

						if(!isset($item["amount"]))
						{
							$errors["budgetArray ".($index+1)." amount"] = "Budget amount is required.";
						}
						else if($item["amount"] <= 0)
						{
							$errors["budgetArray ".($index+1)." amount"] = "Budget amount must be greater than $0";
						}
					}
					else
					{
						$errors["budgetArray ".($index+1)] = "Budget item must not be empty.";
					}

					$index++;
				}
			}
			else //no budget items
			{
				$errors["budgetArray"] = "There must be at least one budget item.";
			}
			
			/*Now insert new application into database*/
			if(empty($errors)) //no errors yet
			{
				if(!$updating) //adding, not updating
				{
					try
					{
						$curDate = date("Y/m/d");
						
						$conn->beginTransaction(); //begin atomic transaction
						//echo "Dates: ".date("Y-m-d",$travelFrom).",".date("Y-m-d",$travelTo).",".date("Y-m-d",$activityFrom).",".date("Y-m-d",$activityTo).".";
						/*Prepare the query*/
						$sql = $conn->prepare("INSERT INTO applications(Applicant, Date, Name, Department, Email, Title, TravelStart, TravelEnd, EventStart, EventEnd, Destination, AmountRequested, 
							IsResearch, IsConference, IsCreativeActivity, IsOtherEventText, OtherFunding, ProposalSummary, FulfillsGoal1, FulfillsGoal2, FulfillsGoal3, FulfillsGoal4, DepartmentChairEmail, NextCycle, Status) 
							VALUES(:applicant, :date, :name, :department, :email, :title, :travelstart, :travelend, :eventstart, :eventend, :destination, :amountrequested, 
							:isresearch, :isconference, :iscreativeactivity, :isothereventtext, :otherfunding, :proposalsummary, :fulfillsgoal1, :fulfillsgoal2, :fulfillsgoal3, :fulfillsgoal4, :departmentchairemail, :nextcycle, 'Pending')");
						$sql->bindParam(':applicant', $broncoNetID);
						$sql->bindParam(':date', $curDate); //create a new date right when inserting to save current time
						$sql->bindParam(':name', $name);
						$sql->bindParam(':department', $department);
						$sql->bindParam(':email', $email);
						$sql->bindParam(':title', $title);
						$sql->bindParam(':travelstart', $travelFrom);
						$sql->bindParam(':travelend', $travelTo);
						$sql->bindParam(':eventstart', $activityFrom);
						$sql->bindParam(':eventend', $activityTo);
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
							//$conn->commit();//commit first part of transaction (we can still rollback if something ahead fails)
							
							/*get the application ID of the just-added application*/
							$sql = $conn->prepare("select max(ID) from applications where Applicant = :applicant LIMIT 1");
							$sql->bindParam(':applicant', $broncoNetID);
							$sql->execute();
							$newAppID = $sql->fetchAll(PDO::FETCH_NUM)[0][0];//now we have the current ID!
							
							try
							{
								foreach($budgetArray as $i)//go through budget array
								{
									if(!empty($i))
									{
										//echo "going to insert:";
										//var_dump($i);

										$sql = $conn->prepare("INSERT INTO applications_budgets(ApplicationID, Name, Cost, Details) VALUES (:appID, :name, :cost, :details)");
										$sql->bindParam(':appID', $newAppID);
										$sql->bindParam(':name', $i['expense']);
										$sql->bindParam(':cost', $i['amount']);
										$sql->bindParam(':details', $i['details']);
										
										if ($sql->execute() === TRUE) //query executed correctly
										{
											//$conn->commit(); //commit next part of transaction
										}
										else //query failed
										{
											//$conn->rollBack(); //rollBack the transaction
											$errors["other"] = "Query failed to insert budget items";
											break;
										}
									}
								}

								//at this point, if there are no errors, we should be able to commit to the database. Otherwise we should rollback.
								if(empty($errors))
								{
									$conn->commit();
								}
								else
								{
									$conn->rollBack();
								}
							}
							catch(Exception $e)
							{
								$errors["other"] = "Exception when trying to insert application budget items into database: ".$e->getMessage();
							}
						} 
						else //query failed
						{
							$errors["other"] = "Query failed to insert application";
						}
					}
					catch(Exception $e)
					{
						$errors["other"] = "Exception when trying to insert application into database: ".$e->getMessage();
					}
				}
				else //just updating
				{
					try
					{
						$conn->beginTransaction(); //begin atomic transaction
						//echo "Dates: ".date("Y-m-d",$travelFrom).",".date("Y-m-d",$travelTo).",".date("Y-m-d",$activityFrom).",".date("Y-m-d",$activityTo).".";
						/*Prepare the query*/
						//UPDATE applications SET Name='Dude', Department='Lel' WHERE ID='123'
						$sql = $conn->prepare("UPDATE applications SET Name=:name, Department=:department, Email=:email, Title=:title, TravelStart=:travelstart, TravelEnd=:travelend, EventStart=:eventstart, EventEnd=:eventend,
							Destination=:destination, AmountRequested=:amountrequested, IsResearch=:isresearch, IsConference=:isconference, IsCreativeActivity=:iscreativeactivity, IsOtherEventText=:isothereventtext,
							OtherFunding=:otherfunding, ProposalSummary=:proposalsummary, FulfillsGoal1=:fulfillsgoal1, FulfillsGoal2=:fulfillsgoal2, FulfillsGoal3=:fulfillsgoal3, FulfillsGoal4=:fulfillsgoal4,
							DepartmentChairEmail=:departmentchairemail, NextCycle=:nextcycle WHERE ID=:id");
						
						$sql->bindParam(':name', $name);
						$sql->bindParam(':department', $department);
						$sql->bindParam(':email', $email);
						$sql->bindParam(':title', $title);
						$sql->bindParam(':travelstart', $travelFrom);
						$sql->bindParam(':travelend', $travelTo);
						$sql->bindParam(':eventstart', $activityFrom);
						$sql->bindParam(':eventend', $activityTo);
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
						$sql->bindParam(':id', $updateID);
						
						if ($sql->execute() === TRUE) //query executed correctly
						{
							//$conn->commit();//commit first part of transaction (we can still rollback if something ahead fails)
							
							/*delete previous budget items*/
							$sql = $conn->prepare("DELETE FROM applications_budgets WHERE ApplicationID=:id");
							$sql->bindParam(':id', $updateID);

							if ($sql->execute() === TRUE) //query executed correctly
							{
								//$conn->commit();//commit second part of transaction

								/*go through budget array*/
								foreach($budgetArray as $i)
								{
									if(!empty($i))
									{
										$sql = $conn->prepare("INSERT INTO applications_budgets(ApplicationID, Name, Cost, Details) VALUES (:appID, :name, :cost, :details)");
										$sql->bindParam(':appID', $updateID);
										$sql->bindParam(':name', $i['expense']);
										$sql->bindParam(':cost', $i['amount']);
										$sql->bindParam(':details', $i['details']);
										
										if ($sql->execute() === TRUE) //query executed correctly
										{
											//$conn->commit(); //commit next part of transaction
										}
										else //query failed
										{
											//$conn->rollBack(); //rollBack the transaction
											$errors["other"] = "Query failed to insert new budget items";
											break;
										}
									}
								}

								//at this point, if there are no errors, we should be able to commit to the database. Otherwise we should rollback.
								if(empty($errors))
								{
									$conn->commit();
								}
								else
								{
									$conn->rollBack();
								}
							}
							else
							{
								//$conn->rollBack(); //rollBack the transaction
								$errors["other"] = "Query failed to delete old budget items";
							}
						} 
						else //query failed
						{
							$errors["other"] = "Query failed to update application";
						}
					}
					catch(Exception $e)
					{
						$errors["other"] = "Exception when trying to update application in database: ".$e->getMessage();
					}
				}
			}

			// response if there are errors
			if ( ! empty($errors)) {
				// if there are items in our errors array, return those errors
				$data['success'] = false;
				$data['errors']  = $errors;
			} else {
				// if there are no errors, return a message
				$data['success'] = true;
				if(!$updating)
				{
					$data['appID'] = $newAppID;
					$data['message'] = "Successfully inserted application into database";
				}
				else
				{
					$data['appID'] = $updateID;
					$data['message'] = "Successfully updated application in database";
				}
			}
			
			//$data["temp"] = "just a test";
			return $data; //return both the return code and status
		}
	}
	
	
	
	/*
	Insert a final report into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use. If $updating is true, update this entry rather than inserting a new one.

	This function returns a data array; If the report is successfully inserted or updated, then data["success"] is set to true, and data["message"] is set to a confirmation message.
	Otherwise, data["success"] is set to false, and data["errors"] is set to an array of errors following the format of ["field", "message"], where field corresponds to one of the report's fields.
	*/
	if(!function_exists('insertFinalReport')){
		function insertFinalReport($conn, $updating, $updateID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $totalAwardSpent)
		{
			$errors = array(); // array to hold validation errors
			$data = array(); // array to pass back data
			
			/*Sanitize everything, may be redundant since PDO sanitizes prepared statements*/
			try
			{
				$projectSummary = trim(filter_var($projectSummary, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
			}
			catch(Exception $e)
			{
				$errors["other"] = "Exception when sanitizing fields!";
			}
			
			/*Now validate everything that needs it*/
			if(empty($errors)) //no errors yet
			{
				if($projectSummary === '')
				{
					$errors["projectSummary"] = "Project Summary field is required.";
				}

				if($totalAwardSpent == null)
				{
					$errors["amountAwardedSpent"] = "Awarded Amount Spent field is required.";
				}

				if($travelFrom == null || $travelFrom === '')
				{
					$errors["travelFrom"] = "Travel From field is required.";
				}
				if($travelTo == null || $travelTo === '')
				{
					$errors["travelTo"] = "Travel To field is required.";
				}
				if($activityFrom == null || $activityFrom === '')
				{
					$errors["activityFrom"] = "Activity From field is required.";
				}
				if($activityTo == null || $activityTo === '')
				{
					$errors["activityTo"] = "Activity To field is required.";
				}

				/*Make sure dates are acceptable*/
				if(!isset($errors["travelFrom"]) && !isset($errors["activityFrom"]))//making sure dates were set
				{
					if($travelFrom > $activityFrom)
					{
						$errors["travelFrom"] = "Travel dates are impossible (activity cannot start before travel!).";
					}
				}
				if(!isset($errors["activityFrom"]) && !isset($errors["activityTo"]))//making sure dates were set
				{
					if($activityFrom > $activityTo)
					{
						$errors["activityFrom"] = "Travel dates are impossible (activity cannot end before it begins!).";
					}
				}
				if(!isset($errors["activityTo"]) && !isset($errors["travelTo"]))//making sure dates were set
				{
					if($activityTo > $travelTo)
					{
						$errors["activityTo"] = "Travel dates are impossible (travel cannot end before activity ends!).";
					}
				}
			}
			
			/*Now insert new final report into database*/
			if(empty($errors)) //no errors yet
			{
				if(!$updating) //adding, not updating
				{
					try
					{
						//Get current date
						$curDate = date("Y/m/d");

						$conn->beginTransaction(); //begin atomic transaction

						$sql = $conn->prepare("INSERT INTO final_reports(ApplicationID, TravelStart, TravelEnd, EventStart, EventEnd, ProjectSummary, TotalAwardSpent, Date, Status) 
							VALUES(:applicationid, :travelstart, :travelend, :eventstart, :eventend, :projectsummary, :totalawardspent, :date, 'Pending')");
						$sql->bindParam(':applicationid', $updateID);
						$sql->bindParam(':travelstart', $travelFrom);
						$sql->bindParam(':travelend', $travelTo);
						$sql->bindParam(':eventstart', $activityFrom);
						$sql->bindParam(':eventend', $activityTo);
						$sql->bindParam(':projectsummary', $projectSummary);
						$sql->bindParam(':totalawardspent', $totalAwardSpent);
						$sql->bindParam(':date', $curDate); //create a new date right when inserting to save current time
						
						if ($sql->execute() === TRUE) //query executed correctly
						{
							$conn->commit();//commit transaction (probably don't need transactions for this since it is only 1 command)
						} 
						else //query failed
						{
							$errors["other"] = "Query failed to insert final report";
							$conn->rollBack();
						}
					}
					catch(Exception $e)
					{
						$errors["other"] = "Exception when trying to insert final report into database: ".$e->getMessage();
					}
				}
				else //just updating
				{
					try
					{
						$conn->beginTransaction(); //begin atomic transaction

						$sql = $conn->prepare("UPDATE final_reports SET TravelStart = :travelstart, TravelEnd = :travelend, EventStart = :eventstart, EventEnd = :eventend,
							ProjectSummary = :projectsummary, TotalAwardSpent = :totalawardspent WHERE ApplicationID = :applicationid");
						$sql->bindParam(':travelstart', $travelFrom);
						$sql->bindParam(':travelend', $travelTo);
						$sql->bindParam(':eventstart', $activityFrom);
						$sql->bindParam(':eventend', $activityTo);
						$sql->bindParam(':projectsummary', $projectSummary);
						$sql->bindParam(':totalawardspent', $totalAwardSpent);
						$sql->bindParam(':applicationid', $updateID);
						
						if ($sql->execute() === TRUE) //query executed correctly
						{
							$conn->commit();//commit transaction (probably don't need transactions for this since it is only 1 command)
						} 
						else //query failed
						{
							$errors["other"] = "Query failed to insert final report";
							$conn->rollBack();
						}
					}
					catch(Exception $e)
					{
						$errors["other"] = "Exception when trying to insert final report into database: ".$e->getMessage();
					}
				}
			}
			
			// response if there are errors
			if ( ! empty($errors)) {
				// if there are items in our errors array, return those errors
				$data['success'] = false;
				$data['errors']  = $errors;
			} else {
				// if there are no errors, return a message
				$data['success'] = true;
				if(!$updating)
				{
					$data['message'] = "Successfully inserted final report into database";
				}
				else
				{
					$data['message'] = "Successfully updated final report in database";
				}
			}
			
			//$data["temp"] = "just a test";
			return $data; //return both the return code and status
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