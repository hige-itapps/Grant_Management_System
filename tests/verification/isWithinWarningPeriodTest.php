<?php

include_once(dirname(__FILE__) . "/../../functions/verification.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class isWithinWarningPeriodTest extends TestCase
{    
    
    //Check a date in the middle of the fall warning period
    public function testMidFallWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/11/2"))
        );
    }
    //Check a date at the start of the fall warning period
    public function testStartFallWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/11/1"))
        );
    }
    //Check a date at the end of the fall warning period
    public function testEndFallWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/11/3"))
        );
    }



    //Check a date in the middle of the spring warning period
    public function testMidSpringWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/4/2"))
        );
    }
    //Check a date at the start of the spring warning period
    public function testStartSpringWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/4/1"))
        );
    }
    //Check a date at the end of the spring warning period
    public function testEndSpringWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/4/3"))
        );
    }



    //Check a date in the middle of the fall cycle
    public function testMidFallNoWarn()
    {
        $this->assertEquals(
            false,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/9/12"))
        );
    }
    //Check a date at the start of the fall cycle
    public function testStartFallNoWarn()
    {
        $this->assertEquals(
            false,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/4/4"))
        );
    }
    //Check a date near the end of the fall cycle, the day before the first expected warning date
    public function testEndFallNoWarn()
    {
        $this->assertEquals(
            false,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/10/31"))
        );
    }



    //Check a date in the middle of the spring cycle
    public function testMidSpringNoWarn()
    {
        $this->assertEquals(
            false,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/2/7"))
        );
    }
    //Check a date at the start of the spring cycle
    public function testStartSpringNoWarn()
    {
        $this->assertEquals(
            false,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/11/4"))
        );
    }
    //Check a date near the end of the spring cycle, the day before the first expected warning date
    public function testEndSpringNoWarn()
    {
        $this->assertEquals(
            false,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/3/31"))
        );
    }
   
}

?>