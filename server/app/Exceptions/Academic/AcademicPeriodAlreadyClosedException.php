<?php

namespace App\Exceptions\Academic;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AcademicPeriodAlreadyClosedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Cette période est clôturée et n'est plus modifiable.");
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), 422, 'ACADEMIC_PERIOD_CLOSED');
    }
}
