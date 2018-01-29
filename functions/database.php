<?php
	include "config.php"; //allows us to use configurations

	/* Establishes an sql connection to the database, and returns the object; MAKE SURE TO SET OBJECT TO NULL WHEN FINISHED */
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
	
	/* Returns array of all administrators */
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
	
	/* Returns array of all applicants */
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
	
	/* Returns array of all applications for a specified BroncoNetID, or ALL applications if no ID is provided */
	function getApplications($conn, $user)
	{
		if ($user != "") //valid username
		{
			/* Select only applications that this user has has submitted */
			$sql = $conn->prepare("Select ID, Applicant, Date FROM applications WHERE Applicant = :username");
			$sql->bindParam(':username', $user);
		}
		else //no username
		{
			/* Select all applications */
			$sql = $conn->prepare("Select ID, Applicant, Date FROM applications");
		}
		/* run the prepared query */
		$sql->execute();
		$res = $sql->fetchAll();
		/* Close finished query and connection */
		$sql = null;
		/* return list */
		return $res;
	}
	
	/* Returns array of application approvers */
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
	
	/* Returns array of committee members */
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
	
	/* Returns array of follow-up report approvers */
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
?>