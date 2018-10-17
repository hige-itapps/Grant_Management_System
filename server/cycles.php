<?php
/* This class deals with 'cycles', bi-yearly measurements of time spanning the spring and fall semesters. The cycles do not match up perfectly with the beginning and ends of the year,
and they are used bureaucratically throughout the IEFDF process, prompting the creation of these functions. */

class Cycles
{
	private $springDateEndFaux; //the displayed ending date for Spring cycles
	private $springDateEndReal; //the REAL ending date for Spring cycles; allows for extra time for late submissions
	private $springDueDateString; //string representation of the Spring due date
	private $fallDateEndFaux; //the displayed ending date for Fall cycles
	private $fallDateEndReal; //the REAL ending date for Fall cycles; allows for extra time for late submissions
	private $fallDueDateString; //string representation of the Fall due date

	/* Constructior sets date vars */
	public function __construct(){
		//$this->springDateEndFaux = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'4/1');
		//$this->springDateEndReal = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'4/3');
		$this->springDateEndFaux = '4/1';
		$this->springDateEndReal = '4/3';
		$this->springDueDateString = 'due Apr. 1';
		//$this->fallDateEndFaux = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'11/1');
		//$this->fallDateEndReal = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'11/3');
		$this->fallDateEndFaux = '11/1';
		$this->fallDateEndReal = '11/3';
		$this->fallDueDateString = 'due Nov. 1';
	}

	/*Return true if 2 cycle strings are far enough apart to be valid (for creating a new application). The old cycle should come first, followed by the new cycle*/
	public function areCyclesFarEnoughApart($firstCycle, $secondCycle)
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

	/*Return the next cycle for which the applicant can apply for based off of the latest approved application's cycle name.*/
	public function getNextCycleToApplyFor($cycle)
	{
		if($cycle == null || $cycle === ''){return null;} //escape if cycle wasn't given
		/*	
		RULES: If applying in Fall 2016, must wait until Fall 2018. 
		If applying in Spring 2017, must also wait until Fall 2018 (Spring 2019 is the earliest acceptable Spring cycle in this case)
		Example 2-year schedule:
		(2015) Fall 2015 cycle, Spring 2016 cycle
		(2016) Fall 2016 cycle, Spring 2017 cycle
		*/
		//initialize semester and year of the next applicable cycle
		$newCycleSemester = 'Fall '; //the next applicable cycle will ALWAYS be a fall cycle according to the rules
		$newCycleYear = 0;

		$cycleParts = explode(" ", $cycle); //[0] holds Spring/Fall, [1] holds Year

		if($cycleParts[0] === "Fall"){$newCycleYear = (int)$cycleParts[1] + 2;} //if fall cycle, add 2 years
		else if($cycleParts[0] === "Spring"){$newCycleYear = (int)$cycleParts[1] + 1;} //if spring cycle, add 1 year

		return $newCycleSemester.$newCycleYear; // concatenate the cycle semester and year into 1 sting, and return.
	}
	
	/*Find the cycle of a given application. Use the showDueDate boolean to optionally return the cycle's due date as well.*/
	public function getCycleName($date, $nextCycle, $showDueDate)
	{
		if(!$date){return null;} //escape if no date was given

		$dateYear = $date->format("Y"); //current year
		$springDate = DateTime::createFromFormat('m/d', $this->springDateEndReal); //create dates given the month/day set in the constructor
		$fallDate = DateTime::createFromFormat('m/d', $this->fallDateEndReal);
		$dueSpring = ''; //strings that will contain due date if requested
		$dueFall = '';
		
		if($showDueDate)
		{
			$dueSpring = ', '.$this->springDueDateString;
			$dueFall = ', '.$this->fallDueDateString;
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

	/*Return true if current date is within 3 days of one of the due dates*/
	public function isWithinWarningPeriod($curDate)
	{
		$isWithin = false;
		$springDate = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/'.$this->springDateEndReal); //get Spring/Fall dates for current year
		$fallDate = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/'.$this->fallDateEndReal);
		$springDateWarn = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/'.$this->springDateEndFaux);
		$fallDateWarn = DateTime::createFromFormat('Y/m/d', ($curDate->format("Y")).'/'.$this->fallDateEndFaux);

		if(($curDate >= $springDateWarn && $curDate <= $springDate) || ($curDate >= $fallDateWarn && $curDate <= $fallDate)){
			$isWithin = true;
		}

		return $isWithin;
	}
	
	/*Sort a list of cycles in descending order*/
	public function sortCycles($cycles)
	{
		usort($cycles, array($this, "cmpCycles")); //initial sort
		return array_reverse($cycles); //reverse order
	}
	/*The comparator between two cycles, used exclusively for the sorting method above.*/
	private function cmpCycles($c1, $c2)
	{
		if($c1 === $c2) //equal case
		{
			return 0;
		}
		
		$fullCycle1 = explode(' ', $c1);
		$fullCycle2 = explode(' ', $c2);

		$cycle1Year = $fullCycle1[1];
		$cycle2Year = $fullCycle2[1];
		$cycle1Semester = $fullCycle1[0];
		$cycle2Semester = $fullCycle2[0];
		
		
		if ($cycle1Year === $cycle2Year) { //same year
			return ($cycle1Semester > $cycle2Semester) ? -1 : 1; 
		}
		
		return ($cycle1Year < $cycle2Year) ? -1 : 1; //different year
	}
}
?>