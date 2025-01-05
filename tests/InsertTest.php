<?php

namespace Test\PhpDevCommunity\Sql;

use PhpDevCommunity\Sql\Insert;
use PhpDevCommunity\UniTester\TestCase;

class InsertTest extends TestCase
{

    protected function execute(): void
    {
        $this->testConstructor();
        $this->testSetValue();
    }
    public function testConstructor()
    {
        $insert = new Insert('my_table');
        $this->expectException(\LogicException::class , function () use ($insert) {
            $insert->__toString();
        });
    }

    public function testSetValue()
    {
        $insert = new Insert('my_table');
        $insert->setValue('column1', 'value1')->setValue('column2', 'value2');
        $this->assertEquals('INSERT INTO my_table (column1, column2) VALUES (value1, value2)', (string)$insert);
    }

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

}