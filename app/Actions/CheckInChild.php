<?php

namespace App\Actions;

use App\Exceptions\ChildAlreadyCheckedInException;
use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use MongoDB\Driver\Exception\BulkWriteException;

class CheckInChild
{
    private const DUPLICATE_KEY = 11000;

    /**
     * @throws ChildAlreadyCheckedInException if the child is already in a room
     */
    public function handle(Room $room, Child $child): CheckIn
    {
        $this->ensureNotCheckedIn($child);

        try {
            return CheckIn::create([
                'room_id' => $room->id,
                'child_id' => $child->id,
                'checked_in_at' => now(),
            ]);
        } catch (BulkWriteException $e) {
            // Another request checked the child in between our check and the
            // insert. The unique index caught it; report it the same way.
            if ($e->getCode() === self::DUPLICATE_KEY) {
                $this->ensureNotCheckedIn($child);
            }

            throw $e;
        }
    }

    private function ensureNotCheckedIn(Child $child): void
    {
        $current = $child->checkIns()->active()->with('room')->first();

        if ($current !== null) {
            throw new ChildAlreadyCheckedInException($current);
        }
    }
}
