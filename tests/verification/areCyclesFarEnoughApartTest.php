<?php

include_once(dirname(__FILE__) . "/../../functions/verification.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class verificationTest extends TestCase
{
    //Both should be *just* far enough apart
    public function testAreCyclesJustFarEnoughApart1()
    {
        $this->assertEquals(
            true,
            areCyclesFarEnoughApart("Fall 2016", "Fall 2018")
        );
    }
    public function testAreCyclesJustFarEnoughApart2()
    {
        $this->assertEquals(
            true,
            areCyclesFarEnoughApart("Spring 2017", "Fall 2018")
        );
    }

    //Both should be *just* too close together
    public function testAreCyclesJustTooCloseTogether1()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Fall 2016", "Spring 2018")
        );
    }
    public function testAreCyclesJustTooCloseTogether2()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Spring 2017", "Spring 2018")
        );
    }

    //Both should be really far apart
    public function testAreCyclesVeryFarApart1()
    {
        $this->assertEquals(
            true,
            areCyclesFarEnoughApart("Fall 2016", "Fall 2020")
        );
    }
    public function testAreCyclesVeryFarApart2()
    {
        $this->assertEquals(
            true,
            areCyclesFarEnoughApart("Spring 2017", "Fall 2020")
        );
    }

    //Both should be way too close together
    public function testAreCyclesWayTooCloseTogether1()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Fall 2016", "Spring 2017")
        );
    }
    public function testAreCyclesWayTooCloseTogether2()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Spring 2017", "Fall 2017")
        );
    }

    //Same cycle
    public function testAreCyclesTheSame()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Fall 2016", "Fall 2016")
        );
    }

    //Past cycle (not expected)
    public function testIsCycleTooFarBack()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Spring 2017", "Fall 2003")
        );
    }
}

?>