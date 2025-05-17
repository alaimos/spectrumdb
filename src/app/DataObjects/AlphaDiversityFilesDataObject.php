<?php

declare(strict_types=1);

namespace App\DataObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class AlphaDiversityFilesDataObject implements Arrayable, JsonSerializable
{
    public ?string $faith = null;

    public ?string $chao = null;

    public ?string $evenness = null;

    public ?string $shannon = null;

    /**
     * @param  array{
     *     faith: string|null,
     *     chao: string|null,
     *     evenness: string|null,
     *     shannon: string|null
     * }  $data
     */
    public function __construct(array $data)
    {
        if (isset($data['faith'])) {
            $this->faith = $data['faith'];
        }
        if (isset($data['chao'])) {
            $this->chao = $data['chao'];
        }
        if (isset($data['evenness'])) {
            $this->evenness = $data['evenness'];
        }
        if (isset($data['shannon'])) {
            $this->shannon = $data['shannon'];
        }
    }

    /**
     * @return array{
     *     faith: string|null,
     *     chao: string|null,
     *     evenness: string|null,
     *     shannon: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'faith' => $this->faith,
            'chao' => $this->chao,
            'evenness' => $this->evenness,
            'shannon' => $this->shannon,
        ];
    }

    /**
     * @return array{
     *     faith: string|null,
     *     chao: string|null,
     *     evenness: string|null,
     *     shannon: string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
