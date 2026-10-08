<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants');

        Schema::create('rooms', function (Blueprint $collection) {
            $collection->index('tenant_id');
        });

        Schema::create('children', function (Blueprint $collection) {
            $collection->index('tenant_id');
        });

        Schema::create('check_ins', function (Blueprint $collection) {
            // Serves the occupants query.
            $collection->index(['tenant_id', 'room_id', 'checked_out_at']);

            // A child can have at most one open check-in. Enforced by the
            // database so two simultaneous requests cannot both succeed.
            $collection->unique('child_id', 'one_open_check_in_per_child', options: [
                'partialFilterExpression' => ['checked_out_at' => null],
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('children');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('tenants');
    }
};
