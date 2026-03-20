<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\School\StoreSchoolBasicInfoRequest;
use App\Http\Requests\Api\V1\School\StoreSchoolDetailsRequest;
use App\Services\Api\V1\School\SchoolSetupService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolSetupController extends Controller
{
    public function basicInfo(StoreSchoolBasicInfoRequest $request, SchoolSetupService $service): JsonResponse
    {
        $school = TenantHelper::currentSchool();

        return ApiResponse::success('Informations de base enregistrees.', $service->saveBasicInfo($school, $request->validated(), $request->file('logo')));
    }

    public function contactInfo(StoreSchoolDetailsRequest $request, SchoolSetupService $service): JsonResponse
    {
        $school = TenantHelper::currentSchool();

        return ApiResponse::success('Coordonnees enregistrees.', $service->saveContactInfo($school, $request->validated()));
    }

    public function complete(Request $request, SchoolSetupService $service): JsonResponse
    {
        $school = TenantHelper::currentSchool();

        return ApiResponse::success('Votre ecole est maintenant configuree.', ['school' => $service->complete($school)]);
    }
}
