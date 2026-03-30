<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testTrueIsTrue()
    {
        $this->assertTrue(true);
    }

    public function testBasicAddition()
    {
        $this->assertEquals(2, 1 + 1);
    }
}