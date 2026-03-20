<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\ReviewRegistrationRequest;
use App\Http\Requests\Api\V1\Platform\StoreSchoolRegistrationRequest;
use App\Models\SchoolRegistrationRequest;
use App\Services\Api\V1\Platform\SchoolApprovalService;
use App\Services\Api\V1\Platform\SchoolRegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolRegistrationRequestController extends Controller
{
    public function store(StoreSchoolRegistrationRequest $request, SchoolRegistrationService $service): JsonResponse
    {
        return ApiResponse::success('Votre demande a ete recue et sera traitee prochainement.', $service->submitRequest($request->validated()), 201);
    }

    public function index(SchoolRegistrationService $service): JsonResponse
    {
        return ApiResponse::success('Demandes en attente.', $service->pendingRequests());
    }

    public function approve(ReviewRegistrationRequest $request, SchoolRegistrationRequest $registrationRequest, SchoolApprovalService $service): JsonResponse
    {
        return ApiResponse::success('La demande a ete approuvee.', $service->approve($registrationRequest, $request->user(), $request->validated('subscription_plan_id')));
    }

    public function reject(Request $request, SchoolRegistrationRequest $registrationRequest, SchoolApprovalService $service): JsonResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:10']]);

        return ApiResponse::success('La demande a ete rejetee.', $service->reject($registrationRequest, $request->user(), $data['rejection_reason']));
    }
}
