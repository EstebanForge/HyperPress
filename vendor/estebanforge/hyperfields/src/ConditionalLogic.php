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

    public static function if(string $fieldName): self
    {
        return new self($fieldName);
    }

    public static function where(string $fieldName): self
    {
        return new self($fieldName);
    }

    private function __construct(string $fieldName)
    {
        $this->field_name = $fieldName;
    }

    public function equals(mixed $value): self
    {
        $this->operator = '=';
        $this->value = $value;

        return $this;
    }

    public function notEquals(mixed $value): self
    {
        $this->operator = '!=';
        $this->value = $value;

        return $this;
    }

    public function greaterThan(mixed $value): self
    {
        $this->operator = '>';
        $this->value = $value;

        return $this;
    }

    public function lessThan(mixed $value): self
    {
        $this->operator = '<';
        $this->value = $value;

        return $this;
    }

    public function in(array $values): self
    {
        $this->operator = 'IN';
        $this->value = $values;

        return $this;
    }

    public function notIn(array $values): self
    {
        $this->operator = 'NOT IN';
        $this->value = $values;

        return $this;
    }

    public function contains(string $value): self
    {
        $this->operator = 'CONTAINS';
        $this->value = $value;

        return $this;
    }

    public function empty(): self
    {
        $this->operator = 'EMPTY';
        $this->value = null;

        return $this;
    }

    public function notEmpty(): self
    {
        $this->operator = 'NOT EMPTY';
        $this->value = null;

        return $this;
    }

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

    public function or(string $fieldName): self
    {
        $this->relation = 'OR';

        return $this->and($fieldName);
    }

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
                return apply_filters('hyperpress/fields/conditional_logic_evaluate', false, $fieldValue, $operator, $compareValue);
        }
    }

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

    public static function factory(array $conditions): self
    {
        $logic = new self('');
        $logic->conditions = $conditions;

        return $logic;
    }
}
