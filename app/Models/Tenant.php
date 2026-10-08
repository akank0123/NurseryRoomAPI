<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * A nursery group: one customer of the platform.
 */
class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
