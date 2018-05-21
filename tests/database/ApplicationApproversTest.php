<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class ApplicationApproversTest extends TestCase
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/application_approvers.xml');
    }

    

    public function testGetApplicationApprovers()
    {
        $testArray = array(
            array(0 => "alg4357", 1 => "Algernon"),
            array(0 => "jim6345", 1 => "Jimmy"),
            array(0 => "pte5211", 1 => "Petey")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            getApplicationApprovers($this->pdo),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddApplicationApprover_Existing()
    {
        $existingApproverID = "pte5211"; $existingApproverName = "Petey";

        $this->expectException(PDOException::class);

        addApplicationApprover($this->pdo, $existingApproverID, $existingApproverName);
    }

    public function testAddApplicationApprover_New()
    {
        $newApproverID = "gry9922"; $newApproverName = "Gary";
        $this->assertEquals(3, $this->getConnection()->getRowCount('application_approval'));

        addApplicationApprover($this->pdo, $newApproverID, $newApproverName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('application_approval'));
    }


    public function testRemoveApplicationApprover()
    {
        $existingApproverID = "pte5211";
        $newApproverID = "gry9922";

        $this->assertEquals(3, $this->getConnection()->getRowCount('application_approval'));

        removeApplicationApprover($this->pdo, $newApproverID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('application_approval'));

        removeApplicationApprover($this->pdo, $existingApproverID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('application_approval'));
    }


    public function testIsApplicationApprover()
    {
        $existingApproverID = "pte5211";
        $newApproverID = "gry9922";

        $this->assertEquals(false, isApplicationApprover($this->pdo, $newApproverID));

        $this->assertEquals(true, isApplicationApprover($this->pdo, $existingApproverID));
    }
}

?>