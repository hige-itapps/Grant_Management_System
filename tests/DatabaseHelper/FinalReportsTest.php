<?php

include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php"); //the associated file
include_once(dirname(__FILE__) . "/../../server/FinalReport.php");//for final report class
include_once(dirname(__FILE__) . "/../../server/Logger.php"); //Logger is used to generate mock Logger object

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
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $exisitingAppID = 1;
        $existingReportArray = array(
            0 => "1", 1 => "2012-05-25", 2 => "2012-06-05", 3 => "2012-06-23", 4 => "2012-06-08", 5 => "2012-06-13", 6 => number_format((float)700, 2, '.', ''), //format budget as decimal to 2 places
            7 => "Lorum Ipsum Lorum Ipsum etc. 2.0", 8 => "Approved"
        );
        $existingReport = new FinalReport($existingReportArray);
        //should exist
        $this->assertEquals($existingReport, $database->getFinalReport($exisitingAppID));

        $newAppID = 6;
        //shouldn't exist
        $this->assertEquals(0, $database->getApplication($newAppID));
        $this->assertEquals(0, $database->getFinalReport($newAppID));
    }



    //Check if app has a final report already
    public function testHasFinalReport()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $reportApp = 1;
        $noReportApp = 2;

        $this->assertEquals(true, $database->hasFinalReport($reportApp));
        $this->assertEquals(false, $database->hasFinalReport($noReportApp));
    }



    //Check the max lengths of the reports columns (no point in testing specific numbers since this could easily change depending on MySQL/schema configuration)
    public function testGetFinalReportsMaxLengths()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $this->assertEquals(9, count($database->getFinalReportsMaxLengths()));
    }



    /*Test approving a final report*/
    public function testApproveFinalReport()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $approvedAppID = 1;
        $pendingAppID = 3;
        $deniedAppID = 4;
        $newAppID = 6;

        $database->approveFinalReport($approvedAppID, null);
        $this->assertEquals("Approved", $database->getFinalReport($approvedAppID)->status);

        $database->approveFinalReport($deniedAppID, null);
        $this->assertEquals("Approved", $database->getFinalReport($deniedAppID)->status);

        $database->approveFinalReport($pendingAppID, null);
        $this->assertEquals("Approved", $database->getFinalReport($pendingAppID)->status);

        $database->approveFinalReport($newAppID, null);
        $this->assertEquals(0, $database->getApplication($newAppID)); //shouldn't exist
        $this->assertEquals(0, $database->getFinalReport($newAppID));
    }



    /*Test holding a final report*/
    public function testHoldFinalReport()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $approvedAppID = 1;
        $pendingAppID = 3;
        $deniedAppID = 4;
        $newAppID = 6;

        $database->holdFinalReport($approvedAppID, null);
        $this->assertEquals("Hold", $database->getFinalReport($approvedAppID)->status);

        $database->holdFinalReport($deniedAppID, null);
        $this->assertEquals("Hold", $database->getFinalReport($deniedAppID)->status);

        $database->holdFinalReport($pendingAppID, null);
        $this->assertEquals("Hold", $database->getFinalReport($pendingAppID)->status);

        $database->holdFinalReport($newAppID, null);
        $this->assertEquals(0, $database->getApplication($newAppID)); //shouldn't exist
        $this->assertEquals(0, $database->getFinalReport($newAppID));
    }



    /*Test inserting a final report. There are more errors than usual to check for*/
    public function testInsertFinalReport()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $newReportID = 5; //application 5 shouldn't have a report yet

        //new, acceptable variables
        $validTravelFrom = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 1, 2018));
        $validTravelTo = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 25, 2018));
        $validActivityFrom = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 10, 2018));
        $validActivityTo = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 15, 2018));
        $validProjectSummary = "Lorem Ipsum";
        $validTotalAwardSpent = 300;

        $this->assertEquals(false, $database->hasFinalReport($newReportID)); //shouldn't have a report yet

        /*for inserting new reports*/

        //pass in empty values for all fields
        $testReturn = $database->insertFinalReport(false, $newReportID, "", "", "", "", "", "", null);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(false, $database->hasFinalReport($newReportID)); //shouldn't have a report yet

        //every field should have an error regarding missing data
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);
        $this->assertArrayHasKey('travelTo', $testReturn['errors']);
        $this->assertArrayHasKey('activityFrom', $testReturn['errors']);
        $this->assertArrayHasKey('activityTo', $testReturn['errors']);
        $this->assertArrayHasKey('projectSummary', $testReturn['errors']);
        $this->assertArrayHasKey('amountAwardedSpent', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = $database->insertFinalReport(false, $newReportID, $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validProjectSummary, $validTotalAwardSpent, null);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(false, $database->hasFinalReport($newReportID)); //shouldn't have a report yet

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //insert an acceptable new final report
        $testReturn = $database->insertFinalReport(false, $newReportID, $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validProjectSummary, $validTotalAwardSpent, null);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
        $this->assertEquals(true, $database->hasFinalReport($newReportID)); //should now have a report





        /*for updating reports*/

        //pass in empty values for all fields
        $testReturn = $database->insertFinalReport(true, $newReportID, "", "", "", "", "", "", null);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed

        //every field should have an error regarding missing data
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);
        $this->assertArrayHasKey('travelTo', $testReturn['errors']);
        $this->assertArrayHasKey('activityFrom', $testReturn['errors']);
        $this->assertArrayHasKey('activityTo', $testReturn['errors']);
        $this->assertArrayHasKey('projectSummary', $testReturn['errors']);
        $this->assertArrayHasKey('amountAwardedSpent', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = $database->insertFinalReport(true, $newReportID, $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validProjectSummary, $validTotalAwardSpent, null);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //insert an acceptable new final report
        $testReturn = $database->insertFinalReport(true, $newReportID, $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validProjectSummary, $validTotalAwardSpent, null);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
    }
}

?>