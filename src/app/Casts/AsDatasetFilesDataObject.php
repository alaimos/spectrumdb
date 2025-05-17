<?php

declare(strict_types=1);

namespace App\Casts;

use App\DataObjects\DatasetFilesDataObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Model;

final class AsDatasetFilesDataObject implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?DatasetFilesDataObject
    {
        if (! isset($attributes[$key])) {
            return null;
        }

        $data = Json::decode($attributes[$key]);

        if (! is_array($data)) {
            return new DatasetFilesDataObject([]);
        }

        return new DatasetFilesDataObject($data);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$key => Json::encode($value)];
    }
}
