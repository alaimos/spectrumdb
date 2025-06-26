<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Actions\AbundancePlotAction;
use App\Actions\SubmitBatchAction;
use App\Enums\TaxaAbundanceCharts;
use App\Enums\TaxonomicLevels;
use App\Models\Dataset;
use App\Traits\Livewire\RunsBatchableJobs;
use App\Utils;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * @property-read Collection<int, string> $availableMetadata
 */
final class TaxaComposition extends Component
{
    use RunsBatchableJobs;
    use WithPagination;

    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public TaxaAbundanceCharts $chartType;

    #[Validate]
    public TaxonomicLevels $taxonomicLevel;

    #[Validate]
    public ?string $classVariable;

    public string $sortBy = 'taxa';

    /** @var 'asc'|'desc' */
    public string $sortDirection = 'asc';

    private $batchActionType = AbundancePlotAction::class; // @phpstan-ignore-line

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws Throwable
     */
    public function runAnalysis(): void
    {
        $this->validate();

        $action = app()->make(
            SubmitBatchAction::class,
            [
                'actionClass' => AbundancePlotAction::class,
                'actionParams' => [
                    'dataset' => $this->dataset,
                    'chartType' => $this->chartType,
                    'taxonomicLevel' => $this->taxonomicLevel,
                    'classVariable' => $this->classVariable,
                ],
            ]
        );
        $action->handle();

        Flux::toast(
            text: __('Analysis submitted successfully. It will start processing as soon as resources are available.'),
            heading: __('Analysis Submitted'),
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

    #[Computed]
    public function abundancePlotUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => AbundancePlotAction::DEFAULT_PLOT_OUTPUT_FILE,
            ]
        );
    }

    #[Computed]
    public function abundanceTableUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => AbundancePlotAction::DEFAULT_TABLE_OUTPUT_FILE,
            ]
        );
    }

    #[Computed]
    public function abundanceTable(): array|string|null
    {
        if (! isset($this->batch)) {
            return null;
        }

        $abundanceTableFile = Utils::analysisPath(
            auth()->id(),
            $this->analysisId
        ).'/'.AbundancePlotAction::DEFAULT_TABLE_OUTPUT_FILE;
        if (Storage::missing($abundanceTableFile)) {
            return 'No abundance table available.';
        }
        $stream = Storage::readStream($abundanceTableFile);
        if (! is_resource($stream)) {
            return 'Error reading abundance table.';
        }
        $groups = [];
        $data = [];
        $header = true;
        while (($line = fgets($stream)) !== false) {
            if ($header) {
                $header = false; // Skip the first line (header)

                continue;
            }
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            /** @noinspection PhpDeprecatedPassingNonEmptyEscapeToCsvFunctionInspection */
            $fields = str_getcsv($line, "\t", escape: '\\');
            if (count($fields) < 4) {
                continue; // Skip lines that do not have enough fields
            }
            [$group, $taxa, $value, $color] = $fields;
            if (! isset($data[$taxa])) {
                $data[$taxa] = [
                    'taxa' => $taxa,
                    'color' => $color,
                ];
            }
            $data[$taxa][$group] = (float) $value;
            $groups[$group] = true;
        }
        fclose($stream);
        $data = collect(array_values($data))
            ->sortBy($this->sortBy, descending: $this->sortDirection === 'desc');
        $groups = array_keys($groups);

        return [$groups, $data->paginate()];
    }

    public function getListeners(): array
    {
        return $this->getBatchListeners();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    protected function rules(): array
    {
        return [
            'chartType' => ['required', Rule::enum(TaxaAbundanceCharts::class)],
            'taxonomicLevel' => ['required', Rule::enum(TaxonomicLevels::class)],
            'classVariable' => ['required', Rule::in($this->availableMetadata->toArray())],
        ];
    }

    protected function updateParametersFromBatch(): void
    {
        $params = $this->batch->actionParams();
        $this->chartType = $params['chartType'];
        $this->taxonomicLevel = $params['taxonomicLevel'];
        $this->classVariable = $params['classVariable'];
    }

    protected function refreshRoute(): array
    {
        return [
            'route' => 'datasets.show.taxa_composition',
            'params' => [
                'dataset' => $this->dataset,
            ],
        ];
    }
}
