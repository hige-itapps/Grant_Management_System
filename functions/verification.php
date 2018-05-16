<?php
	include('database.php'); /*include important database functions*/

	
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