<?php

declare(strict_types=1);

namespace App\DataObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class PicrustFilesDataObject implements Arrayable, JsonSerializable
{
    public ?string $ko = null;

    public ?string $pathways = null;

    public ?string $ec = null;

    /**
     * @param  array{
     *     ko?: string|null,
     *     pathways?: string|null,
     *     ec?: string|null
     * }  $data
     */
    public function __construct(array $data)
    {
        if (isset($data['ko'])) {
            $this->ko = $data['ko'];
        }
        if (isset($data['pathways'])) {
            $this->pathways = $data['pathways'];
        }
        if (isset($data['ec'])) {
            $this->ec = $data['ec'];
        }
    }

    /**
     * @return array{
     *     ko: string|null,
     *     pathways: string|null,
     *     ec: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'ko' => $this->ko,
            'pathways' => $this->pathways,
            'ec' => $this->ec,
        ];
    }

    /**
     * @return array{
     *     ko: string|null,
     *     pathways: string|null,
     *     ec: string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
