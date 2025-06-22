<?php

declare(strict_types=1);

namespace App\Actions;

use App\CommandExecutor;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CombineDatasetsAction implements ActionInterface
{
    public function __construct(
        public string $configFile,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $workingDir = dirname($this->configFile);
        CommandExecutor::forScript('merge')
            ->withArguments('--config_file', Storage::path($this->configFile))
            ->withCwd(Storage::path($workingDir))
            ->execute();
    }
}
