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

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(
    function () {
        Route::redirect('settings', 'settings/profile');

        Route::get('settings/profile', Profile::class)->name('settings.profile');
        Route::get('settings/password', Password::class)->name('settings.password');
        Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
        Route::get('notifications', NotificationsIndex::class)->name('notifications');
    }
);

Route::middleware(['auth', 'admin'])->group(
    function () {
        Route::get('/admin/users', App\Livewire\Pages\Admin\Users\Index::class)->name('admin.users.index');
        Route::get('/admin/users/create', App\Livewire\Pages\Admin\Users\Create::class)->name('admin.users.create');
        Route::get('/admin/users/{user}', App\Livewire\Pages\Admin\Users\Edit::class)->name('admin.users.edit');
    }
);

Route::middleware(['auth'])->group(
    function () {
        Route::get('/datasets', App\Livewire\Pages\Datasets\Index::class)->name('datasets.index');
        Route::get('/datasets/create', App\Livewire\Pages\Datasets\Create::class)->name('datasets.create');
        Route::get(
            uri: '/datasets/{dataset}',
            action: App\Livewire\Pages\Datasets\Explore\Index::class
        )->name('datasets.show');
        Route::get(
            uri: '/datasets/{dataset}/alpha_diversity',
            action: App\Livewire\Pages\Datasets\Explore\AlphaDiversity::class
        )->name('datasets.show.alpha_diversity');
        Route::get(
            uri: '/datasets/{dataset}/beta_diversity',
            action: App\Livewire\Pages\Datasets\Explore\BetaDiversity::class
        )->name('datasets.show.beta_diversity');
        Route::get('/datasets/{dataset}/edit', App\Livewire\Pages\Datasets\Edit::class)->name('datasets.edit');
    }
);

require __DIR__.'/auth.php';
