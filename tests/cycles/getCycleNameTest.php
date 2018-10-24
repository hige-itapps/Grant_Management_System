<?php

include_once(dirname(__FILE__) . "/../../server/Cycles.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class getCycleNameTest extends TestCase
{    
    
    //These tests should be well within the deadline bounds
    public function testNormalFall()
    {
        $cycles = new Cycles(); //initialize Cycles object
        //get a date that should fall within the Fall 2040 cycle
        $newDate = DateTime::createFromFormat('Y/m/d', "2040/5/14");

        $this->assertEquals(
            "Fall 2040",
            $cycles->getCycleName($newDate, false, false)
        );
    }
    public function testNormalSpring1()
    {
        $cycles = new Cycles(); //initialize Cycles object
        //get a date that should fall within the Spring 2053 cycle
        $newDate = DateTime::createFromFormat('Y/m/d', "2053/2/12");

        $this->assertEquals(
            "Spring 2053",
            $cycles->getCycleName($newDate, false, false)
        );
    }
    public function testNormalSpring2()
    {
        $cycles = new Cycles(); //initialize Cycles object
        //get a date that should fall within the Spring 2013 cycle, at the end of the 2012 year
        $newDate = DateTime::createFromFormat('Y/m/d', "2012/12/15");

        $this->assertEquals(
            "Spring 2013",
            $cycles->getCycleName($newDate, false, false)
        );
    }

    //Both tests should make sure the date is *just* within the deadline date for the expected cycle
    public function testFinalFallDeadline()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $newDate = DateTime::createFromFormat('Y/m/d', "2020/11/3");

        $this->assertEquals(
            "Fall 2020",
            $cycles->getCycleName($newDate, false, false)
        );
    }
    public function testFinalSpringDeadline()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $newDate = DateTime::createFromFormat('Y/m/d', "2015/4/3");

        $this->assertEquals(
            "Spring 2015",
            $cycles->getCycleName($newDate, false, false)
        );
    }

    //Both tests should make sure the the date is *just* after the final deadline date for the cycle (so the next cycle afterwards)
    public function testStartSpringDeadline()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $newDate = DateTime::createFromFormat('Y/m/d', "2020/11/4");

        $this->assertEquals(
            "Spring 2021",
            $cycles->getCycleName($newDate, false, false)
        );
    }
    public function testStartFallDeadline()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $newDate = DateTime::createFromFormat('Y/m/d', "2015/4/4");

        $this->assertEquals(
            "Fall 2015",
            $cycles->getCycleName($newDate, false, false)
        );
    }

    //Check that the due dates are shown correctly when requested
    public function testFallDueDate()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $newDate = DateTime::createFromFormat('Y/m/d', "2003/7/9");

        $this->assertEquals(
            "Fall 2003, due Nov. 1",
            $cycles->getCycleName($newDate, false, true)
        );
    }
    public function testSpringDueDate()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $newDate = DateTime::createFromFormat('Y/m/d', "2012/12/16");

        $this->assertEquals(
            "Spring 2013, due Apr. 1",
            $cycles->getCycleName($newDate, false, true)
        );
    }

    //Same as the first 3 tests, but with the nextCycle boolean set to true (so everything should be shifted forward by 1 cycle)
    public function testNextCycleFall()
    {
        $cycles = new Cycles(); //initialize Cycles object
        //get a date that should fall within the Fall 2040 cycle
        $newDate = DateTime::createFromFormat('Y/m/d', "2040/5/14");

        $this->assertEquals(
            "Spring 2041",
            $cycles->getCycleName($newDate, true, false)
        );
    }
    public function testNextCycleSpring1()
    {
        $cycles = new Cycles(); //initialize Cycles object
        //get a date that should fall within the Spring 2053 cycle
        $newDate = DateTime::createFromFormat('Y/m/d', "2053/2/12");

        $this->assertEquals(
            "Fall 2053",
            $cycles->getCycleName($newDate, true, false)
        );
    }
    public function testNextCycleSpring2()
    {
        $cycles = new Cycles(); //initialize Cycles object
        //get a date that should fall within the Spring 2013 cycle, at the end of the 2012 year
        $newDate = DateTime::createFromFormat('Y/m/d', "2012/12/15");

        $this->assertEquals(
            "Fall 2013",
            $cycles->getCycleName($newDate, true, false)
        );
    }
}

?>