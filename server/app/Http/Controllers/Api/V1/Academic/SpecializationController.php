<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreSpecializationRequest;
use App\Http\Requests\Api\V1\Academic\UpdateSpecializationRequest;
use App\Http\Resources\Academic\SpecializationResource;
use App\Models\Specialization;
use App\Services\Api\V1\Academic\SpecializationService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class SpecializationController extends Controller
{
    public function __construct(
        private readonly SpecializationService $service
    ) {}

    public function index(): JsonResponse
    {
        $specializations = Specialization::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Liste des spécialisations.', SpecializationResource::collection($specializations));
    }

    public function show(Specialization $specialization): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($specialization->school_id);

        return ApiResponse::success('Spécialisation.', new SpecializationResource($specialization));
    }

    public function store(StoreSpecializationRequest $request): JsonResponse
    {
        $specialization = $this->service->create($request->validated(), TenantHelper::currentSchoolId());

        return ApiResponse::success('Spécialisation créée.', new SpecializationResource($specialization), 201);
    }

    public function update(UpdateSpecializationRequest $request, Specialization $specialization): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($specialization->school_id);

        $specialization = $this->service->update($specialization, $request->validated());

        return ApiResponse::success('Spécialisation mise à jour.', new SpecializationResource($specialization));
    }

    public function destroy(Specialization $specialization): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($specialization->school_id);

        $this->service->archive($specialization);

        return ApiResponse::success('Spécialisation archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $specialization = Specialization::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($specialization->school_id);

        $specialization = $this->service->restore($specialization);

        return ApiResponse::success('Spécialisation restaurée.', new SpecializationResource($specialization));
    }
}
