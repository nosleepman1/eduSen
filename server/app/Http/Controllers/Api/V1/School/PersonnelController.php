<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\School\UpdatePersonnelRequest;
use App\Http\Requests\Api\V1\School\UpdatePersonnelRolesRequest;
use App\Http\Resources\School\PersonnelResource;
use App\Models\Personnel;
use App\Services\Api\V1\School\PersonnelService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class PersonnelController extends Controller
{
    public function __construct(
        private readonly PersonnelService $service
    ) {}

    public function index(): JsonResponse
    {
        $personnel = Personnel::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with('user')
            ->paginate(15);

        return ApiResponse::success('Liste du personnel.', PersonnelResource::collection($personnel));
    }

    public function show(Personnel $personnel): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($personnel->school_id);

        return ApiResponse::success('Personnel.', new PersonnelResource($personnel));
    }

    public function update(UpdatePersonnelRequest $request, Personnel $personnel): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($personnel->school_id);

        $personnel = $this->service->update($personnel, $request->validated());

        return ApiResponse::success('Personnel mis à jour.', new PersonnelResource($personnel));
    }

    public function updateRoles(UpdatePersonnelRolesRequest $request, Personnel $personnel): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($personnel->school_id);

        $this->service->updateRoles($personnel, $request->validated('roles'), $request->user());

        return ApiResponse::success('Rôles mis à jour.');
    }

    public function destroy(Personnel $personnel): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($personnel->school_id);

        $this->service->archive($personnel);

        return ApiResponse::success('Personnel archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $personnel = Personnel::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($personnel->school_id);

        $personnel = $this->service->restore($personnel);

        return ApiResponse::success('Personnel restauré.', new PersonnelResource($personnel));
    }
}
