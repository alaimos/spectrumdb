<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * @template TConditionValue of array<int, string> = array<int, string>
 * @template TConditionsArray of array{key: string, values: TConditionValue} = array{key: string, values: TConditionValue}
 * @template TConnector of "AND"|"OR"|"NOT" = "AND"|"OR"|"NOT"
 * @template TConnectorsArray of array<int, TConnector> = array<int, TConnector>
 */
final class CombineDatasetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $datasetMetadata
     * @param  array<int, array{id: int, criteria: array{includeAllSamples: bool, conditions: array<int, TConditionsArray>, connectors: TConnectorsArray}}>  $selectedDatasets
     * @param  array<string, array{datasets: array<int, string>, default_values: array<int, string|null>}>  $samplesMetadataPairing
     */
    public function __construct(
        public int $userId,
        public string $name,
        public string $description,
        public array $datasetMetadata,
        public array $selectedDatasets,
        public array $samplesMetadataPairing
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void {}
}
