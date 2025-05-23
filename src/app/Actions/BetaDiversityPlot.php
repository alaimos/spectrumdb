<?php

declare(strict_types=1);

namespace App\Actions;

use App\CommandExecutor;
use App\Enums\Analysis;
use App\Enums\BetaDiversityMetrics;
use App\Enums\DatasetPermission;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Dataset;
use App\Models\User;
use App\Utils;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class BetaDiversityPlot implements BatchableActionInterface
{
    public const string DEFAULT_OUTPUT_FILE = 'beta_diversity_plot.svg';

    public string $batchId;

    public User $user;

    public function __construct(
        public Dataset $dataset,
        public BetaDiversityMetrics $metrics,
        public string $colorVariable,
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
        CommandExecutor::forAnalysis(Analysis::BETA_DIVERSITY)
            ->withArguments(
                '--beta_diversity_file',
                Storage::path($this->betaDiversityFile()),
                '--metadata_file',
                Storage::path($this->dataset->files->metadata),
                '--color_var',
                $this->colorVariable,
                '--output_file',
                $outputAbsolutePath.'/'.self::DEFAULT_OUTPUT_FILE,
            )
            ->withCwd($outputAbsolutePath)
            ->execute();
    }

    private function betaDiversityFile(): string
    {
        $file = $this->dataset->getBetaDiversityFile($this->metrics);
        if (! $file) {
            throw new RuntimeException('This dataset does not have the requested beta diversity file.');
        }

        return $file;
    }
}
