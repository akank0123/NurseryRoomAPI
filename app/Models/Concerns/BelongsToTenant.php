<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes every query on the model to the current tenant and stamps the
 * tenant on new records.
 *
 * Because the scope is global, route model binding, relations and ad-hoc
 * queries are all filtered without each caller having to remember to do it.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $query->where('tenant_id', app(TenantContext::class)->id());
        });

        static::creating(function ($model) {
            $model->tenant_id ??= app(TenantContext::class)->id();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
