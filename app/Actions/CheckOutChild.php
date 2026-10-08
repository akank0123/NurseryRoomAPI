<?php

namespace App\Actions;

use App\Exceptions\AlreadyCheckedOutException;
use App\Models\CheckIn;

class CheckOutChild
{
    /**
     * @throws AlreadyCheckedOutException if the check-in is already closed
     */
    public function handle(CheckIn $checkIn): CheckIn
    {
        // Conditional update so two simultaneous check-outs cannot both
        // succeed and overwrite each other's timestamp.
        $closed = CheckIn::whereKey($checkIn->id)
            ->active()
            ->update(['checked_out_at' => now()]);

        if ($closed === 0) {
            throw new AlreadyCheckedOutException;
        }

        return $checkIn->refresh();
    }
}
