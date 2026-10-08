<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class Child extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['first_name', 'last_name'];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}
