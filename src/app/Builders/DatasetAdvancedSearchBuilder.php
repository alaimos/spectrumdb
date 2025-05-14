<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DatasetAdvancedSearchBuilder
{
    public const array STRING_OPERATORS = [
        'equals_string' => 'Equals',
        'not_equals_string' => 'Not Equals',
        'contains' => 'Contains',
        'not_contains' => 'Not Contains',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
    ];

    public const array NUMERIC_OPERATORS = [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'less_than' => 'Less Than',
        'greater_than' => 'Greater Than',
        'less_than_equal' => 'Less Than or Equal',
        'greater_than_equal' => 'Greater Than or Equal',
    ];

    public const array SAMPLE_FIXED_FIELDS = [
        'variety' => 'Variety',
        'plant_stage' => 'Plant Stage',
        'biological_replica' => 'Biological Replica',
        'sample_conditions' => 'Sample Conditions',
        'plant_section' => 'Plant Section',
        'sampling_date' => 'Sampling Date',
        'location' => 'Location',
    ];

    /**
     * @var Builder<\App\Models\Dataset>
     */
    private Builder $query;

    /**
     * @param  (array{type: "dataset"|"sample", key: string, operator: string, value: string})[]  $advancedSearchConditions
     * @param  ("AND"|"OR"|"NOT")[]  $advancedSearchConnectors
     */
    public function __construct(
        private readonly array $advancedSearchConditions = [],
        private array $advancedSearchConnectors = []
    ) {}

    public function __invoke(Builder $query): void
    {
        $this->query = $query;
        $this->applyAdvancedSearchConditions();
    }

    private function applyAdvancedSearchConditions(): void
    {
        $this->query->where(fn () => $this->applyAllConditionsToBuilder($this->query));
    }

    /**
     * @param  Builder<\App\Models\Dataset>  $query
     */
    private function applyAllConditionsToBuilder(Builder $query): void
    {
        foreach ($this->advancedSearchConditions as $index => $condition) {
            $method = $index === 0 ? 'where' : mb_strtolower($this->advancedSearchConnectors[$index - 1]);
            if ($method === 'not') {
                $method = 'whereNot';
            }
            $query->$method(fn (Builder $query) => $this->applyMetadataCondition($query, $condition));
        }
    }

    /**
     * @param  Builder<\App\Models\Dataset>  $query
     */
    private function applyMetadataCondition(Builder $query, array $condition): void
    {
        if ($condition['type'] === 'sample' && array_key_exists($condition['key'], self::SAMPLE_FIXED_FIELDS)) {
            $query->whereHas(
                'samples',
                fn (Builder $query): \Illuminate\Database\Eloquent\Builder => $this->applyOperatorCondition(
                    query: $query,
                    field: $condition['key'],
                    operator: $condition['operator'],
                    value: $condition['value']
                )
            );
        } else {
            $query->whereHas(
                $condition['type'] === 'dataset' ? 'metadata' : 'samples.metadata',
                fn (Builder $query) => $this
                    ->applyOperatorCondition($query, 'value', $condition['operator'], $condition['value'])
                    ->where('key', $condition['key'])
            );
        }
    }

    private function prepareFieldForNumericComparison(string $field): Expression
    {
        $secureField = DB::getQueryGrammar()->wrap($field);

        return DB::raw("CAST({$secureField} AS DECIMAL)");
    }

    /**
     * @param  Builder<\App\Models\DatasetMetadata|\App\Models\SampleMetadata>  $query
     * @return Builder<\App\Models\DatasetMetadata|\App\Models\SampleMetadata>
     */
    private function applyOperatorCondition(Builder $query, string $field, string $operator, $value): Builder
    {
        if (array_key_exists($operator, self::NUMERIC_OPERATORS)) {
            $preparedField = $this->prepareFieldForNumericComparison($field);
        } else {
            $preparedField = $field;
        }
        match ($operator) {
            'equals', 'equals_string' => $query->where($preparedField, $value),
            'not_equals', 'not_equals_string' => $query->where($preparedField, '!=', $value),
            'contains' => $query->where($preparedField, 'like', "%{$value}%"),
            'not_contains' => $query->where($preparedField, 'not like', "%{$value}%"),
            'starts_with' => $query->where($preparedField, 'like', "{$value}%"),
            'ends_with' => $query->where($preparedField, 'like', "%{$value}"),
            'less_than' => $query->where($preparedField, '<', $value),
            'greater_than' => $query->where($preparedField, '>', $value),
            'less_than_equal' => $query->where($preparedField, '<=', $value),
            'greater_than_equal' => $query->where($preparedField, '>=', $value),
            default => $query,
        };

        return $query;
    }
}
