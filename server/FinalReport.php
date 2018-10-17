<?php
/* Holds information for a final report; these fields should be mostly self-explanatory, they mirror the fields in the database. */
class FinalReport{
    public $appID;
    public $date;				// (DATE)
    public $travelFrom;			// (DATE)
    public $travelTo;			// (DATE)
    public $activityFrom;		// (DATE)
    public $activityTo;			// (DATE)
    public $amountAwardedSpent; // (DECIMAL)
    public $projectSummary;		// (STRING)
    public $status;				// approved, denied, on hold, pending, etc. (STRING)
    
    /*Constructor; just pass in the report array received from the database call*/
    public function __construct($reportInfo) {
        $this->appID = $reportInfo[0];
        $this->date = $reportInfo[1];
        $this->travelFrom = $reportInfo[2];
        $this->travelTo = $reportInfo[3];
        $this->activityFrom = $reportInfo[4];
        $this->activityTo = $reportInfo[5];
        $this->amountAwardedSpent = $reportInfo[6];
        $this->projectSummary = $reportInfo[7];
        $this->status = $reportInfo[8];
    }
}
?>