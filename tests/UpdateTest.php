<?php

namespace Test\PhpDevCommunity\Sql;

use PhpDevCommunity\Sql\Update;
use PhpDevCommunity\UniTester\TestCase;

class UpdateTest extends TestCase
{
    protected function execute(): void
    {
        $this->testNoTable();
        $this->testToString();
        $this->testWhereWithoutColumns();
        $this->testSet();
    }
    public function testNoTable()
    {
        $update = new Update('my_table', 't');
        $this->expectException(\LogicException::class, function () use ($update) {
            $update->__toString();
        });
    }

    public function testToString()
    {
        $update = new Update('my_table');
        $update->set('column1', 'value1')->set('column2', 'value2')->where('condition1');
        $this->assertEquals('UPDATE my_table SET column1 = value1, column2 = value2 WHERE condition1', (string)$update);
    }

    public function testWhereWithoutColumns()
    {
        $update = new Update('my_table');
        $update->where('condition1', 'condition2');
        $this->expectException(\LogicException::class, function () use ($update) {
            $update->__toString();
        });
    }

    public function testSet()
    {
        $update = new Update('my_table');
        $update->set('column1', 'value1')->set('column2', 'value2');
        $this->assertEquals('UPDATE my_table SET column1 = value1, column2 = value2', (string)$update);
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