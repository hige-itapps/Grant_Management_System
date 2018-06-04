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
                $this->pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/applications.xml');
    }

    
    //test getting a single application- make sure all fields are correct!
    public function testGetApplication()
    {
        $exisitingAppID = 1;
        $existingAppArray = array(
            0 => "1", 1 => "abc1234", 2 => "2012-05-20", 3 => "Alphy", 4 => "Literature",
            /*5 => NULL,*/ 6 => "abc@wmich.edu", 7 => "Lorum Ipsum", 8 => "2012-06-05", 9 => "2012-06-23",
            10 => "2012-06-08", 11 => "2012-06-13", 12 => "Cambridge University", 13 => number_format((float)700, 2, '.', ''), 14 => 0, //format budget as decimal to 2 places
            15 => 1, 16 => 0, 17 => "This is also a barbeque", 18 => "No other funding", 19 => "Lorum Ipsum Lorum Ipsum etc.",
            20 => 0, 21 => 1, 22 => 0, 23 => 1, 24 => "samuel.j.kison@wmich.edu",
            25 => "Sam", 26 => 1, 27 => number_format((float)700, 2, '.', ''), 28 => 0, 29 => 1
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
        $newDeptChairEmail = "mrDerp@wmich.edu";

        $this->assertEquals(1, count(getApplicationsToSign($this->pdo, $existingDeptChairEmail)));

        $this->assertEquals(0, count(getApplicationsToSign($this->pdo, $newDeptChairEmail)));
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
        $newDeptChairEmail = "mrDerp@wmich.edu";

        $this->assertEquals(1, getNumberOfApplicationsToSign($this->pdo, $existingDeptChairEmail));

        $this->assertEquals(0, getNumberOfApplicationsToSign($this->pdo, $newDeptChairEmail));
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

        $this->assertEquals("2017-05-20", $firstApplicantMostRecent->dateS);
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
        $this->assertEquals(30, count(getApplicationsMaxLengths($this->pdo)));
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
        $this->assertEquals(false, denyApplication($this->pdo, $unapprovedAppID));
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
        $this->assertEquals(false, holdApplication($this->pdo, $heldAppID));
        $this->assertEquals(false, holdApplication($this->pdo, $newAppID));
    }

    /*Test signing an app*/
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
}

?>