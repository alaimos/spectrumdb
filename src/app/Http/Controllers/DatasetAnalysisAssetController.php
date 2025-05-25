<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DatasetPermission;
use App\Models\Dataset;
use App\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class DatasetAnalysisAssetController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Dataset $dataset, string $analysisId, string $assetName)
    {
        abort_unless(
            $dataset->userHasPermission(auth()->user(), DatasetPermission::READ),
            404,
            'Dataset not found'
        );
        $analysisPath = Utils::analysisPath(auth()->id(), $analysisId);
        abort_if(Storage::directoryMissing($analysisPath), 404, 'Analysis not found');
        $assetPath = $analysisPath.'/'.$assetName;
        /* Check if the asset name is valid */
        abort_unless(preg_match('/^[a-zA-Z0-9_\-.]+$/', $assetName), 400, 'Invalid asset name');
        abort_if(Storage::missing($assetPath), 404, 'Asset not found');

        return Storage::download($assetPath);
    }
}
