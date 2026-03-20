<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\AssignPrincipalTeacherRequest;
use App\Http\Requests\Api\V1\Academic\StoreClassroomRequest;
use App\Http\Requests\Api\V1\Academic\UpdateClassroomRequest;
use App\Http\Resources\Academic\ClassroomResource;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Personnel;
use App\Services\Api\V1\Academic\ClassroomService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class ClassroomController extends Controller
{
    public function __construct(
        private readonly ClassroomService $service
    ) {}

    public function index(): JsonResponse
    {
        $classrooms = Classroom::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with(['level', 'specialization'])
            ->orderBy('name')
            ->paginate(20);

        return ApiResponse::success('Liste des classes.', ClassroomResource::collection($classrooms));
    }

    public function show(Classroom $classroom): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $classroom->loadMissing(['level', 'specialization']);

        return ApiResponse::success('Classe.', new ClassroomResource($classroom));
    }

    public function store(StoreClassroomRequest $request): JsonResponse
    {
        $level = Level::findOrFail($request->validated('level_id'));

        $classroom = $this->service->create($request->validated(), $level);

        return ApiResponse::success('Classe créée.', new ClassroomResource($classroom), 201);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $classroom = $this->service->update($classroom, $request->validated());

        return ApiResponse::success('Classe mise à jour.', new ClassroomResource($classroom));
    }

    public function open(Classroom $classroom): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $classroom = $this->service->open($classroom);

        return ApiResponse::success('Classe ouverte.', new ClassroomResource($classroom));
    }

    public function close(Classroom $classroom): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $classroom = $this->service->close($classroom);

        return ApiResponse::success('Classe fermée.', new ClassroomResource($classroom));
    }

    public function assignPrincipalTeacher(AssignPrincipalTeacherRequest $request, Classroom $classroom): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $personnelId = $request->validated('main_teacher_id');
        $personnel = $personnelId ? Personnel::findOrFail($personnelId) : null;

        $classroom = $this->service->assignPrincipalTeacher($classroom, $personnel);

        return ApiResponse::success('Professeur principal assigné.', new ClassroomResource($classroom));
    }

    public function destroy(Classroom $classroom): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $this->service->archive($classroom);

        return ApiResponse::success('Classe archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $classroom = Classroom::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $classroom = $this->service->restore($classroom);

        return ApiResponse::success('Classe restaurée.', new ClassroomResource($classroom));
    }
}
