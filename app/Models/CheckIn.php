<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

/**
 * One stay of a child in a room. An open check-in (no checked_out_at)
 * means the child is in that room right now.
 */
class CheckIn extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['room_id', 'child_id', 'checked_in_at', 'checked_out_at'];

    // Store the field explicitly so every document has the same shape.
    protected $attributes = ['checked_out_at' => null];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'immutable_datetime',
            'checked_out_at' => 'immutable_datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('checked_out_at');
    }
}
