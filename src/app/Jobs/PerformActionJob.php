<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\AnalysisCanceled;
use App\Events\AnalysisCompleted;
use App\Events\AnalysisError;
use App\Events\AnalysisProcessing;
use App\Exceptions\ProcessingJobException;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Throwable;

/**
 * @template T of \App\Actions\BatchableActionInterface
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
        private readonly User $user,
        private readonly array $actionParams = []
    ) {}

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        AnalysisProcessing::dispatch($this->batch()->id, $this->user->id);
        if ($this->batch()?->canceled()) {
            AnalysisCanceled::dispatch($this->batch()->id, $this->user->id);

            return;
        }
        try {
            /** @var T $action */
            $action = app()->make($this->actionClass, $this->actionParams);
            $action->batchId = $this->batch()->id;
            $action->user = $this->user;
            $action->handle();
            $url = $action->url(
                [
                    'analysis_id' => $this->batch()->id,
                ]
            );
            AnalysisCompleted::dispatch($this->batch()->id, $this->user->id, $url);
        } catch (Throwable $e) {
            if ($e instanceof ProcessingJobException && $e->getCode() === 100 &&
                ($previous = $e->getPrevious()) instanceof ProcessFailedException) {
                /** @var ProcessFailedException $previous */
                $output = $previous->getProcess()->getOutput();
                // Check if the output contains something between "//---BEGIN ERROR---//" and "//---END ERROR---//"
                if (preg_match('/\/\/---BEGIN ERROR---\/\/(.*?)\/\/---END ERROR---\/\//s', $output, $matches)) {
                    $e = new ProcessingJobException(
                        mb_trim($matches[1]),
                        $e->getCode(),
                        $previous
                    );
                }
            }
            Log::error($e->getMessage(), $e->getTrace());
            AnalysisError::dispatch($this->batch()->id, $e->getMessage(), $e->getTraceAsString(), $this->user->id);
            throw $e;
        }
    }
}
