<?php
	include('database.php'); /*include important database functions*/

	/*Checks if a user is an administrator-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	function isAdministrator($broncoNetID, $conn)
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
	
	/*Checks if a user is an application approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	function isApplicationApprover($broncoNetID, $conn)
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
	
	/*Checks if a user is a follow-up report approver-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	function isFollowUpReportApprover($broncoNetID, $conn)
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
	
	/*Checks if a user is a committee member-returns a boolean; NEEDS DATABASE CONNECTION OBJECT TO WORK*/
	function isCommitteeMember($broncoNetID, $conn)
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
?>