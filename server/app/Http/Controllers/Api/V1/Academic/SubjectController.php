<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreSubjectRequest;
use App\Http\Requests\Api\V1\Academic\UpdateSubjectRequest;
use App\Http\Resources\Academic\SubjectResource;
use App\Models\Subject;
use App\Services\Api\V1\Academic\SubjectService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function __construct(
        private readonly SubjectService $service
    ) {}

    public function index(): JsonResponse
    {
        $subjects = Subject::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Liste des matières.', SubjectResource::collection($subjects));
    }

    public function show(Subject $subject): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($subject->school_id);

        return ApiResponse::success('Matière.', new SubjectResource($subject));
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->service->create($request->validated(), TenantHelper::currentSchoolId());

        return ApiResponse::success('Matière créée.', new SubjectResource($subject), 201);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($subject->school_id);

        $subject = $this->service->update($subject, $request->validated());

        return ApiResponse::success('Matière mise à jour.', new SubjectResource($subject));
    }

    public function destroy(Subject $subject): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($subject->school_id);

        $this->service->archive($subject);

        return ApiResponse::success('Matière archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $subject = Subject::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($subject->school_id);

        $subject = $this->service->restore($subject);

        return ApiResponse::success('Matière restaurée.', new SubjectResource($subject));
    }
}
