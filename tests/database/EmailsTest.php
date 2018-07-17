<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class EmailsTest extends TestCase
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
        $ds1 = $this->createXMLDataSet(dirname(__FILE__).'/datasets/applicants.xml');
        $ds2 = $this->createXMLDataSet(dirname(__FILE__).'/datasets/applications.xml');
        $ds3 = $this->createXMLDataSet(dirname(__FILE__).'/datasets/emails.xml');

        $compositeDs = new PHPUnit\DbUnit\DataSet\CompositeDataSet();
        $compositeDs->addDataSet($ds1);
        $compositeDs->addDataSet($ds2);
        $compositeDs->addDataSet($ds3);

        return $compositeDs;
    }

    

    public function testSaveEmail()
    {
        $newSubject = 'New Subject';
        $newMessage = 'New Message';

        $this->assertEquals(3, $this->getConnection()->getRowCount('emails')); //start with 3 emails

        saveEmail($this->pdo, 1, $newSubject, $newMessage);
        
        $this->assertEquals(4, $this->getConnection()->getRowCount('emails')); //there should be 4 emails now
    }


    public function testGetEmails()
    {
        $this->assertEquals(3, count(getEmails($this->pdo, 1)));
        $this->assertEquals(0, count(getEmails($this->pdo, 4)));
    }
}

?>