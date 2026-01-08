<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\ConditionalLogic;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class ConditionalLogicTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub WordPress functions
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('apply_filters')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testStaticIfMethod()
    {
        $logic = ConditionalLogic::if('test_field');

        $this->assertInstanceOf(ConditionalLogic::class, $logic);
    }

    public function testStaticWhereMethod()
    {
        $logic = ConditionalLogic::where('test_field');

        $this->assertInstanceOf(ConditionalLogic::class, $logic);
    }

    public function testEqualsOperator()
    {
        $logic = ConditionalLogic::if('test_field')->equals('test_value');
        $result = $logic->evaluate(['test_field' => 'test_value']);

        $this->assertTrue($result);

        $result = $logic->evaluate(['test_field' => 'different_value']);
        $this->assertFalse($result);
    }

    public function testNotEqualsOperator()
    {
        $logic = ConditionalLogic::if('test_field')->notEquals('test_value');
        $result = $logic->evaluate(['test_field' => 'different_value']);

        $this->assertTrue($result);

        $result = $logic->evaluate(['test_field' => 'test_value']);
        $this->assertFalse($result);
    }

    public function testGreaterThanOperator()
    {
        $logic = ConditionalLogic::if('test_field')->greaterThan(5);

        $this->assertTrue($logic->evaluate(['test_field' => 10]));
        $this->assertFalse($logic->evaluate(['test_field' => 5]));
        $this->assertFalse($logic->evaluate(['test_field' => 3]));
    }

    public function testLessThanOperator()
    {
        $logic = ConditionalLogic::if('test_field')->lessThan(10);

        $this->assertTrue($logic->evaluate(['test_field' => 5]));
        $this->assertFalse($logic->evaluate(['test_field' => 10]));
        $this->assertFalse($logic->evaluate(['test_field' => 15]));
    }

    public function testInOperator()
    {
        $logic = ConditionalLogic::if('test_field')->in(['option1', 'option2', 'option3']);

        $this->assertTrue($logic->evaluate(['test_field' => 'option1']));
        $this->assertTrue($logic->evaluate(['test_field' => 'option2']));
        $this->assertFalse($logic->evaluate(['test_field' => 'option4']));
    }

    public function testNotInOperator()
    {
        $logic = ConditionalLogic::if('test_field')->notIn(['option1', 'option2']);

        $this->assertFalse($logic->evaluate(['test_field' => 'option1']));
        $this->assertTrue($logic->evaluate(['test_field' => 'option3']));
    }

    public function testContainsOperator()
    {
        $logic = ConditionalLogic::if('test_field')->contains('search_term');

        $this->assertTrue($logic->evaluate(['test_field' => 'This contains search_term']));
        $this->assertTrue($logic->evaluate(['test_field' => 'search_term is here']));
        $this->assertFalse($logic->evaluate(['test_field' => 'No match here']));
    }

    public function testEmptyOperator()
    {
        $logic = ConditionalLogic::if('test_field')->empty();

        $this->assertTrue($logic->evaluate(['test_field' => '']));
        $this->assertTrue($logic->evaluate(['test_field' => null]));
        $this->assertTrue($logic->evaluate(['test_field' => 0]));
        $this->assertTrue($logic->evaluate(['test_field' => []]));
        $this->assertFalse($logic->evaluate(['test_field' => 'not empty']));
        $this->assertFalse($logic->evaluate(['test_field' => [1, 2, 3]]));
    }

    public function testNotEmptyOperator()
    {
        $logic = ConditionalLogic::if('test_field')->notEmpty();

        $this->assertFalse($logic->evaluate(['test_field' => '']));
        $this->assertFalse($logic->evaluate(['test_field' => null]));
        $this->assertFalse($logic->evaluate(['test_field' => 0]));
        $this->assertFalse($logic->evaluate(['test_field' => []]));
        $this->assertTrue($logic->evaluate(['test_field' => 'not empty']));
        $this->assertTrue($logic->evaluate(['test_field' => [1, 2, 3]]));
    }

    public function testAndConditions()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->and('field2')->equals('value2');

        $values = ['field1' => 'value1', 'field2' => 'value2'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'value1', 'field2' => 'wrong'];
        $this->assertFalse($logic->evaluate($values));

        $values = ['field1' => 'wrong', 'field2' => 'value2'];
        $this->assertFalse($logic->evaluate($values));
    }

    public function testOrConditions()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->or('field2')->equals('value2');

        $values = ['field1' => 'value1', 'field2' => 'wrong'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'wrong', 'field2' => 'value2'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'value1', 'field2' => 'value2'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'wrong', 'field2' => 'wrong'];
        $this->assertFalse($logic->evaluate($values));
    }

    public function testComplexAndOrConditions()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->and('field2')->equals('value2')
                   ->or('field3')->equals('value3');

        // With OR relation, any true condition makes the whole expression true
        $values = ['field1' => 'value1', 'field2' => 'value2', 'field3' => 'wrong'];
        $this->assertTrue($logic->evaluate($values)); // true OR true OR false = true

        // OR condition true
        $values = ['field1' => 'wrong', 'field2' => 'wrong', 'field3' => 'value3'];
        $this->assertTrue($logic->evaluate($values));
    }

    public function testMissingFieldValues()
    {
        $logic = ConditionalLogic::if('nonexistent_field')->equals('value');

        $this->assertFalse($logic->evaluate([]));
        $this->assertFalse($logic->evaluate(['other_field' => 'value']));
    }

    public function testNumericComparisons()
    {
        // Greater than or equal
        $logic = ConditionalLogic::if('number')->greaterThanOrEqual(10);
        $this->assertTrue($logic->evaluate(['number' => 10]));
        $this->assertTrue($logic->evaluate(['number' => 15]));
        $this->assertFalse($logic->evaluate(['number' => 5]));

        // Less than or equal
        $logic = ConditionalLogic::if('number')->lessThanOrEqual(10);
        $this->assertTrue($logic->evaluate(['number' => 10]));
        $this->assertTrue($logic->evaluate(['number' => 5]));
        $this->assertFalse($logic->evaluate(['number' => 15]));
    }

    public function testStringComparisons()
    {
        $logic = ConditionalLogic::if('text')->equals('Hello World');

        $this->assertTrue($logic->evaluate(['text' => 'Hello World']));
        $this->assertFalse($logic->evaluate(['text' => 'hello world'])); // Case sensitive
    }

    public function testArrayValues()
    {
        $logic = ConditionalLogic::if('array_field')->in(['a', 'b', 'c']);

        $this->assertTrue($logic->evaluate(['array_field' => 'a']));
        $this->assertTrue($logic->evaluate(['array_field' => 'b']));
        $this->assertFalse($logic->evaluate(['array_field' => 'd']));
    }

    public function testBooleanValues()
    {
        $logic = ConditionalLogic::if('bool_field')->equals(true);

        $this->assertTrue($logic->evaluate(['bool_field' => true]));
        $this->assertFalse($logic->evaluate(['bool_field' => false]));
        $this->assertFalse($logic->evaluate(['bool_field' => 1])); // Strict comparison
    }

    public function testToArrayConversion()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->and('field2')->equals('value2');

        $array = $logic->toArray();

        $this->assertEquals('AND', $array['relation']);
        $this->assertCount(2, $array['conditions']);
        $this->assertEquals([
            'field' => 'field1',
            'operator' => '=',
            'value' => 'value1'
        ], $array['conditions'][0]);
        $this->assertEquals([
            'field' => 'field2',
            'operator' => '=',
            'value' => 'value2'
        ], $array['conditions'][1]);
    }

    public function testToArrayWithOrRelation()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->or('field2')->equals('value2');

        $array = $logic->toArray();

        $this->assertEquals('OR', $array['relation']);
    }

    public function testToArrayWithSingleCondition()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1');

        $array = $logic->toArray();

        $this->assertEquals('AND', $array['relation']);
        $this->assertCount(1, $array['conditions']);
    }

    public function testFactoryMethod()
    {
        $conditions = [
            [
                'field' => 'field1',
                'operator' => '=',
                'value' => 'value1'
            ],
            [
                'field' => 'field2',
                'operator' => '!=',
                'value' => 'value2'
            ]
        ];

        $logic = ConditionalLogic::factory($conditions);

        $this->assertInstanceOf(ConditionalLogic::class, $logic);

        $array = $logic->toArray();
        $this->assertEquals($conditions, $array['conditions']);
    }

    public function testCustomFilter()
    {
        // Use when to stub the return value
        Functions\when('apply_filters')
            ->alias(function ($hook, $default, $value, $op, $compare) {
                if ($hook === 'hyperpress/fields/conditional_logic_evaluate' && $op === 'CUSTOM_OP') {
                    return true;
                }
                return $default;
            });

        $reflection = new \ReflectionClass(ConditionalLogic::class);
        $method = $reflection->getMethod('evaluateCondition');

        $result = $method->invoke(
            new \ReflectionClass(ConditionalLogic::class)->newInstanceWithoutConstructor(),
            'value',
            'CUSTOM_OP',
            'compare'
        );

        $this->assertTrue($result);
    }

    public function testEmptyEvaluationWithNoConditions()
    {
        $logic = ConditionalLogic::factory([]);

        $this->assertTrue($logic->evaluate([])); // Empty conditions should return true
        $this->assertTrue($logic->evaluate(['any_field' => 'any_value']));
    }

    public function testChainingMultipleAndConditions()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->and('field2')->equals('value2')
                   ->and('field3')->equals('value3');

        $values = ['field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'value1', 'field2' => 'wrong', 'field3' => 'value3'];
        $this->assertFalse($logic->evaluate($values));
    }

    public function testChainingMultipleOrConditions()
    {
        $logic = ConditionalLogic::if('field1')->equals('value1')
                   ->or('field2')->equals('value2')
                   ->or('field3')->equals('value3');

        $values = ['field1' => 'wrong', 'field2' => 'wrong', 'field3' => 'value3'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'value1', 'field2' => 'wrong', 'field3' => 'wrong'];
        $this->assertTrue($logic->evaluate($values));

        $values = ['field1' => 'wrong', 'field2' => 'wrong', 'field3' => 'wrong'];
        $this->assertFalse($logic->evaluate($values));
    }

    public function testNotContainsOperator()
    {
        $logic = ConditionalLogic::if('test_field')->notContains('search_term');

        $this->assertFalse($logic->evaluate(['test_field' => 'This contains search_term']));
        $this->assertTrue($logic->evaluate(['test_field' => 'No match here']));
        $this->assertTrue($logic->evaluate(['test_field' => '']));
    }

    public function testOperatorConstants()
    {
        $expectedOperators = [
            '=', '!=', '>', '<', '>=', '<=', 'IN', 'NOT IN',
            'CONTAINS', 'NOT CONTAINS', 'EMPTY', 'NOT EMPTY'
        ];

        $this->assertEquals($expectedOperators, ConditionalLogic::OPERATORS);
    }
}