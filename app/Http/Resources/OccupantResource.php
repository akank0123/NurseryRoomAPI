<?php

namespace App\Http\Resources;

use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A child currently in a room. Includes the check-in id because that is
 * what the client needs to check the child out.
 *
 * @mixin CheckIn
 */
class OccupantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'check_in_id' => $this->id,
            'checked_in_at' => $this->checked_in_at->toIso8601String(),
            'child' => [
                'id' => $this->child->id,
                'first_name' => $this->child->first_name,
                'last_name' => $this->child->last_name,
            ],
        ];
    }
}
