<?php

include_once(dirname(__FILE__) . "/../functions/verification.php"); //the associated file

use PHPUnit\Framework\TestCase;

final class verificationTest extends TestCase
{
    public function testAreCyclesFarEnoughApart1()
    {
        $this->assertEquals(
            true,
            areCyclesFarEnoughApart("Fall 2014", "Spring 2018")
        );
    }

    public function testAreCyclesFarEnoughApart2()
    {
        $this->assertEquals(
            false,
            areCyclesFarEnoughApart("Fall 2016", "Spring 2017")
        );
    }
}

?>