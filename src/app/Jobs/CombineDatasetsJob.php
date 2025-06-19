<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class CombineDatasetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, int>  $selectedDatasetIds
     * @param  array<int, array{selectAll: bool, conditions: array, connectors: array}>  $datasetSampleCriteria
     * @param  array<int, array{key: string, value: string}>  $combinedDatasetMetadata
     * @param  array<int, int>  $metadataToCopy
     */
    public function __construct(
        public int $userId,
        public string $name,
        public string $description,
        public array $selectedDatasetIds,
        public array $datasetSampleCriteria,
        public array $combinedDatasetMetadata,
        public array $metadataToCopy,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void {}
}
