<?php
	include_once(dirname(__FILE__) . "/../include/classDefinitions.php");
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
	
	/*Returns a single follow up report for a specified application ID*/
	if(!function_exists('getFUReport')) {
		function getFUReport($conn, $appID)
		{
			$sql = $conn->prepare("Select * FROM follow_up_reports WHERE ApplicationID = :appID");
			$sql->bindParam(':appID', $appID);
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if(!empty($res))
			{
				/*create application object*/
				$FUReport = new FUReport($res[0]);
				
				
				/* Close finished query and connection */
				$sql = null;
				
				/* return FUReport object */
				return $FUReport;
			}
			else
			{
				return null;
			}
			
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
		function getNumberOfApplicationsToSign($conn, $email)
		{
			if ($email != "") //valid email
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND DepartmentChairEmail != Email AND Approved IS NULL");
				$sql->bindParam(':dEmail', $email);
				$sql->execute();
				$res = $sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				/* Close finished query and connection */
				$sql = null;
				
				//echo 'Count: '.$res[0][0].".";
				
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
				
				//echo 'Count: '.$res[0][0].".";
				
				return $res[0][0];
			}
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
	
	/* Returns array of follow-up report approvers */
	if(!function_exists('getFollowUpReportApprovers')) {
		function getFollowUpReportApprovers($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select BroncoNetID, Name FROM follow_up_approval");
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
	
	/*Checks if a user is a follow-up report approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	if(!function_exists('isFollowUpReportApprover')) {
		function isFollowUpReportApprover($conn, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			$approverList = getFollowUpReportApprovers($conn);//grab follow-up report approver list
			
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
	1. Must be in a non-student position
	2. Must not have a pending application
	3. Must not have received funding within the past year
	4. Must not be an admin, committee member, application approver, or follow-up report approver
	&nextCycle = false if checking current cycle, or true if checking next cycle*/
	if(!function_exists('isUserAllowedToCreateApplication')) {
		function isUserAllowedToCreateApplication($conn, $broncoNetID, $positions, $nextCycle)
		{
			//make sure user is not part of HIGE staff
			if(!isCommitteeMember($conn, $broncoNetID) && !isApplicationApprover($conn, $broncoNetID) && !isAdministrator($conn, $broncoNetID) && !isFollowUpReportApprover($conn, $broncoNetID))
			{
				$check = true;
				/*
				//check all positions to see if any are 'Faculty' or 'Staff'!
				if (is_array($positions)) {
					foreach ($positions as $position) {
						if ($position === 'Faculty' || $position === 'faculty' 
							|| $position === 'Staff' || $position === 'staff'
							|| $position === 'Provisional Employee' || $position === 'Student') {
							$check = true;
						}
					}	
				} else {
					if ($positions === 'Faculty' || $positions === 'faculty'
						|| $positions === 'Staff' || $positions === 'staff'
						|| $positions === 'Provisional Employee' || $position === 'Student') {
							$check = true;
						}
				} */
				
				$lastApproved = false; //set to true if last approved application was long enough ago
				$lastApprovedApp = getMostRecentApprovedApplication($conn, $broncoNetID);

				//echo "Last approved: " .$lastApprovedApp->id;
				
				if($lastApprovedApp != null) //if a previous application exists
				{
					$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateS);
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
				
				if($check && !hasPendingApplication($conn, $broncoNetID) && $lastApproved)
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

	/*Checks if a user is allowed to create a follow up report for a specified appID
	Rules:
	Application must not already have a follow up report
	Application must belong to the user
	Application must have 'Approved' status*/
	if(!function_exists('isUserAllowedToCreateFollowUpReport')) {
		function isUserAllowedToCreateFollowUpReport($conn, $broncoNetID, $appID)
		{
			if(doesUserOwnApplication($conn, $broncoNetID, $appID) && !getFUReport($conn, $appID) && isApplicationApproved($conn, $appID))
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
	}
	
	/*Checks if a user is allowed to freely see applications. ALSO USED FOR VIEWING FOLLOW-UP REPORTS
	Rules: Must be an application approver, follow-up report approver, administrator, or a committee member*/
	if(!function_exists('isUserAllowedToSeeApplications')) {
		function isUserAllowedToSeeApplications($conn, $broncoNetID)
		{
				
			if(isCommitteeMember($conn, $broncoNetID) || isApplicationApprover($conn, $broncoNetID) || isAdministrator($conn, $broncoNetID) || isFollowUpReportApprover($conn, $broncoNetID))
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
		function isUserAllowedToSignApplication($conn, $email, $appID)
		{
			$is = false; //initialize boolean to false
			
			if ($email != "" && $appID != "") //valid email & ID
			{
				/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND DepartmentChairEmail != Email AND Approved IS NULL");
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
		function isUserDepartmentChair($conn, $email, $appID)
		{
			$is = false; //initialize boolean to false
			
			if ($email != "" && $appID != "") //valid email & ID
			{
				/* Only count applications meant for this person */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail");
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

	/*returns true if the user owns this application, or false if they don't*/
	if(!function_exists('doesUserOwnApplication')){
		function doesUserOwnApplication($conn, $broncoNetID, $appID)
		{
			$is = false; //initialize boolean to false
			
			if ($broncoNetID != "" && $appID != "") //valid broncoNetID & appID
			{
				/* Only count applications with the right ID that this user has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND Applicant = :bNetID");
				$sql->bindParam(':appID', $appID);
				$sql->bindParam(':bNetID', $broncoNetID);
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
		function hasPendingApplication($conn, $bNetID)
		{
			$is = false;
			
			if ($bNetID != "") //valid username
			{
				/* Select only pending applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Approved IS NULL AND Applicant = :bNetID");
				$sql->bindParam(':bNetID', $bNetID);
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
	
	/*Get the most recently approved application of a user- return null if none*/
	if(!function_exists('getMostRecentApprovedApplication')){
		function getMostRecentApprovedApplication($conn, $bNetID)
		{
			$mostRecent = null;
			
			if ($bNetID != "") //valid username
			{
				/* Select the most recent from this applicant */
				$sql = $conn->prepare("SELECT * FROM applications WHERE Applicant = :bNetID AND Approved = 1 ORDER BY Date DESC LIMIT 1");
				$sql->bindParam(':bNetID', $bNetID);
				
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
	
	/*return an array of the maximum lengths of every column in the applications table*/
	if(!function_exists('getApplicationsMaxLengths')){
		function getApplicationsMaxLengths($conn)
		{
			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = 'hige' AND table_name = 'applications'");
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
			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = 'hige' AND table_name = 'applications_budgets'");
			$sql->execute();
			$res = $sql->fetchAll(); //return indexes AND names as keys
					
			/* Close finished query and connection */
			$sql = null;
			
			return $res;
		}
	}

	/*return an array of the maximum lengths of every column in the follow_up_reports table*/
	if(!function_exists('getFollowUpReportsMaxLengths')){
		function getFollowUpReportsMaxLengths($conn)
		{
			$sql = $conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = 'hige' AND table_name = 'follow_up_reports'");
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
				$sql = $conn->prepare("UPDATE applications SET Approved = 1, AmountAwarded = :aw, OnHold = 0 WHERE ID = :id");
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
				$sql = $conn->prepare("UPDATE applications SET Approved = 0, OnHold = 0 WHERE ID = :id");
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
				$sql = $conn->prepare("UPDATE applications SET OnHold = 1 WHERE ID = :id");
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
	
	

	/*Update a FU Report to be approved*/
	if(!function_exists('approveFU')){
		function approveFU($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE follow_up_reports SET Approved = 1 WHERE ApplicationID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				$ret = $sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
				/* Close finished query and connection */
				$sql = null;
				return $ret;
			}
		}
	}
	
	/*Update a FU Report to be denied*/
	if(!function_exists('denyFU')){
		function denyFU($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE follow_up_reports SET Approved = 0 WHERE ApplicationID = :id");
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
		$budgetArray[i][1] is the comment on the expense
		$budgetArray[i][2] is the actual cost

	This function returns an array, with array[0] being the return code and array[1] being the return status string (useful for specific error messages)
	The return code is the new application's ID (or the existing ID if updating) if EVERYTHING is successful, OTHERWISE it is a negative error number that corresponds to a specific problem

	ERROR return codes:
	-1: default code, will only return if a more specific error code was not applied (shouldn't happen). Within the code, to see if any specific errors have occurred already, check for this value.

	-10: sanitization exception (one or more fields are not the types they should be)

	-20: departmnet chair's email address isn't a wmich email
	-21: at least one of the required fields is empty
	-22: travel/activity dates aren't possible (activity happening before travel, etc.)
	-23: at least one of the email addresses is incorrectly formatted (not a valid address)
	-24: this user is not allowed to submit for this cycle
	-25: at least one of the budget fields is empty

	-40: exception when inserting application into database
	-41: first insert query (the main application) failed
	-42: second insert query (budget items) failed

	-50: exception when updating application in database
	-51: first query (updating the main application) failed
	-52: second query (deleting the old budget items) failed
	-53: third query (inserting new budget items) failed
	*/
	if(!function_exists('insertApplication')){
		function insertApplication($conn, $updating, $updateID, $broncoNetID, $name, $email, $department, $deptChairEmail, $travelFrom, $travelTo,
			$activityFrom, $activityTo, $title, $destination, $amountRequested, $purpose1, $purpose2, $purpose3,
			$purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetArray)
		{

			$returnCode = -1; //default error code
			$returnStatus = "Unspecified error";
			$newAppID = 0; //set this to the new application's ID if successful

			//echo "Dates: ".$travelFrom.",".$travelTo.",".$activityFrom.",".$activityTo.".";
			echo "inserting app";

			if(!$updating)
			{
				//First, add this user to the applicants table IF they don't already exist
				insertApplicantIfNew($conn, $broncoNetID);
			}
			
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
				$returnCode = -10; //sanitization exception
				$returnStatus = "Exception when sanitizing fields";
			}
			
			/*Now validate everything that needs it*/
			if($returnCode == -1) //no errors yet
			{
				list($em, $domain) = explode('@', $deptChairEmail);

				if (!strstr(strtolower($domain), "wmich.edu")) 
				{
					$returnCode = -20;
					$returnStatus = "Department chair's email address must be a wmich.edu address";
				}
				/*Make sure necessary strings aren't empty*/
				if($name === '' || $email === '' || $department === '' || $title === '' || $proposalSummary === '' || $deptChairEmail === '')
				{
					$returnCode = -21;
					$returnStatus = "At least one required field is empty";
				}
				/*Make sure dates are acceptable*/
				if($travelFrom > $activityFrom || $activityFrom > $activityTo || $activityTo > $travelTo)
				{
					$returnCode = -22;
					$returnStatus = "Travel dates are impossible (must follow the form travelFrom <= activityFrom <= activityTo <= travelTo)";
				}
				/*Make sure emails are correct format*/
				if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($deptChairEmail, FILTER_VALIDATE_EMAIL))
				{
					$returnCode = -23;
					$returnStatus = "At least one email address is invalid (invalid email format)";
				}

				/*Make sure cycle is allowed; ignore this check if an admin is updating*/
				if(!$updating)
				{
					$lastApprovedApp = getMostRecentApprovedApplication($conn, $broncoNetID);
					if($lastApprovedApp != null) //if a previous application exists
					{
						$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateS);
						$lastCycle = getCycleName($lastDate, $lastApprovedApp->nextCycle, false);
						
						$curCycle = getCycleName(DateTime::createFromFormat('Y/m/d', date("Y/m/d")), $nextCycle, false);
						
						$lastApproved = areCyclesFarEnoughApart($lastCycle, $curCycle); //check cycle age

						if(!$lastApproved) 
						{
							$returnCode = -24;
							$returnStatus = "Applicant is not allowed to apply for the specified cycle (not enough cycles have passed since last approved application)";
						}
					}
				}
				
				/*go through budget array*/
				foreach($budgetArray as $i)
				{
					if(!empty($i))
					{
						if($i[0] === '' || $i[1] === '')
						{
							$returnCode = -25;
							$returnStatus = "At least one required budget item field is empty";
							break;
						}
					}
				}
			}
			
			/*Now insert new application into database*/
			if($returnCode == -1) //no errors yet
			{
				if(!$updating) //adding, not updating
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
							$newAppID = $sql->fetchAll(PDO::FETCH_NUM)[0][0];//now we have the current ID!
							
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
										$returnCode = -42;
										$returnStatus = "Query failed to insert budget items";
										break;
									}
								}
							}
						} 
						else //query failed
						{
							$returnCode = -41;
							$returnStatus = "Query failed to insert application";
						}
					}
					catch(Exception $e)
					{
						$returnCode = -40;
						$returnStatus = "Exception when trying to insert application into database";
					}
				}
				else //just updating
				{
					try
					{
						//Get relevant dates
						$travelFromDate = date("Y-m-d", $travelFrom);
						$travelToDate = date("Y-m-d", $travelTo);
						$activityFromDate = date("Y-m-d", $activityFrom);
						$activityToDate = date("Y-m-d", $activityTo);
						
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
						$sql->bindParam(':id', $updateID);
						
						if ($sql->execute() === TRUE) //query executed correctly
						{
							$conn->commit();//commit first part of transaction (we can still rollback if something ahead fails)
							
							/*delete previous budget items*/
							$sql = $conn->prepare("DELETE FROM applications_budgets WHERE ApplicationID=:id");
							$sql->bindParam(':id', $updateID);

							if ($sql->execute() === TRUE) //query executed correctly
							{
								$conn->commit();//commit second part of transaction

								/*go through budget array*/
								foreach($budgetArray as $i)
								{
									if(!empty($i))
									{
										$sql = $conn->prepare("INSERT INTO applications_budgets(ApplicationID, Name, Cost, Comment) VALUES (:appID, :name, :cost, :comment)");
										$sql->bindParam(':appID', $updateID);
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
											$returnCode = -53;
											$returnStatus = "Query failed to insert new budget items";
											break;
										}
									}
								}
							}
							else
							{
								$conn->rollBack(); //rollBack the transaction
								$returnCode = -52;
								$returnStatus = "Query failed to delete old budget items";
							}
						} 
						else //query failed
						{
							$returnCode = -51;
							$returnStatus = "Query failed to update application";
						}
					}
					catch(Exception $e)
					{
						$returnCode = -50;
						$returnStatus = "Exception when trying to update application in database";
					}
				}
			}

			if($returnCode == -1) //no errors
			{
				if(!$updating)
				{
					$returnCode = $newAppID;
					$returnStatus = "Successfully inserted application into database";
				}
				else
				{
					$returnCode = $updateID;
					$returnStatus = "Successfully updated application in database";
				}
			}
			
			return array($returnCode, $returnStatus); //return both the return code and status
		}
	}
	
	
	
	/*
	Insert a follow-up-report into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use.
	Fields: DB connection, application ID, travel start & end dates, activity start & end dates, project summary, and total award spent
	return 1 if insert is successful, 0 otherwise
	*/
	if(!function_exists('insertFollowUpReport')){
		function insertFollowUpReport($conn, $updating, $updateID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $totalAwardSpent)
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
				if(!$updating) //adding, not updating
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
						$sql = $conn->prepare("INSERT INTO follow_up_reports(ApplicationID, TravelStart, TravelEnd, EventStart, EventEnd, ProjectSummary, TotalAwardSpent, Date) 
							VALUES(:applicationid, :travelstart, :travelend, :eventstart, :eventend, :projectsummary, :totalawardspent, :date)");
						$sql->bindParam(':applicationid', $updateID);
						$sql->bindParam(':travelstart', $travelFromDate);
						$sql->bindParam(':travelend', $travelToDate);
						$sql->bindParam(':eventstart', $activityFromDate);
						$sql->bindParam(':eventend', $activityToDate);
						$sql->bindParam(':projectsummary', $projectSummary);
						$sql->bindParam(':totalawardspent', $totalAwardSpent);
						$sql->bindParam(':date', $curDate); //create a new date right when inserting to save current time
						
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
				else //just updating
				{
					try
					{
						//Get dates
						$travelFromDate = date("Y-m-d", $travelFrom);
						$travelToDate = date("Y-m-d", $travelTo);
						$activityFromDate = date("Y-m-d", $activityFrom);
						$activityToDate = date("Y-m-d", $activityTo);

						$conn->beginTransaction(); //begin atomic transaction

						$sql = $conn->prepare("UPDATE follow_up_reports SET TravelStart = :travelstart, TravelEnd = :travelend, EventStart = :eventstart, EventEnd = :eventend,
							ProjectSummary = :projectsummary, TotalAwardSpent = :totalawardspent WHERE ApplicationID = :applicationid");
						$sql->bindParam(':travelstart', $travelFromDate);
						$sql->bindParam(':travelend', $travelToDate);
						$sql->bindParam(':eventstart', $activityFromDate);
						$sql->bindParam(':eventend', $activityToDate);
						$sql->bindParam(':projectsummary', $projectSummary);
						$sql->bindParam(':totalawardspent', $totalAwardSpent);
						$sql->bindParam(':applicationid', $updateID);
						
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
						echo "Error updating follow-up report in database: " . $e->getMessage();
						$valid = false;
					}
				}
			}
			
			if($valid) //if successful, return 1
			{
				return $updateID;
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