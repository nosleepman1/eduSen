<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\ReassignTeachingAssignmentRequest;
use App\Http\Requests\Api\V1\Academic\StoreTeachingAssignmentRequest;
use App\Http\Requests\Api\V1\Academic\UpdateTeachingAssignmentRequest;
use App\Http\Resources\Academic\TeachingAssignmentResource;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Services\Api\V1\Academic\TeachingAssignmentService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class TeachingAssignmentController extends Controller
{
    public function __construct(
        private readonly TeachingAssignmentService $service
    ) {}

    public function index(): JsonResponse
    {
        $assignments = TeachingAssignment::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with(['classroom', 'teacher'])
            ->latest('started_at')
            ->paginate(20);

        return ApiResponse::success('Liste des affectations pédagogiques.', TeachingAssignmentResource::collection($assignments));
    }

    public function show(TeachingAssignment $teachingAssignment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($teachingAssignment->school_id);

        return ApiResponse::success('Affectation pédagogique.', new TeachingAssignmentResource($teachingAssignment->load(['classroom', 'teacher'])));
    }

    public function store(StoreTeachingAssignmentRequest $request): JsonResponse
    {
        $classroom = Classroom::findOrFail($request->validated('classroom_id'));
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $curriculum = Curriculum::findOrFail($request->validated('curriculum_id'));
        TenantHelper::assertOwnedByCurrentSchool($curriculum->school_id);

        $teacher = Teacher::findOrFail($request->validated('teacher_id'));

        $assignment = $this->service->create($request->validated(), $classroom, $curriculum, $teacher);

        return ApiResponse::success('Affectation pédagogique créée.', new TeachingAssignmentResource($assignment), 201);
    }

    public function update(UpdateTeachingAssignmentRequest $request, TeachingAssignment $teachingAssignment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($teachingAssignment->school_id);

        $teachingAssignment = $this->service->update($teachingAssignment, $request->validated());

        return ApiResponse::success('Affectation pédagogique mise à jour.', new TeachingAssignmentResource($teachingAssignment));
    }

    public function reassign(ReassignTeachingAssignmentRequest $request, TeachingAssignment $teachingAssignment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($teachingAssignment->school_id);

        $newTeacher = Teacher::findOrFail($request->validated('teacher_id'));

        $newAssignment = $this->service->reassign($teachingAssignment, $newTeacher);

        return ApiResponse::success('Enseignant réaffecté.', new TeachingAssignmentResource($newAssignment));
    }

    public function close(TeachingAssignment $teachingAssignment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($teachingAssignment->school_id);

        $teachingAssignment = $this->service->close($teachingAssignment);

        return ApiResponse::success('Affectation pédagogique clôturée.', new TeachingAssignmentResource($teachingAssignment));
    }

    public function destroy(TeachingAssignment $teachingAssignment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($teachingAssignment->school_id);

        $this->service->archive($teachingAssignment);

        return ApiResponse::success('Affectation pédagogique archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $teachingAssignment = TeachingAssignment::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($teachingAssignment->school_id);

        $teachingAssignment = $this->service->restore($teachingAssignment);

        return ApiResponse::success('Affectation pédagogique restaurée.', new TeachingAssignmentResource($teachingAssignment));
    }

    public function workload(string $teacherId): JsonResponse
    {
        $teacher = Teacher::findOrFail($teacherId);
        TenantHelper::assertOwnedByCurrentSchool($teacher->school?->id);

        return ApiResponse::success('Charge horaire hebdomadaire.', [
            'teacher_id' => $teacher->id,
            'weekly_hours' => $this->service->workloadFor($teacher),
        ]);
    }
}
