<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ClassroomNameConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Une classe portant ce nom existe déjà pour ce niveau et cette spécialisation.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'CLASSROOM_NAME_CONFLICT');
    }
}
