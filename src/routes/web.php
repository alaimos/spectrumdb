<?php

use App\Livewire\Pages\Dashboard;
use App\Livewire\Pages\Profile\Index as ProfileIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(
    function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/playground', \App\Livewire\Pages\Playground::class)->name('playground');
        Route::get('/profile', ProfileIndex::class)->name('profile.edit');
    }
);

require __DIR__.'/auth.php';
