<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\School\StoreGuardianRequest;
use App\Http\Requests\Api\V1\School\UpdateGuardianRequest;
use App\Http\Resources\School\GuardianResource;
use App\Models\Guardian;
use App\Services\Api\V1\School\GuardianService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class GuardianController extends Controller
{
    public function __construct(
        private readonly GuardianService $service
    ) {}

    public function index(): JsonResponse
    {
        $guardians = Guardian::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderBy('lastname')
            ->paginate(15);

        return ApiResponse::success('Liste des parents.', GuardianResource::collection($guardians));
    }

    public function show(Guardian $guardian): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($guardian->school_id);

        return ApiResponse::success('Parent.', new GuardianResource($guardian));
    }

    public function store(StoreGuardianRequest $request): JsonResponse
    {
        $guardian = $this->service->create($request->validated(), TenantHelper::currentSchoolId());

        return ApiResponse::success('Parent créé.', new GuardianResource($guardian), 201);
    }

    public function update(UpdateGuardianRequest $request, Guardian $guardian): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($guardian->school_id);

        $guardian = $this->service->update($guardian, $request->validated());

        return ApiResponse::success('Parent mis à jour.', new GuardianResource($guardian));
    }

    public function destroy(Guardian $guardian): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($guardian->school_id);

        $this->service->archive($guardian);

        return ApiResponse::success('Parent archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $guardian = Guardian::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($guardian->school_id);

        $guardian = $this->service->restore($guardian);

        return ApiResponse::success('Parent restauré.', new GuardianResource($guardian));
    }
}
