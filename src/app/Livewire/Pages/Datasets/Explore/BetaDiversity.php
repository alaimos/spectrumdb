<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Actions\Batch;
use App\Actions\BetaDiversityPlot;
use App\Actions\SubmitBatchAction;
use App\Enums\BetaDiversityMetrics;
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

final class BetaDiversity extends Component
{
    use RunsBatchableJobs;

    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public BetaDiversityMetrics $metrics;

    #[Validate]
    public ?string $colorVariable;

    private $batchActionType = BetaDiversityPlot::class;

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|Throwable
     */
    public function runAnalysis(): void
    {
        $this->validate();

        $action = app()->make(
            SubmitBatchAction::class,
            [
                'actionClass' => BetaDiversityPlot::class,
                'actionParams' => [
                    'dataset' => $this->dataset,
                    'metrics' => $this->metrics,
                    'colorVariable' => $this->colorVariable,
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
        if (! isset($this->colorVariable)) {
            return collect();
        }

        return $this->dataset
            ->through('samples')
            ->has('metadata')
            ->where('key', $this->colorVariable)
            ->distinct()
            ->pluck('value')
            ->sort();
    }

    #[Computed]
    public function betaDiversityPlotUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => BetaDiversityPlot::DEFAULT_OUTPUT_FILE,
            ]
        );
    }

    public function getListeners(): array
    {
        return $this->getBatchListeners();
    }

    protected function rules(): array
    {
        return [
            'metrics' => ['required', Rule::enum(BetaDiversityMetrics::class)],
            'colorVariable' => [
                'required',
                'string',
                Rule::in($this->availableMetadata->toArray()),
            ],
        ];
    }

    protected function updateParametersFromBatch(): void
    {
        $params = $this->batch->actionParams();
        $this->metrics = $params['metrics'];
        $this->colorVariable = $params['colorVariable'];
    }

    protected function refreshRoute(): array
    {
        return [
            'route' => 'datasets.show.beta_diversity',
            'params' => [
                'dataset' => $this->dataset,
            ],
        ];
    }
}
