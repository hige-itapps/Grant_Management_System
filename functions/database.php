<?php
	include $_SERVER['DOCUMENT_ROOT']."/include/application.php";

	/* Establishes an sql connection to the database, and returns the object; MAKE SURE TO SET OBJECT TO NULL WHEN FINISHED */
	if(!function_exists('connection')) {
		function connection()
		{
			try 
			{
				/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
				$config_url = $_SERVER['DOCUMENT_ROOT'].'/config.ini';
				
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
			$application = new Application();
			$application->id = $res[0][0];
			$application->bnid = $res[0][1];
			$application->name = $res[0][3];
			$application->dateS = $res[0][2];
			$application->dept = $res[0][4];
			$application->deptM = $res[0][5];
			$application->email = $res[0][6];
			$application->rTitle = $res[0][7];
			$application->tStart = $res[0][8];
			$application->tEnd = $res[0][9];
			$application->aStart = $res[0][10];
			$application->aEnd = $res[0][11];
			$application->dest = $res[0][12];
			$application->aReq = $res[0][13];
			$application->pr1 = $res[0][14];
			$application->pr2 = $res[0][15];
			$application->pr3 = $res[0][16];
			$application->pr4 = $res[0][17];
			$application->oF = $res[0][18];
			$application->pS = $res[0][19];
			$application->fg1 = $res[0][20];
			$application->fg2 = $res[0][21];
			$application->fg3 = $res[0][22];
			$application->fg4 = $res[0][23];
			$application->deptCE = $res[0][24];
			$application->deptCS = $res[0][25];
			
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
					$application = new Application(); //initialize
					$application->id = $res[$i][0];
					$application->bnid = $res[$i][1];
					$application->name = $res[$i][3];
					$application->dateS = $res[$i][2];
					$application->dept = $res[$i][4];
					$application->deptM = $res[$i][5];
					$application->email = $res[$i][6];
					$application->rTitle = $res[$i][7];
					$application->tStart = $res[$i][8];
					$application->tEnd = $res[$i][9];
					$application->aStart = $res[$i][10];
					$application->aEnd = $res[$i][11];
					$application->dest = $res[$i][12];
					$application->aReq = $res[$i][13];
					$application->pr1 = $res[$i][14];
					$application->pr2 = $res[$i][15];
					$application->pr3 = $res[$i][16];
					$application->pr4 = $res[$i][17];
					$application->oF = $res[$i][18];
					$application->pS = $res[$i][19];
					$application->fg1 = $res[$i][20];
					$application->fg2 = $res[$i][21];
					$application->fg3 = $res[$i][22];
					$application->fg4 = $res[$i][23];
					$application->deptCE = $res[$i][24];
					$application->deptCS = $res[0][25];
					
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
				$application = new Application(); //initialize
				$application->id = $res[$i][0];
				$application->bnid = $res[$i][1];
				$application->name = $res[$i][3];
				$application->dateS = $res[$i][2];
				$application->dept = $res[$i][4];
				$application->deptM = $res[$i][5];
				$application->email = $res[$i][6];
				$application->rTitle = $res[$i][7];
				$application->tStart = $res[$i][8];
				$application->tEnd = $res[$i][9];
				$application->aStart = $res[$i][10];
				$application->aEnd = $res[$i][11];
				$application->dest = $res[$i][12];
				$application->aReq = $res[$i][13];
				$application->pr1 = $res[$i][14];
				$application->pr2 = $res[$i][15];
				$application->pr3 = $res[$i][16];
				$application->pr4 = $res[$i][17];
				$application->oF = $res[$i][18];
				$application->pS = $res[$i][19];
				$application->fg1 = $res[$i][20];
				$application->fg2 = $res[$i][21];
				$application->fg3 = $res[$i][22];
				$application->fg4 = $res[$i][23];
				$application->deptCE = $res[$i][24];
				$application->deptCS = $res[0][25];
				
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
				$application = new Application(); //initialize
				$application->id = $res[$i][0];
				$application->bnid = $res[$i][1];
				$application->name = $res[$i][3];
				$application->dateS = $res[$i][2];
				$application->dept = $res[$i][4];
				$application->deptM = $res[$i][5];
				$application->email = $res[$i][6];
				$application->rTitle = $res[$i][7];
				$application->tStart = $res[$i][8];
				$application->tEnd = $res[$i][9];
				$application->aStart = $res[$i][10];
				$application->aEnd = $res[$i][11];
				$application->dest = $res[$i][12];
				$application->aReq = $res[$i][13];
				$application->pr1 = $res[$i][14];
				$application->pr2 = $res[$i][15];
				$application->pr3 = $res[$i][16];
				$application->pr4 = $res[$i][17];
				$application->oF = $res[$i][18];
				$application->pS = $res[$i][19];
				$application->fg1 = $res[$i][20];
				$application->fg2 = $res[$i][21];
				$application->fg3 = $res[$i][22];
				$application->fg4 = $res[$i][23];
				$application->deptCE = $res[$i][24];
				$application->deptCS = $res[0][25];
				
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
	
	/* Returns true if this applicant has an approved application from up to a year ago */
	if(!function_exists('hasApprovedApplicationWithinPastYear')){
		function hasApprovedApplicationWithinPastYear($conn, $bNetID)
		{
			$is = false;
			
			if ($bNetID != "") //valid username
			{
				/* Select only approved applications that this user has has submitted within the past year*/
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Date >= DATE_SUB(NOW(),INTERVAL 1 YEAR) AND Applicant = :bNetID AND Approved = true");
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
		function approveApplication($conn, $id)
		{
			if ($id != "") //valid application id
			{
				/*Update any application with the given id*/
				$sql = $conn->prepare("UPDATE applications SET Approved = 1 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
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
				$sql = $conn->prepare("UPDATE applications SET Approved = 0 WHERE ID = :id");
				$sql->bindParam(':id', $id);
				$sql->execute();
				
				/* Close finished query and connection */
				$sql = null;
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
	
	
	/*
	Insert an application into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use
	Most fields are self-explanatory. It's worth mentioning that $budgetArray is a 2-dimensional array of expenses.
		$budgetArray[i][0] is the name of the expense
		$budgetArray[i][1] is the comment on the expense
		$budgetArray[i][2] is the actual cost
	return new application ID if EVERYTHING was successful, otherwise 0
	*/
	if(!function_exists('insertApplication')){
		function insertApplication($conn, $broncoNetID, $name, $email, $department, $departmentMailStop, $deptChairEmail, $travelFrom, $travelTo,
			$activityFrom, $activityTo, $title, $destination, $amountRequested, $purpose1, $purpose2, $purpose3,
			$purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $budgetArray)
		{
			//echo "Dates: ".$travelFrom.",".$travelTo.",".$activityFrom.",".$activityTo.".";
			
			//First, add this user to the applicants table IF they don't already exist
			insertApplicantIfNew($conn, $broncoNetID);
			
			/*Server-Side validation!*/
			$valid = true; //start valid, turn false if anything is wrong!
			$newAppID = 0; //set this to the new application's ID if successful
			
			/*Sanitize everything*/
			try
			{
				$name = trim(filter_var($name, FILTER_SANITIZE_STRING));
				$email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
				$department = trim(filter_var($department, FILTER_SANITIZE_STRING));
				$departmentMailStop = filter_var($departmentMailStop, FILTER_SANITIZE_NUMBER_INT);
				$travelFrom = strtotime($travelFrom);
				$travelTo = strtotime($travelTo);
				$title = trim(filter_var($title, FILTER_SANITIZE_STRING));
				$activityFrom = strtotime($activityFrom);
				$activityTo = strtotime($activityTo);
				$destination = trim(filter_var($destination, FILTER_SANITIZE_STRING));
				//$amountRequested = filter_var($amountRequested, FILTER_SANITIZE_NUMBER_INT);
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
				/*Make sure necessary strings aren't empty*/
				if($name === '' || $email === '' || $department === '' || $title === '' || $proposalSummary === '' || $deptChairEmail === '')
				{
					echo "Application Validation Error: Empty String Given!";
					$valid = false;
				}
				/*Make sure dates are acceptable*/
				if($travelTo < $travelFrom || $activityTo < $activityFrom || $activityFrom < $travelFrom || $activityTo > $travelTo)
				{
					echo "Application Validation Error: Invalid Date Given!";
					$valid = false;
				}
				/*Make sure emails are correct format*/
				if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($deptChairEmail, FILTER_VALIDATE_EMAIL))
				{
					echo "Application Validation Error: Invalid Email Given!";
					$valid = false;
				}
				
				/*go through budget array*/
				foreach($budgetArray as $i)
				{
					if(!empty($i))
					{
						if($i[0] === '' || $i[1] === '')
						{
							echo "Application Validation Error: Empty Budget String Given!: ".$i[0].",".$i[1].".";
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
					$conn->beginTransaction(); //begin atomic transaction
					//echo "Dates: ".date("Y-m-d",$travelFrom).",".date("Y-m-d",$travelTo).",".date("Y-m-d",$activityFrom).",".date("Y-m-d",$activityTo).".";
					/*Prepare the query*/
					$sql = $conn->prepare("INSERT INTO applications(Applicant, Date, Name, Department, MailStop, Email, Title, TravelStart, TravelEnd, EventStart, EventEnd, Destination, AmountRequested, 
						IsResearch, IsConference, IsCreativeActivity, IsOtherEventText, OtherFunding, ProposalSummary, FulfillsGoal1, FulfillsGoal2, FulfillsGoal3, FulfillsGoal4, DepartmentChairEmail) 
						VALUES(:applicant, :date, :name, :department, :mailstop, :email, :title, :travelstart, :travelend, :eventstart, :eventend, :destination, :amountrequested, 
						:isresearch, :isconference, :iscreativeactivity, :isothereventtext, :otherfunding, :proposalsummary, :fulfillsgoal1, :fulfillsgoal2, :fulfillsgoal3, :fulfillsgoal4, :departmentchairemail)");
					$sql->bindParam(':applicant', $broncoNetID);
					$sql->bindParam(':date', date("Y/m/d")); //create a new date right when inserting to save current time
					$sql->bindParam(':name', $name);
					$sql->bindParam(':department', $department);
					$sql->bindParam(':mailstop', $departmentMailStop);
					$sql->bindParam(':email', $email);
					$sql->bindParam(':title', $title);
					$sql->bindParam(':travelstart', date("Y-m-d", $travelFrom));
					$sql->bindParam(':travelend', date("Y-m-d", $travelTo));
					$sql->bindParam(':eventstart', date("Y-m-d", $activityFrom));
					$sql->bindParam(':eventend', date("Y-m-d", $activityTo));
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