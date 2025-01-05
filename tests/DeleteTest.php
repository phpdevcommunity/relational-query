<?php

namespace Test\PhpDevCommunity\Sql;

use PhpDevCommunity\UniTester\TestCase;
use PhpDevCommunity\Sql\Delete;

class DeleteTest extends TestCase
{

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        $this->testToStringWithConditions();
        $this->testToStringWithoutConditions();
    }

    public function testToStringWithoutConditions()
    {
        $delete = new Delete('table_name');
        $this->assertEquals('DELETE FROM table_name', $delete->__toString());
    }

    public function testToStringWithConditions()
    {
        $delete = new Delete('table_name');
        $delete->where('condition1', 'condition2');
        $this->assertEquals('DELETE FROM table_name WHERE condition1 AND condition2', $delete->__toString());
    }

}