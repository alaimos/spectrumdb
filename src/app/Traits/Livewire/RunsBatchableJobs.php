<?php

declare(strict_types=1);

namespace App\Traits\Livewire;

use App\Actions\Batch;
use App\Enums\BatchStatus;
use App\Exceptions\BatchNotFoundException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * @property-read string|class-string<\App\Actions\BatchableActionInterface> $batchActionType
 *
 * @method void redirectRoute(string $name, array $parameters = [], bool $absolute = true, bool $navigate = false)
 */
trait RunsBatchableJobs
{
    #[Url('analysis_id')]
    public ?string $analysisId;

    protected Batch $batch;

    /**
     * Returns the route and parameters to refresh the component.
     *
     * @return array{route: string, params: array<string, mixed>}
     */
    abstract protected function refreshRoute(): array;

    public function mountRunsBatchableJobs(): void
    {
        $this->updateBatch();
        if (isset($this->batch)) {
            $this->updateParametersFromBatch();
        }
    }

    #[Computed]
    public function batchStatus(): ?BatchStatus
    {
        if (! isset($this->batch)) {
            return null;
        }

        return $this->batch->status();
    }

    public function analysisStatusUpdated(array $event): void
    {
        if (! isset($this->analysisId)) {
            return;
        }
        $analysisId = $event['batchId'] ?? null;
        if ($analysisId !== $this->analysisId) {
            return;
        }
        $this->refresh(withTimestamp: true);
    }

    protected function updateBatch(): void
    {
        if (isset($this->analysisId)) {
            try {
                $this->batch = new Batch($this->analysisId);
                if (! $this->batch->is($this->batchActionType)) {
                    throw new BatchNotFoundException();
                }
            } catch (BatchNotFoundException) {
                abort(404, 'Analysis not found');
            }
        }
    }

    /**
     * Get the listeners for analysis updates.
     *
     * @return array<string, string>
     */
    protected function getBatchListeners(): array
    {
        if (! isset($this->analysisId)) {
            return [];
        }
        $userId = auth()->id();

        return [
            "echo-private:analysis.{$userId},.analysis.error" => 'analysisStatusUpdated',
            "echo-private:analysis.{$userId},.analysis.completed" => 'analysisStatusUpdated',
            "echo-private:analysis.{$userId},.analysis.processing" => 'analysisStatusUpdated',
        ];
    }

    protected function updateParametersFromBatch(): void {}

    /**
     * Set the analysis identifier and refresh the component.
     *
     * @param  array<string, mixed>  $params
     */
    protected function refreshWithAnalysisId(string $analysisId, bool $withTimestamp = false, array $params = []): void
    {
        $this->analysisId = $analysisId;
        $this->refresh($withTimestamp, $params);
    }

    /**
     * Refresh the current component
     *
     * @param  array<string, mixed>  $params
     */
    protected function refresh(bool $withTimestamp = false, array $params = []): void
    {
        ['route' => $route, 'params' => $baseParams] = $this->refreshRoute();
        $params = [
            ...$params,
            'analysis_id' => $this->analysisId,
            ...$baseParams,
        ];
        if ($withTimestamp) {
            $params['refresh'] = now()->timestamp;
        }
        $this->redirectRoute(
            $route,
            $params,
            navigate: true
        );
    }
}
