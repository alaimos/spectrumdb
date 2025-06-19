<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Actions\CorrelationNetworkAction;
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

final class CorrelationNetwork extends Component
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
    public float $correlationThreshold = 0.6;

    public int $graph = 0;

    private $batchActionType = CorrelationNetworkAction::class;

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
                'actionClass' => CorrelationNetworkAction::class,
                'actionParams' => [
                    'dataset' => $this->dataset,
                    'taxonomicLevel' => $this->taxonomicLevel,
                    'classVariable' => $this->classVariable,
                    'group1' => $this->group1,
                    'group2' => $this->group2,
                    'correlationThreshold' => $this->correlationThreshold,
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
    public function networkTable(): LengthAwarePaginator|string|null
    {
        if (! isset($this->batch)) {
            return null;
        }

        $tableFile = Utils::analysisPath(
            auth()->id(),
            $this->analysisId
        ).'/'.CorrelationNetworkAction::DEFAULT_OUTPUT_FILE;
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
            if (count($fields) < 6) {
                continue; // Skip lines that do not have enough fields
            }
            [$sourceTaxa, $targetTaxa, $correlationGroup1, $correlationGroup2, $pv, $fdr] = $fields;
            $data[] = [
                'source_taxa' => $sourceTaxa,
                'target_taxa' => $targetTaxa,
                'correlation_group_1' => (float) $correlationGroup1,
                'correlation_group_2' => (float) $correlationGroup2,
                'pv' => (float) $pv,
                'fdr' => (float) $fdr,
            ];
        }
        fclose($stream);
        $data = collect($data)->sortBy($this->sortBy, descending: $this->sortDirection === 'desc');

        return $data->paginate();
    }

    #[Computed]
    public function networkGraph(): array|string|null
    {
        if (! isset($this->batch)) {
            return null;
        }

        $tableFile = Utils::analysisPath(
            auth()->id(),
            $this->analysisId
        ).'/'.CorrelationNetworkAction::DEFAULT_OUTPUT_FILE;
        if (Storage::missing($tableFile)) {
            return 'No differential abundance table available.';
        }
        $stream = Storage::readStream($tableFile);
        if (! is_resource($stream)) {
            return 'Error reading differential abundance table.';
        }
        $nodes = [];
        $addNode = static function ($taxa, $group) use (&$nodes) {
            if (! isset($nodes[$taxa])) {
                $nodes[$taxa] = [
                    'id' => $taxa,
                    'group' => $group,
                ];
            } else {
                $previousGroup = $nodes[$taxa]['group'];
                if ($previousGroup !== $group) {
                    $nodes[$taxa]['group'] = 3;
                }
            }
        };
        $links = [];
        $header = true;
        while (($line = fgets($stream)) !== false) {
            if ($header) {
                $header = false; // Skip the first line (header)

                continue;
            }
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            /** @noinspection PhpDeprecatedPassingNonEmptyEscapeToCsvFunctionInspection */
            $fields = str_getcsv($line, "\t", escape: '\\');
            if (count($fields) < 6) {
                continue; // Skip lines that do not have enough fields
            }
            [$sourceTaxa, $targetTaxa, $correlationGroup1, $correlationGroup2, $pv, $fdr] = $fields;
            $fdr = (float) $fdr;
            if ($fdr > 0.05) {
                continue; // Skip links with FDR > 0.05
            }
            $correlationGroup1 = (float) $correlationGroup1;
            $correlationGroup2 = (float) $correlationGroup2;
            $group = ($correlationGroup1 > $correlationGroup2) ? 1 : 2;
            $correlation = ($group === 1) ? $correlationGroup1 : $correlationGroup2;
            $addNode($sourceTaxa, $group);
            $addNode($targetTaxa, $group);
            $links[] = [
                'source' => $sourceTaxa,
                'target' => $targetTaxa,
                'correlation' => $correlation,
                'value' => (int) (abs($correlation - $this->correlationThreshold + 0.1) * 10), // Convert FDR to an integer value for the link weight
            ];
        }
        fclose($stream);

        return [
            'nodes' => array_values($nodes),
            'links' => $links,
        ];
    }

    #[Computed]
    public function networkTableUrl(): ?string
    {
        if (! isset($this->batch)) {
            return null;
        }

        return route(
            'datasets.analysis.asset',
            [
                'dataset' => $this->dataset,
                'analysisId' => $this->analysisId,
                'assetName' => CorrelationNetworkAction::DEFAULT_OUTPUT_FILE,
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
            'classVariable' => ['required', Rule::in($this->availableMetadata->toArray())],
            // @phpstan-ignore-line
            'group1' => ['required', 'string', Rule::in($this->availableClasses->toArray())],
            // @phpstan-ignore-line
            'group2' => ['required', 'string', Rule::in($this->availableClasses->toArray())],
            // @phpstan-ignore-line
            'correlationThreshold' => ['required', 'numeric', 'min:0', 'max:1'],
        ];
    }

    protected function updateParametersFromBatch(): void
    {
        $params = $this->batch->actionParams();
        $this->taxonomicLevel = $params['taxonomicLevel'];
        $this->classVariable = $params['classVariable'];
        $this->group1 = $params['group1'] ?? null;
        $this->group2 = $params['group2'] ?? null;
        $this->correlationThreshold = $params['correlationThreshold'] ?? 0.6;
    }

    protected function refreshRoute(): array
    {
        return [
            'route' => 'datasets.show.correlation_network',
            'params' => [
                'dataset' => $this->dataset,
            ],
        ];
    }
}
