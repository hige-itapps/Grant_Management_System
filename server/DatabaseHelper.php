<?php
/*
    Database helper class for transactions
    This class is built to support all necessary queries to the database.
*/

/*Application and Final Report classes*/
include_once(dirname(__FILE__) . "/Application.php");
include_once(dirname(__FILE__) . "/FinalReport.php");

/*Cycles class to deal with yearly cycles*/
include_once(dirname(__FILE__) . '/Cycles.php');

/*Logger*/
include_once(dirname(__FILE__) . "/Logger.php");

class DatabaseHelper
{
	private $logger; //for logging to files
	private $cycles; //for cycle functions
	private $conn; //pdo database connection object
	private $sql; //pdo prepared statement
	private $config_url; //url of config file
	private $settings; //configuration settings

	/* Constructior retrieves configurations and sets up a connection. Pass in a logger object for error logging. If a connection is passed in as a parameter, use that instead. */
	public function __construct($logger, $connection = null){
		$this->logger = $logger;
		$this->cycles = new Cycles(); //Cycles object
		$this->config_url = dirname(__FILE__).'/../config.ini'; //set config file url
		$this->settings = parse_ini_file($this->config_url); //get all settings

		if($connection === NULL){ //create a connection if one wasn't passed in
			$this->connect();
		}
		else{ //use the passed connection
			$this->conn = $connection;
		}
	}

	public function getConnection(){
		return $this->conn;
	}

	public function close(){
		$this->sql = null;
		$this->conn = null;
	}

	/*Save a new email message to the database, return true if successful or false otherwise*/
	public function saveEmail($appID, $subject, $message){
		$curTime = date('Y-m-d H:i:s');//get current timestamp
					
		$this->sql = $this->conn->prepare("INSERT INTO emails(ApplicationID, Subject, Message, Time) VALUES(:applicationid, :subject, :message, :time)");
		$this->sql->bindParam(':applicationid', $appID);
		$this->sql->bindParam(':subject', $subject);
		$this->sql->bindParam(':message', $message);
		$this->sql->bindParam(':time', $curTime);
		return $this->sql->execute();
	}

	/*Return all emails for a given application*/
	public function getEmails($appID){
		$curTime = date('Y-m-d H:i:s');//get current timestamp
					
		$this->sql = $this->conn->prepare("Select * FROM emails WHERE ApplicationID = :applicationid");
		$this->sql->bindParam(':applicationid', $appID);
		$this->sql->execute();
		return $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
	}

	/*Return staff notes for a given application*/
	public function getStaffNotes($appID){
		$this->sql = $this->conn->prepare("Select * FROM notes WHERE ApplicationID = :applicationid");
		$this->sql->bindParam(':applicationid', $appID);
		$this->sql->execute();
		$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
		/*return confirmation*/
		if(!empty($res)){return $res[0];}
		else{return null;}
	}

