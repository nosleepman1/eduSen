<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreEvaluationRequest;
use App\Http\Requests\Api\V1\Academic\UpdateEvaluationRequest;
use App\Http\Resources\Academic\EvaluationResource;
use App\Models\AcademicPeriod;
use App\Models\Evaluation;
use App\Models\LessonSession;
use App\Models\TeachingAssignment;
use App\Services\Api\V1\Academic\EvaluationService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $service
    ) {}

    public function index(): JsonResponse
    {
        $evaluations = Evaluation::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->latest('evaluation_date')
            ->paginate(20);

        return ApiResponse::success('Liste des évaluations.', EvaluationResource::collection($evaluations));
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        return ApiResponse::success('Évaluation.', new EvaluationResource($evaluation));
    }

    public function byTeachingAssignment(string $teachingAssignmentId): JsonResponse
    {
        $assignment = TeachingAssignment::findOrFail($teachingAssignmentId);
        TenantHelper::assertOwnedByCurrentSchool($assignment->school_id);

        return ApiResponse::success('Évaluations de cette affectation.', EvaluationResource::collection($assignment->evaluations));
    }

    public function byAcademicPeriod(string $academicPeriodId): JsonResponse
    {
        $period = AcademicPeriod::findOrFail($academicPeriodId);
        TenantHelper::assertOwnedByCurrentSchool($period->school_id);

        return ApiResponse::success('Évaluations de cette période.', EvaluationResource::collection($period->evaluations));
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $period = AcademicPeriod::findOrFail($request->validated('academic_period_id'));
        TenantHelper::assertOwnedByCurrentSchool($period->school_id);

        $assignment = TeachingAssignment::findOrFail($request->validated('teaching_assignment_id'));
        TenantHelper::assertOwnedByCurrentSchool($assignment->school_id);

        $lessonSessionId = $request->validated('lesson_session_id');
        $lessonSession = $lessonSessionId ? LessonSession::findOrFail($lessonSessionId) : null;

        [$evaluation, $warning] = $this->service->create($request->validated(), $period, $assignment, $lessonSession);

        return ApiResponse::success('Évaluation créée.', [
            'evaluation' => new EvaluationResource($evaluation),
            'warning' => $warning,
        ], 201);
    }

    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $evaluation = $this->service->update($evaluation, $request->validated());

        return ApiResponse::success('Évaluation mise à jour.', new EvaluationResource($evaluation));
    }

    public function open(Evaluation $evaluation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $evaluation = $this->service->open($evaluation);

        return ApiResponse::success('Saisie des notes ouverte.', new EvaluationResource($evaluation));
    }

    public function close(Evaluation $evaluation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $evaluation = $this->service->close($evaluation);

        return ApiResponse::success('Saisie des notes clôturée.', new EvaluationResource($evaluation));
    }

    public function lock(Evaluation $evaluation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $evaluation = $this->service->lock($evaluation);

        return ApiResponse::success('Évaluation verrouillée.', new EvaluationResource($evaluation));
    }

    public function destroy(Evaluation $evaluation): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $this->service->archive($evaluation);

        return ApiResponse::success('Évaluation archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $evaluation = Evaluation::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($evaluation->school_id);

        $evaluation = $this->service->restore($evaluation);

        return ApiResponse::success('Évaluation restaurée.', new EvaluationResource($evaluation));
    }
}
