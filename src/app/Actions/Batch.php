<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BatchStatus;
use App\Exceptions\BatchNotFoundException;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch as BusBatch;
use Illuminate\Support\Facades\Bus;
use JsonSerializable;
use Stringable;

final readonly class Batch implements JsonSerializable, Stringable
{
    private BusBatch $batch;

    public function __construct(public private(set) string $id)
    {
        $batch = Bus::findBatch($id);
        if ($batch === null) {
            throw new BatchNotFoundException();
        }
        $this->batch = $batch;
    }

    public function __toString(): string
    {
        return $this->batch->id;
    }

    public function status(): BatchStatus
    {
        if ($this->batch->failedJobs > 0) {
            return BatchStatus::FAILED;
        }
        if ($this->batch->finished()) {
            return BatchStatus::FINISHED;
        }
        if ($this->batch->cancelled()) {
            return BatchStatus::CANCELLED;
        }

        return BatchStatus::PENDING;
    }

    public function progress(): float
    {
        return $this->batch->progress();
    }

    public function createdAt(): CarbonImmutable
    {
        return $this->batch->createdAt;
    }

    public function finishedAt(): ?CarbonImmutable
    {
        return $this->batch->finishedAt;
    }

    public function cancelledAt(): ?CarbonImmutable
    {
        return $this->batch->cancelledAt;
    }

    public function options(): array
    {
        return $this->batch->options['action_options'] ?? [];
    }

    public function actionClass(): string
    {
        return $this->batch->options['action_class'] ?? '';
    }

    public function actionParams(): array
    {
        return $this->batch->options['action_params'] ?? [];
    }

    /**
     * @param  string|class-string  $actionClass
     */
    public function is(string $actionClass): bool
    {
        return $this->actionClass() === $actionClass;
    }

    public function jsonSerialize(): string
    {
        return $this->batch->id;
    }
}
