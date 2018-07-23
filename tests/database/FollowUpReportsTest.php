<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

include_once(dirname(__FILE__) . "/../../include/classDefinitions.php");//for final report class

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class FinalReportsTest extends TestCase
{    
    use TestCaseTrait;

    // only instantiate pdo once for test clean-up/fixture load
    private $pdo = null;
    // only instantiate PHPUnit\DbUnit\Database\Connection once per test
    private $conn = null;
   

    /**
     * @return PHPUnit\DbUnit\Database\Connection
     */
    public function getConnection()
    {
        //Connection information
        $config_url = dirname(__FILE__).'/../testconfig.ini';
        $settings = parse_ini_file($config_url);

        if ($this->conn === null) {
            if ($this->pdo == null) {
                    $dsn = "mysql:dbname=".$settings["test_database_name"].";host=".$settings["test_hostname"];

                $this->pdo = new PDO( $dsn, $settings["test_database_username"], $settings["test_database_password"] );
                // set the PDO error mode to exception
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            }
            $this->conn = $this->createDefaultDBConnection($this->pdo, "administrators");
        }

        return $this->conn;
    }

    /**
     * @return PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        $ds1 = $this->createXMLDataSet(dirname(__FILE__).'/datasets/applicants.xml');
        $ds2 = $this->createXMLDataSet(dirname(__FILE__).'/datasets/applications.xml');
        $ds3 = $this->createXMLDataSet(dirname(__FILE__).'/datasets/final_reports.xml');

        $compositeDs = new PHPUnit\DbUnit\DataSet\CompositeDataSet();
        $compositeDs->addDataSet($ds1);
        $compositeDs->addDataSet($ds2);
        $compositeDs->addDataSet($ds3);

        return $compositeDs;
    }

    
    //test getting a single final report- make sure all fields are correct!
    public function testGetFinalReport()
    {
        $exisitingAppID = 1;
        $existingReportArray = array(
            0 => "1", 1 => "2012-05-25", 2 => "2012-06-05", 3 => "2012-06-23", 4 => "2012-06-08", 5 => "2012-06-13", 6 => number_format((float)700, 2, '.', ''), //format budget as decimal to 2 places
            7 => "Lorum Ipsum Lorum Ipsum etc. 2.0", 8 => "Approved"
        );
        $existingReport = new FinalReport($existingReportArray);
        //should exist
        $this->assertEquals($existingReport, getFinalReport($this->pdo, $exisitingAppID));

        $newAppID = 6;
        //shouldn't exist
        $this->assertEquals(0, getApplication($this->pdo, $newAppID));
    }



    //Check if app has a final report already
    public function testHasFinalReport()
    {
        $reportApp = 1;
        $noReportApp = 2;

        $this->assertEquals(true, hasFinalReport($this->pdo, $reportApp));
        $this->assertEquals(false, hasFinalReport($this->pdo, $noReportApp));
    }



    //Check the max lengths of the reports columns (no point in testing specific numbers since this could easily change depending on MySQL/schema configuration)
    public function testGetFinalReportsMaxLengths()
    {
        $this->assertEquals(9, count(getFinalReportsMaxLengths($this->pdo)));
    }



    /*Test approving a final report*/
    public function testApproveFinalReport()
    {
        $approvedAppID = 1;
        $pendingAppID = 3;
        $deniedAppID = 4;
        $newAppID = 6;

        $this->assertEquals(false, approveFinalReport($this->pdo, $approvedAppID));
        $this->assertEquals(true, approveFinalReport($this->pdo, $deniedAppID));
        $this->assertEquals(true, approveFinalReport($this->pdo, $pendingAppID));
        $this->assertEquals(false, approveFinalReport($this->pdo, $newAppID));
    }

    /*Test denying a final report*/
    public function testDenyFinalReport()
    {
        $approvedAppID = 1;
        $pendingAppID = 3;
        $deniedAppID = 4;
        $newAppID = 6;

        $this->assertEquals(true, denyFinalReport($this->pdo, $approvedAppID));
        $this->assertEquals(false, denyFinalReport($this->pdo, $deniedAppID));
        $this->assertEquals(true, denyFinalReport($this->pdo, $pendingAppID));
        $this->assertEquals(false, denyFinalReport($this->pdo, $newAppID));
    }



    /*Test inserting a final report. There are more errors than usual to check for*/
    public function testInsertFinalReport()
    {
        $newReportID = 5; //application 5 shouldn't have a report yet

        //new, acceptable variables
        $validTravelFrom = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 1, 2018));
        $validTravelTo = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 25, 2018));
        $validActivityFrom = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 10, 2018));
        $validActivityTo = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 15, 2018));
        $validProjectSummary = "Lorem Ipsum";
        $validTotalAwardSpent = 300;

        $this->assertEquals(false, hasFinalReport($this->pdo, $newReportID)); //shouldn't have a report yet

        /*for inserting new reports*/

        //pass in empty values for all fields
        $testReturn = insertFinalReport($this->pdo, false, $newReportID, "", "", "", "", "", "");
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(false, hasFinalReport($this->pdo, $newReportID)); //shouldn't have a report yet

        //every field should have an error regarding missing data
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);
        $this->assertArrayHasKey('travelTo', $testReturn['errors']);
        $this->assertArrayHasKey('activityFrom', $testReturn['errors']);
        $this->assertArrayHasKey('activityTo', $testReturn['errors']);
        $this->assertArrayHasKey('projectSummary', $testReturn['errors']);
        $this->assertArrayHasKey('amountAwardedSpent', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = insertFinalReport($this->pdo, false, $newReportID, $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validProjectSummary, $validTotalAwardSpent);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(false, hasFinalReport($this->pdo, $newReportID)); //shouldn't have a report yet

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //insert an acceptable new final report
        $testReturn = insertFinalReport($this->pdo, false, $newReportID, $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validProjectSummary, $validTotalAwardSpent);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
        $this->assertEquals(true, hasFinalReport($this->pdo, $newReportID)); //should now have a report





        /*for updating reports*/

        //pass in empty values for all fields
        $testReturn = insertFinalReport($this->pdo, true, $newReportID, "", "", "", "", "", "");
        $this->assertEquals(false, $testReturn['success']);//insert should have failed

        //every field should have an error regarding missing data
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);
        $this->assertArrayHasKey('travelTo', $testReturn['errors']);
        $this->assertArrayHasKey('activityFrom', $testReturn['errors']);
        $this->assertArrayHasKey('activityTo', $testReturn['errors']);
        $this->assertArrayHasKey('projectSummary', $testReturn['errors']);
        $this->assertArrayHasKey('amountAwardedSpent', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = insertFinalReport($this->pdo, true, $newReportID, $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validProjectSummary, $validTotalAwardSpent);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //insert an acceptable new final report
        $testReturn = insertFinalReport($this->pdo, true, $newReportID, $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validProjectSummary, $validTotalAwardSpent);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
    }
}

?>