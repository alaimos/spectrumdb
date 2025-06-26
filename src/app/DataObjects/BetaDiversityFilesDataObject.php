<?php

declare(strict_types=1);

namespace App\DataObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class BetaDiversityFilesDataObject implements Arrayable, JsonSerializable
{
    public ?string $jaccard = null;

    public ?string $brayCurtis = null;

    public ?string $unweightedUnifrac = null;

    public ?string $weightedUnifrac = null;

    /**
     * @param  array{
     *     jaccard?: string|null,
     *     brayCurtis?: string|null,
     *     unweightedUnifrac?: string|null,
     *     weightedUnifrac?: string|null
     * }  $data
     */
    public function __construct(array $data)
    {
        if (isset($data['jaccard'])) {
            $this->jaccard = $data['jaccard'];
        }
        if (isset($data['brayCurtis'])) {
            $this->brayCurtis = $data['brayCurtis'];
        }
        if (isset($data['unweightedUnifrac'])) {
            $this->unweightedUnifrac = $data['unweightedUnifrac'];
        }
        if (isset($data['weightedUnifrac'])) {
            $this->weightedUnifrac = $data['weightedUnifrac'];
        }
    }

    /**
     * @return array{
     *     jaccard: string|null,
     *     brayCurtis: string|null,
     *     unweightedUnifrac: string|null,
     *     weightedUnifrac: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'jaccard' => $this->jaccard,
            'brayCurtis' => $this->brayCurtis,
            'unweightedUnifrac' => $this->unweightedUnifrac,
            'weightedUnifrac' => $this->weightedUnifrac,
        ];
    }

    /**
     * @return array{
     *     jaccard: string|null,
     *     brayCurtis: string|null,
     *     unweightedUnifrac: string|null,
     *     weightedUnifrac: string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
