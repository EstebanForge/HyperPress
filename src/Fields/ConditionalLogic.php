<?php

declare(strict_types=1);

namespace HyperPress\Fields;

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

    public static function if(string $field_name): self
    {
        return new self($field_name);
    }

    public static function where(string $field_name): self
    {
        return new self($field_name);
    }

    private function __construct(string $field_name)
    {
        $this->field_name = $field_name;
    }

    public function equals(mixed $value): self
    {
        $this->operator = '=';
        $this->value = $value;

        return $this;
    }

    public function not_equals(mixed $value): self
    {
        $this->operator = '!=';
        $this->value = $value;

        return $this;
    }

    public function greater_than(mixed $value): self
    {
        $this->operator = '>';
        $this->value = $value;

        return $this;
    }

    public function less_than(mixed $value): self
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

    public function not_in(array $values): self
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

    public function not_empty(): self
    {
        $this->operator = 'NOT EMPTY';
        $this->value = null;

        return $this;
    }

    public function and(string $field_name): self
    {
        $this->conditions[] = [
            'field' => $this->field_name,
            'operator' => $this->operator,
            'value' => $this->value,
        ];

        $this->field_name = $field_name;
        $this->operator = '';
        $this->value = null;

        return $this;
    }

    public function or(string $field_name): self
    {
        $this->relation = 'OR';

        return $this->and($field_name);
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
            $results[] = $this->evaluate_condition($field_value, $condition['operator'], $condition['value']);
        }

        if ($this->relation === 'OR') {
            return in_array(true, $results, true);
        }

        return !in_array(false, $results, true);
    }

    private function evaluate_condition(mixed $field_value, string $operator, mixed $compare_value): bool
    {
        switch ($operator) {
            case '=':
                return $field_value === $compare_value;
            case '!=':
                return $field_value !== $compare_value;
            case '>':
                return $field_value > $compare_value;
            case '<':
                return $field_value < $compare_value;
            case '>=':
                return $field_value >= $compare_value;
            case '<=':
                return $field_value <= $compare_value;
            case 'IN':
                return in_array($field_value, (array) $compare_value, true);
            case 'NOT IN':
                return !in_array($field_value, (array) $compare_value, true);
            case 'CONTAINS':
                return strpos((string) $field_value, (string) $compare_value) !== false;
            case 'NOT CONTAINS':
                return strpos((string) $field_value, (string) $compare_value) === false;
            case 'EMPTY':
                return empty($field_value);
            case 'NOT EMPTY':
                return !empty($field_value);
            default:
                return apply_filters('hyperpress/fields/conditional_logic_evaluate', false, $field_value, $operator, $compare_value);
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
