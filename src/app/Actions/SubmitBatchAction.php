<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\PerformActionJob;
use App\Models\User;
use App\Traits\Makeable;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

final class SubmitBatchAction implements ActionInterface
{
    use Makeable;

    private(set) public string $batchId;

    private readonly User $user;

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Create a new job instance.
     *
     * @param  class-string  $actionClass
     * @param  array<string, mixed>  $actionParams
     * @return void
     */
    public function __construct(
        private readonly string $actionClass,
        private readonly array $actionParams = [],
        ?User $user = null
    ) {
        $this->user = $user ?? auth()->user();
    }

    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        $job = new PerformActionJob($this->actionClass, $this->user, $this->actionParams);
        $batch = Bus::batch([$job])
            ->withOption('action_class', $this->actionClass)
            ->withOption('action_params', $this->actionParams)
            ->withOption('action_options', $this->options)
            ->dispatch();

        $this->batchId = $batch->id;
    }

    public function withOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    private static function batchStatusText(Batch $batch): string
    {
        if ($batch->failedJobs > 0) {
            return 'failed';
        }

        return ($batch->finishedAt === null) ? 'pending' : 'finished';
    }
}
