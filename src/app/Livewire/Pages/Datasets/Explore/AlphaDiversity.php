<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Enums\AlphaDiversityMetrics;
use App\Models\Dataset;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class AlphaDiversity extends Component
{
    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public AlphaDiversityMetrics $metrics;

    #[Validate]
    public ?string $classVariable;

    #[Url]
    public ?string $batchId;

    /**
     * @var array<int, array{string, string}>
     */
    #[Validate]
    public array $comparisons = [];

    /**
     * @return Collection<int, string>
     */
    #[Computed(persist: true)]
    public function availableMetadata(): Collection
    {
        return $this->dataset
            ->through('samples')
            ->has('metadata')
            ->select('key')
            ->distinct()
            ->pluck('key');
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function availableClasses(): Collection
    {
        if (is_null($this->classVariable)) {
            return collect();
        }

        return $this->dataset
            ->through('samples')
            ->has('metadata')
            ->where('key', $this->classVariable)
            ->distinct()
            ->pluck('value')
            ->sort();
    }

    public function updatedClassVariable(): void
    {
        $this->comparisons = [];
    }

    protected function rules(): array
    {
        $rules = [
            'metrics' => ['required', Rule::enum(AlphaDiversityMetrics::class)],
            'classVariable' => [
                'required',
                'string',
                Rule::in($this->availableMetadata->toArray()),
            ],
        ];
        if ($this->classVariable) {
            $rules['comparisons'] = ['array'];
            $rules['comparisons.*.0'] = [
                'required',
                'string',
                Rule::in($this->availableClasses->toArray()),
            ];
            $rules['comparisons.*.1'] = [
                'required',
                'string',
                Rule::in($this->availableClasses->toArray()),
            ];
        } else {
            $rules['comparisons'] = ['array', 'max:0'];
        }

        return $rules;
    }
}
