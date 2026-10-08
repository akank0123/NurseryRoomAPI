<?php

namespace App\Providers;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request / job lifecycle.
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
