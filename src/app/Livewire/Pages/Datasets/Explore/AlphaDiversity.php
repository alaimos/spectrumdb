<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Actions\AlphaDiversityPlot;
use App\Actions\Batch;
use App\Actions\SubmitBatchAction;
use App\Enums\AlphaDiversityMetrics;
use App\Enums\BatchStatus;
use App\Exceptions\BatchNotFoundException;
use App\Models\Dataset;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

final class AlphaDiversity extends Component
{
    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public AlphaDiversityMetrics $metrics;

    #[Validate]
    public ?string $classVariable;

    #[Url('analysis_id')]
    public ?string $analysisId;

    /**
     * @var array<int, array{string, string}>
     */
    #[Validate]
    public array $comparisons = [];

    private Batch $batch;

    public function mount(): void
    {
        $this->updateBatch();
        if (isset($this->batch)) {
            $params = $this->batch->actionParams();
            $this->metrics = $params['metrics'];
            $this->classVariable = $params['classVariable'];
            $this->comparisons = $params['comparisons'] ?? [];
        }
    }

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
                'actionClass' => AlphaDiversityPlot::class,
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
        $this->redirectRoute(
            'datasets.show.alpha_diversity',
            [
                'dataset' => $this->dataset,
                'analysis_id' => $action->batchId,
            ],
            navigate: true
        );
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
    public function batchStatus(): ?BatchStatus
    {
        if (! isset($this->batch)) {
            return null;
        }

        return $this->batch->status();
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
                'assetName' => AlphaDiversityPlot::DEFAULT_OUTPUT_FILE,
            ]
        );
    }

    public function updatedClassVariable(): void
    {
        $this->comparisons = [];
    }

    public function updatedAnalysisId(): void
    {
        $this->batch = new Batch($this->analysisId);
    }

    public function getListeners(): array
    {
        if (! isset($this->analysisId)) {
            return [];
        }
        $userId = auth()->id();

        return [
            "echo-private:analysis.{$userId},.analysis.error" => 'analysisStatusUpdated',
            "echo-private:analysis.{$userId},.analysis.completed" => 'analysisStatusUpdated',
            "echo-private:analysis.{$userId},.analysis.processing" => 'analysisStatusUpdated',
        ];
    }

    public function analysisStatusUpdated(array $event): void
    {
        if (! isset($this->analysisId)) {
            return;
        }
        $analysisId = $event['batchId'] ?? null;
        if ($analysisId !== $this->analysisId) {
            return;
        }
        $this->redirectRoute(
            'datasets.show.alpha_diversity',
            [
                'dataset' => $this->dataset,
                'analysis_id' => $this->analysisId,
                'refresh' => now()->timestamp,
            ],
            navigate: true
        );
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
        if (isset($this->classVariable) && $this->classVariable) {
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

    protected function updateBatch(): void
    {
        if (isset($this->analysisId)) {
            try {
                $this->batch = new Batch($this->analysisId);
                if (! $this->batch->is(AlphaDiversityPlot::class)) {
                    throw new BatchNotFoundException();
                }
            } catch (BatchNotFoundException) {
                abort(404, 'Analysis not found');
            }
        }
    }
}
