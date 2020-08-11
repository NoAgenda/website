<?php

namespace App\Tests;

use App\Utilities;
use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase
{
    public function testParsePrettyTimestamp()
    {
        $this->assertEquals(12, Utilities::parsePrettyTimestamp('12'));
        $this->assertEquals(42, Utilities::parsePrettyTimestamp('0:42'));
        $this->assertEquals(63, Utilities::parsePrettyTimestamp('1:03'));
        $this->assertEquals(654, Utilities::parsePrettyTimestamp('10:54'));
        $this->assertEquals(5426, Utilities::parsePrettyTimestamp('1:30:26'));
    }
}
