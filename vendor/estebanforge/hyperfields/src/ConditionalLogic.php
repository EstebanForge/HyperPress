<?php

declare(strict_types=1);

namespace HyperFields;

class ConditionalLogic
{
    private string $field_name;
    private string $operator;
    private mixed $value;
    private string $relation = 'AND';
    private array $conditions = [];

    public const OPERATORS = [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'IN',
        'NOT IN',
        'CONTAINS',
        'NOT CONTAINS',
        'EMPTY',
        'NOT EMPTY',
    ];

    /**
     * If.
     *
     * @return self
     */
    public static function if(string $fieldName): self
    {
        return new self($fieldName);
    }

    /**
     * Where.
     *
     * @return self
     */
    public static function where(string $fieldName): self
    {
        return new self($fieldName);
    }

    /**
     *   construct.
     */
    private function __construct(string $fieldName)
    {
        $this->field_name = $fieldName;
    }

    /**
     * Equals.
     *
     * @return self
     */
    public function equals(mixed $value): self
    {
        $this->operator = '=';
        $this->value = $value;

        return $this;
    }

    /**
     * NotEquals.
     *
     * @return self
     */
    public function notEquals(mixed $value): self
    {
        $this->operator = '!=';
        $this->value = $value;

        return $this;
    }

    /**
     * GreaterThan.
     *
     * @return self
     */
    public function greaterThan(mixed $value): self
    {
        $this->operator = '>';
        $this->value = $value;

        return $this;
    }

    /**
     * LessThan.
     *
     * @return self
     */
    public function lessThan(mixed $value): self
    {
        $this->operator = '<';
        $this->value = $value;

        return $this;
    }

    /**
     * GreaterThanOrEqual.
     *
     * @return self
     */
    public function greaterThanOrEqual(mixed $value): self
    {
        $this->operator = '>=';
        $this->value = $value;

        return $this;
    }

    /**
     * LessThanOrEqual.
     *
     * @return self
     */
    public function lessThanOrEqual(mixed $value): self
    {
        $this->operator = '<=';
        $this->value = $value;

        return $this;
    }

    /**
     * In.
     *
     * @return self
     */
    public function in(array $values): self
    {
        $this->operator = 'IN';
        $this->value = $values;

        return $this;
    }

    /**
     * NotIn.
     *
     * @return self
     */
    public function notIn(array $values): self
    {
        $this->operator = 'NOT IN';
        $this->value = $values;

        return $this;
    }

    /**
     * Contains.
     *
     * @return self
     */
    public function contains(string $value): self
    {
        $this->operator = 'CONTAINS';
        $this->value = $value;

        return $this;
    }

    /**
     * NotContains.
     *
     * @return self
     */
    public function notContains(string $value): self
    {
        $this->operator = 'NOT CONTAINS';
        $this->value = $value;

        return $this;
    }

    /**
     * Empty.
     *
     * @return self
     */
    public function empty(): self
    {
        $this->operator = 'EMPTY';
        $this->value = null;

        return $this;
    }

    /**
     * NotEmpty.
     *
     * @return self
     */
    public function notEmpty(): self
    {
        $this->operator = 'NOT EMPTY';
        $this->value = null;

        return $this;
    }

    /**
     * And.
     *
     * @return self
     */
    public function and(string $fieldName): self
    {
        $this->conditions[] = [
            'field' => $this->field_name,
            'operator' => $this->operator,
            'value' => $this->value,
        ];

        $this->field_name = $fieldName;
        $this->operator = '';
        $this->value = null;

        return $this;
    }

    /**
     * Or.
     *
     * @return self
     */
    public function or(string $fieldName): self
    {
        $this->relation = 'OR';

        return $this->and($fieldName);
    }

    /**
     * Evaluate.
     *
     * @return bool
     */
    public function evaluate(array $values): bool
    {
        $conditions = $this->conditions;

        if (!empty($this->operator)) {
            $conditions[] = [
                'field' => $this->field_name,
                'operator' => $this->operator,
                'value' => $this->value,
            ];
        }

        $results = [];
        foreach ($conditions as $condition) {
            $field_value = $values[$condition['field']] ?? null;
            $results[] = $this->evaluateCondition($field_value, $condition['operator'], $condition['value']);
        }

        if ($this->relation === 'OR') {
            return in_array(true, $results, true);
        }

        return !in_array(false, $results, true);
    }

    /**
     * EvaluateCondition.
     *
     * @return bool
     */
    private function evaluateCondition(mixed $fieldValue, string $operator, mixed $compareValue): bool
    {
        switch ($operator) {
            case '=':
                return $fieldValue === $compareValue;
            case '!=':
                return $fieldValue !== $compareValue;
            case '>':
                return $fieldValue > $compareValue;
            case '<':
                return $fieldValue < $compareValue;
            case '>=':
                return $fieldValue >= $compareValue;
            case '<=':
                return $fieldValue <= $compareValue;
            case 'IN':
                return in_array($fieldValue, (array) $compareValue, true);
            case 'NOT IN':
                return !in_array($fieldValue, (array) $compareValue, true);
            case 'CONTAINS':
                return strpos((string) $fieldValue, (string) $compareValue) !== false;
            case 'NOT CONTAINS':
                return strpos((string) $fieldValue, (string) $compareValue) === false;
            case 'EMPTY':
                return empty($fieldValue);
            case 'NOT EMPTY':
                return !empty($fieldValue);
            default:
                return (bool) apply_filters('hyperfields/conditional_logic_evaluate', false, $fieldValue, $operator, $compareValue);
        }
    }

    /**
     * ToArray.
     *
     * @return array
     */
    public function toArray(): array
    {
        $conditions = $this->conditions;

        if (!empty($this->operator)) {
            $conditions[] = [
                'field' => $this->field_name,
                'operator' => $this->operator,
                'value' => $this->value,
            ];
        }

        return [
            'relation' => $this->relation,
            'conditions' => $conditions,
        ];
    }

    /**
     * Factory.
     *
     * @return self
     */
    public static function factory(array $conditions): self
    {
        $logic = new self('');
        $logic->conditions = $conditions;

        return $logic;
    }
}
