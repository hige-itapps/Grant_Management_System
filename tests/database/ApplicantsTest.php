<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

final class ApplicantsTest extends TestCase
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
        return $this->createXMLDataSet(dirname(__FILE__).'/datasets/applicants.xml');
    }

    

    public function testGetApplicants()
    {
        $testArray = array(
            array(0 => "abc1234"),
            array(0 => "zyx4321"),
            array(0 => "bbb7777")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            getApplicants($this->pdo),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testInsertApplicantIfNew()
    {
        $existingApplicant = "abc1234";
        $newApplicant = "plo9770";
        
        $this->assertEquals(3, $this->getConnection()->getRowCount('applicants'));

        insertApplicantIfNew($this->pdo, $existingApplicant);
        $this->assertEquals(3, $this->getConnection()->getRowCount('applicants'));

        insertApplicantIfNew($this->pdo, $newApplicant);
        $this->assertEquals(4, $this->getConnection()->getRowCount('applicants'));
    }
}

?>