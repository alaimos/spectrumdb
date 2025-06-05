<?php

declare(strict_types=1);

use App\Livewire\Pages\Notifications\Index as NotificationsIndex;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    static function () {
        return redirect()->route('dashboard');
    }
)->name('home');

Route::get('dashboard', App\Livewire\Pages\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(
    function () {
        Route::redirect('settings', 'settings/profile');

        Route::get('settings/profile', Profile::class)->name('settings.profile');
        Route::get('settings/password', Password::class)->name('settings.password');
        Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
        Route::get('notifications', NotificationsIndex::class)->name('notifications');
    }
);

Route::middleware(['auth', 'admin', 'verified'])->group(
    function () {
        Route::get('/admin/users', App\Livewire\Pages\Admin\Users\Index::class)->name('admin.users.index');
        Route::get('/admin/users/create', App\Livewire\Pages\Admin\Users\Create::class)->name('admin.users.create');
        Route::get('/admin/users/{user}', App\Livewire\Pages\Admin\Users\Edit::class)->name('admin.users.edit');
    }
);

Route::middleware(['auth', 'verified'])->group(
    function () {
        Route::get('/datasets', App\Livewire\Pages\Datasets\Index::class)->name('datasets.index');
        Route::get('/datasets/create', App\Livewire\Pages\Datasets\Create::class)->can(
            'create',
            App\Models\Dataset::class
        )->name('datasets.create');
        Route::get('/datasets/combine', App\Livewire\Pages\Datasets\Combine::class)
            ->can('view', 'dataset')->name('datasets.combine');
        Route::get(
            uri: '/datasets/{dataset}',
            action: App\Livewire\Pages\Datasets\Explore\Index::class
        )->can('view', 'dataset')->name('datasets.show');
        Route::get(
            uri: '/datasets/{dataset}/taxa_composition',
            action: App\Livewire\Pages\Datasets\Explore\TaxaComposition::class
        )->can('analyze', 'dataset')->name('datasets.show.taxa_composition');
        Route::get(
            uri: '/datasets/{dataset}/alpha_diversity',
            action: App\Livewire\Pages\Datasets\Explore\AlphaDiversity::class
        )->can('analyze', 'dataset')->name('datasets.show.alpha_diversity');
        Route::get(
            uri: '/datasets/{dataset}/beta_diversity',
            action: App\Livewire\Pages\Datasets\Explore\BetaDiversity::class
        )->can('analyze', 'dataset')->name('datasets.show.beta_diversity');
        Route::get(
            uri: '/datasets/{dataset}/picrust_table',
            action: App\Livewire\Pages\Datasets\Explore\PicrustTable::class
        )->can('download', 'dataset')->name('datasets.show.picrust_table');
        Route::get(
            uri: '/datasets/{dataset}/differential_abundance',
            action: App\Livewire\Pages\Datasets\Explore\DifferentialAbundance::class
        )->can('analyze', 'dataset')->name('datasets.show.differential_abundance');
        Route::get(
            uri: '/datasets/{dataset}/functional_analysis',
            action: App\Livewire\Pages\Datasets\Explore\FunctionalAnalysis::class
        )->can('analyze', 'dataset')->name('datasets.show.functional_analysis');
        Route::get(
            uri: '/datasets/{dataset}/edit',
            action: App\Livewire\Pages\Datasets\Edit::class
        )->can('analyze', 'dataset')->name('datasets.edit');
        Route::get(
            uri: '/datasets/{dataset}/analysis/{analysisId}/assets/{assetName}',
            action: App\Http\Controllers\DatasetAnalysisAssetController::class
        )->can('analyze', 'dataset')->name('datasets.analysis.asset');
        Route::get(
            uri: '/datasets/{dataset}/download',
            action: App\Livewire\Pages\Datasets\Explore\Download::class
        )->can('download', 'dataset')->name('datasets.show.download');
        Route::get(
            uri: '/datasets/{dataset}/download/{assetName}',
            action: App\Http\Controllers\DatasetDownloadAssetController::class
        )->can('download', 'dataset')->name('datasets.download.asset');
    }
);

require __DIR__.'/auth.php';
