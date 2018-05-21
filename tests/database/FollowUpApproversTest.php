<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class FollowUpApproversTest extends TestCase
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/follow_up_approvers.xml');
    }

    

    public function testGetFollowUpReportApprovers()
    {
        $testArray = array(
            array(0 => "wdr5341", 1 => "Wander"),
            array(0 => "mno3409", 1 => "Mono"),
            array(0 => "agr1308", 1 => "Agro")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            getFollowUpReportApprovers($this->pdo),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddFollowUpApprover_Existing()
    {
        $existingApproverID = "wdr5341"; $existingApproverName = "Wander";

        $this->expectException(PDOException::class);

        addFollowUpApprover($this->pdo, $existingApproverID, $existingApproverName);
    }

    public function testAddFollowUpApprover_New()
    {
        $newApproverID = "dor3122"; $newApproverName = "Dormin";
        $this->assertEquals(3, $this->getConnection()->getRowCount('follow_up_approval'));

        addFollowUpApprover($this->pdo, $newApproverID, $newApproverName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('follow_up_approval'));
    }


    public function testRemoveFollowUpApprover()
    {
        $existingApproverID = "wdr5341";
        $newApproverID = "dor3122";

        $this->assertEquals(3, $this->getConnection()->getRowCount('follow_up_approval'));

        removeFollowUpApprover($this->pdo, $newApproverID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('follow_up_approval'));

        removeFollowUpApprover($this->pdo, $existingApproverID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('follow_up_approval'));
    }


    public function testIsFollowUpReportApprover()
    {
        $existingApproverID = "wdr5341";
        $newApproverID = "dor3122";

        $this->assertEquals(false, isFollowUpReportApprover($this->pdo, $newApproverID));

        $this->assertEquals(true, isFollowUpReportApprover($this->pdo, $existingApproverID));
    }
}

?>