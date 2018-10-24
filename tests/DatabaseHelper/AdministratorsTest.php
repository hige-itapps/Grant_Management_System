<?php

include_once(dirname(__FILE__) . "/../../server/DatabaseHelper.php"); //the associated file
include_once(dirname(__FILE__) . "/../../server/Logger.php"); //Logger is used to generate mock Logger object

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class AdministratorsTest extends TestCase
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/administrators.xml');
    }

    

    public function testGetAdministrators()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $testArray = array(
            array(0 => "rot1222", 1 => "Guy"),
            array(0 => "cvb5233", 1 => "Tom"),
            array(0 => "hhr0012", 1 => "June")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            $database->getAdministrators(),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddAdmin_Existing()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingAdminID = "rot1222"; $existingAdminName = "Guy";

        $this->expectException(PDOException::class);

        $database->addAdmin($existingAdminID, $existingAdminName);
    }

    public function testAddAdmin_New()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $newAdminID = "red7778"; $newAdminName = "Redson";
        $this->assertEquals(3, $this->getConnection()->getRowCount('administrators'));

        $database->addAdmin($newAdminID, $newAdminName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('administrators'));
    }


    public function testRemoveAdmin()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingAdminID = "rot1222";
        $newAdminID = "red7778";

        $this->assertEquals(3, $this->getConnection()->getRowCount('administrators'));

        $database->removeAdmin($newAdminID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('administrators'));

        $database->removeAdmin($existingAdminID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('administrators'));
    }


    public function testIsAdministrator()
    {
        $mockLogger = $this->createMock(Logger::class); //create mock Logger object
        $database = new DatabaseHelper($mockLogger, $this->pdo); //initialize database helper object

        $existingAdminID = "rot1222";
        $newAdminID = "red7778";

        $this->assertEquals(false, $database->isAdministrator($newAdminID));

        $this->assertEquals(true, $database->isAdministrator($existingAdminID));
    }
}

?>