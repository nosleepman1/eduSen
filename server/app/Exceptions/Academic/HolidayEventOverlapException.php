<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class HolidayEventOverlapException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Cet événement chevauche un congé ou une période de vacances déjà planifiée sur ce calendrier.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'HOLIDAY_EVENT_OVERLAP');
    }
}
