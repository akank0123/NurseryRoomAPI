<?php

namespace App\Http\Controllers;

use App\Actions\CheckOutChild;
use App\Http\Resources\CheckInResource;
use App\Models\CheckIn;

class CheckOutController
{
    public function __invoke(CheckIn $checkIn, CheckOutChild $checkOut): CheckInResource
    {
        return CheckInResource::make($checkOut->handle($checkIn));
    }
}
