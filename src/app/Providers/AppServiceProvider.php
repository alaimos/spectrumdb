<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Sleep;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useAggressivePrefetching();
        Model::automaticallyEagerLoadRelationships();
        Sleep::fake();
        Date::use(CarbonImmutable::class);
        Password::defaults(fn (): ?Password => app()->isProduction() ? Password::min(12)->max(255)->uncompromised() : null);
        Model::shouldBeStrict();
        Model::unguard();
    }
}
