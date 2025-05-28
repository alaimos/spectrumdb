<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Actions\AlphaDiversityPlotAction;
use App\Actions\SubmitBatchAction;
use App\Enums\AlphaDiversityMetrics;
use App\Models\Dataset;
use App\Traits\Livewire\RunsBatchableJobs;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

final class AlphaDiversity extends Component
{
    use RunsBatchableJobs;

    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public AlphaDiversityMetrics $metrics;

    #[Validate]
    public ?string $classVariable;

    /**
     * @var array<int, array{string, string}>
     */
    #[Validate]
    public array $comparisons = [];

    protected $validationAttributes = [
        'comparisons.*.0' => 'case',
        'comparisons.*.1' => 'control',
    ];

    private string $batchActionType = AlphaDiversityPlotAction::class; // @phpstan-ignore-line

    public function addComparison(): void
    {
        $this->comparisons[] = ['', ''];
    }

    public function removeComparison(int $index): void
    {
        unset($this->comparisons[$index]);
        $this->comparisons = array_values($this->comparisons);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|Throwable
     */
    public function runAnalysis(): void
    {
        $this->validate();

        $action = app()->make(
            SubmitBatchAction::class,
            [
                'actionClass' => AlphaDiversityPlotAction::class,
                'actionParams' => [
                    'dataset' => $this->dataset,
                    'metrics' => $this->metrics,
                    'classVariable' => $this->classVariable,
                    'comparisons' => $this->comparisons,
                ],
            ]
        );
        $action->handle();

        Flux::toast(
            text: 'Analysis submitted successfully. It will start processing as soon as resources are available.',
            heading: 'Analysis Submitted',
            variant: 'success',
        );
        $this->refreshWithAnalysisId($action->batchId);
    }

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
        if (! isset($this->classVariable)) {
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

    #[Computed]
    public function alphaDiversityPlotUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => AlphaDiversityPlotAction::DEFAULT_OUTPUT_FILE,
            ]
        );
    }

    public function updatedClassVariable(): void
    {
        $this->comparisons = [];
    }

    public function getListeners(): array
    {
        return $this->getBatchListeners();
    }

    protected function rules(): array
    {
        $rules = [
            'metrics' => ['required', Rule::enum(AlphaDiversityMetrics::class)],
            'classVariable' => [
                'required',
                'string',
                Rule::in($this->availableMetadata->toArray()), // @phpstan-ignore-line
            ],
        ];
        if (isset($this->classVariable) && $this->classVariable) {
            $rules['comparisons'] = ['array'];
            $rules['comparisons.*.0'] = [
                'required',
                'string',
                Rule::in($this->availableClasses->toArray()), // @phpstan-ignore-line
            ];
            $rules['comparisons.*.1'] = [
                'required',
                'string',
                Rule::in($this->availableClasses->toArray()), // @phpstan-ignore-line
            ];
        } else {
            $rules['comparisons'] = ['array', 'max:0'];
        }

        return $rules;
    }

    protected function updateParametersFromBatch(): void
    {
        $params = $this->batch->actionParams();
        $this->metrics = $params['metrics'];
        $this->classVariable = $params['classVariable'];
        $this->comparisons = $params['comparisons'] ?? [];
    }

    protected function refreshRoute(): array
    {
        return [
            'route' => 'datasets.show.alpha_diversity',
            'params' => [
                'dataset' => $this->dataset,
            ],
        ];
    }
}
