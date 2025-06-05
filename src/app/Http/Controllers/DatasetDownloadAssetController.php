<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DatasetPermission;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

final class DatasetDownloadAssetController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Dataset $dataset, string $assetName)
    {
        abort_unless(
            $dataset->userHasAnyPermission(auth()->user(), [DatasetPermission::DOWNLOAD, DatasetPermission::ALL]),
            404,
            'Dataset not found'
        );
        $assets = Arr::dot($dataset->files->toArray());
        $asset = $assets[$assetName] ?? null;
        abort_if($asset === null || Storage::fileMissing($asset), 404, 'Asset not found');

        return Storage::download($asset);
    }
}
