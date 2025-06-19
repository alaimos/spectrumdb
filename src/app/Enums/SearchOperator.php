<?php

declare(strict_types=1);

namespace App\Enums;

enum SearchOperator: string
{
    // String operators
    case EQUALS_STRING = 'equals_string';
    case NOT_EQUALS_STRING = 'not_equals_string';
    case CONTAINS = 'contains';
    case NOT_CONTAINS = 'not_contains';
    case STARTS_WITH = 'starts_with';
    case ENDS_WITH = 'ends_with';

    // Numeric operators
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case LESS_THAN = 'less_than';
    case GREATER_THAN = 'greater_than';
    case LESS_THAN_EQUAL = 'less_than_equal';
    case GREATER_THAN_EQUAL = 'greater_than_equal';

    public static function stringOperators(): array
    {
        return array_filter(self::cases(), fn ($case) => $case->isStringOperator());
    }

    public static function numericOperators(): array
    {
        return array_filter(self::cases(), fn ($case) => $case->isNumericOperator());
    }

    public static function getStringOperatorsForSelect(): array
    {
        return collect(self::stringOperators())
            ->mapWithKeys(fn ($operator) => [$operator->value => $operator->label()])
            ->toArray();
    }

    public static function getNumericOperatorsForSelect(): array
    {
        return collect(self::numericOperators())
            ->mapWithKeys(fn ($operator) => [$operator->value => $operator->label()])
            ->toArray();
    }

    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::EQUALS_STRING, self::EQUALS => __('Equals'),
            self::NOT_EQUALS_STRING, self::NOT_EQUALS => __('Not Equals'),
            self::CONTAINS => __('Contains'),
            self::NOT_CONTAINS => __('Not Contains'),
            self::STARTS_WITH => __('Starts With'),
            self::ENDS_WITH => __('Ends With'),
            self::LESS_THAN => __('Less Than'),
            self::GREATER_THAN => __('Greater Than'),
            self::LESS_THAN_EQUAL => __('Less Than or Equal'),
            self::GREATER_THAN_EQUAL => __('Greater Than or Equal'),
        };
    }

    public function isStringOperator(): bool
    {
        return in_array($this, [
            self::EQUALS_STRING,
            self::NOT_EQUALS_STRING,
            self::CONTAINS,
            self::NOT_CONTAINS,
            self::STARTS_WITH,
            self::ENDS_WITH,
        ]);
    }

    public function isNumericOperator(): bool
    {
        return in_array($this, [
            self::EQUALS,
            self::NOT_EQUALS,
            self::LESS_THAN,
            self::GREATER_THAN,
            self::LESS_THAN_EQUAL,
            self::GREATER_THAN_EQUAL,
        ]);
    }
}
