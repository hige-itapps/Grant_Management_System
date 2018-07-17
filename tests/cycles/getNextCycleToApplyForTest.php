<?php

include_once(dirname(__FILE__) . "/../../functions/cycles.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class getNextCycleToApplyForTest extends TestCase
{    
    
    //Test both Fall and Spring cycles
    public function testCycles()
    {
        $fallCycle = "Fall 2040";
        $springCycle = "Spring 1996";

        $this->assertEquals("Fall 2042", getNextCycleToApplyFor($fallCycle));
        $this->assertEquals("Fall 1997", getNextCycleToApplyFor($springCycle));
    }
}

?>