<?php

declare(strict_types=1);

namespace App\Actions;

use App\CommandExecutor;
use App\Enums\Analysis;
use App\Enums\DatasetPermission;
use App\Enums\PicrustTables;
use App\Exceptions\ProcessingJobException;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Dataset;
use App\Models\User;
use App\Utils;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class FunctionalAnalysisAction implements BatchableActionInterface
{
    public const string DEFAULT_OUTPUT_PREFIX = 'picrust_functional';

    public const string DEFAULT_TABLE_OUTPUT_FILE = self::DEFAULT_OUTPUT_PREFIX.'_all.tsv';

    public const string DEFAULT_TABLE_OUTPUT_FILE_PV_FILTERED = self::DEFAULT_OUTPUT_PREFIX.'_pvalue.tsv';

    public const string DEFAULT_TABLE_OUTPUT_FILE_FDR_FILTERED = self::DEFAULT_OUTPUT_PREFIX.'_fdr.tsv';

    public const string DEFAULT_RAW_OUTPUT_FILE = self::DEFAULT_OUTPUT_PREFIX.'_raw.rds';

    public const string DEFAULT_TOP_FREQ_PLOT_FILE = self::DEFAULT_OUTPUT_PREFIX.'_top_freq.svg';

    public const string DEFAULT_TOP_SIGNIFICANT_PLOT_OUTPUT_FILE = self::DEFAULT_OUTPUT_PREFIX.'_top_significant.svg';

    public const string DEFAULT_TOP_FOLD_CHANGE_PLOT_OUTPUT_FILE = self::DEFAULT_OUTPUT_PREFIX.'_top_fold_change.svg';

    public string $batchId;

    public User $user;

    public function __construct(
        public Dataset $dataset,
        public PicrustTables $picrustTable,
        public string $classVariable,
        public string $group1,
        public string $group2,
        public float $pvThreshold = 0.05,
        public float $fdrThreshold = 0.05,
        public int $topN = 20,
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
        $this->handleFunctionalAnalysis($outputAbsolutePath);
        $this->handleFunctionalPlot($outputAbsolutePath, Analysis::TOP_FC_PLOT, self::DEFAULT_TOP_FOLD_CHANGE_PLOT_OUTPUT_FILE);
        $this->handleFunctionalPlot($outputAbsolutePath, Analysis::TOP_SIGN_PLOT, self::DEFAULT_TOP_SIGNIFICANT_PLOT_OUTPUT_FILE);
        $this->handleFunctionalPlot($outputAbsolutePath, Analysis::TOP_FREQ_PLOT, self::DEFAULT_TOP_FREQ_PLOT_FILE);

    }

    /**
     * @param  array<string, string>  $queryParams
     */
    public function url(array $queryParams = []): string
    {
        return route(
            'datasets.show.taxa_composition',
            [
                ...$queryParams,
                'dataset' => $this->dataset,
            ]
        );
    }

    /**
     * @throws ProcessingJobException
     * @throws Throwable
     */
    private function handleFunctionalAnalysis(string $outputAbsolutePath): void
    {
        $tableFile = $this->dataset->getPicrustTableFile($this->picrustTable);
        throw_unless(
            $tableFile,
            ProcessingJobException::class,
            'The selected Picrust table does not exist.'
        );
        CommandExecutor::forAnalysis(Analysis::FUNCTIONAL_ANALYSIS)
            ->withArguments(
                '--asv_file',
                Storage::path($tableFile),
                '--metadata_file',
                Storage::path($this->dataset->files->metadata),
                '--class_variable',
                $this->classVariable,
                '--group1',
                $this->group1,
                '--group2',
                $this->group2,
                '--pv_threshold',
                number_format($this->pvThreshold, 4, '.', ''),
                '--fdr_threshold',
                number_format($this->fdrThreshold, 4, '.', ''),
                '--output_prefix',
                $outputAbsolutePath.'/'.self::DEFAULT_OUTPUT_PREFIX,
            )
            ->withCwd($outputAbsolutePath)
            ->execute();
    }

    /**
     * @throws ProcessingJobException
     */
    private function handleFunctionalPlot(
        string $outputAbsolutePath,
        Analysis $analysis,
        string $outputFile
    ): void {
        CommandExecutor::forAnalysis($analysis)
            ->withArguments(
                '--deseq2_results_file',
                $outputAbsolutePath.'/'.self::DEFAULT_RAW_OUTPUT_FILE,
                '--class_variable',
                $this->classVariable,
                '--group1',
                $this->group1,
                '--group2',
                $this->group2,
                '--n',
                $this->topN,
                '--output_file',
                $outputAbsolutePath.'/'.$outputFile,
            )
            ->withCwd($outputAbsolutePath)
            ->execute();
    }
}
