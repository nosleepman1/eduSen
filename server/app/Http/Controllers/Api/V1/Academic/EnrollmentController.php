<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreEnrollmentRequest;
use App\Http\Requests\Api\V1\Academic\TransferEnrollmentRequest;
use App\Http\Requests\Api\V1\Academic\UpdateEnrollmentRequest;
use App\Http\Requests\Api\V1\Academic\WithdrawEnrollmentRequest;
use App\Http\Resources\Academic\EnrollmentResource;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Api\V1\Academic\EnrollmentService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $service
    ) {}

    public function index(): JsonResponse
    {
        $enrollments = Enrollment::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with(['classroom', 'student'])
            ->latest('enrolled_at')
            ->paginate(20);

        return ApiResponse::success('Liste des inscriptions.', EnrollmentResource::collection($enrollments));
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        return ApiResponse::success('Inscription.', new EnrollmentResource($enrollment->load(['classroom', 'student'])));
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $classroom = Classroom::findOrFail($request->validated('classroom_id'));
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $student = Student::findOrFail($request->validated('student_id'));
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        $enrollment = $this->service->create($request->validated(), $classroom, $student);

        return ApiResponse::success('Inscription créée.', new EnrollmentResource($enrollment), 201);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $enrollment = $this->service->update($enrollment, $request->validated());

        return ApiResponse::success('Inscription mise à jour.', new EnrollmentResource($enrollment));
    }

    public function transfer(TransferEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $destination = Classroom::findOrFail($request->validated('classroom_id'));
        TenantHelper::assertOwnedByCurrentSchool($destination->school_id);

        $newEnrollment = $this->service->transfer($enrollment, $destination);

        return ApiResponse::success('Élève transféré.', new EnrollmentResource($newEnrollment));
    }

    public function withdraw(WithdrawEnrollmentRequest $request, Enrollment $enrollment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $enrollment = $this->service->withdraw($enrollment, $request->validated('reason'));

        return ApiResponse::success('Inscription clôturée.', new EnrollmentResource($enrollment));
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $this->service->archive($enrollment);

        return ApiResponse::success('Inscription archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $enrollment = Enrollment::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $enrollment = $this->service->restore($enrollment);

        return ApiResponse::success('Inscription restaurée.', new EnrollmentResource($enrollment));
    }

    public function byClassroom(string $classroomId): JsonResponse
    {
        $classroom = Classroom::findOrFail($classroomId);
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $enrollments = $classroom->enrollments()->with('student')->paginate(20);

        return ApiResponse::success('Inscriptions de la classe.', EnrollmentResource::collection($enrollments));
    }

    public function byStudent(string $studentId): JsonResponse
    {
        $student = Student::findOrFail($studentId);
        TenantHelper::assertOwnedByCurrentSchool($student->school_id);

        $enrollments = $student->enrollments()->with('classroom')->latest('enrolled_at')->paginate(20);

        return ApiResponse::success("Historique des inscriptions de l'élève.", EnrollmentResource::collection($enrollments));
    }
}
