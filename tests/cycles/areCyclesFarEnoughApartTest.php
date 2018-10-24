<?php

include_once(dirname(__FILE__) . "/../../server/Cycles.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class verificationTest extends TestCase
{
    //Both should be *just* far enough apart
    public function testAreCyclesJustFarEnoughApart1()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            true,
            $cycles->areCyclesFarEnoughApart("Fall 2016", "Fall 2018")
        );
    }
    public function testAreCyclesJustFarEnoughApart2()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            true,
            $cycles->areCyclesFarEnoughApart("Spring 2017", "Fall 2018")
        );
    }

    //Both should be *just* too close together
    public function testAreCyclesJustTooCloseTogether1()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            false,
            $cycles->areCyclesFarEnoughApart("Fall 2016", "Spring 2018")
        );
    }
    public function testAreCyclesJustTooCloseTogether2()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            false,
            $cycles->areCyclesFarEnoughApart("Spring 2017", "Spring 2018")
        );
    }

    //Both should be really far apart
    public function testAreCyclesVeryFarApart1()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            true,
            $cycles->areCyclesFarEnoughApart("Fall 2016", "Fall 2020")
        );
    }
    public function testAreCyclesVeryFarApart2()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            true,
            $cycles->areCyclesFarEnoughApart("Spring 2017", "Fall 2020")
        );
    }

    //Both should be way too close together
    public function testAreCyclesWayTooCloseTogether1()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            false,
            $cycles->areCyclesFarEnoughApart("Fall 2016", "Spring 2017")
        );
    }
    public function testAreCyclesWayTooCloseTogether2()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            false,
            $cycles->areCyclesFarEnoughApart("Spring 2017", "Fall 2017")
        );
    }

    //Same cycle
    public function testAreCyclesTheSame()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            false,
            $cycles->areCyclesFarEnoughApart("Fall 2016", "Fall 2016")
        );
    }

    //Past cycle (not expected)
    public function testIsCycleTooFarBack()
    {
        $cycles = new Cycles(); //initialize Cycles object
        $this->assertEquals(
            false,
            $cycles->areCyclesFarEnoughApart("Spring 2017", "Fall 2003")
        );
    }
}

?>