	/*Save staff notes for a certain application*/
	public function saveStaffNotes($appID, $note, $broncoNetID){
		if ($appID != ""){ //valid app id
			$this->logger->logInfo("Saving staff notes", $broncoNetID, dirname(__FILE__));
			/*Update note or insert if new*/
			$this->sql = $this->conn->prepare("INSERT INTO notes(ApplicationID, Note) VALUES(:applicationid, :note) ON DUPLICATE KEY UPDATE Note = :updatenote");
			$this->sql->bindParam(':applicationid', $appID);
			$this->sql->bindParam(':note', $note);
			$this->sql->bindParam(':updatenote', $note); //have to bind to different name despite being the same variable
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
		else{return null;}
	}
	
	/* Returns array of all administrators */
	public function getAdministrators(){
		$this->sql = $this->conn->prepare("Select BroncoNetID, Name FROM administrators");
		$this->sql->execute();
		return $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
	}
	
	/* Returns array of all applicants */
	public function getApplicants(){
		$this->sql = $this->conn->prepare("Select BroncoNetID FROM applicants");
		$this->sql->execute();
		return $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
	}
	
	/*Adds applicant to database IF they don't already exist. Otherwise, just ignore*/
	public function insertApplicantIfNew($newID){
		$this->sql = $this->conn->prepare("Select Count(*) FROM applicants WHERE BroncoNetID = :id");
		$this->sql->bindParam(':id', $newID);
		$this->sql->execute();
		$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
		
		if($res[0][0] == 0){//only triggers if user doesn't already exist
			$this->logger->logInfo("Inserting new applicant: ".$newID, $newID, dirname(__FILE__));
			$this->sql = $this->conn->prepare("INSERT INTO applicants VALUES(:id)");
			$this->sql->bindParam(':id', $newID);
			return $this->sql->execute();
		}
		else{
			return false;
		}
	}
	
	/*Returns a single application for a specified application ID*/
	public function getApplication($appID){
		$this->sql = $this->conn->prepare("Select * FROM applications WHERE ID = :appID");
		$this->sql->bindParam(':appID', $appID);
		$this->sql->execute();
		$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys

		if(!empty($res)){
			/*create application object*/
			$application = new Application($res[0]);
			
			$this->sql = $this->conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
			$this->sql->bindParam(':id', $application->id);
			$this->sql->execute();
			$resBudget = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			$application->budget = $resBudget;
			
			/* return application */
			return $application;
		}
		else{
			return null;
		}
	}
	
	/*Returns a single final report for a specified application ID*/
	public function getFinalReport($appID){
		$this->sql = $this->conn->prepare("Select * FROM final_reports WHERE ApplicationID = :appID");
		$this->sql->bindParam(':appID', $appID);
		$this->sql->execute();
		$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
		
		if(!empty($res)){
			/*create application object*/
			$FinalReport = new FinalReport($res[0]);

			/* return FinalReport object */
			return $FinalReport;
		}
		else{
			return null;
		}
		
	}


	/* Returns array of all applications that still need to be signed by a specific user(via email address)*/
	public function getApplicationsToSign($email, $broncoNetID){
		if ($email != ""){ //valid email
			/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
			$this->sql = $this->conn->prepare("Select * FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND Applicant != :broncoNetID AND Status = 'Pending'");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			$applicationsArray = []; //create new array of applications
			
			/*go through all applications, adding them to the array*/
			for($i = 0; $i < count($res); $i++){
				$application = new Application($res[$i]); //initialize
				
				$this->sql = $this->conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
				$this->sql->bindParam(':id', $application->id);
				$this->sql->execute();
				$resBudget = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				$application->budget = $resBudget;
				
				/*add application to array*/
				$applicationsArray[$i] = $application;
			}

			/* return array */
			return $applicationsArray;
		}
	}


	/* Returns array of all applications signed by this email address's owner*/
	public function getSignedApplications($email){
		if ($email != ""){//valid email
			/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
			$this->sql = $this->conn->prepare("Select * FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NOT NULL AND DepartmentChairEmail != Email");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			$applicationsArray = []; //create new array of applications
			
			/*go through all applications, adding them to the array*/
			for($i = 0; $i < count($res); $i++){
				$application = new Application($res[$i]); //initialize
				
				$this->sql = $this->conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
				$this->sql->bindParam(':id', $application->id);
				$this->sql->execute();
				$resBudget = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				$application->budget = $resBudget;
				
				/*add application to array*/
				$applicationsArray[$i] = $application;
			}
			
			/* return array */
			return $applicationsArray;
		}
	}

	/* Returns number of applications that this user(via email address) needs to sign (for department chairs) */
	public function getNumberOfApplicationsToSign($email, $broncoNetID){
		if ($email != ""){//valid email
			/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND Applicant != :broncoNetID AND Status = 'Pending'");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys

			return $res[0][0];
		}
	}

	/* Returns number of previously signed applications from this dept. chair's email address*/
	public function getNumberOfSignedApplications($email){
		if ($email != ""){//valid email
			/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's email == this email*/
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NOT NULL AND DepartmentChairEmail != Email");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			return $res[0][0];
		}
	}

	
	/* Returns array of all applications for a specified BroncoNetID, or ALL applications if no ID is provided */
	public function getApplications($broncoNetID){
		if ($broncoNetID != ""){//valid username
			/* Select only applications that this user has has submitted */
			$this->sql = $this->conn->prepare("Select * FROM applications WHERE Applicant = :broncoNetID");
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
		}
		else{//no username
			/* Select all applications */
			$this->sql = $this->conn->prepare("Select * FROM applications");
		}
		
		$this->sql->execute();
		$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
		
		$applicationsArray = []; //create new array of applications
		
		/*go through all applications, adding them to the array*/
		for($i = 0; $i < count($res); $i++){
			$application = new Application($res[$i]); //initialize
			
			$this->sql = $this->conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
			$this->sql->bindParam(':id', $application->id);
			$this->sql->execute();
			$resBudget = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			$application->budget = $resBudget;
			
			/*add application to array*/
			$applicationsArray[$i] = $application;
		}
		
		/* return array */
		return $applicationsArray;
	}


	/* Returns number of all applications for a specified BroncoNetID, or number of ALL applications if no ID is provided */
	public function getNumberOfApplications($broncoNetID){
		if ($broncoNetID != ""){//valid username
			/* Select only applications that this user has has submitted */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Applicant = :broncoNetID");
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
		}
		else{//no username
			/* Select all applications */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications");
		}
		
		$this->sql->execute();
		$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
		
		return $res[0][0];
	}
	
	/* Returns array of application approvers */
	public function getApplicationApprovers(){
		$this->sql = $this->conn->prepare("Select BroncoNetID, Name FROM application_approval");
		$this->sql->execute();
		return $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
	}
	
	/* Returns array of committee members */
	public function getCommittee(){
		$this->sql = $this->conn->prepare("Select BroncoNetID, Name FROM committee");
		$this->sql->execute();
		return $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
	}
	
	/* Returns array of final report approvers */
	public function getFinalReportApprovers(){
		$this->sql = $this->conn->prepare("Select BroncoNetID, Name FROM final_report_approval");
		$this->sql->execute();
		return $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
	}
	
	/* Add an admin to the administrators table */
	public function addAdmin($broncoNetID, $name){
		if ($broncoNetID != "" && $name != ""){//valid params
			$this->logger->logInfo("Inserting administrator (".$broncoNetID.", ".$name.")", $broncoNetID, dirname(__FILE__));
			$this->sql = $this->conn->prepare("INSERT INTO administrators(BroncoNetID, Name) VALUES(:id, :name)");
			$this->sql->bindParam(':id', $broncoNetID);
			$this->sql->bindParam(':name', $name);
			return $this->sql->execute();
		}
	}
	
	/* Add a committee member to the committee table */
	public function addCommittee($broncoNetID, $name){
		if ($broncoNetID != "" && $name != ""){//valid params
			$this->logger->logInfo("Inserting committee member (".$broncoNetID.", ".$name.")", $broncoNetID, dirname(__FILE__));
			$this->sql = $this->conn->prepare("INSERT INTO committee(BroncoNetID, Name) VALUES(:id, :name)");
			$this->sql->bindParam(':id', $broncoNetID);
			$this->sql->bindParam(':name', $name);
			return $this->sql->execute();
		}
	}
	
	/* Add a final report approver to the final_report_approval table */
	public function addFinalReportApprover($broncoNetID, $name){
		if ($broncoNetID != "" && $name != ""){//valid params
			$this->logger->logInfo("Inserting final report approver (".$broncoNetID.", ".$name.")", $broncoNetID, dirname(__FILE__));
			$this->sql = $this->conn->prepare("INSERT INTO final_report_approval(BroncoNetID, Name) VALUES(:id, :name)");
			$this->sql->bindParam(':id', $broncoNetID);
			$this->sql->bindParam(':name', $name);
			return $this->sql->execute();
		}
	}
	
	/* Add an application approver to the application_approval table */
	public function addApplicationApprover($broncoNetID, $name){
		if ($broncoNetID != "" && $name != ""){//valid params
			$this->logger->logInfo("Inserting application approver (".$broncoNetID.", ".$name.")", $broncoNetID, dirname(__FILE__));
			$this->sql = $this->conn->prepare("INSERT INTO application_approval(BroncoNetID, Name) VALUES(:id, :name)");
			$this->sql->bindParam(':id', $broncoNetID);
			$this->sql->bindParam(':name', $name);
			return $this->sql->execute();
		}
	}
	
	
	/* Remove an admin to the administrators table */
	public function removeAdmin($broncoNetID){
		if ($broncoNetID != ""){//valid params
			$this->sql = $this->conn->prepare("DELETE FROM administrators WHERE BroncoNetID = :id");
			$this->sql->bindParam(':id', $broncoNetID);
			return $this->sql->execute();
		}
	}
	
	/* Remove a committee member to the committee table */
	public function removeCommittee($broncoNetID){
		if ($broncoNetID != ""){//valid params
			$this->sql = $this->conn->prepare("DELETE FROM committee WHERE BroncoNetID = :id");
			$this->sql->bindParam(':id', $broncoNetID);
			return $this->sql->execute();
		}
	}
	
	/* Remove a final report approver to the final_report_approval table */
	public function removeFinalReportApprover($broncoNetID){
		if ($broncoNetID != ""){//valid params
			$this->sql = $this->conn->prepare("DELETE FROM final_report_approval WHERE BroncoNetID = :id");
			$this->sql->bindParam(':id', $broncoNetID);
			return $this->sql->execute();
		}
	}
	
	/* Remove an application approver to the application_approval table */
	public function removeApplicationApprover($broncoNetID){
		if ($broncoNetID != ""){//valid params
			$this->sql = $this->conn->prepare("DELETE FROM application_approval WHERE BroncoNetID = :id");
			$this->sql->bindParam(':id', $broncoNetID);
			return $this->sql->execute();
		}
	}
	



	/*Checks if a user is an administrator-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	public function isAdministrator($broncoNetID){
		$is = false; //initialize boolean to false
		$adminList = $this->getAdministrators();//grab admin list
		
		foreach($adminList as $i){//loop through admins
			$newID = $i[0];
			if(strcmp($newID, $broncoNetID) == 0){
				$is = true;
				break; //no need to continue loop
			}
		}
		return $is;
	}
	
	/*Checks if a user is an application approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	public function isApplicationApprover($broncoNetID){
		$is = false; //initialize boolean to false
		$approverList = $this->getApplicationApprovers();//grab application approver list
		
		foreach($approverList as $i){//loop through approvers
			$newID = $i[0];
			if($newID == $broncoNetID){
				$is = true;
				break; //no need to continue loop
			}
		}
		return $is;
	}
	
	/*Checks if a user is a final report approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	public function isFinalReportApprover($broncoNetID){
		$is = false; //initialize boolean to false
		$approverList = $this->getFinalReportApprovers();//grab final report approver list
		
		foreach($approverList as $i){//loop through approvers
			$newID = $i[0];
			if($newID == $broncoNetID){
				$is = true;
				break; //no need to continue loop
			}
		}
		return $is;
	}
	
	/*Checks if a user is a committee member-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	public function isCommitteeMember($broncoNetID){
		$is = false; //initialize boolean to false
		$committeeList = $this->getCommittee();//grab committee member list
		
		foreach($committeeList as $i){//loop through committee members
			$newID = $i[0];
			if($newID == $broncoNetID){
				$is = true;
				break; //no need to continue loop
			}
		}
		return $is;
	}
	
	/*Checks if a user is allowed to create an application
	Rules:
	2. Must not have a pending application
	3. Must not have received funding within the past year (use rules from cycles.php)
	4. Must not be an admin, committee member, application approver, or final report approver (HIGE Staff)
	&nextCycle = false if checking current cycle, or true if checking next cycle*/
	public function isUserAllowedToCreateApplication($broncoNetID, $nextCycle){
		//make sure user is not part of HIGE staff
		if(!$this->isCommitteeMember($broncoNetID) && !$this->isApplicationApprover($broncoNetID) && !$this->isAdministrator($broncoNetID) && !$this->isFinalReportApprover($broncoNetID)){
			$lastApproved = false; //set to true if last approved application was long enough ago
			$lastApprovedApp = $this->getMostRecentApprovedApplication($broncoNetID);
			
			if($lastApprovedApp != null){//if a previous application exists
				$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateSubmitted);
				$lastCycle = $this->cycles->getCycleName($lastDate, $lastApprovedApp->nextCycle, false);
				$curCycle = $this->cycles->getCycleName(DateTime::createFromFormat('Y/m/d', date("Y/m/d")), $nextCycle, false);
				$lastApproved = $this->cycles->areCyclesFarEnoughApart($lastCycle, $curCycle); //check cycles in function down below
			}
			else{//no previous application
				$lastApproved = true;
			}
			
			if(!$this->hasPendingApplication($broncoNetID) && !$this->hasApplicationOnHold($broncoNetID) && $lastApproved){
				return true;
			}
			else {
				return false;
			}
		}
		else{//user is part of HIGE staff, so they cannot apply
			return false;
		}
	}

