<?php

use App\Livewire\Pages\Dashboard;
use App\Livewire\Pages\Notifications\Index as NotificationsIndex;
use App\Livewire\Pages\Profile\Index as ProfileIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(
    function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/playground', \App\Livewire\Pages\Playground::class)->name('playground');
        Route::get('/profile', ProfileIndex::class)->name('profile.edit');
        Route::get('/notifications', NotificationsIndex::class)->name('notifications');
    }
);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', App\Livewire\Pages\Admin\Users\Index::class)->name('admin.users.index');
    Route::get('/admin/users/create', App\Livewire\Pages\Admin\Users\Create::class)->name('admin.users.create');
    Route::get('/admin/users/{user}', App\Livewire\Pages\Admin\Users\Edit::class)->name('admin.users.edit');
});

require __DIR__.'/auth.php';
