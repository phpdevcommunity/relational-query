<?php

namespace Test\PhpDevCommunity\Sql;

use PhpDevCommunity\Sql\Expression\Expr;
use PhpDevCommunity\UniTester\TestCase;

class ExprTest extends TestCase
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
        $this->testEqual();
        $this->testNotEqual();
        $this->testGreaterThan();
        $this->testGreaterThanEqual();
        $this->testLowerThan();
        $this->testLowerThanEqual();
        $this->testIsNull();
        $this->testIsNotNull();
        $this->testIn();
        $this->testNotIn();
    }
    public function testEqual()
    {
        $this->assertEquals('id = 1', Expr::equal('id', '1'));
    }

    public function testNotEqual()
    {
        $this->assertEquals('name <> John', Expr::notEqual('name', 'John'));
    }

    public function testGreaterThan()
    {
        $this->assertEquals('quantity > 10', Expr::greaterThan('quantity', '10'));
    }

    public function testGreaterThanEqual()
    {
        $this->assertEquals('price >= 100', Expr::greaterThanEqual('price', '100'));
    }

    public function testLowerThan()
    {
        $this->assertEquals('age < 30', Expr::lowerThan('age', '30'));
    }

    public function testLowerThanEqual()
    {
        $this->assertEquals('score <= 80', Expr::lowerThanEqual('score', '80'));
    }

    public function testIsNull()
    {
        $this->assertEquals('description IS NULL', Expr::isNull('description'));
    }

    public function testIsNotNull()
    {
        $this->assertEquals('status IS NOT NULL', Expr::isNotNull('status'));
    }

    public function testIn()
    {
        $this->assertEquals('category IN (1, 2, 3)', Expr::in('category', [1, 2, 3]));
    }

    public function testNotIn()
    {
        $this->assertEquals("color NOT IN ('red', 'blue')", Expr::notIn('color', ['red', 'blue']));
    }

}