	/*Checks if a user is allowed to create a final report for a specified appID
	Rules:
	Application must not already have a final report
	Application must belong to the user
	Application must have 'Approved' status*/
	public function isUserAllowedToCreateFinalReport($broncoNetID, $appID){
		if($this->doesUserOwnApplication($broncoNetID, $appID) && !$this->getFinalReport($appID) && $this->isApplicationApproved($appID)){
			return true;
		}
		else{
			return false;
		}
	}
	
	/*Checks if a user is allowed to freely see applications. ALSO USED FOR VIEWING FINAL REPORTS
	Rules: Must be an application approver, final report approver, administrator, or a committee member*/
	public function isUserAllowedToSeeApplications($broncoNetID){
		if($this->isCommitteeMember($broncoNetID) || $this->isApplicationApprover($broncoNetID) || $this->isAdministrator($broncoNetID) || $this->isFinalReportApprover($broncoNetID)){
			return true;
		}
		else{
			return false;
		}
	}




	/* Returns true if the given email == the deptChairEmail for a given app ID, or false otherwise */
	public function isUserAllowedToSignApplication($email, $appID, $broncoNetID){
		$is = false; //initialize boolean to false
		
		if ($email != "" && $appID != ""){//valid email & ID
			/* Only count applications meant for this person that HAVEN'T already been signed; also, don't grab any where the applicant's broncoNetID == this broncoNetID*/
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND Applicant != :broncoNetID");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->bindParam(':appID', $appID);
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//this is the correct user to sign
				$is = true;
			}
		}
		
