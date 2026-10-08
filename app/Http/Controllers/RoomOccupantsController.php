<?php

namespace App\Http\Controllers;

use App\Http\Resources\OccupantResource;
use App\Models\Room;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomOccupantsController
{
    public function __invoke(Room $room): AnonymousResourceCollection
    {
        $occupants = $room->activeCheckIns()
            ->with('child')
            ->oldest('checked_in_at')
            ->get();

        return OccupantResource::collection($occupants);
    }
}
