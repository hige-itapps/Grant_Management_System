<?php
	include "config.php"; //allows us to use configurations

	include_once "applicant.php";
	
	/* Establishes an sql connection to the database, and returns the object; MAKE SURE TO SET OBJECT TO NULL WHEN FINISHED */
	if(!function_exists('connection')) {
		function connection()
		{
			
			try 
			{
				$settings = parse_ini_file('config.ini');
				//var_dump($settings);
				$conn = new PDO("mysql:host=" . $settings["hostname"] . ";dbname=" . $settings["database_name"] . ";charset=utf8", $settings["database_username"], 
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
	/* Returns array of all applications */
	if(!function_exists('getAllApplicantions')) {
		function getAllApplicantions($conn)
		{
			/* Prepare & run the query */
			$sql = $conn->prepare("Select ID, Applicant, Name, Date FROM applications WHERE Approved is NULL");
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
		}
	}
	
	/* Returns array of all applications for a specified BroncoNetID, or ALL applications if no ID is provided */
	if(!function_exists('getApplications')) {
		function getApplications($conn, $id)
		{
			$applicant = new Applicant();
			if ($id != "") //valid username
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select * FROM applications WHERE ID = :id");
				$sql->bindParam(':id', $id);
			}
			/*else //no username
			{
				/* Select all applications */
				/*$sql = $conn->prepare("Select * FROM applications");
			}*/
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			
			
			
			
			
			$applicant->bnid = $res[0][1];
			$applicant->name = $res[0][3];
			$applicant->dateS = $res[0][2];
			$applicant->dept = $res[0][4];
			$applicant->deptM = $res[0][5];
			$applicant->email = $res[0][6];
			$applicant->rTitle = $res[0][7];
			$applicant->tStart = $res[0][8];
			$applicant->tEnd = $res[0][9];
			$applicant->aStart = $res[0][10];
			$applicant->aEnd = $res[0][11];
			$applicant->dest = $res[0][12];
			$applicant->aReq = $res[0][13];
			$applicant->pr1 = $res[0][14];
			$applicant->pr2 = $res[0][15];
			$applicant->pr3 = $res[0][16];
			$applicant->pr4 = $res[0][17];
			$applicant->oF = $res[0][18];
			$applicant->pS = $res[0][19];
			$applicant->fg1 = $res[0][20];
			$applicant->fg2 = $res[0][21];
			$applicant->fg3 = $res[0][22];
			$applicant->fg4 = $res[0][23];
			$applicant->deptCE = $res[0][24];
			
			$sql = $conn->prepare("Select * FROM applications_budgets WHERE ApplicationID = :id");
			$sql->bindParam(':id', $id);
			
			/*else //no username
			{
				/* Select all applications */
				/*$sql = $conn->prepare("Select * FROM applications");
			}*/
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			$applicant->budget = $res;
			
			/* Close finished query and connection */
			$sql = null;
			
			
			/* return object */
			return $applicant;
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
	
	/* Returns true if applicant's application has been approved, or false otherwise */
	if(!function_exists('isApplicationApproved')){
		function isApplicationApproved($conn, $user)
		{
			$is = false;
			
			if ($user != "") //valid username
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE Applicant = :username AND Approved = true");
				$sql->bindParam(':username', $user);
				$sql->execute();
				$res = $sql->fetchAll();
				
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
				$sql = $conn->prepare("Select COUNT(*) AS Count FROM applications WHERE DepartmentChairEmail = :dEmail AND DepartmentChairSignature IS NULL AND DepartmentChairEmail != Email");
				$sql->bindParam(':dEmail', $email);
				$sql->execute();
				$res = $sql->fetchAll();
				
				//echo 'Count: '.$res[0][0].".";
				
				return $res[0][0];
			}
		}
	}
	
?>