<?php

namespace App\Http\Controllers\Api\V1\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\School\LinkGuardianRequest;
use App\Http\Requests\Api\V1\School\StoreStudentRequest;
use App\Http\Requests\Api\V1\School\UpdateStudentRequest;
use App\Http\Resources\School\StudentResource;
use App\Models\Student;
use App\Services\Api\V1\School\StudentService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $students = Student::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%");
            }))
            ->orderBy('lastname')
            ->paginate(15);

        return ApiResponse::success('Liste des élèves.', StudentResource::collection($students));
    }

    public function show(Student $student): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        // index() deliberately doesn't load this (would be N+1 across a page
        // of 15); a single record's detail view is the one place `guardians`
        // is worth the extra query.
        return ApiResponse::success('Élève.', new StudentResource($student->load('guardians')));
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $school = TenantHelper::currentSchool();

        if (! $school) {
            return ApiResponse::error('Aucune école associée à cet utilisateur.', 403, 'NO_SCHOOL_ASSOCIATED');
        }

        $student = $this->service->create($request->validated(), $school);

        return ApiResponse::success('Élève créé.', new StudentResource($student), 201);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        $student = $this->service->update($student, $request->validated());

        return ApiResponse::success('Élève mis à jour.', new StudentResource($student));
    }

    public function destroy(Student $student): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        $this->service->archive($student);

        return ApiResponse::success('Élève archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $student = Student::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        $student = $this->service->restore($student);

        return ApiResponse::success('Élève restauré.', new StudentResource($student));
    }

    public function linkGuardian(LinkGuardianRequest $request, Student $student): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        $this->service->linkGuardian(
            $student,
            $request->validated('guardian_id'),
            $request->validated('relationship'),
            $request->boolean('is_primary')
        );

        return ApiResponse::success('Parent associé à l\'élève.', new StudentResource($student->fresh()->load('guardians')));
    }
}
