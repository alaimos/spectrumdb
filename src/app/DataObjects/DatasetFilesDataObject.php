<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Casts\AsDatasetFilesDataObject;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class DatasetFilesDataObject implements Arrayable, Castable, JsonSerializable
{
    public string $taxonomy;

    public string $asvTable;

    public string $metadata;

    public PicrustFilesDataObject $picrust;

    public AlphaDiversityFilesDataObject $alphaDiversity;

    public BetaDiversityFilesDataObject $betaDiversity;

    /**
     * @param  array{
     *     taxonomy?: string,
     *     asvTable?: string,
     *     metadata?: string,
     *     picrust?: array{
     *         ko?: string|null,
     *         pathways?: string|null,
     *         ec?: string|null
     *     },
     *     alphaDiversity?: array{
     *         faith?: string|null,
     *         chao?: string|null,
     *         evenness?: string|null,
     *         shannon?: string|null
     *     },
     *     betaDiversity?: array{
     *         jaccard?: string|null,
     *         brayCurtis?: string|null,
     *         unweightedUnifrac?: string|null,
     *         weightedUnifrac?: string|null
     *     }
     * }  $data
     */
    public function __construct(array $data = [])
    {
        $this->taxonomy = $data['taxonomy'] ?? '';
        $this->asvTable = $data['asvTable'] ?? '';
        $this->metadata = $data['metadata'] ?? '';
        $this->picrust = new PicrustFilesDataObject($data['picrust'] ?? []);
        $this->alphaDiversity = new AlphaDiversityFilesDataObject($data['alphaDiversity'] ?? []);
        $this->betaDiversity = new BetaDiversityFilesDataObject($data['betaDiversity'] ?? []);
    }

    public static function castUsing(array $arguments): string
    {
        return AsDatasetFilesDataObject::class;
    }

    /**
     * @return array{
     *      taxonomy: string,
     *      asvTable: string,
     *      metadata: string,
     *      picrust: array{
     *          ko: string|null,
     *          pathways: string|null,
     *          ec: string|null
     *      },
     *      alphaDiversity: array{
     *          faith: string|null,
     *          chao: string|null,
     *          evenness: string|null,
     *          shannon: string|null
     *      },
     *      betaDiversity: array{
     *          jaccard: string|null,
     *          brayCurtis: string|null,
     *          unweightedUnifrac: string|null,
     *          weightedUnifrac: string|null
     *      }
     *  }
     */
    public function toArray(): array
    {
        return [
            'taxonomy' => $this->taxonomy,
            'asvTable' => $this->asvTable,
            'metadata' => $this->metadata,
            'picrust' => $this->picrust->toArray(),
            'alphaDiversity' => $this->alphaDiversity->toArray(),
            'betaDiversity' => $this->betaDiversity->toArray(),
        ];
    }

    /**
     * @return array{
     *      taxonomy: string,
     *      asvTable: string,
     *      metadata: string,
     *      picrust: array{
     *          ko: string|null,
     *          pathways: string|null,
     *          ec: string|null
     *      },
     *      alphaDiversity: array{
     *          faith: string|null,
     *          chao: string|null,
     *          evenness: string|null,
     *          shannon: string|null
     *      },
     *      betaDiversity: array{
     *          jaccard: string|null,
     *          brayCurtis: string|null,
     *          unweightedUnifrac: string|null,
     *          weightedUnifrac: string|null
     *      }
     *  }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
