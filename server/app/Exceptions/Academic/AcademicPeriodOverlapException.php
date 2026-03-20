<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AcademicPeriodOverlapException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Les dates de cette période chevauchent une autre période de la même année scolaire.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'ACADEMIC_PERIOD_OVERLAP');
    }
}
