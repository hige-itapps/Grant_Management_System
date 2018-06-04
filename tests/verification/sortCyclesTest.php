<?php

include_once(dirname(__FILE__) . "/../../functions/verification.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class sortCyclesTest extends TestCase
{    
    
    //Test simple pairs of cycles to make sure they are sorted properly
    public function testCmpCycles()
    {
        $this->assertEquals(
            0,
            cmpCycles("Fall 2017", "Fall 2017")
        );

        $this->assertEquals(
            1,
            cmpCycles("Fall 2018", "Spring 2017")
        );

        $this->assertEquals(
            -1,
            cmpCycles("Fall 2017", "Spring 2018")
        );

        $this->assertEquals(
            1,
            cmpCycles("Fall 2017", "Spring 2017")
        );
    }
   

    //Test a full array of cycles to make sure they are sorted properly (and in descending order)
    public function testSortCycles()
    {
        $this->assertEquals(
            array("Spring 2019", "Fall 2018", "Spring 2018", "Spring 2016", "Fall 1996"),
            sortCycles(array("Spring 2018", "Fall 1996", "Spring 2016", "Spring 2019", "Fall 2018"))
        );
    }
}

?>