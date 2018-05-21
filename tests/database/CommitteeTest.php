<?php

include_once(dirname(__FILE__) . "/../../functions/database.php"); //the associated file

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
        $testArray = array(
            array(0 => "lke4568", 1 => "Luke"),
            array(0 => "lia4441", 1 => "Leia"),
            array(0 => "han1111", 1 => "Han")
        );
        
        //test array in no particular order
        $this->assertEquals(
            $testArray,
            getCommittee($this->pdo),
            "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true
        );
    }


    public function testAddCommittee_Existing()
    {
        $exisitingCommitteeID = "lke4568"; $exisitingCommitteeName = "Luke";

        $this->expectException(PDOException::class);

        addCommittee($this->pdo, $exisitingCommitteeID, $exisitingCommitteeName);
    }

    public function testAddCommittee_New()
    {
        $newCommitteeID = "chw3333"; $newCommitteeName = "Chewy";
        $this->assertEquals(3, $this->getConnection()->getRowCount('committee'));

        addCommittee($this->pdo, $newCommitteeID, $newCommitteeName);
        $this->assertEquals(4, $this->getConnection()->getRowCount('committee'));
    }


    public function testRemoveCommittee()
    {
        $exisitingCommitteeID = "lke4568";
        $newCommitteeID = "chw3333";

        $this->assertEquals(3, $this->getConnection()->getRowCount('committee'));

        removeCommittee($this->pdo, $newCommitteeID);
        $this->assertEquals(3, $this->getConnection()->getRowCount('committee'));

        removeCommittee($this->pdo, $exisitingCommitteeID);
        $this->assertEquals(2, $this->getConnection()->getRowCount('committee'));
    }


    public function testIsCommitteeMember()
    {
        $exisitingCommitteeID = "lke4568";
        $newCommitteeID = "chw3333";

        $this->assertEquals(false, isCommitteeMember($this->pdo, $newCommitteeID));

        $this->assertEquals(true, isCommitteeMember($this->pdo, $exisitingCommitteeID));
    }
}

?>