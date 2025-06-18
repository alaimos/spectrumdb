<?php

declare(strict_types=1);

namespace App\Actions;

use App\CommandExecutor;
use App\Enums\Analysis;
use App\Enums\DatasetPermission;
use App\Enums\TaxonomicLevels;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Dataset;
use App\Models\User;
use App\Utils;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CorrelationNetworkAction implements BatchableActionInterface
{
    public const string DEFAULT_OUTPUT_FILE = 'correlation_network.tsv';

    public string $batchId;

    public User $user;

    public function __construct(
        public Dataset $dataset,
        public TaxonomicLevels $taxonomicLevel,
        public string $classVariable,
        public string $group1,
        public string $group2,
        public float $correlationThreshold = 0.6,
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
        CommandExecutor::forAnalysis(Analysis::CORRELATION_NETWORK)
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
                '--group1',
                $this->group1,
                '--group2',
                $this->group2,
                '--corr_threshold',
                number_format($this->correlationThreshold, 4, '.', ''),
                '--output_file',
                $outputAbsolutePath.'/'.self::DEFAULT_OUTPUT_FILE,
            )
            ->withCwd($outputAbsolutePath)
            ->execute();
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
}
