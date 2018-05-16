<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class getAdministratorsTest extends TestCase
{    
    use TestCaseTrait;

    /**
     * @return PHPUnit\DbUnit\Database\Connection
     */
    public function getConnection()
    {
        $config_url = dirname(__FILE__).'/../testconfig.ini';
        $settings = parse_ini_file($config_url);
        
        //var_dump($settings);
        $conn = new PDO("mysql:host=" . $settings["test_hostname"] . ";dbname=" . $settings["test_database_name"] . ";charset=utf8", $settings["test_database_username"], 
            $settings["test_database_password"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
    }

    /**
     * @return PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_files/guestbook-seed.xml');
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