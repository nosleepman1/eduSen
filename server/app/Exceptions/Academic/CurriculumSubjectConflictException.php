<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CurriculumSubjectConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Cette matière est déjà présente dans le programme de ce niveau et cette spécialisation.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'CURRICULUM_SUBJECT_CONFLICT');
    }
}
