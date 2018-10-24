<?php

include_once(dirname(__FILE__) . "/../../server/Cycles.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class sortCyclesTest extends TestCase
{    
    //Test a full array of cycles to make sure they are sorted properly (and in descending order)
    public function testSortCycles()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            array("Spring 2019", "Fall 2018", "Spring 2018", "Spring 2016", "Fall 1996"),
            $cycles->sortCycles(array("Spring 2018", "Fall 1996", "Spring 2016", "Spring 2019", "Fall 2018"))
        );
    }
}

?>