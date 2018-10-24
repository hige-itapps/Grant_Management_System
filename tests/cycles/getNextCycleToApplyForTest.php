<?php

include_once(dirname(__FILE__) . "/../../server/Cycles.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class getNextCycleToApplyForTest extends TestCase
{    
    
    //Test both Fall and Spring cycles
    public function testCycles()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $fallCycle = "Fall 2040";
        $springCycle = "Spring 1996";

        $this->assertEquals("Fall 2042", $cycles->getNextCycleToApplyFor($fallCycle));
        $this->assertEquals("Fall 1997", $cycles->getNextCycleToApplyFor($springCycle));
    }
}

?>