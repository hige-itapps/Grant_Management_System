<?php
	include "config.php"; //allows us to use configurations

	/* Establishes an sql connection to the database, and returns the object; MAKE SURE TO SET OBJECT TO NULL WHEN FINISHED */
	if(!function_exists('connection')) {
		function connection()
		{
			$configData = parseConfig('config.ini');
			$site_url = $configData['site_url'];
			$database_name = $configData['database_name'];
			$database_username = $configData['database_username'];
			$database_password = $configData['database_password'];
			
			try 
			{
				$conn = new PDO("mysql:host=" . $site_url . ";dbname=" . $database_name . ";charset=utf8", $database_username, $database_password); //connect to DB using config settings
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
	
	/* Returns array of all applications for a specified BroncoNetID, or ALL applications if no ID is provided */
	if(!function_exists('getApplications')) {
		function getApplications($conn, $user)
		{
			if ($user != "") //valid username
			{
				/* Select only applications that this user has has submitted */
				$sql = $conn->prepare("Select * FROM applications WHERE Applicant = :username");
				$sql->bindParam(':username', $user);
			}
			else //no username
			{
				/* Select all applications */
				$sql = $conn->prepare("Select * FROM applications");
			}
			/* run the prepared query */
			$sql->execute();
			$res = $sql->fetchAll();
			/* Close finished query and connection */
			$sql = null;
			/* return list */
			return $res;
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