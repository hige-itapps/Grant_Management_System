<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

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
        $testArray = array(
            array(0 => "rot1222", 1 => "Guy"),
            array(0 => "cvb5233", 1 => "Tom"),
            array(0 => "hhr0012", 1 => "June")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            getAdministrators($this->pdo),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddAdmin_Existing()
    {
        $existingAdminID = "rot1222"; $existingAdminName = "Guy";

        $this->expectException(PDOException::class);

        addAdmin($this->pdo, $existingAdminID, $existingAdminName);
    }

    public function testAddAdmin_New()
    {
        $newAdminID = "red7778"; $newAdminName = "Redson";
        $this->assertEquals(3, $this->getConnection()->getRowCount('administrators'));

        addAdmin($this->pdo, $newAdminID, $newAdminName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('administrators'));
    }


    public function testRemoveAdmin()
    {
        $existingAdminID = "rot1222";
        $newAdminID = "red7778";

        $this->assertEquals(3, $this->getConnection()->getRowCount('administrators'));

        removeAdmin($this->pdo, $newAdminID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('administrators'));

        removeAdmin($this->pdo, $existingAdminID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('administrators'));
    }


    public function testIsAdministrator()
    {
        $existingAdminID = "rot1222";
        $newAdminID = "red7778";

        $this->assertEquals(false, isAdministrator($this->pdo, $newAdminID));

        $this->assertEquals(true, isAdministrator($this->pdo, $existingAdminID));
    }
}

?>