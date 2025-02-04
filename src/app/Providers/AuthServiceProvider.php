<?php

namespace App\Providers;

use App\Models\Dataset;
use App\Models\Sample;
use App\Models\User;
use App\Policies\DatasetPolicy;
use App\Policies\SamplePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Dataset::class => DatasetPolicy::class,
        Sample::class => SamplePolicy::class,
        User::class => UserPolicy::class,
    ];
}
