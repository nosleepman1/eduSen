<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreGradeRequest;
use App\Http\Requests\Api\V1\Academic\UpdateGradeRequest;
use App\Http\Resources\Academic\GradeResource;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Services\Api\V1\Academic\GradeService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function __construct(
        private readonly GradeService $service
    ) {}

    public function index(): JsonResponse
    {
        $grades = Grade::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->paginate(20);

        return ApiResponse::success('Liste des notes.', GradeResource::collection($grades));
    }

    public function show(Grade $grade): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($grade->school_id);

        return ApiResponse::success('Note.', new GradeResource($grade));
    }

    public function byEvaluation(string $evaluationId): JsonResponse
    {
        $evaluation = Evaluation::findOrFail($evaluationId);
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        return ApiResponse::success("Notes de l'évaluation.", GradeResource::collection($evaluation->grades));
    }

    public function byEnrollment(string $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        return ApiResponse::success("Notes de l'élève.", GradeResource::collection($enrollment->grades));
    }

    public function bulkCreate(string $evaluationId): JsonResponse
    {
        $evaluation = Evaluation::findOrFail($evaluationId);
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $created = $this->service->bulkGenerateFor($evaluation);

        return ApiResponse::success('Notes générées.', GradeResource::collection($created), 201);
    }

    public function store(StoreGradeRequest $request): JsonResponse
    {
        $evaluation = Evaluation::findOrFail($request->validated('evaluation_id'));
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $enrollment = Enrollment::findOrFail($request->validated('enrollment_id'));
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $grade = $this->service->create($request->validated(), $evaluation, $enrollment);

        return ApiResponse::success('Note enregistrée.', new GradeResource($grade), 201);
    }

    public function update(UpdateGradeRequest $request, Grade $grade): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($grade->school_id);

        $grade = $this->service->update($grade, $request->validated());

        return ApiResponse::success('Note mise à jour.', new GradeResource($grade));
    }

    public function destroy(Grade $grade): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($grade->school_id);

        $this->service->archive($grade);

        return ApiResponse::success('Note archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $grade = Grade::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($grade->school_id);

        $grade = $this->service->restore($grade);

        return ApiResponse::success('Note restaurée.', new GradeResource($grade));
    }

    public function averages(Request $request, string $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        return ApiResponse::success('Moyennes de l\'élève.', $this->service->averagesFor($enrollment, $request->query('academic_period_id')));
    }
}
