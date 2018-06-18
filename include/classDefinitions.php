<?php


/* Holds information for a follow-up report; these fields should be mostly self-explanatory */
	if(!class_exists('FollowUpReport')){
		class FollowUpReport{
			public $travelFrom;			// (DATE)
			public $travelTo;			// (DATE)
			public $activityFrom;		// (DATE)
			public $activityTo;			// (DATE)
			public $projectSummary;		// (STRING)
			public $amountAwardedSpent; // (DECIMAL)
			public $status;				// (STRING)
			
			/*Constructor(for everything except budget); just pass in the application array received from the database call (SELECT * FROM applications ...)*/
			public function __construct($appInfo) {
				$this->travelFrom = $appInfo[1];
				$this->travelTo = $appInfo[2];
				$this->activityFrom = $appInfo[3];
				$this->activityTo = $appInfo[4];
				$this->projectSummary = $appInfo[5];
				$this->amountAwardedSpent = $appInfo[6];
				$this->status = $appInfo[7];
			}

			/*Return a text representation of the status boolean*/
			public function getStatus(){
				$currentStatus = "Pending";

				if(isset($this->status))
				{
					if($this->status == 0){$currentStatus = "Denied";}
					if($this->status == 1){$currentStatus = "Approved";}
				}
					
				return $currentStatus;
			}
		}
	}

	/* Holds information for an application; these fields should be mostly self-explanatory */
	if(!class_exists('Application')){
		class Application{
			public $id; 				// id of application (INT)
			public $broncoNetID;		// applicant's broncoNetID (STRING)
			public $name;				// (STRING)
			public $dateSubmitted;		// date submitted (DATE)
			public $department;			// (STRING)
			public $email;				// (STRING)
			public $title;				// title of activity (STRING)
			public $travelFrom;			// (DATE)
			public $travelTo;			// (DATE)
			public $activityFrom;		// (DATE)
			public $activityTo;			// (DATE)
			public $destination;		// (STRING)
			public $amountRequested;	// (DECIMAL)	
			public $purpose1;			// is research (BOOLEAN)
			public $purpose2;			// is conference (BOOLEAN)
			public $purpose3;			// is creative activity (BOOLEAN)
			public $purpose4;			// is other event text (STRING)
			public $otherFunding;		// (STRING)
			public $proposalSummary;	// (STRING)
			public $goal1;				// (BOOLEAN)
			public $goal2;				// (BOOLEAN)
			public $goal3;				// (BOOLEAN)
			public $goal4;				// (BOOLEAN)
			public $deptChairEmail;		// (STRING)
			public $deptChairApproval;	// (STRING)
			public $budget; 			// (ARRAY of budget items)
			public $status;				// true=approved, false=denied, null=pending (BOOLEAN)
			public $amountAwarded; 		// (DECIMAL)
			public $onHold; 			// true = on hold (BOOLEAN)
			public $nextCycle; 			// true=submitted for next cycle, false=current (BOOLEAN)
			
			/*Constructor(for everything except budget); just pass in the application array received from the database call (SELECT * FROM applications ...)*/
			public function __construct($appInfo) {
				$this->id = $appInfo[0]; 
				$this->broncoNetID = $appInfo[1];
				$this->name = $appInfo[3];
				$this->dateSubmitted = $appInfo[2];
				$this->department = $appInfo[4];
				//$this->deptM = $appInfo[5]; //no longer used
				$this->email = $appInfo[6];
				$this->title = $appInfo[7];
				$this->travelFrom = $appInfo[8];
				$this->travelTo = $appInfo[9];
				$this->activityFrom = $appInfo[10];
				$this->activityTo = $appInfo[11];
				$this->destination = $appInfo[12];
				$this->amountRequested = $appInfo[13];
				$this->purpose1 = $appInfo[14];
				$this->purpose2 = $appInfo[15];
				$this->purpose3 = $appInfo[16];
				$this->purpose4 = $appInfo[17];
				$this->otherFunding = $appInfo[18];
				$this->proposalSummary = $appInfo[19];
				$this->goal1 = $appInfo[20];
				$this->goal2 = $appInfo[21];
				$this->goal3 = $appInfo[22];
				$this->goal4 = $appInfo[23];
				$this->deptChairEmail = $appInfo[24];
				$this->deptChairApproval = $appInfo[25];
				$this->status = $appInfo[26];
				$this->amountAwarded = $appInfo[27];
				$this->onHold = $appInfo[28];
				$this->nextCycle = $appInfo[29];
			}
			
			/*Return a text representation of the status boolean*/
			public function getStatus(){
				$currentStatus = "Hold";
				
				if($this->onHold == 0)
				{
					if(isset($this->status))
					{
						if($this->status == 0){$currentStatus = "Denied";}
						if($this->status == 1){$currentStatus = "Approved";}
					}
					else
					{
						$currentStatus = "Pending";
					}
				}
				
				return $currentStatus;
			}
		}
	}

?>