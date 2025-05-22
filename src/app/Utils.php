<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\IgnoredException;
use App\Exceptions\ProcessingJobException;
use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use JsonException;
use JsonSerializable;
use Stringable;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use UnitEnum;

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

    public static function cachePath(string|array $name): string
    {
        return self::makeCachePath($name, config('app.cache_path'));
    }

    private static function makeCachePath(string|array $name, string $cachePath): string
    {
        if (is_array($name)) {
            $name = Arr::map(
                Arr::flatten($name),
                static function (mixed $item) {
                    if ($item === null) {
                        return 'NULL';
                    }
                    if (! is_object($item)) {
                        return (string) $item;
                    }
                    if ($item instanceof BackedEnum) {
                        return $item->value;
                    }
                    if ($item instanceof UnitEnum) {
                        return $item->name;
                    }
                    if ($item instanceof JsonSerializable) {
                        return $item->jsonSerialize();
                    }
                    if ($item instanceof Arrayable) {
                        return $item->toArray();
                    }
                    if ($item instanceof Stringable) {
                        return $item->__toString();
                    }

                    return (string) $item;
                }
            );
            $name = md5(implode('_', $name));
        }
        $firstPath = mb_substr($name, 0, 2);
        $secondPath = mb_substr($name, 2, 2);
        $cachePath .= '/'.$firstPath.'/'.$secondPath;
        if (Storage::directoryMissing($cachePath)) {
            Storage::makeDirectory($cachePath);
        }
        //        if (! file_exists($cachePath) && ! mkdir($cachePath, 0755, true) && ! is_dir($cachePath)) {
        //            throw new RuntimeException(sprintf('Directory "%s" was not created', $cachePath));
        //        }

        return $cachePath.'/'.$name;
    }
}
