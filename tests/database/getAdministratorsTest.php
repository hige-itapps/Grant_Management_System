<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class getAdministratorsTest extends TestCase
{    
    use TestCaseTrait;

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;
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
            if (self::$pdo == null) {
                self::$pdo = new PDO("mysql:host=" . $settings["test_hostname"] . ";dbname=" . $settings["test_database_name"] . ";charset=utf8", $settings["test_database_username"], 
                    $settings["test_database_password"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
                // set the PDO error mode to exception
                //self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                //self::$pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
            }
            //$this->conn = $this->createDefaultDBConnection(self::$pdo, $settings["test_database_name"]);
            $this->conn = $this->createDefaultDBConnection(self::$pdo);

            //$conn = new PDO("mysql:host=" . $settings["test_hostname"] . ";dbname=" . $settings["test_database_name"] . ";charset=utf8", $settings["test_database_username"], $settings["test_database_password"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        }

        return $this->conn;
    }

    /**
     * @return PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/datasets/administrators2.xml');
    }




    
    public function testNoAdministrators()
    {
        $testArray = array();
        $this->assertEquals(
            $testArray,
            getAdministrators()
        );
    }

    public function testOneAdministrator()
    {
        $testArray = array(
            array("abc1234", "Guy")
        );

        $this->assertEquals(
            $testArray,
            getAdministrators()
        );
    }

    public function testManyAdministrators()
    {
        $testArray = array(
            array("abc1234", "Guy"),
            array("zyx4321", "Tom"),
            array("bbb7777", "June")
        );

        $this->assertEquals(
            $testArray,
            getAdministrators()
        );
    }
}

?>