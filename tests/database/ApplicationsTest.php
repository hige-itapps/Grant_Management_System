<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

include_once(dirname(__FILE__) . "/../../include/classDefinitions.php");//for application class

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
        $this->assertEquals($existingApp, getApplication($this->pdo, $exisitingAppID));


        $newAppID = 6;
        //shouldn't exist
        $this->assertEquals(0, getApplication($this->pdo, $newAppID));
    }



    //test for new applications for a user to sign (that haven't been signed yet)
    public function testGetApplicationsToSign()
    {
        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $existingDeptChairID = "test3249";
        $newDeptChairEmail = "mrDerp@wmich.edu";
        $newDeptChairID = "good3445";

        $this->assertEquals(1, count(getApplicationsToSign($this->pdo, $existingDeptChairEmail, $existingDeptChairID)));

        $this->assertEquals(0, count(getApplicationsToSign($this->pdo, $newDeptChairEmail, $newDeptChairID)));
    }

    //test for previously signed applications
    public function testGetSignedApplications()
    {
        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $newDeptChairEmail = "mrDerp@wmich.edu";

        $this->assertEquals(4, count(getSignedApplications($this->pdo, $existingDeptChairEmail)));

        $this->assertEquals(0, count(getSignedApplications($this->pdo, $newDeptChairEmail)));
    }

    //test for number of new applications for a user to sign (that haven't been signed yet)
    public function testGetNumberOfApplicationsToSign()
    {
        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $existingDeptChairID = "test3249";
        $newDeptChairEmail = "mrDerp@wmich.edu";
        $newDeptChairID = "good3445";

        $this->assertEquals(1, getNumberOfApplicationsToSign($this->pdo, $existingDeptChairEmail, $existingDeptChairID));

        $this->assertEquals(0, getNumberOfApplicationsToSign($this->pdo, $newDeptChairEmail, $newDeptChairID));
    }

    //test for number of previously signed applications
    public function testGetNumberOfSignedApplications()
    {
        $existingDeptChairEmail = "samuel.j.kison@wmich.edu";
        $newDeptChairEmail = "mrDerp@wmich.edu";

        $this->assertEquals(4, getNumberOfSignedApplications($this->pdo, $existingDeptChairEmail));

        $this->assertEquals(0, getNumberOfSignedApplications($this->pdo, $newDeptChairEmail));
    }

    

    //test getting all applications or a subset of applications
    public function testGetApplications()
    {
        $existingApplicant1 = "abc1234";
        $existingApplicant2 = "zyx4321";
        $newApplicant = "eqk7410";

        $this->assertEquals(3, count(getApplications($this->pdo, $existingApplicant1)));
        $this->assertEquals(1, count(getApplications($this->pdo, $existingApplicant2)));
        $this->assertEquals(0, count(getApplications($this->pdo, $newApplicant)));
        $this->assertEquals(5, count(getApplications($this->pdo, "")));
    }



    //test getting number of all applications or a subset of applications
    public function testGetNumberOfApplications()
    {
        $existingApplicant1 = "abc1234";
        $existingApplicant2 = "zyx4321";
        $newApplicant = "eqk7410";

        $this->assertEquals(3, getNumberOfApplications($this->pdo, $existingApplicant1));
        $this->assertEquals(1, getNumberOfApplications($this->pdo, $existingApplicant2));
        $this->assertEquals(0, getNumberOfApplications($this->pdo, $newApplicant));
        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));
    }



    //Check if an application is approved
    public function testIsApplicationApproved()
    {
        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $newAppID = 6;

        $this->assertEquals(true, isApplicationApproved($this->pdo, $approvedAppID));
        $this->assertEquals(false, isApplicationApproved($this->pdo, $unapprovedAppID));
        $this->assertEquals(false, isApplicationApproved($this->pdo, $pendingAppID));
        $this->assertEquals(false, isApplicationApproved($this->pdo, $newAppID));
    }

    //Check if an application is signed
    public function testIsApplicationSigned()
    {
        $signedAppID = 1;
        $unsignedAppID = 3;
        $newAppID = 6;

        $this->assertEquals(true, isApplicationSigned($this->pdo, $signedAppID));
        $this->assertEquals(false, isApplicationSigned($this->pdo, $unsignedAppID));
        $this->assertEquals(false, isApplicationSigned($this->pdo, $newAppID));
    }



    //Check if user has a pending application (where approved is Null)
    public function testHasPendingApplication()
    {
        $nonPendingApplicant = "abc1234";
        $pendingApplicant = "zyx4321";
        $newApplicant = "eqk7410";

        $this->assertEquals(false, hasPendingApplication($this->pdo, $nonPendingApplicant));
        $this->assertEquals(true, hasPendingApplication($this->pdo, $pendingApplicant));
        $this->assertEquals(false, hasPendingApplication($this->pdo, $newApplicant));
    }



    //Check for most recent approved application from a user
    public function testGetMostRecentApprovedApplication()
    {
        $firstApplicant = "abc1234";
        $secondApplicant = "zyx4321";
        $thirdApplicant = "bbb7777";
        $newApplicant = "eqk7410";

        $firstApplicantMostRecent = getMostRecentApprovedApplication($this->pdo, $firstApplicant);
        $secondApplicantMostRecent = getMostRecentApprovedApplication($this->pdo, $secondApplicant);
        $thirdApplicantMostRecent = getMostRecentApprovedApplication($this->pdo, $thirdApplicant);
        $newApplicantMostRecent = getMostRecentApprovedApplication($this->pdo, $newApplicant);

        $this->assertEquals("2017-05-20", $firstApplicantMostRecent->dateSubmitted);
        $this->assertEquals(null, $secondApplicantMostRecent);
        $this->assertEquals(null, $thirdApplicantMostRecent);
        $this->assertEquals(null, $newApplicantMostRecent);
    }

    //Check for all past approved application cycles for a user
    public function testGetPastApprovedCycles()
    {
        $firstApplicant = "abc1234";
        $secondApplicant = "zyx4321";
        $thirdApplicant = "bbb7777";
        $newApplicant = "eqk7410";

        $firstApplicantApproved = getPastApprovedCycles($this->pdo, $firstApplicant);
        $secondApplicantApproved = getPastApprovedCycles($this->pdo, $secondApplicant);
        $thirdApplicantApproved = getPastApprovedCycles($this->pdo, $thirdApplicant);
        $newApplicantApproved = getPastApprovedCycles($this->pdo, $newApplicant);

        $this->assertEquals(array("Fall 2017", "Spring 2013"), $firstApplicantApproved);
        $this->assertEquals(null, $secondApplicantApproved);
        $this->assertEquals(null, $thirdApplicantApproved);
        $this->assertEquals(null, $newApplicantApproved);
    }



    //Check the max lengths of the applications columns (no point in testing specific numbers since this could easily change depending on MySQL/schema configuration)
    public function testGetApplicationsMaxLengths()
    {
        $this->assertEquals(28, count(getApplicationsMaxLengths($this->pdo)));
    }

    //Check the max lengths of the applications_budgets columns (no point in testing specific numbers since this could easily change depending on MySQL/schema configuration)
    public function testGetApplicationsBudgetsMaxLengths()
    {
        $this->assertEquals(5, count(getApplicationsBudgetsMaxLengths($this->pdo)));
    }



    /*Test approving an application*/
    public function testApproveApplication()
    {
        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $newAppID = 6;

        $this->assertEquals(false, approveApplication($this->pdo, $approvedAppID, 700)); //awarded amount has to be the same if already approved, otherwise that will be updated
        $this->assertEquals(true, approveApplication($this->pdo, $unapprovedAppID, 700));
        $this->assertEquals(true, approveApplication($this->pdo, $pendingAppID, 700));
        $this->assertEquals(false, approveApplication($this->pdo, $newAppID, 700));
    }

    /*Test denying an application*/
    public function testDenyApplication()
    {
        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $newAppID = 6;

        $this->assertEquals(true, denyApplication($this->pdo, $approvedAppID));
        $this->assertEquals(true, denyApplication($this->pdo, $unapprovedAppID));
        $this->assertEquals(true, denyApplication($this->pdo, $pendingAppID));
        $this->assertEquals(false, denyApplication($this->pdo, $newAppID));
    }

    /*Test holding an application*/
    public function testHoldApplication()
    {
        $approvedAppID = 1;
        $unapprovedAppID = 2;
        $pendingAppID = 3;
        $heldAppID = 4;
        $newAppID = 6;

        $this->assertEquals(true, holdApplication($this->pdo, $approvedAppID));
        $this->assertEquals(true, holdApplication($this->pdo, $unapprovedAppID));
        $this->assertEquals(true, holdApplication($this->pdo, $pendingAppID));
        $this->assertEquals(true, holdApplication($this->pdo, $heldAppID));
        $this->assertEquals(false, holdApplication($this->pdo, $newAppID));
    }

    /*Test signing an application*/
    public function testSignApplication()
    {
        $signedAppID = 1;
        $unsignedAppID = 3;
        $newAppID = 6;

        $this->assertEquals(false, signApplication($this->pdo, $signedAppID, "Sam"));
        $this->assertEquals(true, signApplication($this->pdo, $unsignedAppID, "Sam"));
        $this->assertEquals(false, signApplication($this->pdo, $newAppID, "Sam"));
        $this->assertEquals(true, signApplication($this->pdo, $signedAppID, "Dude")); //try signing an already signed app with a new sig
    }



    /*Test inserting an application. There are MANY errors to check for*/
    public function testInsertApplication()
    {
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

        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));//should start with only 5 applications

        /*for inserting new applications*/

        //pass in empty values for all fields
        $testReturn = insertApplication($this->pdo, false, null, $newApplicant, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", array(array("expense"=>"", "details"=>"", "amount"=>0), array("expense"=>"", "details"=>"", "amount"=>0)));
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));//should still only have 5 applications

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
        $testReturn = insertApplication($this->pdo, false, null, $newApplicant, $validName, "samuel.j.kisonATwmich.edu", $validDepartment, "jeffkrony@wmichDOTedu", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));//should still only have 5 applications

        //just check for email errors
        $this->assertArrayHasKey('email', $testReturn['errors']);
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in a non-wmu deptChairEmail
        $testReturn = insertApplication($this->pdo, false, null, $newApplicant, $validName, $validEmail, $validDepartment, "jeffkrony@gmail.com", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));//should still only have 5 applications

        //just check for email error
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = insertApplication($this->pdo, false, null, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));//should still only have 5 applications

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //pass in no budget array
        $testReturn = insertApplication($this->pdo, false, null, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, array());
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(5, getNumberOfApplications($this->pdo, ""));//should still only have 5 applications

        //just check for budget array error
        $this->assertArrayHasKey('budgetArray', $testReturn['errors']);

        
        //@todo: try inserting an application before enough time has passed. Requires changing dynamic dates to static dates, probably via namespaces or dependency injection


        //insert an acceptable new application
        $testReturn = insertApplication($this->pdo, false, null, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
        $this->assertEquals(6, $testReturn['appID']);//appID should be 6
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should now have 6 applications





        /*for updating applications*/

        $testReturn = insertApplication($this->pdo, true, 6, $newApplicant, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", array(array("expense"=>"", "details"=>"", "amount"=>0), array("expense"=>"", "details"=>"", "amount"=>0)));
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should still have 6 applications

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
        $testReturn = insertApplication($this->pdo, true, 6, $newApplicant, $validName, "samuel.j.kisonATwmich.edu", $validDepartment, "jeffkrony@wmichDOTedu", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should still have 6 applications

        //just check for email errors
        $this->assertArrayHasKey('email', $testReturn['errors']);
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in a non-wmu deptChairEmail
        $testReturn = insertApplication($this->pdo, true, 6, $newApplicant, $validName, $validEmail, $validDepartment, "jeffkrony@gmail.com", 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should still have 6 applications

        //just check for email error
        $this->assertArrayHasKey('deptChairEmail', $testReturn['errors']);


        //pass in an invalid travel date
        $testReturn = insertApplication($this->pdo, true, 6, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, date('Y-m-d h:i:s', mktime(0, 0, 0, 6, 1, 2018)), $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should still have 6 applications

        //just check for invalid travel date
        $this->assertArrayHasKey('travelFrom', $testReturn['errors']);


        //pass in no budget array
        $testReturn = insertApplication($this->pdo, true, 6, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, array());
        $this->assertEquals(false, $testReturn['success']);//insert should have failed
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should still have 6 applications

        //just check for budget array error
        $this->assertArrayHasKey('budgetArray', $testReturn['errors']);


        //insert an acceptable new application
        $testReturn = insertApplication($this->pdo, true, 6, $newApplicant, $validName, $validEmail, $validDepartment, $validDeptChairEmail, 
            $validTravelFrom, $validTravelTo, $validActivityFrom, $validActivityTo, $validTitle, $validDestination, $validAmountRequested, 0, 0, 0, $validPurpose4Other, 
            "", $validProposalSummary, 1, 0, 0, 0, $validNextCycle, $validBudgetArray);
        $this->assertEquals(true, $testReturn['success']);//insert should have succeeded
        $this->assertEquals(6, $testReturn['appID']);//appID should be 6
        $this->assertEquals(6, getNumberOfApplications($this->pdo, ""));//should still have 6 applications
    }
}

?>