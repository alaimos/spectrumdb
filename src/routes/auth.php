<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(
    static function () {
        Route::get('register', Register::class)->name('register');

        Route::get('login', Login::class)->name('login');

        Route::get('forgot-password', ForgotPassword::class)->name('password.request');

        Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
    }
);

Route::middleware('auth')->group(
    static function () {
        Route::get('confirm-password', ConfirmPassword::class)->name('password.confirm');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    }
);
