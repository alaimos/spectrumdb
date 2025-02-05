<?php

namespace App\Builders;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DatasetAdvancedSearchBuilder
{
    const array STRING_OPERATORS = [
        'equals_string' => 'Equals',
        'not_equals_string' => 'Not Equals',
        'contains' => 'Contains',
        'not_contains' => 'Not Contains',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
    ];

    const array NUMERIC_OPERATORS = [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'less_than' => 'Less Than',
        'greater_than' => 'Greater Than',
        'less_than_equal' => 'Less Than or Equal',
        'greater_than_equal' => 'Greater Than or Equal',
    ];

    const array SAMPLE_FIXED_FIELDS = [
        'variety' => 'Variety',
        'plant_stage' => 'Plant Stage',
        'biological_replica' => 'Biological Replica',
        'sample_conditions' => 'Sample Conditions',
        'plant_section' => 'Plant Section',
        'sampling_date' => 'Sampling Date',
        'location' => 'Location',
    ];

    /**
     * @var \Illuminate\Database\Eloquent\Builder<\App\Models\Dataset>
     */
    protected Builder $query;

    /**
     * @param  (array{type: "dataset"|"sample", key: string, operator: string, value: string})[]  $advancedSearchConditions
     * @param  ("AND"|"OR"|"NOT")[]  $advancedSearchConnectors
     */
    public function __construct(
        protected array $advancedSearchConditions = [],
        protected array $advancedSearchConnectors = []
    ) {}

    protected function applyAdvancedSearchConditions(): void
    {
        $this->query->where(fn () => $this->applyAllConditionsToBuilder($this->query));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Dataset>  $query
     */
    protected function applyAllConditionsToBuilder(Builder $query): void
    {
        foreach ($this->advancedSearchConditions as $index => $condition) {
            $method = $index === 0 ? 'where' : strtolower($this->advancedSearchConnectors[$index - 1]);
            if ($method === 'not') {
                $method = 'whereNot';
            }
            $query->$method(fn (Builder $query) => $this->applyMetadataCondition($query, $condition));
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Dataset>  $query
     */
    protected function applyMetadataCondition(Builder $query, $condition): void
    {
        if ($condition['type'] === 'sample' && array_key_exists($condition['key'], self::SAMPLE_FIXED_FIELDS)) {
            $query->whereHas(
                'samples',
                fn (Builder $query) => $this->applyOperatorCondition(
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

    protected function prepareFieldForNumericComparison(string $field): Expression
    {
        $secureField = DB::getQueryGrammar()->wrap($field);

        return DB::raw("CAST({$secureField} AS DECIMAL)");
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\DatasetMetadata|\App\Models\SampleMetadata>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\DatasetMetadata|\App\Models\SampleMetadata>
     */
    protected function applyOperatorCondition(Builder $query, string $field, string $operator, $value): Builder
    {
        if (array_key_exists($operator, self::NUMERIC_OPERATORS)) {
            $preparedField = $this->prepareFieldForNumericComparison($field);
        } else {
            $preparedField = $field;
        }
        switch ($operator) {
            case 'equals':
            case 'equals_string':
                $query->where($preparedField, $value);
                break;
            case 'not_equals':
            case 'not_equals_string':
                $query->where($preparedField, '!=', $value);
                break;
            case 'contains':
                $query->where($preparedField, 'like', "%{$value}%");
                break;
            case 'not_contains':
                $query->where($preparedField, 'not like', "%{$value}%");
                break;
            case 'starts_with':
                $query->where($preparedField, 'like', "{$value}%");
                break;
            case 'ends_with':
                $query->where($preparedField, 'like', "%{$value}");
                break;
            case 'less_than':
                $query->where($preparedField, '<', $value);
                break;
            case 'greater_than':
                $query->where($preparedField, '>', $value);
                break;
            case 'less_than_equal':
                $query->where($preparedField, '<=', $value);
                break;
            case 'greater_than_equal':
                $query->where($preparedField, '>=', $value);
                break;
        }

        return $query;
    }

    public function __invoke(Builder $query): void
    {
        $this->query = $query;
        $this->applyAdvancedSearchConditions();
    }
}
