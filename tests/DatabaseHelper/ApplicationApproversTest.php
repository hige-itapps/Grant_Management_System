<?php

include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php"); //the associated file
include_once(dirname(__FILE__) . "/../../server/Logger.php"); //Logger is used to generate mock Logger object

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
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $testArray = array(
            array(0 => "alg4357", 1 => "Algernon"),
            array(0 => "jim6345", 1 => "Jimmy"),
            array(0 => "pte5211", 1 => "Petey")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            $database->getApplicationApprovers(),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddApplicationApprover_Existing()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApproverID = "pte5211"; $existingApproverName = "Petey";

        $this->expectException(PDOException::class);

        $database->addApplicationApprover($existingApproverID, $existingApproverName);
    }

    public function testAddApplicationApprover_New()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $newApproverID = "gry9922"; $newApproverName = "Gary";
        $this->assertEquals(3, $this->getConnection()->getRowCount('application_approval'));

        $database->addApplicationApprover($newApproverID, $newApproverName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('application_approval'));
    }


    public function testRemoveApplicationApprover()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApproverID = "pte5211";
        $newApproverID = "gry9922";

        $this->assertEquals(3, $this->getConnection()->getRowCount('application_approval'));

        $database->removeApplicationApprover($newApproverID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('application_approval'));

        $database->removeApplicationApprover($existingApproverID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('application_approval'));
    }


    public function testIsApplicationApprover()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApproverID = "pte5211";
        $newApproverID = "gry9922";

        $this->assertEquals(false, $database->isApplicationApprover($newApproverID));

        $this->assertEquals(true, $database->isApplicationApprover($existingApproverID));
    }
}

?>