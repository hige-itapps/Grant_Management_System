<?php

include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php"); //the associated file
include_once(dirname(__FILE__) . "/../../server/Logger.php"); //Logger is used to generate mock Logger object

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class CommitteeTest extends TestCase
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/committee.xml');
    }

    

    public function testGetCommittee()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $testArray = array(
            array(0 => "lke4568", 1 => "Luke"),
            array(0 => "lia4441", 1 => "Leia"),
            array(0 => "han1111", 1 => "Han")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            $database->getCommittee(),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddCommittee_Existing()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $exisitingCommitteeID = "lke4568"; $exisitingCommitteeName = "Luke";

        $this->expectException(PDOException::class);

        $database->addCommittee($exisitingCommitteeID, $exisitingCommitteeName);
    }

    public function testAddCommittee_New()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $newCommitteeID = "chw3333"; $newCommitteeName = "Chewy";
        $this->assertEquals(3, $this->getConnection()->getRowCount('committee'));

        $database->addCommittee($newCommitteeID, $newCommitteeName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('committee'));
    }


    public function testRemoveCommittee()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $exisitingCommitteeID = "lke4568";
        $newCommitteeID = "chw3333";

        $this->assertEquals(3, $this->getConnection()->getRowCount('committee'));

        $database->removeCommittee($newCommitteeID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('committee'));

        $database->removeCommittee($exisitingCommitteeID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('committee'));
    }


    public function testIsCommitteeMember()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $exisitingCommitteeID = "lke4568";
        $newCommitteeID = "chw3333";

        $this->assertEquals(false, $database->isCommitteeMember($newCommitteeID));

        $this->assertEquals(true, $database->isCommitteeMember($exisitingCommitteeID));
    }
}

?>