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
				$check = false;
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
				}
				
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
	
	/*Return true if 2 cycle strings are far enough apart to be valid (for creating a new application). The old cycle should come first, followed by the new cycle*/
	if(!function_exists('areCyclesFarEnoughApart')){
		function areCyclesFarEnoughApart($firstCycle, $secondCycle)
		{
			$farEnoughApart = false;
			
			//parse $firstCycle to get Spring/Fall and Year
			$firstCycleParts = explode(" ", $firstCycle); //[0] holds Spring/Fall, [1] holds Year
				
			//parse $secondCycle to get Spring/Fall and Year
			$secondCycleParts = explode(" ", $secondCycle); //[0] holds Spring/Fall, [1] holds Year
							
			//RULES: If applying in Spring, must wait for cur cycle + 3 full cycles. If applying in Fall, must wait for cur cycle + 2 full cycles
			//This can be simplified by just checking the years between application cycles. If years difference >1, then they are far enough apart

			//echo "years: ".(int)$secondCycleParts[1].", ".(int)$firstCycleParts[1];
			
			$yearsBetween = (int)$secondCycleParts[1] - (int)$firstCycleParts[1];//get years between cycles
			
			if($yearsBetween > 1) {$farEnoughApart = true;}
			return $farEnoughApart;
		}
	}
	
	/*Find the cycle of a given application; */
	if(!function_exists('getCycleName')){
		function getCycleName($date, $nextCycle, $showDueDate)
		{
			//$date = DateTime::createFromFormat('Y/m/d', $dateString);
			$dateYear = $date->format("Y");
			$springDate = DateTime::createFromFormat('m-d', '04-03'); //added 2 days to 'real' deadline to allow for weekends
			$fallDate = DateTime::createFromFormat('m-d', '11-03'); //^
			$dueSpring = ''; //strings that will contain due date if requested
			$dueFall = '';
			
			if($showDueDate)
			{
				$dueSpring = ', due Apr. 1';
				$dueFall = ', due Nov. 1';
			}
			
			if($date->format('md') > $springDate->format('md') && $date->format('md') <= $fallDate->format('md')) //current date within fall cycle
			{
				if(!$nextCycle) //current Fall Cycle
				{
					return 'Fall '.$dateYear.$dueFall;
				}
				else //next Spring Cycle
				{
					return 'Spring '.($dateYear+1).$dueSpring;
				}
			}
			else if($date->format('md') <= $springDate->format('md')) //current date within spring cycle
			{
				if(!$nextCycle)//current Spring Cycle
				{
					return 'Spring '.$dateYear.$dueSpring;
				}
				else //next Fall Cycles
				{
					return 'Fall '.$dateYear.$dueFall;
				}
			}
			else //current date within NEXT spring cycle
			{
				if(!$nextCycle)//current Spring Cycle
				{
					return 'Spring '.($dateYear+1).$dueSpring;
				}
				else //next Fall Cycles
				{
					return 'Fall '.($dateYear+1).$dueFall;
				}
			}
		}
	}

	/*Return true if current date is within 3 days of due date*/
	if(!function_exists('isWithinWarningPeriod')){
		function isWithinWarningPeriod()
		{
			$isWithin = false;
			$curDate = DateTime::createFromFormat('Y/m/d', date("Y/m/d"));
			$springDate = DateTime::createFromFormat('m-d', '04-03'); //added 2 days to 'real' deadline to allow for weekends
			$fallDate = DateTime::createFromFormat('m-d', '11-03'); //^
			$springDateWarn = $springDate->modify('-2 day');//find date 2 days before 'real' deadline
			$fallDateWarn = $fallDate->modify('-2 day');//^

			if(($curDate >= $springDateWarn && $curDate <= $springDate) || ($curDate >= $fallDateWarn && $curDate <= $fallDate))
			{
				$isWithin = true;
			}

			return $isWithin;
		}
	}	
	
?>