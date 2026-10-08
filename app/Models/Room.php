<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class Room extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['name'];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Check-ins for children who are in the room right now.
     */
    public function activeCheckIns(): HasMany
    {
        return $this->checkIns()->active();
    }
}
