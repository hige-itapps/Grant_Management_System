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
	
	
	/*Return true if 2 cycle strings are far enough apart to be valid (for creating a new application). The old cycle should come first, followed by the new cycle*/
	if(!function_exists('areCyclesFarEnoughApart')){
		function areCyclesFarEnoughApart($firstCycle, $secondCycle)
		{
			$farEnoughApart = false;
			
			//parse $firstCycle to get Spring/Fall and Year
			$firstCycleParts = explode(" ", $firstCycle); //[0] holds Spring/Fall, [1] holds Year
				
			//parse $secondCycle to get Spring/Fall and Year
			$secondCycleParts = explode(" ", $secondCycle); //[0] holds Spring/Fall, [1] holds Year
							
			/*	
			RULES: If applying in Fall 2016, must wait until Fall 2018. 
			If applying in Spring 2017, must also wait until Fall 2018 (Spring 2019 is the earliest acceptable Spring cycle in this case)
			Example 2-year schedule:
			(2015) Fall 2015 cycle, Spring 2016 cycle
			(2016) Fall 2016 cycle, Spring 2017 cycle
			*/
			
			$yearsBetween = (int)$secondCycleParts[1] - (int)$firstCycleParts[1];//get years between cycles


			//If old cycle was fall
			if($firstCycleParts[0] === "Fall")
			{
				//If the new cycle is spring
				if($secondCycleParts[0] === "Spring")
				{
					if($yearsBetween > 2) {$farEnoughApart = true;} //must be greater than 2 years difference
				}

				//If the new cycle is fall
				if($secondCycleParts[0] === "Fall")
				{
					if($yearsBetween > 1) {$farEnoughApart = true;} //must be at least 1 year difference
				}
			}



			//If old cycle was spring
			if($firstCycleParts[0] === "Spring")
			{
				//If the new cycle is spring
				if($secondCycleParts[0] === "Spring")
				{
					if($yearsBetween > 1) {$farEnoughApart = true;} //must be greater than 1 year difference
				}

				//If the new cycle is fall
				if($secondCycleParts[0] === "Fall")
				{
					if($yearsBetween > 0) {$farEnoughApart = true;} //must be at least 1 year difference
				}
			}
			
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

	/*Return true if current date is within 3 days of one of the due dates*/
	if(!function_exists('isWithinWarningPeriod')){
		function isWithinWarningPeriod($curDate)
		{
			$isWithin = false;
			$springDate = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/4/3'); //added 2 days to 'real' deadline to allow for weekends
			$fallDate = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/11/3'); //^
			$springDateWarn = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/4/1');//find date 2 days before 'real' deadline
			$fallDateWarn = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/11/1');//^

			if(($curDate >= $springDateWarn && $curDate <= $springDate) || ($curDate >= $fallDateWarn && $curDate <= $fallDate))
			{
				$isWithin = true;
			}

			return $isWithin;
		}
	}	
	
?>