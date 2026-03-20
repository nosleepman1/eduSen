<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AcademicYearOverlapException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Les dates de cette année scolaire chevauchent une année existante.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'ACADEMIC_YEAR_OVERLAP');
    }
}
