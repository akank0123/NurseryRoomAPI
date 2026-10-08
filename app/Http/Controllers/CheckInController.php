<?php

namespace App\Http\Controllers;

use App\Actions\CheckInChild;
use App\Http\Requests\CheckInRequest;
use App\Http\Resources\CheckInResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class CheckInController
{
    public function __invoke(CheckInRequest $request, Room $room, CheckInChild $checkIn): JsonResponse
    {
        $record = $checkIn->handle($room, $request->child());

        return CheckInResource::make($record)->response()->setStatusCode(201);
    }
}
