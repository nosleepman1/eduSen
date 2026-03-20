<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\School\StoreTeacherRequest;
use App\Http\Requests\Api\V1\School\UpdateTeacherRequest;
use App\Http\Resources\School\TeacherResource;
use App\Models\Personnel;
use App\Models\Teacher;
use App\Services\Api\V1\School\TeacherService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class TeacherController extends Controller
{
    public function __construct(
        private readonly TeacherService $service
    ) {}

    public function index(): JsonResponse
    {
        $teachers = Teacher::query()
            ->whereHas('personnel', fn ($query) => $query->where('school_id', TenantHelper::currentSchoolId()))
            ->with('personnel.user')
            ->paginate(15);

        return ApiResponse::success('Liste des enseignants.', TeacherResource::collection($teachers));
    }

    public function show(Teacher $teacher): JsonResponse
    {
        $teacher->loadMissing('personnel');
        TenantHelper::assertOwnedByCurrentSchool($teacher->personnel?->school_id);

        return ApiResponse::success('Enseignant.', new TeacherResource($teacher));
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $personnel = Personnel::findOrFail($request->validated('personnel_id'));

        $teacher = $this->service->create($personnel, $request->validated());

        return ApiResponse::success('Profil enseignant créé.', new TeacherResource($teacher), 201);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher->loadMissing('personnel');
        TenantHelper::assertOwnedByCurrentSchool($teacher->personnel?->school_id);

        $teacher = $this->service->update($teacher, $request->validated());

        return ApiResponse::success('Enseignant mis à jour.', new TeacherResource($teacher));
    }

    public function destroy(Teacher $teacher): JsonResponse
    {
        $teacher->loadMissing('personnel');
        TenantHelper::assertOwnedByCurrentSchool($teacher->personnel?->school_id);

        $this->service->archive($teacher);

        return ApiResponse::success('Enseignant archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $personnel = $teacher->personnel()->withTrashed()->first();
        TenantHelper::assertOwnedByCurrentSchool($personnel?->school_id);

        $teacher = $this->service->restore($teacher);

        return ApiResponse::success('Enseignant restauré.', new TeacherResource($teacher));
    }
}
