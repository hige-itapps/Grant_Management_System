<?php

include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php"); //the associated file
include_once(dirname(__FILE__) . "/../../server/Application.php");//for application class
include_once(dirname(__FILE__) . "/../../server/Logger.php"); //Logger is used to generate mock Logger object

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class ApplicationsTest extends TestCase
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

        $compositeDs = new PHPUnit\DbUnit\DataSet\CompositeDataSet();
        $compositeDs->addDataSet($ds1);
        $compositeDs->addDataSet($ds2);

        return $compositeDs;
    }

    
    //test getting a single application- make sure all fields are correct!
    public function testGetApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $exisitingAppID = 1;
        $existingAppArray = array(
            0 => "1", 1 => "abc1234", 2 => "2012-05-20", 3 => 1, 4 => "Alphy", 5 => "abc@wmich.edu", 
            6 => "Literature", 7 => "samuel.j.kison@wmich.edu", 8 => "2012-06-05", 9 => "2012-06-23",
            10 => "2012-06-08", 11 => "2012-06-13", 12 => "Lorum Ipsum", 13 => "Cambridge University", 14 => number_format((float)700, 2, '.', ''), //format budget as decimal to 2 places
            15 => 0, 16 => 1, 17 => 0, 18 => "This is also a barbeque", 19 => "No other funding", 20 => "Lorum Ipsum Lorum Ipsum etc.",
            21 => 0, 22 => 1, 23 => 0, 24 => 1, 
            25 => "Sam", 26 => number_format((float)700, 2, '.', ''), 27 => "Approved"
        );
        $existingApp = new Application($existingAppArray);
        $existingApp->budget = array(
            array(0 => 1, 1 => 1, 2 => "Hotel", 3 => number_format((float)700, 2, '.', ''), 4 => "3 nights"),
            array(0 => 2, 1 => 1, 2 => "Air Travel", 3 => number_format((float)450, 2, '.', ''), 4 => "Flight to Destination")
        );
        //should exist
        $this->assertEquals($existingApp, $database->getApplication($exisitingAppID));


        $newAppID = 6;
        //shouldn't exist
        $this->assertEquals(0, $database->getApplication($newAppID));
    }



    //test for new applications for a user to sign (that haven't been signed yet)
    public function testGetApplicationsToSign()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $existingDeptChairID = "test3249";
        $newDeptChairEmail = "mrDerp@wmich.edu";
        $newDeptChairID = "good3445";

        $this->assertEquals(1, count($database->getApplicationsToSign($existingDeptChairEmail, $existingDeptChairID)));

        $this->assertEquals(0, count($database->getApplicationsToSign($newDeptChairEmail, $newDeptChairID)));
    }

    //test for previously signed applications
    public function testGetSignedApplications()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $newDeptChairEmail = "mrDerp@wmich.edu";

        $this->assertEquals(4, count($database->getSignedApplications($existingDeptChairEmail)));

        $this->assertEquals(0, count($database->getSignedApplications($newDeptChairEmail)));
    }

    //test for number of new applications for a user to sign (that haven't been signed yet)
    public function testGetNumberOfApplicationsToSign()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $existingDeptChairID = "test3249";
        $newDeptChairEmail = "mrDerp@wmich.edu";
        $newDeptChairID = "good3445";

        $this->assertEquals(1, $database->getNumberOfApplicationsToSign($existingDeptChairEmail, $existingDeptChairID));

        $this->assertEquals(0, $database->getNumberOfApplicationsToSign($newDeptChairEmail, $newDeptChairID));
    }

    //test for number of previously signed applications
    public function testGetNumberOfSignedApplications()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $newDeptChairEmail = "mrDerp@wmich.edu";

        $this->assertEquals(4, $database->getNumberOfSignedApplications($existingDeptChairEmail));

        $this->assertEquals(0, $database->getNumberOfSignedApplications($newDeptChairEmail));
    }

    

    //test getting all applications or a subset of applications
    public function testGetApplications()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApplicant1 = "abc1234";
        $existingApplicant2 = "zyx4321";
        $newApplicant = "eqk7410";

        $this->assertEquals(3, count($database->getApplications($existingApplicant1)));
        $this->assertEquals(1, count($database->getApplications($existingApplicant2)));
        $this->assertEquals(0, count($database->getApplications($newApplicant)));
        $this->assertEquals(5, count($database->getApplications("")));
    }



    //test getting number of all applications or a subset of applications
    public function testGetNumberOfApplications()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApplicant1 = "abc1234";
        $existingApplicant2 = "zyx4321";
        $newApplicant = "eqk7410";

        $this->assertEquals(3, $database->getNumberOfApplications($existingApplicant1));
        $this->assertEquals(1, $database->getNumberOfApplications($existingApplicant2));
        $this->assertEquals(0, $database->getNumberOfApplications($newApplicant));
        $this->assertEquals(5, $database->getNumberOfApplications(""));
    }



    //Check if an application is approved
    public function testIsApplicationApproved()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $newAppID = 6;

        $this->assertEquals(true, $database->isApplicationApproved($approvedAppID));
        $this->assertEquals(false, $database->isApplicationApproved($unapprovedAppID));
        $this->assertEquals(false, $database->isApplicationApproved($pendingAppID));
        $this->assertEquals(false, $database->isApplicationApproved($newAppID));
    }

    //Check if an application is signed
    public function testIsApplicationSigned()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $signedAppID = 1;
        $unsignedAppID = 3;
        $newAppID = 6;

        $this->assertEquals(true, $database->isApplicationSigned($signedAppID));
        $this->assertEquals(false, $database->isApplicationSigned($unsignedAppID));
        $this->assertEquals(false, $database->isApplicationSigned($newAppID));
    }



    //Check if user has a pending application (where approved is Null)
    public function testHasPendingApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $nonPendingApplicant = "abc1234";
        $pendingApplicant = "zyx4321";
        $newApplicant = "eqk7410";

        $this->assertEquals(false, $database->hasPendingApplication($nonPendingApplicant));
        $this->assertEquals(true, $database->hasPendingApplication($pendingApplicant));
        $this->assertEquals(false, $database->hasPendingApplication($newApplicant));
    }



    //Check for most recent approved application from a user
    public function testGetMostRecentApprovedApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $firstApplicant = "abc1234";
        $secondApplicant = "zyx4321";
        $thirdApplicant = "bbb7777";
        $newApplicant = "eqk7410";

        $firstApplicantMostRecent = $database->getMostRecentApprovedApplication($firstApplicant);
        $secondApplicantMostRecent = $database->getMostRecentApprovedApplication($secondApplicant);
        $thirdApplicantMostRecent = $database->getMostRecentApprovedApplication($thirdApplicant);
        $newApplicantMostRecent = $database->getMostRecentApprovedApplication($newApplicant);

        $this->assertEquals("2017-05-20", $firstApplicantMostRecent->dateSubmitted);
        $this->assertEquals(null, $secondApplicantMostRecent);
        $this->assertEquals(null, $thirdApplicantMostRecent);
        $this->assertEquals(null, $newApplicantMostRecent);
    }

    //Check for all past approved application cycles for a user
    public function testGetPastApprovedCycles()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $firstApplicant = "abc1234";
        $secondApplicant = "zyx4321";
        $thirdApplicant = "bbb7777";
        $newApplicant = "eqk7410";

        $firstApplicantApproved = $database->getPastApprovedCycles($firstApplicant);
        $secondApplicantApproved = $database->getPastApprovedCycles($secondApplicant);
        $thirdApplicantApproved = $database->getPastApprovedCycles($thirdApplicant);
        $newApplicantApproved = $database->getPastApprovedCycles($newApplicant);

        $this->assertEquals(array("Fall 2017", "Spring 2013"), $firstApplicantApproved);
        $this->assertEquals(null, $secondApplicantApproved);
        $this->assertEquals(null, $thirdApplicantApproved);
        $this->assertEquals(null, $newApplicantApproved);
    }



    //Check the max lengths of the applications columns (no point in testing specific numbers since this could easily change depending on MySQL/schema configuration)
    public function testGetApplicationsMaxLengths()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $this->assertEquals(28, count($database->getApplicationsMaxLengths()));
    }

    //Check the max lengths of the applications_budgets columns (no point in testing specific numbers since this could easily change depending on MySQL/schema configuration)
    public function testGetApplicationsBudgetsMaxLengths()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $this->assertEquals(5, count($database->getApplicationsBudgetsMaxLengths()));
    }



    /*Test approving an application*/
    public function testApproveApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $newAppID = 6;

        $database->approveApplication($approvedAppID, 700, null); //awarded amount has to be the same if already approved, otherwise that will be updated
        $this->assertEquals("Approved", $database->getApplication($approvedAppID)->status);

        $database->approveApplication($unapprovedAppID, 700, null);
        $this->assertEquals("Approved", $database->getApplication($unapprovedAppID)->status);

        $database->approveApplication($pendingAppID, 700, null);
        $this->assertEquals("Approved", $database->getApplication($pendingAppID)->status);

        $database->approveApplication($newAppID, 700, null);
        $this->assertEquals(0, $database->getApplication($newAppID)); //shouldn't exist
    }

    /*Test denying an application*/
    public function testDenyApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $newAppID = 6;

        $database->denyApplication($approvedAppID, null);
        $this->assertEquals("Denied", $database->getApplication($approvedAppID)->status);

        $database->denyApplication($unapprovedAppID, null);
        $this->assertEquals("Denied", $database->getApplication($unapprovedAppID)->status);

        $database->denyApplication($pendingAppID, null);
        $this->assertEquals("Denied", $database->getApplication($pendingAppID)->status);

        $database->denyApplication($newAppID, null);
        $this->assertEquals(0, $database->getApplication($newAppID)); //shouldn't exist
    }

    /*Test holding an application*/
    public function testHoldApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $heldAppID = 4;
        $newAppID = 6;

        $database->holdApplication($approvedAppID, null);
        $this->assertEquals("Hold", $database->getApplication($approvedAppID)->status);

        $database->holdApplication($unapprovedAppID, null);
        $this->assertEquals("Hold", $database->getApplication($unapprovedAppID)->status);

        $database->holdApplication($pendingAppID, null);
        $this->assertEquals("Hold", $database->getApplication($pendingAppID)->status);

        $database->holdApplication($heldAppID, null);
        $this->assertEquals("Hold", $database->getApplication($heldAppID)->status);

        $database->holdApplication($newAppID, null);
        $this->assertEquals(0, $database->getApplication($newAppID)); //shouldn't exist
    }

    /*Test signing an application*/
    public function testSignApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $signedAppID = 1;
        $unsignedAppID = 3;
        $newAppID = 6;

        $database->signApplication($signedAppID, "Sam", null);
        $this->assertEquals("Sam", $database->getApplication($signedAppID)->deptChairApproval);

        $database->signApplication($unsignedAppID, "Sam", null);
        $this->assertEquals("Sam", $database->getApplication($unsignedAppID)->deptChairApproval);

        $database->signApplication($newAppID, "Sam", null);
        $this->assertEquals(0, $database->getApplication($newAppID)); //shouldn't exist

        $database->signApplication($signedAppID, "Dude", null); //try signing an already signed app with a new sig
        $this->assertEquals("Dude", $database->getApplication($signedAppID)->deptChairApproval);
    }



    /*Test inserting an application. There are MANY errors to check for*/
    public function testInsertApplication()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $newApplicant = "eqk7410";

        //new, acceptable variables
        $validName = "Sam";
        $validEmail = "samuel.j.kison@wmich.edu";
        $validDepartment = "CS";
        $validDeptChairEmail = "jeffkrony@wmich.edu";
        $validTravelFrom = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 1, 2018));
        $validTravelTo = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 25, 2018));
        $validActivityFrom = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 10, 2018));
        $validActivityTo = date('Y-m-d h:i:s', mktime(0, 0, 0, 7, 15, 2018));
        $validTitle = "This is a title";
        $validDestination = "This is a destination";
        $validAmountRequested = 300;
        $validPurpose4Other = "This is a special purpose";
        $validProposalSummary = "Lorem Ipsum";
        $validNextCycle = 0;
        $validBudgetArray = array(array("expense"=>"Air Travel", "details"=>"2 way flight", "amount"=>400), array("expense"=>"Hotel", "details"=>"4 nights", "amount"=>1000));

        $this->assertEquals(5, $database->getNumberOfApplications(""));//should start with only 5 applications

        /*for inserting new applications*/

        //pass in empty values for all fields
        $testReturn = $database->insertApplication(false, null, $newApplicant, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", array(array("expense"=>"", "details"=>"", "amount"=>0), array("expense"=>"", "details"=>"", "amount"=>0)));
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, $database->getNumberOfApplications(""));//should still only have 5 applications

        //almost every field should have an error regarding missing data
        $this->assertArrayHasKey('name', $testReturn['errors']);
        $this->assertArrayHasKey('email', $testReturn['errors']);
        $this->assertArrayHasKey('department', $testReturn['errors']);
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);
        $this->assertArrayHasKey('travelTo', $testReturn['errors']);
        $this->assertArrayHasKey('activityFrom', $testReturn['errors']);
        $this->assertArrayHasKey('activityTo', $testReturn['errors']);
        $this->assertArrayHasKey('title', $testReturn['errors']);
        $this->assertArrayHasKey('destination', $testReturn['errors']);
        $this->assertArrayHasKey('amountRequested', $testReturn['errors']);
        $this->assertArrayHasKey('purpose', $testReturn['errors']);
        $this->assertArrayHasKey('proposalSummary', $testReturn['errors']);
        $this->assertArrayHasKey('goal', $testReturn['errors']);
        $this->assertArrayHasKey('cycleChoice', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 1 expense', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 1 details', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 1 amount', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 2 expense', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 2 details', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 2 amount', $testReturn['errors']);


        //pass in invalid email address formats
        $testReturn = $database->insertApplication(false, null, $newApplicant, $validName, "samuel.j.kisonATwmich.edu", $validDepartment, "jeffkrony@wmichDOTedu", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, $database->getNumberOfApplications(""));//should still only have 5 applications

        //just check for email errors
        $this->assertArrayHasKey('email', $testReturn['errors']);
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in a non-wmu deptChairEmail
        $testReturn = $database->insertApplication(false, null, $newApplicant, $validName, $validEmail, $validDepartment, "jeffkrony@gmail.com", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, $database->getNumberOfApplications(""));//should still only have 5 applications

        //just check for email error
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = $database->insertApplication(false, null, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, $database->getNumberOfApplications(""));//should still only have 5 applications

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //pass in no budget array
        $testReturn = $database->insertApplication(false, null, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, array());
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, $database->getNumberOfApplications(""));//should still only have 5 applications

        //just check for budget array error
        $this->assertArrayHasKey('budgetArray', $testReturn['errors']);

        
        //@todo: try inserting an application before enough time has passed. Requires changing dynamic dates to static dates, probably via namespaces or dependency injection


        //insert an acceptable new application
        $testReturn = $database->insertApplication(false, null, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
        $this->assertEquals(6, $testReturn['appID']);//appID should be 6
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should now have 6 applications





        /*for updating applications*/

        $testReturn = $database->insertApplication(true, 6, $newApplicant, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", array(array("expense"=>"", "details"=>"", "amount"=>0), array("expense"=>"", "details"=>"", "amount"=>0)));
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should still have 6 applications

        //almost every field should have an error regarding missing data
        $this->assertArrayHasKey('name', $testReturn['errors']);
        $this->assertArrayHasKey('email', $testReturn['errors']);
        $this->assertArrayHasKey('department', $testReturn['errors']);
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);
        $this->assertArrayHasKey('travelTo', $testReturn['errors']);
        $this->assertArrayHasKey('activityFrom', $testReturn['errors']);
        $this->assertArrayHasKey('activityTo', $testReturn['errors']);
        $this->assertArrayHasKey('title', $testReturn['errors']);
        $this->assertArrayHasKey('destination', $testReturn['errors']);
        $this->assertArrayHasKey('amountRequested', $testReturn['errors']);
        $this->assertArrayHasKey('purpose', $testReturn['errors']);
        $this->assertArrayHasKey('proposalSummary', $testReturn['errors']);
        $this->assertArrayHasKey('goal', $testReturn['errors']);
        $this->assertArrayHasKey('cycleChoice', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 1 expense', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 1 details', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 1 amount', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 2 expense', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 2 details', $testReturn['errors']);
        $this->assertArrayHasKey('budgetArray 2 amount', $testReturn['errors']);


        //pass in invalid email address formats
        $testReturn = $database->insertApplication(true, 6, $newApplicant, $validName, "samuel.j.kisonATwmich.edu", $validDepartment, "jeffkrony@wmichDOTedu", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should still have 6 applications

        //just check for email errors
        $this->assertArrayHasKey('email', $testReturn['errors']);
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in a non-wmu deptChairEmail
        $testReturn = $database->insertApplication(true, 6, $newApplicant, $validName, $validEmail, $validDepartment, "jeffkrony@gmail.com", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should still have 6 applications

        //just check for email error
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = $database->insertApplication(true, 6, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should still have 6 applications

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //pass in no budget array
        $testReturn = $database->insertApplication(true, 6, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, array());
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should still have 6 applications

        //just check for budget array error
        $this->assertArrayHasKey('budgetArray', $testReturn['errors']);


        //insert an acceptable new application
        $testReturn = $database->insertApplication(true, 6, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
        $this->assertEquals(6, $testReturn['appID']);//appID should be 6
        $this->assertEquals(6, $database->getNumberOfApplications(""));//should still have 6 applications
    }
}

?>