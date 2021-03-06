<?php

include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php"); //the associated file
include_once(dirname(__FILE__) . "/../../server/Logger.php"); //Logger is used to generate mock Logger object

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class FinalReportApproversTest extends TestCase
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/final_report_approvers.xml');
    }

    

    public function testGetFinalReportApprovers()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $testArray = array(
            array(0 => "wdr5341", 1 => "Wander"),
            array(0 => "mno3409", 1 => "Mono"),
            array(0 => "agr1308", 1 => "Agro")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            $database->getFinalReportApprovers(),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddFinalReportApprover_Existing()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApproverID = "wdr5341"; $existingApproverName = "Wander";

        $this->expectException(PDOException::class);

        $database->addFinalReportApprover($existingApproverID, $existingApproverName);
    }

    public function testAddFinalReportApprover_New()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $newApproverID = "dor3122"; $newApproverName = "Dormin";
        $this->assertEquals(3, $this->getConnection()->getRowCount('final_report_approval'));

        $database->addFinalReportApprover($newApproverID, $newApproverName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('final_report_approval'));
    }


    public function testRemoveFinalReportApprover()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApproverID = "wdr5341";
        $newApproverID = "dor3122";

        $this->assertEquals(3, $this->getConnection()->getRowCount('final_report_approval'));

        $database->removeFinalReportApprover($newApproverID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('final_report_approval'));

        $database->removeFinalReportApprover($existingApproverID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('final_report_approval'));
    }


    public function testIsFinalReportApprover()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingApproverID = "wdr5341";
        $newApproverID = "dor3122";

        $this->assertEquals(false, $database->isFinalReportApprover($newApproverID));

        $this->assertEquals(true, $database->isFinalReportApprover($existingApproverID));
    }
}

?>