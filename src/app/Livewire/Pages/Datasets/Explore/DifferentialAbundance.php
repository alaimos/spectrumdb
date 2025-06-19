<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Actions\DifferentialAbundanceAction;
use App\Actions\SubmitBatchAction;
use App\Enums\TaxonomicLevels;
use App\Models\Dataset;
use App\Traits\Livewire\RunsBatchableJobs;
use App\Utils;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

final class DifferentialAbundance extends Component
{
    use RunsBatchableJobs;
    use WithPagination;

    public string $sortBy = 'padj';

    /** @var 'asc'|'desc' */
    public string $sortDirection = 'asc';

    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public TaxonomicLevels $taxonomicLevel;

    #[Validate]
    public ?string $classVariable;

    #[Validate]
    public ?string $group1;

    #[Validate]
    public ?string $group2;

    #[Validate]
    public float $pvThreshold = 0.05;

    #[Validate]
    public float $fdrThreshold = 0.05;

    #[Validate]
    public int $topN = 20;

    public int $graph = 0;

    private $batchActionType = DifferentialAbundanceAction::class;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * @throws Throwable
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function runAnalysis(): void
    {
        $this->validate();

        $action = app()->make(
            SubmitBatchAction::class,
            [
                'actionClass' => DifferentialAbundanceAction::class,
                'actionParams' => [
                    'dataset' => $this->dataset,
                    'taxonomicLevel' => $this->taxonomicLevel,
                    'classVariable' => $this->classVariable,
                    'group1' => $this->group1,
                    'group2' => $this->group2,
                    'pvThreshold' => $this->pvThreshold,
                    'fdrThreshold' => $this->fdrThreshold,
                    'topN' => $this->topN,
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
    public function differentialAbundancePlotTitle(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        if ($this->graph < 0 || $this->graph > 2) {
            return null;
        }

        return match ($this->graph) {
            0 => 'Top Significant Features',
            1 => 'Top Fold Change Features',
            2 => 'Top Frequency Features', // @phpstan-ignore-line
            default => null,
        };
    }

    #[Computed]
    public function differentialAbundancePlotUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        if ($this->graph < 0 || $this->graph > 2) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => match ($this->graph) {
                    0 => DifferentialAbundanceAction::DEFAULT_TOP_SIGNIFICANT_PLOT_OUTPUT_FILE,
                    1 => DifferentialAbundanceAction::DEFAULT_TOP_FOLD_CHANGE_PLOT_OUTPUT_FILE,
                    2 => DifferentialAbundanceAction::DEFAULT_TOP_FREQ_PLOT_FILE,
                },
            ]
        );
    }

    #[Computed]
    public function differentialAbundanceTable(): LengthAwarePaginator|string|null
    {
        if (! isset($this->batch)) {
            return null;
        }

        $tableFile = Utils::analysisPath(
            auth()->id(),
            $this->analysisId
        ).'/'.DifferentialAbundanceAction::DEFAULT_TABLE_OUTPUT_FILE;
        if (Storage::missing($tableFile)) {
            return 'No differential abundance table available.';
        }
        $stream = Storage::readStream($tableFile);
        if (! is_resource($stream)) {
            return 'Error reading differential abundance table.';
        }
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
            if (count($fields) < 7) {
                continue; // Skip lines that do not have enough fields
            }
            [, $logFoldChange, $lfcSE, , $pValue, $padj, $taxa] = $fields;
            $data[] = [
                'taxa' => $taxa,
                'logFoldChange' => (float) $logFoldChange,
                'lfcSE' => (float) $lfcSE,
                'pValue' => (float) $pValue,
                'padj' => (float) $padj,
            ];
        }
        fclose($stream);
        $data = collect($data)->sortBy($this->sortBy, descending: $this->sortDirection === 'desc');

        return $data->paginate();
    }

    #[Computed]
    public function differentialAbundanceTableUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => DifferentialAbundanceAction::DEFAULT_TABLE_OUTPUT_FILE,
            ]
        );
    }

    #[Computed]
    public function differentialAbundanceTablePVUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => DifferentialAbundanceAction::DEFAULT_TABLE_OUTPUT_FILE_PV_FILTERED,
            ]
        );
    }

    #[Computed]
    public function differentialAbundanceTableFDRUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => DifferentialAbundanceAction::DEFAULT_TABLE_OUTPUT_FILE_FDR_FILTERED,
            ]
        );
    }

    public function updatedClassVariable(): void
    {
        $this->group1 = null;
        $this->group2 = null;
    }

    public function getListeners(): array
    {
        return $this->getBatchListeners();
    }

    protected function rules(): array
    {
        return [
            'taxonomicLevel' => ['required', Rule::enum(TaxonomicLevels::class)],
            'classVariable' => ['required', Rule::in($this->availableMetadata->toArray())], // @phpstan-ignore-line
            'group1' => ['required', 'string', Rule::in($this->availableClasses->toArray())], // @phpstan-ignore-line
            'group2' => ['required', 'string', Rule::in($this->availableClasses->toArray())], // @phpstan-ignore-line
            'pvThreshold' => ['required', 'numeric', 'min:0', 'max:1'],
            'fdrThreshold' => ['required', 'numeric', 'min:0', 'max:1'],
            'topN' => ['required', 'integer', 'min:2', 'max:100'],
        ];
    }

    protected function updateParametersFromBatch(): void
    {
        $params = $this->batch->actionParams();
        $this->taxonomicLevel = $params['taxonomicLevel'];
        $this->classVariable = $params['classVariable'];
        $this->group1 = $params['group1'] ?? null;
        $this->group2 = $params['group2'] ?? null;
        $this->pvThreshold = $params['pvThreshold'] ?? 0.05;
        $this->fdrThreshold = $params['fdrThreshold'] ?? 0.05;
        $this->topN = $params['topN'] ?? 20;
    }

    protected function refreshRoute(): array
    {
        return [
            'route' => 'datasets.show.differential_abundance',
            'params' => [
                'dataset' => $this->dataset,
            ],
        ];
    }
}
