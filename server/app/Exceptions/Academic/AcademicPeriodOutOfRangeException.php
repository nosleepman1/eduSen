<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AcademicPeriodOutOfRangeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Les dates de la période doivent être comprises dans les dates de son année scolaire.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'ACADEMIC_PERIOD_OUT_OF_RANGE');
    }
}
