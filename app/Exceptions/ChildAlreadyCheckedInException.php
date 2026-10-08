<?php

namespace App\Exceptions;

use App\Models\CheckIn;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * A child can only be in one room at a time. Rather than moving them
 * silently, we refuse and say where they currently are, so staff can
 * check them out of that room first (see README for the reasoning).
 */
class ChildAlreadyCheckedInException extends RuntimeException
{
    public function __construct(public readonly CheckIn $current)
    {
        parent::__construct('This child is already checked into a room.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'current_check_in' => [
                'id' => $this->current->id,
                'room' => [
                    'id' => $this->current->room->id,
                    'name' => $this->current->room->name,
                ],
                'checked_in_at' => $this->current->checked_in_at->toIso8601String(),
            ],
        ], 409);
    }
}
