<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\IgnoredException;
use App\Exceptions\ProcessingJobException;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class Utils
{
    public const string UNKNOWN_ERROR_CODE = '===UNKNOWN===';

    public const string IGNORED_ERROR_CODE = '===IGNORED===';

    /**
     * Map command exception to message
     *
     * @param  array<string|int, string>  $errorCodeMap
     */
    public static function mapCommandException(
        ProcessFailedException $e,
        array $errorCodeMap = []
    ): IgnoredException|ProcessingJobException {
        $code = $e->getProcess()->getExitCode();

        return match ($errorCodeMap[$code] ?? self::UNKNOWN_ERROR_CODE) {
            self::IGNORED_ERROR_CODE => new IgnoredException('Error', $code),
            self::UNKNOWN_ERROR_CODE => new ProcessingJobException(
                'Unknown error',
                $code,
                $e
            ),
            default => new ProcessingJobException($errorCodeMap[$code], $code, $e),
        };
    }

    /**
     * Runs a shell command and checks for successful completion of execution
     *
     * @param  string[]  $command
     * @param  callable("stdout"|"stderr", string): void|null  $callback
     */
    public static function runCommand(
        array $command,
        ?string $cwd = null,
        ?int $timeout = null,
        ?callable $callback = null,
    ): string {
        $process = new Process($command, $cwd, null, null, $timeout);
        $process->run($callback);
        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    public static function hashArray(array $array): string
    {
        try {
            return md5(json_encode($array, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            return md5(serialize($array));
        }
    }

    public static function scriptPath(string $script, string $extension = '.R'): string
    {
        return config('app.bin_path').'/'.$script.$extension;
    }

    public static function analysisPath(int $userId, string $batchId): string
    {
        $path = config('app.analysis_path').'/'.$userId.'/'.$batchId;
        if (Storage::directoryMissing($path)) {
            Storage::makeDirectory($path);
        }

        return $path;
    }
}
