<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\GenerateBulletinRequest;
use App\Http\Requests\Api\V1\Academic\GenerateClassBulletinsRequest;
use App\Http\Requests\Api\V1\Academic\ValidateBulletinRequest;
use App\Http\Resources\Academic\BulletinResource;
use App\Jobs\GenerateClassBulletinsJob;
use App\Models\AcademicPeriod;
use App\Models\Bulletin;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Services\Api\V1\Academic\BulletinService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulletinController extends Controller
{
    public function __construct(
        private readonly BulletinService $service
    ) {}

    public function index(): JsonResponse
    {
        $bulletins = Bulletin::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with('lines')
            ->latest('generated_at')
            ->paginate(20);

        return ApiResponse::success('Liste des bulletins.', BulletinResource::collection($bulletins));
    }

    public function show(Bulletin $bulletin): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        return ApiResponse::success('Bulletin.', new BulletinResource($bulletin->load('lines')));
    }

    public function byEnrollment(string $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        return ApiResponse::success("Bulletins de l'élève.", BulletinResource::collection($enrollment->bulletins()->with('lines')->get()));
    }

    public function byAcademicPeriod(string $academicPeriodId): JsonResponse
    {
        $period = AcademicPeriod::findOrFail($academicPeriodId);
        TenantHelper::assertOwnedByCurrentSchool($period->school_id);

        return ApiResponse::success('Bulletins de la période.', BulletinResource::collection($period->bulletins()->with('lines')->get()));
    }

    public function generate(GenerateBulletinRequest $request): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($request->validated('enrollment_id'));
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $period = AcademicPeriod::findOrFail($request->validated('academic_period_id'));
        TenantHelper::assertOwnedByCurrentSchool($period->school_id);

        $bulletin = $this->service->generateFor($enrollment, $period);

        return ApiResponse::success('Bulletin généré.', new BulletinResource($bulletin->load('lines')), 201);
    }

    /**
     * RB-010: dispatched to the queue rather than run inline — the response
     * confirms the job was queued, not that every bulletin already exists.
     */
    public function generateClass(GenerateClassBulletinsRequest $request): JsonResponse
    {
        $classroom = Classroom::findOrFail($request->validated('classroom_id'));
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $period = AcademicPeriod::findOrFail($request->validated('academic_period_id'));
        TenantHelper::assertOwnedByCurrentSchool($period->school_id);

        GenerateClassBulletinsJob::dispatch($classroom->id, $period->id);

        return ApiResponse::success('Génération des bulletins de la classe mise en file d\'attente.', null, 202);
    }

    public function validateBulletin(ValidateBulletinRequest $request, Bulletin $bulletin): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        $bulletin = $this->service->validate($bulletin, $request->validated('class_council_comment'));

        return ApiResponse::success('Bulletin validé.', new BulletinResource($bulletin));
    }

    public function publish(Bulletin $bulletin): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        $bulletin = $this->service->publish($bulletin);

        return ApiResponse::success('Bulletin publié.', new BulletinResource($bulletin));
    }

    public function lock(Bulletin $bulletin): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        $bulletin = $this->service->lock($bulletin);

        return ApiResponse::success('Bulletin verrouillé.', new BulletinResource($bulletin));
    }

    public function destroy(Bulletin $bulletin): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        $this->service->archive($bulletin);

        return ApiResponse::success('Bulletin archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $bulletin = Bulletin::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        $bulletin = $this->service->restore($bulletin);

        return ApiResponse::success('Bulletin restauré.', new BulletinResource($bulletin));
    }

    /**
     * "Export PDF" per the spec's route contract — no PDF rendering library
     * (e.g. barryvdh/laravel-dompdf) is installed in this project, so this
     * returns the same structured JSON a PDF template would consume rather
     * than a binary file. Documented honestly instead of faking a download;
     * wiring an actual renderer is a follow-up once that dependency is added.
     */
    public function exportPdf(Bulletin $bulletin): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($bulletin->school_id);

        return ApiResponse::success(
            'Données du bulletin (rendu PDF non disponible : aucune librairie PDF installée).',
            new BulletinResource($bulletin->load(['lines', 'enrollment.student', 'academicPeriod']))
        );
    }

    public function classroomSummary(Request $request, string $classroomId): JsonResponse
    {
        $classroom = Classroom::findOrFail($classroomId);
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $period = AcademicPeriod::findOrFail($request->query('academic_period_id'));

        return ApiResponse::success('Statistiques de la classe.', $this->service->classroomSummary($classroom, $period));
    }
}
