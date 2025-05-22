<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\AnalysisCanceled;
use App\Events\AnalysisCompleted;
use App\Events\AnalysisError;
use App\Events\AnalysisProcessing;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @template T of \App\Actions\ActionInterface
 */
final class PerformActionJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  string|class-string<T>  $actionClass
     * @return void
     */
    public function __construct(
        private readonly string $actionClass,
        private readonly int $userId,
        private readonly array $actionParams = []
    ) {}

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        AnalysisProcessing::dispatch($this->batch()->id, $this->userId);
        if ($this->batch()?->canceled()) {
            AnalysisCanceled::dispatch($this->batch()->id, $this->userId);

            return;
        }
        try {
            /** @var T $action */
            $action = app()->make(
                $this->actionClass,
                [
                    ...$this->actionParams,
                    'userId' => $this->userId,
                ]
            );
            $action->handle();
            AnalysisCompleted::dispatch($this->batch()->id, $this->userId);
        } catch (Throwable $e) {
            Log::error($e->getMessage(), $e->getTrace());
            AnalysisError::dispatch($this->batch()->id, $e->getMessage(), $e->getTraceAsString(), $this->userId);
            throw $e;
        }
    }
}
