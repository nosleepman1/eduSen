<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\GeneratePeriodsRequest;
use App\Http\Requests\Api\V1\Academic\StoreAcademicPeriodRequest;
use App\Http\Requests\Api\V1\Academic\UpdateAcademicPeriodRequest;
use App\Http\Resources\Academic\AcademicPeriodResource;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Services\Api\V1\Academic\AcademicPeriodService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicPeriodController extends Controller
{
    public function __construct(
        private readonly AcademicPeriodService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $periods = AcademicPeriod::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->when($request->query('academic_year_id'), fn ($query, $yearId) => $query->where('academic_year_id', $yearId))
            ->orderBy('start_date')
            ->paginate(15);

        return ApiResponse::success('Liste des périodes académiques.', AcademicPeriodResource::collection($periods));
    }

    public function show(AcademicPeriod $academic_period): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_period->school_id);

        return ApiResponse::success('Période académique.', new AcademicPeriodResource($academic_period));
    }

    public function store(StoreAcademicPeriodRequest $request): JsonResponse
    {
        $year = AcademicYear::findOrFail($request->validated('academic_year_id'));

        $period = $this->service->create($request->validated(), $year);

        return ApiResponse::success('Période académique créée.', new AcademicPeriodResource($period), 201);
    }

    public function update(UpdateAcademicPeriodRequest $request, AcademicPeriod $academic_period): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_period->school_id);

        $period = $this->service->update($academic_period, $request->validated());

        return ApiResponse::success('Période académique mise à jour.', new AcademicPeriodResource($period));
    }

    public function activate(AcademicPeriod $academic_period): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_period->school_id);

        $period = $this->service->activate($academic_period);

        return ApiResponse::success('Période académique activée.', new AcademicPeriodResource($period));
    }

    public function close(AcademicPeriod $academic_period): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_period->school_id);

        $period = $this->service->close($academic_period);

        return ApiResponse::success('Période académique clôturée.', new AcademicPeriodResource($period));
    }

    public function destroy(AcademicPeriod $academic_period): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_period->school_id);

        $this->service->archive($academic_period);

        return ApiResponse::success('Période académique archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $period = AcademicPeriod::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($period->school_id);

        $period = $this->service->restore($period);

        return ApiResponse::success('Période académique restaurée.', new AcademicPeriodResource($period));
    }

    public function generate(GeneratePeriodsRequest $request, AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        $periods = $this->service->generateDefaultPeriods($academic_year, $request->validated('mode'));

        return ApiResponse::success('Périodes académiques générées.', AcademicPeriodResource::collection(collect($periods)), 201);
    }
}
