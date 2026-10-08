<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class AlreadyCheckedOutException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This child has already been checked out.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