		return $is;
	}

	/* Returns true if this user signed a given application */
	public function hasUserSignedApplication($email, $appID){
		$is = false; //initialize boolean to false
		
		if ($email != "" && $appID != ""){//valid email & ID
			/* Only count applications meant for this person that HAVE already been signed */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NOT NULL");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->bindParam(':appID', $appID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//this is the correct user to sign
				$is = true;
			}
		}
		
		return $is;
	}

	/* Returns true if this user is the department chair specified by an application */
	public function isUserDepartmentChair($email, $appID, $broncoNetID){
		$is = false; //initialize boolean to false
		
		if ($email != "" && $appID != ""){//valid email & ID
			/* Only count applications meant for this person */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND DepartmentChairEmail = :dEmail AND Applicant != :broncoNetID");
			$this->sql->bindParam(':dEmail', $email);
			$this->sql->bindParam(':appID', $appID);
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//this is the correct user to sign
				$is = true;
			}
		}
		
		return $is;
	}

	/*returns true if the user owns this application, or false if they don't*/
	public function doesUserOwnApplication($broncoNetID, $appID){
		$is = false; //initialize boolean to false
		
		if ($broncoNetID != "" && $appID != ""){//valid broncoNetID & appID
			/* Only count applications with the right ID that this user has submitted */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :appID AND Applicant = :broncoNetID");
			$this->sql->bindParam(':appID', $appID);
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//this is the correct user to sign
				$is = true;
			}
		}
		
		return $is;
	}

	
	
	
	/* Returns true if applicant's application has been approved, or false otherwise */
	public function isApplicationApproved($appID){
		$is = false;
		
		if ($appID != ""){//valid Id
			/* Select only applications that this user has has submitted */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :id AND Status = 'Approved'");
			$this->sql->bindParam(':id', $appID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//at least one result
				$is = true;
			}
		}
		
		return $is;
	}
	
	/* Returns 1 if applicant's application has been signed, or 0 otherwise */
	public function isApplicationSigned($appID){
		
		if ($appID != ""){//valid Id
			/* Select only applications that this user has has submitted that have a signature */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE ID = :id AND DepartmentChairSignature IS NOT NULL");
			$this->sql->bindParam(':id', $appID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){ //at least one result
				return 1;
			}else{
				return 0;
			}
		}else
			return 0;
	}
	
	/* Returns true if this applicant has a pending application, or false otherwise */
	public function hasPendingApplication($broncoNetID){
		$ret = false;
		
		if ($broncoNetID != ""){//valid username
			/* Select only pending applications that this user has has submitted */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Status = 'Pending' AND Applicant = :broncoNetID");
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//at least one result
				$ret = true;
			}
		}
		
		return $ret;
	}

	/* Returns true if this applicant has an application on hold, or false otherwise */
	public function hasApplicationOnHold($broncoNetID){
		$ret = false;
		
		if ($broncoNetID != ""){//valid username
			/* Select only pending applications that this user has has submitted */
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Status = 'Hold' AND Applicant = :broncoNetID");
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//at least one result
				$ret = true;
			}
		}
		
		return $ret;
	}
	
	/* Returns true if this applicant has a final report already created, or false otherwise */
	public function hasFinalReport($appID){
		$ret = false;
		
		if ($appID != ""){//valid username
			$this->sql = $this->conn->prepare("Select COUNT(*) AS Count FROM final_reports WHERE ApplicationID = :appID");
			$this->sql->bindParam(':appID', $appID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
			
			if($res[0][0] > 0){//at least one result
				$ret = true;
			}
		}
		
		return $ret;
	}
	
	/*Get the most recently approved application of a user- return null if none*/
	public function getMostRecentApprovedApplication($broncoNetID){
		$mostRecent = null;
		
		if ($broncoNetID != ""){//valid username
			/* Select the most recent from this applicant */
			$this->sql = $this->conn->prepare("SELECT * FROM applications WHERE Applicant = :broncoNetID AND Status = 'Approved' ORDER BY Date DESC LIMIT 1");
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys

			if($res != null){
				$mostRecent = new Application($res[0]); //initialize
				
				$this->sql = $this->conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
				$this->sql->bindParam(':id', $mostRecent->id);
				$this->sql->execute();
				$resBudget = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys
				
				$mostRecent->budget = $resBudget;
			}
			
			/* return value */
			return $mostRecent;
		}
		
	}

	/*Get all past approved cycles for this user- return null if none*/
	public function getPastApprovedCycles($broncoNetID){
		$pastCycles = null;
		
		if ($broncoNetID != ""){//valid username
			/* Select all dates from past approved applications */
			$this->sql = $this->conn->prepare("SELECT Date, NextCycle FROM applications WHERE Applicant = :broncoNetID AND Status = 'Approved'");
			$this->sql->bindParam(':broncoNetID', $broncoNetID);
			$this->sql->execute();
			$res = $this->sql->fetchAll(PDO::FETCH_NUM); //return indexes as keys

			if($res != null){
				$pastCycles = []; //initialize array

				foreach($res as $i){//go through all the dates, grab the cycles
					if(!empty($i)){
						array_push($pastCycles, $this->cycles->getCycleName(DateTime::createFromFormat('Y-m-d', $i[0]), $i[1], false)); //push the cycle name to the array
					}
				}
				$pastCycles = $this->cycles->sortCycles($pastCycles); //sort cycles in descending order
			}
			
			/* return value */
			return $pastCycles;
		}	
	}
	
	/*return an array of the maximum lengths of every column in the applications table*/
	public function getApplicationsMaxLengths(){
		$this->sql = $this->conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = '" . $this->settings["database_name"] . "' AND table_name = 'applications'");
		$this->sql->execute();
		return $this->sql->fetchAll(); //return indexes AND names as keys
	}
	
	/*return an array of the maximum lengths of every column in the applications_budgets table*/
	public function getApplicationsBudgetsMaxLengths(){
		$this->sql = $this->conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = '" . $this->settings["database_name"] . "' AND table_name = 'applications_budgets'");
		$this->sql->execute();
		return $this->sql->fetchAll(); //return indexes AND names as keys
	}

	/*return an array of the maximum lengths of every column in the final_reports table*/
	public function getFinalReportsMaxLengths(){
		$this->sql = $this->conn->prepare("Select COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema = '" . $this->settings["database_name"] . "' AND table_name = 'final_reports'");
		$this->sql->execute();
		return $this->sql->fetchAll(); //return indexes AND names as keys
	}
	
	/*Update an application to be approved*/
	public function approveApplication($id, $amount, $broncoNetID){
		if ($id != ""){//valid application id
			$this->logger->logInfo("Approving application id: ".$id." for $".$amount, $broncoNetID, dirname(__FILE__));
			/*Update any application with the given id*/
			$this->sql = $this->conn->prepare("UPDATE applications SET Status = 'Approved', AmountAwarded = :aw WHERE ID = :id");
			$this->sql->bindParam(':aw', $amount);
			$this->sql->bindParam(':id', $id);
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
	}
	
	/*Update an application to be denied*/
	public function denyApplication($id, $broncoNetID){
		if ($id != ""){//valid application id
			$this->logger->logInfo("Denying application id: ".$id, $broncoNetID, dirname(__FILE__));
			/*Update any application with the given id*/
			$this->sql = $this->conn->prepare("UPDATE applications SET Status = 'Denied', AmountAwarded = 0 WHERE ID = :id");
			$this->sql->bindParam(':id', $id);
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
	}
	
	
	/*Update an application to be put on hold*/
	public function holdApplication($id, $broncoNetID){
		if ($id != ""){//valid application id
			$this->logger->logInfo("Holding application id: ".$id, $broncoNetID, dirname(__FILE__));
			/*Update any application with the given id*/
			$this->sql = $this->conn->prepare("UPDATE applications SET Status = 'Hold', AmountAwarded = 0 WHERE ID = :id");
			$this->sql->bindParam(':id', $id);
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
	}
	
	/*Update an application to be signed*/
	public function signApplication($id, $signature, $broncoNetID){
		if ($id != "" && $signature != ""){//valid application id & sig
			$this->logger->logInfo("Signing pplication id: ".$id." with signature: ".$signature, $broncoNetID, dirname(__FILE__));
			/*Update any application with the given id*/
			$this->sql = $this->conn->prepare("UPDATE applications SET DepartmentChairSignature = :sig WHERE ID = :id");
			$this->sql->bindParam(':sig', $signature);
			$this->sql->bindParam(':id', $id);
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
	}
	
	

	/*Update a Final Report to be approved*/
	public function approveFinalReport($id, $broncoNetID){
		if ($id != ""){//valid application id
			$this->logger->logInfo("Approving final report id: ".$id, $broncoNetID, dirname(__FILE__));
			/*Update any application with the given id*/
			$this->sql = $this->conn->prepare("UPDATE final_reports SET Status = 'Approved' WHERE ApplicationID = :id");
			$this->sql->bindParam(':id', $id);
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
	}

	/*Update a Final Report to be on hold*/
	public function holdFinalReport($id, $broncoNetID){
		if ($id != ""){//valid application id
			$this->logger->logInfo("Holding final report id: ".$id, $broncoNetID, dirname(__FILE__));
			/*Update any application with the given id*/
			$this->sql = $this->conn->prepare("UPDATE final_reports SET Status = 'Hold' WHERE ApplicationID = :id");
			$this->sql->bindParam(':id', $id);
			$this->sql->execute();
			return $this->sql->rowCount() ? true : false; //will be true if the row was updated to a new amount
		}
	}
	
	
	/*
	Insert an application into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use. If $updating is true, update this entry rather than inserting a new one.
	Most fields are self-explanatory. It's worth mentioning that $budgetArray is a 2-dimensional array of expenses.
		$budgetArray[i][0] is the name of the expense
		$budgetArray[i][1] is the details on the expense
		$budgetArray[i][2] is the actual cost

	This function returns a data array; If the application is successfully inserted or updated, then data["success"] is set to true.
	Otherwise, data["success"] is set to false, and data["errors"] is set to an array of errors following the format of ["field", "message"], where field corresponds to one of the application's fields.
	
	*/
	public function insertApplication($updating, $updateID, $broncoNetID, $name, $email, $department, $deptChairEmail, $travelFrom, $travelTo,
			$activityFrom, $activityTo, $title, $destination, $amountRequested, $purpose1, $purpose2, $purpose3,
			$purpose4Other, $otherFunding, $proposalSummary, $goal1, $goal2, $goal3, $goal4, $nextCycle, $budgetArray){

		$this->logger->logInfo("Inserting application (updating? ".$updating.", updateID: ".$updateID.")", $broncoNetID, dirname(__FILE__));
		$errors = array(); // array to hold validation errors
		$data = array(); // array to pass back data

		$newAppID = 0; //set this to the new application's ID if successful

		if(!$updating){//First, add this user to the applicants table IF they don't already exist
			$this->insertApplicantIfNew($broncoNetID);
		}
		
		/*Sanitize everything - keep quotes. This may be a bit redundant since PDO already sanitizes everything in prepared statements*/
		try{
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
			
			/*go through budget array*/
			foreach($budgetArray as $i){
				if(!empty($i)){
					if(isset($i["expense"])){ $i["expense"] = trim(filter_var($i["expense"], FILTER_SANITIZE_STRING)); }
					if(isset($i["details"])){ $i["details"] = trim(filter_var($i["details"], FILTER_SANITIZE_STRING)); }
				}
			}
			
		}
		catch(Exception $e){
			$errorMessage = $this->logger->logError("Application not saved, exception when sanitizing fields. Exception: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
			$errors["other"] = "Error: Application not saved, exception when sanitizing fields. ".$errorMessage;
		}
		
		/*Now validate everything that needs it*/
		if(empty($errors)){//no errors yet

			/*Make sure necessary strings aren't empty*/
			if($name === ''){
				$errors["name"] = "Name field is required.";
			}

			if($email === ''){
				$errors["email"] = "Email field is required.";
			}
			else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
				$errors["email"] = "Email address is invalid (invalid email format).";
			}

			if($department === ''){
				$errors["department"] = "Department field is required.";
			}
			if($title === ''){
				$errors["title"] = "Title field is required.";
			}
			if($destination === ''){
				$errors["destination"] = "Destination field is required.";
			}
			if($proposalSummary === ''){
				$errors["proposalSummary"] = "Proposal Summary field is required.";
			}

			if($deptChairEmail === ''){
				$errors["deptChairEmail"] = "Department Chair Email field is required.";
			}
			else if(!filter_var($deptChairEmail, FILTER_VALIDATE_EMAIL)){
				$errors["deptChairEmail"] = "Department chair's email address is invalid (invalid email format).";
			}
			else{
				list($em, $domain) = explode('@', $deptChairEmail);
				/*Make sure department chair's email address is a wmich address*/
				if (!strstr(strtolower($domain), "wmich.edu")){
					$errors["deptChairEmail"] = "Department chair's email address must be a wmich.edu address.";
				}
			}

			if($travelFrom == null || $travelFrom === ''){
				$errors["travelFrom"] = "Travel From field is required.";
			}
			if($travelTo == null || $travelTo === ''){
				$errors["travelTo"] = "Travel To field is required.";
			}
			if($activityFrom == null || $activityFrom === ''){
				$errors["activityFrom"] = "Activity From field is required.";
			}
			if($activityTo == null || $activityTo === ''){
				$errors["activityTo"] = "Activity To field is required.";
			}


			if($nextCycle === ''){
				$errors["cycleChoice"] = "Must choose a cycle.";
			}


			/*Make sure dates are acceptable*/
			if(!isset($errors["travelFrom"]) && !isset($errors["activityFrom"])){//making sure dates were set
				if($travelFrom > $activityFrom){
					$errors["travelFrom"] = "Travel dates are impossible (activity cannot start before travel!).";
				}
			}	
			if(!isset($errors["activityFrom"]) && !isset($errors["activityTo"])){//making sure dates were set
				if($activityFrom > $activityTo){
					$errors["activityFrom"] = "Travel dates are impossible (activity cannot end before it begins!).";
				}
			}
			if(!isset($errors["activityTo"]) && !isset($errors["travelTo"])){//making sure dates were set
				if($activityTo > $travelTo){
					$errors["activityTo"] = "Travel dates are impossible (travel cannot end before activity ends!).";
				}
			}

			/*Make sure at least one purpose is chosen*/
			if($purpose1 == 0 && $purpose2 == 0 && $purpose3 == 0 && $purpose4Other === ''){
				$errors["purpose"] = "At least one purpose must be selected.";
			}
			/*Make sure at least one goal is chosen*/
			if($goal1 == 0 && $goal2 == 0 && $goal3 == 0 && $goal4 == 0){
				$errors["goal"] = "At least one goal must be selected.";
			}
			if ($amountRequested <= 0){
				$errors["amountRequested"] = "Amount requested must be greater than $0.";
			}
		}

		/*Make sure cycle is allowed; ignore this check if an admin is updating*/
		if(!$updating){
			$lastApprovedApp = $this->getMostRecentApprovedApplication($broncoNetID);
			if($lastApprovedApp != null){//if a previous application exists
				$lastDate = DateTime::createFromFormat('Y-m-d', $lastApprovedApp->dateSubmitted);
				$lastCycle = $this->cycles->getCycleName($lastDate, $lastApprovedApp->nextCycle, false);
				$curCycle = $this->cycles->getCycleName(DateTime::createFromFormat('Y/m/d', date("Y/m/d")), $nextCycle, false);
				$lastApproved = $this->cycles->areCyclesFarEnoughApart($lastCycle, $curCycle); //check cycle age

				if(!$lastApproved){
					$errors["other"] = "Applicant is not allowed to apply for the specified cycle (not enough cycles have passed since last approved application).";
				}
			}
		}

		if(sizeof($budgetArray) != 0){//at least one budget item
			/*go through budget array*/
			$index = 0; //index of budget item
			foreach($budgetArray as $item){

				if(!empty($item)){
					if(!isset($item["expense"])){
						$errors["budgetArray ".($index+1)." expense"] = "Budget expense is required.";
					}
					else if($item["expense"] === ''){
						$errors["budgetArray ".($index+1)." expense"] = "Budget expense is required.";
					}

					if(!isset($item["details"])){
						$errors["budgetArray ".($index+1)." details"] = "Budget details are required.";
					}
					else if($item["details"] === ''){
						$errors["budgetArray ".($index+1)." details"] = "Budget details are required.";
					}

					if(!isset($item["amount"])){
						$errors["budgetArray ".($index+1)." amount"] = "Budget amount is required.";
					}
					else if($item["amount"] <= 0){
						$errors["budgetArray ".($index+1)." amount"] = "Budget amount must be greater than $0";
					}
				}
				else{
					$errors["budgetArray ".($index+1)] = "Budget item must not be empty.";
				}

				$index++;
			}
		}
		else{//no budget items
			$errors["budgetArray"] = "There must be at least one budget item.";
		}
		
		/*Now insert new application into database*/
		if(empty($errors)){//no errors yet
			if(!$updating){//adding, not updating
				try{
					$curDate = date("Y/m/d");
					
					$this->conn->beginTransaction(); //begin atomic transaction
					
					$this->sql = $this->conn->prepare("INSERT INTO applications(Applicant, Date, Name, Department, Email, Title, TravelStart, TravelEnd, EventStart, EventEnd, Destination, AmountRequested, 
						IsResearch, IsConference, IsCreativeActivity, IsOtherEventText, OtherFunding, ProposalSummary, FulfillsGoal1, FulfillsGoal2, FulfillsGoal3, FulfillsGoal4, DepartmentChairEmail, NextCycle, Status) 
						VALUES(:applicant, :date, :name, :department, :email, :title, :travelstart, :travelend, :eventstart, :eventend, :destination, :amountrequested, 
						:isresearch, :isconference, :iscreativeactivity, :isothereventtext, :otherfunding, :proposalsummary, :fulfillsgoal1, :fulfillsgoal2, :fulfillsgoal3, :fulfillsgoal4, :departmentchairemail, :nextcycle, 'Pending')");
					$this->sql->bindParam(':applicant', $broncoNetID);
					$this->sql->bindParam(':date', $curDate); //create a new date right when inserting to save current time
					$this->sql->bindParam(':name', $name);
					$this->sql->bindParam(':department', $department);
					$this->sql->bindParam(':email', $email);
					$this->sql->bindParam(':title', $title);
					$this->sql->bindParam(':travelstart', $travelFrom);
					$this->sql->bindParam(':travelend', $travelTo);
					$this->sql->bindParam(':eventstart', $activityFrom);
					$this->sql->bindParam(':eventend', $activityTo);
					$this->sql->bindParam(':destination', $destination);
					$this->sql->bindParam(':amountrequested', $amountRequested);
					$this->sql->bindParam(':isresearch', $purpose1);
					$this->sql->bindParam(':isconference', $purpose2);
					$this->sql->bindParam(':iscreativeactivity', $purpose3);
					$this->sql->bindParam(':isothereventtext', $purpose4Other);
					$this->sql->bindParam(':otherfunding', $otherFunding);
					$this->sql->bindParam(':proposalsummary', $proposalSummary);
					$this->sql->bindParam(':fulfillsgoal1', $goal1);
					$this->sql->bindParam(':fulfillsgoal2', $goal2);
					$this->sql->bindParam(':fulfillsgoal3', $goal3);
					$this->sql->bindParam(':fulfillsgoal4', $goal4);
					$this->sql->bindParam(':departmentchairemail', $deptChairEmail);
					$this->sql->bindParam(':nextcycle', $nextCycle);
					
					if ($this->sql->execute() === TRUE){//query executed correctly
						
						/*get the application ID of the just-added application*/
						$this->sql = $this->conn->prepare("select max(ID) from applications where Applicant = :applicant LIMIT 1");
						$this->sql->bindParam(':applicant', $broncoNetID);
						$this->sql->execute();
						$newAppID = $this->sql->fetchAll(PDO::FETCH_NUM)[0][0];//now we have the current ID!
						
						try{
							foreach($budgetArray as $i){//go through budget array
								if(!empty($i)){

									$this->sql = $this->conn->prepare("INSERT INTO applications_budgets(ApplicationID, Name, Cost, Details) VALUES (:appID, :name, :cost, :details)");
									$this->sql->bindParam(':appID', $newAppID);
									$this->sql->bindParam(':name', $i['expense']);
									$this->sql->bindParam(':cost', $i['amount']);
									$this->sql->bindParam(':details', $i['details']);
									
									if ($this->sql->execute() !== TRUE){//query failed
										$errorMessage = $this->logger->logError("Application not saved, query failed to insert budget items.", $broncoNetID, dirname(__FILE__), true);
										$errors["other"] = "Error: Application not saved, query failed to insert budget items. ".$errorMessage;
										break;
									}
								}
							}

							//at this point, if there are no errors, we should be able to commit to the database. Otherwise we should rollback.
							if(empty($errors)){
								$this->conn->commit();
							}
							else{
								$this->conn->rollBack();
							}
						}
						catch(Exception $e){
							$errorMessage = $this->logger->logError("Application not saved, internal exception when trying to insert budget items: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
							$errors["other"] = "Error: Application not saved, internal exception when trying to insert budget items. ".$errorMessage;
						}
					} 
					else{//query failed
						$errorMessage = $this->logger->logError("Application not saved, query failed to insert application.", $broncoNetID, dirname(__FILE__), true);
						$errors["other"] = "Error: Application not saved, query failed to insert application. ".$errorMessage;
					}
				}
				catch(Exception $e){
					$errorMessage = $this->logger->logError("Application not saved, internal exception when trying to insert application: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
					$errors["other"] = "Error: Application not saved, internal exception when trying to insert application. ".$errorMessage;
				}
			}
			else{//just updating
				try{
					$this->conn->beginTransaction(); //begin atomic transaction
					
					$this->sql = $this->conn->prepare("UPDATE applications SET Name=:name, Department=:department, Email=:email, Title=:title, TravelStart=:travelstart, TravelEnd=:travelend, EventStart=:eventstart, EventEnd=:eventend,
						Destination=:destination, AmountRequested=:amountrequested, IsResearch=:isresearch, IsConference=:isconference, IsCreativeActivity=:iscreativeactivity, IsOtherEventText=:isothereventtext,
						OtherFunding=:otherfunding, ProposalSummary=:proposalsummary, FulfillsGoal1=:fulfillsgoal1, FulfillsGoal2=:fulfillsgoal2, FulfillsGoal3=:fulfillsgoal3, FulfillsGoal4=:fulfillsgoal4,
						DepartmentChairEmail=:departmentchairemail, NextCycle=:nextcycle WHERE ID=:id");
					
					$this->sql->bindParam(':name', $name);
					$this->sql->bindParam(':department', $department);
					$this->sql->bindParam(':email', $email);
					$this->sql->bindParam(':title', $title);
					$this->sql->bindParam(':travelstart', $travelFrom);
					$this->sql->bindParam(':travelend', $travelTo);
					$this->sql->bindParam(':eventstart', $activityFrom);
					$this->sql->bindParam(':eventend', $activityTo);
					$this->sql->bindParam(':destination', $destination);
					$this->sql->bindParam(':amountrequested', $amountRequested);
					$this->sql->bindParam(':isresearch', $purpose1);
					$this->sql->bindParam(':isconference', $purpose2);
					$this->sql->bindParam(':iscreativeactivity', $purpose3);
					$this->sql->bindParam(':isothereventtext', $purpose4Other);
					$this->sql->bindParam(':otherfunding', $otherFunding);
					$this->sql->bindParam(':proposalsummary', $proposalSummary);
					$this->sql->bindParam(':fulfillsgoal1', $goal1);
					$this->sql->bindParam(':fulfillsgoal2', $goal2);
					$this->sql->bindParam(':fulfillsgoal3', $goal3);
					$this->sql->bindParam(':fulfillsgoal4', $goal4);
					$this->sql->bindParam(':departmentchairemail', $deptChairEmail);
					$this->sql->bindParam(':nextcycle', $nextCycle);
					$this->sql->bindParam(':id', $updateID);
					
					if ($this->sql->execute() === TRUE){//query executed correctly

						/*delete previous budget items*/
						$this->sql = $this->conn->prepare("DELETE FROM applications_budgets WHERE ApplicationID=:id");
						$this->sql->bindParam(':id', $updateID);

						if ($this->sql->execute() === TRUE){//query executed correctly

							/*go through budget array*/
							foreach($budgetArray as $i){
								if(!empty($i)){
									$this->sql = $this->conn->prepare("INSERT INTO applications_budgets(ApplicationID, Name, Cost, Details) VALUES (:appID, :name, :cost, :details)");
									$this->sql->bindParam(':appID', $updateID);
									$this->sql->bindParam(':name', $i['expense']);
									$this->sql->bindParam(':cost', $i['amount']);
									$this->sql->bindParam(':details', $i['details']);
									
									if ($this->sql->execute() !== TRUE){//query failed
										$errorMessage = $this->logger->logError("Application not saved, query failed to insert new budget items.", $broncoNetID, dirname(__FILE__), true);
										$errors["other"] = "Error: Application not saved, query failed to insert new budget items. ".$errorMessage;
										break;
									}
								}
							}

							//at this point, if there are no errors, we should be able to commit to the database. Otherwise we should rollback.
							if(empty($errors)){
								$this->conn->commit();
							}
							else{
								$this->conn->rollBack();
							}
						}
						else{
							$errorMessage = $this->logger->logError("Application not saved, query failed to delete old budget items.", $broncoNetID, dirname(__FILE__), true);
							$errors["other"] = "Error: Application not saved, query failed to delete old budget items. ".$errorMessage;
						}
					} 
					else{//query failed
						$errorMessage = $this->logger->logError("Application not saved, query failed to update application.", $broncoNetID, dirname(__FILE__), true);
						$errors["other"] = "Error: Application not saved, query failed to update application. ".$errorMessage;
					}
				}
				catch(Exception $e){
					$errorMessage = $this->logger->logError("Application not saved, internal exception when trying to update application: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
					$errors["other"] = "Error: Application not saved, internal exception when trying to update application. ".$errorMessage;
				}
			}
		}

		// response if there are errors
		if ( ! empty($errors)) {
			// if there are items in our errors array, return those errors
			$data['success'] = false;
			$data['errors']  = $errors;
		} 
		else {
			// if there are no errors, return true and the appID
			$data['success'] = true;
			if(!$updating){
				$data['appID'] = $newAppID;
			}
			else{
				$data['appID'] = $updateID;
			}
		}

		return $data; //return both the return code and status
	}
	
	
	
	/*
	Insert a final report into the database WITH SERVER-SIDE VALIDATION. Must pass in a database connection to use. If $updating is true, update this entry rather than inserting a new one.

	This function returns a data array; If the report is successfully inserted or updated, then data["success"] is set to true.
	Otherwise, data["success"] is set to false, and data["errors"] is set to an array of errors following the format of ["field", "message"], where field corresponds to one of the report's fields.
	*/
	public function insertFinalReport($updating, $updateID, $travelFrom, $travelTo, $activityFrom, $activityTo, $projectSummary, $totalAwardSpent, $broncoNetID){
		$this->logger->logInfo("Inserting final report (updating? ".$updating.", updateID: ".$updateID.")", $broncoNetID, dirname(__FILE__));
		$errors = array(); // array to hold validation errors
		$data = array(); // array to pass back data
		
		/*Sanitize everything, may be redundant since PDO sanitizes prepared statements*/
		try{
			$projectSummary = trim(filter_var($projectSummary, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
		}
		catch(Exception $e){
			$errorMessage = $this->logger->logError("Final report not saved, exception when sanitizing fields. Exception: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
			$errors["other"] = "Error: Final report not saved, exception when sanitizing fields. ".$errorMessage;
		}
		
		/*Now validate everything that needs it*/
		if(empty($errors)){//no errors yet
			if($projectSummary === ''){
				$errors["projectSummary"] = "Project Summary field is required.";
			}

			if($totalAwardSpent == null){
				$errors["amountAwardedSpent"] = "Awarded Amount Spent field is required.";
			}

			if($travelFrom == null || $travelFrom === ''){
				$errors["travelFrom"] = "Travel From field is required.";
			}
			if($travelTo == null || $travelTo === ''){
				$errors["travelTo"] = "Travel To field is required.";
			}
			if($activityFrom == null || $activityFrom === ''){
				$errors["activityFrom"] = "Activity From field is required.";
			}
			if($activityTo == null || $activityTo === ''){
				$errors["activityTo"] = "Activity To field is required.";
			}

			/*Make sure dates are acceptable*/
			if(!isset($errors["travelFrom"]) && !isset($errors["activityFrom"])){//making sure dates were set
				if($travelFrom > $activityFrom){
					$errors["travelFrom"] = "Travel dates are impossible (activity cannot start before travel!).";
				}
			}
			if(!isset($errors["activityFrom"]) && !isset($errors["activityTo"])){//making sure dates were set
				if($activityFrom > $activityTo){
					$errors["activityFrom"] = "Travel dates are impossible (activity cannot end before it begins!).";
				}
			}
			if(!isset($errors["activityTo"]) && !isset($errors["travelTo"])){//making sure dates were set
				if($activityTo > $travelTo){
					$errors["activityTo"] = "Travel dates are impossible (travel cannot end before activity ends!).";
				}
			}
		}
		
		/*Now insert new final report into database*/
		if(empty($errors)){//no errors yet
			if(!$updating){//adding, not updating
				try{
					//Get current date
					$curDate = date("Y/m/d");

					$this->conn->beginTransaction(); //begin atomic transaction

					$this->sql = $this->conn->prepare("INSERT INTO final_reports(ApplicationID, TravelStart, TravelEnd, EventStart, EventEnd, ProjectSummary, TotalAwardSpent, Date, Status) 
						VALUES(:applicationid, :travelstart, :travelend, :eventstart, :eventend, :projectsummary, :totalawardspent, :date, 'Pending')");
					$this->sql->bindParam(':applicationid', $updateID);
					$this->sql->bindParam(':travelstart', $travelFrom);
					$this->sql->bindParam(':travelend', $travelTo);
					$this->sql->bindParam(':eventstart', $activityFrom);
					$this->sql->bindParam(':eventend', $activityTo);
					$this->sql->bindParam(':projectsummary', $projectSummary);
					$this->sql->bindParam(':totalawardspent', $totalAwardSpent);
					$this->sql->bindParam(':date', $curDate); //create a new date right when inserting to save current time
					
					if ($this->sql->execute() === TRUE){//query executed correctly
						$this->conn->commit();//commit transaction (probably don't need transactions for this since it is only 1 command)
					} 
					else{//query failed
						$errorMessage = $this->logger->logError("Final report not saved, query failed to insert final report.", $broncoNetID, dirname(__FILE__), true);
						$errors["other"] = "Error: Final report not saved, query failed to insert final report. ".$errorMessage;
						$this->conn->rollBack();
					}
				}
				catch(Exception $e){
					$errorMessage = $this->logger->logError("Final report not saved, internal exception when trying to insert final report: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
					$errors["other"] = "Error: Final report not saved, internal exception when trying to insert final report. ".$errorMessage;
				}
			}
			else{//just updating
				try{
					$this->conn->beginTransaction(); //begin atomic transaction

					$this->sql = $this->conn->prepare("UPDATE final_reports SET TravelStart = :travelstart, TravelEnd = :travelend, EventStart = :eventstart, EventEnd = :eventend,
						ProjectSummary = :projectsummary, TotalAwardSpent = :totalawardspent WHERE ApplicationID = :applicationid");
					$this->sql->bindParam(':travelstart', $travelFrom);
					$this->sql->bindParam(':travelend', $travelTo);
					$this->sql->bindParam(':eventstart', $activityFrom);
					$this->sql->bindParam(':eventend', $activityTo);
					$this->sql->bindParam(':projectsummary', $projectSummary);
					$this->sql->bindParam(':totalawardspent', $totalAwardSpent);
					$this->sql->bindParam(':applicationid', $updateID);
					
					if ($this->sql->execute() === TRUE){//query executed correctly
						$this->conn->commit();//commit transaction (probably don't need transactions for this since it is only 1 command)
					} 
					else{//query failed
						$errorMessage = $this->logger->logError("Final report not saved, query failed to update final report.", $broncoNetID, dirname(__FILE__), true);
						$errors["other"] = "Error: Final report not saved, query failed to update final report. ".$errorMessage;
						$this->conn->rollBack();
					}
				}
				catch(Exception $e){
					$errorMessage = $this->logger->logError("Final report not saved, internal exception when trying to update final report: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
					$errors["other"] = "Error: Final report not saved, internal exception when trying to update final report. ".$errorMessage;
				}
			}
		}
		
		// response if there are errors
		if ( ! empty($errors)) {
			// if there are items in our errors array, return those errors
			$data['success'] = false;
			$data['errors']  = $errors;
		} 
		else {
			// if there are no errors, return true
			$data['success'] = true;
		}
		
		return $data; //return both the return code and status
	}



	/*
	Remove an application from the database. This will remove a final report if present, any associated emails or notes, all budget items, and the base application itself.
	Currently does not remove the associated files from the application's directory, or the application's owner from the database.

	This function returns a data array; If the application is successfully deleted then data["success"] is set to true.
	Otherwise, data["success"] is set to false, and data["error"] will contain any relevant errors.
	
	*/
	public function removeApplication($appID, $broncoNetID){
		$this->logger->logInfo("Removing application id: ".$appID, $broncoNetID, dirname(__FILE__));
		$data = array(); // array to pass back data
		$data["success"] = false; //set to true if successful
		
		try{
			$this->conn->beginTransaction(); //begin atomic transaction

			//first, delete a final report if there is one
			$this->sql = $this->conn->prepare("DELETE FROM final_reports WHERE ApplicationID = :appID");
			$this->sql->bindParam(':appID', $appID);
			if($this->sql->execute() !== TRUE){ //query failed
				$errorMessage = $this->logger->logError("Unable to remove application, query failed to remove associated final reports.", $broncoNetID, dirname(__FILE__), true);
				$data["error"][] = PHP_EOL."Error: Unable to remove application, query failed to remove associated final reports. ".$errorMessage;
			}

			if(!isset($data["error"])){ //no errors yet, move on to deleting emails
				$this->sql = $this->conn->prepare("DELETE FROM emails WHERE ApplicationID = :appID");
				$this->sql->bindParam(':appID', $appID);
				if($this->sql->execute() !== TRUE){ //query failed
					$errorMessage = $this->logger->logError("Unable to remove application, query failed to remove associated emails.", $broncoNetID, dirname(__FILE__), true);
					$data["error"][] = PHP_EOL."Error: Unable to remove application, query failed to remove associated emails. ".$errorMessage;
				}
			}
			if(!isset($data["error"])){ //no errors yet, move on to deleting notes
				$this->sql = $this->conn->prepare("DELETE FROM notes WHERE ApplicationID = :appID");
				$this->sql->bindParam(':appID', $appID);
				if($this->sql->execute() !== TRUE){ //query failed
					$errorMessage = $this->logger->logError("Unable to remove application, query failed to remove associated notes.", $broncoNetID, dirname(__FILE__), true);
					$data["error"][] = PHP_EOL."Error: Unable to remove application, query failed to remove associated notes. ".$errorMessage;
				}
			}
			if(!isset($data["error"])){ //no errors yet, move on to deleting budget items
				$this->sql = $this->conn->prepare("DELETE FROM applications_budgets WHERE ApplicationID = :appID");
				$this->sql->bindParam(':appID', $appID);
				if($this->sql->execute() !== TRUE){ //query failed
					$errorMessage = $this->logger->logError("Unable to remove application, query failed to remove associated budget items.", $broncoNetID, dirname(__FILE__), true);
					$data["error"][] = PHP_EOL."Error: Unable to remove application, query failed to remove associated budget items. ".$errorMessage;
				}
			}
			if(!isset($data["error"])){ //no errors yet, move on to deleting the base application
				$this->sql = $this->conn->prepare("DELETE FROM applications WHERE ID = :appID");
				$this->sql->bindParam(':appID', $appID);
				if($this->sql->execute() !== TRUE){ //query failed
					$errorMessage = $this->logger->logError("Unable to remove application, query failed to remove the base application.", $broncoNetID, dirname(__FILE__), true);
					$data["error"][] = PHP_EOL."Error: Unable to remove application, query failed to remove the base application. ".$errorMessage;
				}
			}

			if(!isset($data["error"])){ //no errors at all, can commit
				$this->conn->commit();
			}
			else{ //an error occurred, so rollback
				$this->conn->rollback();
			}
		}
		catch(Exception $e){
			$errorMessage = $this->logger->logError("Unable to remove application, internal exception when trying to delete application.", $broncoNetID, dirname(__FILE__), true);
			$data["error"][] = PHP_EOL."Error: Unable to remove application, internal exception when trying to delete application. ".$errorMessage;
		}

		//response if there are errors
		if (!isset($data["error"])){
			$data['success'] = true;
		}
		
		return $data; //return both the return code and status
	}

	/* Establishes an sql connection to the database, and returns the object; MAKE SURE TO SET OBJECT TO NULL WHEN FINISHED */
	private function connect(){
		try{
			$this->conn = new AtomicPDO("mysql:host=" . $this->settings["hostname"] . ";dbname=" . $this->settings["database_name"] . ";charset=utf8", $this->settings["database_username"], 
				$this->settings["database_password"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // set the PDO error mode to exception
			$this->conn->setAttribute( PDO::ATTR_EMULATE_PREPARES, true ); //emulate prepared statements, allows for more flexibility
		}
		catch(PDOException $e){
			echo "Connection failed: " . $e->getMessage();
			$errorMessage = $this->logger->logError("Unable to connect to database: ".$e->getMessage(), $broncoNetID, dirname(__FILE__), true);
			echo "Error: Unable to connect to database. ".$errorMessage;
		}
	}
}
	
	
	
	
/*allow for atomic transactions (to ensure one insert only occurs when another has succeeded, so that either both or neither of them succeed)
found at http://php.net/manual/en/pdo.begintransaction.php*/
class AtomicPDO extends PDO
{
	protected $transactionCounter = 0;

	public function beginTransaction(){
		if (!$this->transactionCounter++) {
			return parent::beginTransaction();
		}
		$this->exec('SAVEPOINT trans'.$this->transactionCounter);
		return $this->transactionCounter >= 0;
	}

	public function commit(){
		if (!--$this->transactionCounter) {
			return parent::commit();
		}
		return $this->transactionCounter >= 0;
	}

	public function rollback(){
		if (--$this->transactionCounter) {
			$this->exec('ROLLBACK TO trans'.($this->transactionCounter + 1));
			return true;
		}
		return parent::rollback();
	}
	
}
	
?>