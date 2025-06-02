<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\SearchOperator;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DatasetAdvancedSearchBuilder
{
    public const array SAMPLE_FIXED_FIELDS = [];

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

    public static function getStringOperators(): array
    {
        return SearchOperator::getStringOperatorsForSelect();
    }

    public static function getNumericOperators(): array
    {
        return SearchOperator::getNumericOperatorsForSelect();
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
        $query->whereHas(
            $condition['type'] === 'dataset' ? 'metadata' : 'samples.metadata',
            fn (Builder $query) => $this
                ->applyOperatorCondition($query, 'value', SearchOperator::from($condition['operator']), $condition['value'])
                ->where('key', $condition['key'])
        );
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
    private function applyOperatorCondition(Builder $query, string $field, SearchOperator $operator, $value): Builder
    {
        if ($operator->isNumericOperator()) {
            $preparedField = $this->prepareFieldForNumericComparison($field);
        } else {
            $preparedField = $field;
        }

        match ($operator) {
            SearchOperator::EQUALS, SearchOperator::EQUALS_STRING => $query->where($preparedField, $value),
            SearchOperator::NOT_EQUALS, SearchOperator::NOT_EQUALS_STRING => $query->where($preparedField, '!=', $value),
            SearchOperator::CONTAINS => $query->where($preparedField, 'like', "%{$value}%"),
            SearchOperator::NOT_CONTAINS => $query->where($preparedField, 'not like', "%{$value}%"),
            SearchOperator::STARTS_WITH => $query->where($preparedField, 'like', "{$value}%"),
            SearchOperator::ENDS_WITH => $query->where($preparedField, 'like', "%{$value}"),
            SearchOperator::LESS_THAN => $query->where($preparedField, '<', $value),
            SearchOperator::GREATER_THAN => $query->where($preparedField, '>', $value),
            SearchOperator::LESS_THAN_EQUAL => $query->where($preparedField, '<=', $value),
            SearchOperator::GREATER_THAN_EQUAL => $query->where($preparedField, '>=', $value),
        };

        return $query;
    }
}
