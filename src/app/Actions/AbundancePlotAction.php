<?php

declare(strict_types=1);

namespace App\Actions;

use App\CommandExecutor;
use App\Enums\DatasetPermission;
use App\Enums\TaxaAbundanceCharts;
use App\Enums\TaxonomicLevels;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Dataset;
use App\Models\User;
use App\Utils;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class AbundancePlotAction implements BatchableActionInterface
{
    public const string DEFAULT_PLOT_OUTPUT_FILE = 'abundance_plot.svg';

    public const string DEFAULT_TABLE_OUTPUT_FILE = 'abundance_table.tsv';

    public string $batchId;

    public User $user;

    public function __construct(
        public Dataset $dataset,
        public TaxaAbundanceCharts $chartType,
        public TaxonomicLevels $taxonomicLevel,
        public string $classVariable,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        throw_unless(
            $this->dataset->userHasPermission($this->user, DatasetPermission::READ),
            UnauthorizedActionException::class,
            'You do not have permission to access this dataset.'
        );
        $outputPath = Utils::analysisPath($this->user->id, $this->batchId);
        $outputAbsolutePath = Storage::path($outputPath);
        CommandExecutor::forAnalysis($this->chartType->analysisType())
            ->withArguments(
                '--asv_file',
                Storage::path($this->dataset->files->asvTable),
                '--taxonomy_file',
                Storage::path($this->dataset->files->taxonomy),
                '--metadata_file',
                Storage::path($this->dataset->files->metadata),
                '--taxonomy_level',
                $this->taxonomicLevel->value,
                '--class_variable',
                $this->classVariable,
                '--output_file',
                $outputAbsolutePath.'/'.self::DEFAULT_PLOT_OUTPUT_FILE,
                '--rel_abund_file',
                $outputAbsolutePath.'/'.self::DEFAULT_TABLE_OUTPUT_FILE,
            )
            ->withConditionalArguments(
                $this->chartType === TaxaAbundanceCharts::STACKED,
                '--hide_small',
                'TRUE',
            )
            ->withCwd($outputAbsolutePath)
            ->execute();
    }

    /**
     * @param  array<string, string>  $queryParams
     */
    public function url(array $queryParams = []): ?string
    {
        return route(
            'datasets.show.taxa_composition',
            [
                ...$queryParams,
                'dataset' => $this->dataset,
            ]
        );
    }
}
