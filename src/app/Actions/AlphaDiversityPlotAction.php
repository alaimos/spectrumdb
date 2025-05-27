<?php

declare(strict_types=1);

namespace App\Actions;

use App\CommandExecutor;
use App\Enums\AlphaDiversityMetrics;
use App\Enums\Analysis;
use App\Enums\DatasetPermission;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Dataset;
use App\Models\User;
use App\Utils;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class AlphaDiversityPlotAction implements BatchableActionInterface
{
    public const string DEFAULT_OUTPUT_FILE = 'alpha_diversity_plot.svg';

    public string $batchId;

    public User $user;

    /**
     * @param  array<int, array{string, string}>|null  $comparisons
     */
    public function __construct(
        public Dataset $dataset,
        public AlphaDiversityMetrics $metrics,
        public string $classVariable,
        public ?array $comparisons,
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
        CommandExecutor::forAnalysis(Analysis::ALPHA_DIVERSITY)
            ->withArguments(
                '--alpha_diversity_file',
                Storage::path($this->alphaDiversityFile()),
                '--metadata_file',
                Storage::path($this->dataset->files->metadata),
                '--class_variable',
                $this->classVariable,
                '--output_file',
                $outputAbsolutePath.'/'.self::DEFAULT_OUTPUT_FILE,
            )
            ->withConditionalArguments(
                $this->comparisons !== null && count($this->comparisons) > 0,
                '--comparisons',
                implode(
                    ';',
                    array_map(
                        static fn (array $comparison) => implode(',', $comparison),
                        $this->comparisons
                    )
                ),
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
            'datasets.show.alpha_diversity',
            [
                ...$queryParams,
                'dataset' => $this->dataset,
            ]
        );
    }

    private function alphaDiversityFile(): string
    {
        $file = $this->dataset->getAlphaDiversityFile($this->metrics);
        if (! $file) {
            throw new RuntimeException('This dataset does not have the requested alpha diversity file.');
        }

        return $file;
    }
}
