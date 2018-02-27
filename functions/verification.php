<?php
	include('database.php'); /*include important database functions*/

	/*Checks if a user is an administrator-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	if(!function_exists('isAdministrator')) {
		function isAdministrator($conn, $broncoNetID)
		{
			$is = false; //initialize boolean to false
			$adminList = getAdministrators($conn);//grab admin list
			
			foreach($adminList as $i) //loop through admins
			{
				$newID = $i[0];
				//echo 'admin: '.$newID.' compare to input '.$broncoNetID.'.';
				if($newID == $broncoNetID)
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
	1. Must be faculty
	2. Must not have a pending application
	3. Must not have received funding within the past year*/
	if(!function_exists('isUserAllowedToCreateApplication')) {
		function isUserAllowedToCreateApplication($conn, $broncoNetID, $position)
		{
			/*echo "User ".$broncoNetID.", ".$position."; Has no pending application? ".(!hasPendingApplication($conn, $broncoNetID) ? 'true' : 'false').". 
			Has no approved application within past year? ".(!hasApprovedApplicationWithinPastYear($conn, $broncoNetID) ? 'true' : 'false').". 
			Is faculty? ".($position === 'faculty' ? 'true' : 'false').". ";*/
			
			if($position === 'faculty' && !hasPendingApplication($conn, $broncoNetID) && !hasApprovedApplicationWithinPastYear($conn, $broncoNetID))
			{
				return true;
			}
			else 
			{
				return false; //necessary to specify true/false because of dumb php rules :(
			}
		}
	}
	
	/*Checks if a user is allowed to freely see applications. ALSO USED FOR VIEWING FOLLOW-UP REPORTS
	Rules: Must be either an application approver or a committee member*/
	if(!function_exists('isUserAllowedToSeeApplications')) {
		function isUserAllowedToSeeApplications($conn, $broncoNetID)
		{
				
			if(isCommitteeMember($conn, $broncoNetID) || isApplicationApprover($conn, $broncoNetID))
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
				$res = $sql->fetchAll();
				
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
				$res = $sql->fetchAll();
				
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
	
?>