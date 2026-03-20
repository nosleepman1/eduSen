<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ClassroomEndDateOutOfRangeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("La date de fin personnalisée doit être comprise dans les dates de l'année scolaire.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'CLASSROOM_END_DATE_OUT_OF_RANGE');
    }
}
