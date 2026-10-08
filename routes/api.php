<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\RoomOccupantsController;
use Illuminate\Support\Facades\Route;

Route::post('/rooms/{room}/check-ins', CheckInController::class);
Route::post('/check-ins/{checkIn}/check-out', CheckOutController::class);
Route::get('/rooms/{room}/occupants', RoomOccupantsController::class);
