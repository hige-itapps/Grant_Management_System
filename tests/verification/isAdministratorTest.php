<?php

include_once(dirname(__FILE__) . "/../../functions/verification.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class isAdministratorTest extends TestCase
{    
    
    //Check a date in the middle of the fall warning period
    public function testMidFallWarn()
    {
        $this->assertEquals(
            true,
            isWithinWarningPeriod(DateTime::createFromFormat('Y/m/d', "2012/11/2"))
        );
    }
  

    
   
}

?>