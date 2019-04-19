<?php

namespace HughCube\Crontab\Parse\Tests;

use HughCube\Crontab\Parse\DateParser;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    public function testInstance()
    {
        $instance = new DateParser('* * * * *');
        $this->assertInstanceOf(DateParser::class, $instance);


        $instance->withTimestamp(time());
        $this->assertInstanceOf(DateParser::class, $instance);


        $instance = new DateParser('* * * * * ');
        $this->assertTrue($instance->isRuntime());


        $instance = new DateParser('*/1 * * * * ');
        $this->assertTrue($instance->isRuntime());


        $instance = new DateParser('*/1,18 * * * * ');
        $this->assertTrue($instance->isRuntime());


        $instance = new DateParser('18 * * * * ');
        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:18:00'));
        $this->assertTrue($instance->isRuntime());


        $instance = new DateParser('18,19,20-40/3 * * * * ');
        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:18:00'));
        $this->assertTrue($instance->isRuntime());

        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:20:00'));
        $this->assertTrue($instance->isRuntime());

        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:23:00'));
        $this->assertTrue($instance->isRuntime());

        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:40:00'));
        $this->assertFalse($instance->isRuntime());


        $instance = new DateParser('18 1 * * * ');
        $instance = $instance->withTimestamp(strtotime('2019-04-19 1:18:00'));
        $this->assertTrue($instance->isRuntime());

        $instance = $instance->withTimestamp(strtotime('2019-04-19 1:18:00'));
        $this->assertTrue($instance->isRuntime());

        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:18:00'));
        $this->assertFalse($instance->isRuntime());


        $instance = new DateParser('18 1 1 1 * ');
        $instance = $instance->withTimestamp(strtotime('2019-01-01 1:18:00'));
        $this->assertTrue($instance->isRuntime());

        $instance = $instance->withTimestamp(strtotime('2019-04-19 12:18:00'));
        $this->assertFalse($instance->isRuntime());

        $instance = new DateParser('* * * * 5');
        $instance = $instance->withTimestamp(strtotime('2019-04-19 1:18:00'));
        $this->assertTrue($instance->isRuntime());
    }
}
