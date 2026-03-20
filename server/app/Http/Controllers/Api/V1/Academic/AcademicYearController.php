<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreAcademicYearRequest;
use App\Http\Requests\Api\V1\Academic\UpdateAcademicYearRequest;
use App\Http\Resources\Academic\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\Api\V1\Academic\AcademicYearService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class AcademicYearController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $service
    ) {}

    public function index(): JsonResponse
    {
        $years = AcademicYear::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderByDesc('start_date')
            ->paginate(15);

        return ApiResponse::success('Liste des années scolaires.', AcademicYearResource::collection($years));
    }

    public function show(AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        return ApiResponse::success('Année scolaire.', new AcademicYearResource($academic_year));
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $year = $this->service->create($request->validated(), TenantHelper::currentSchoolId());

        return ApiResponse::success('Année scolaire créée.', new AcademicYearResource($year), 201);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        $year = $this->service->update($academic_year, $request->validated());

        return ApiResponse::success('Année scolaire mise à jour.', new AcademicYearResource($year));
    }

    public function activate(AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        $year = $this->service->activate($academic_year);

        return ApiResponse::success('Année scolaire activée.', new AcademicYearResource($year));
    }

    public function close(AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        $year = $this->service->close($academic_year);

        return ApiResponse::success('Année scolaire clôturée.', new AcademicYearResource($year));
    }

    public function reopen(AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        $year = $this->service->reopen($academic_year);

        return ApiResponse::success('Année scolaire réouverte.', new AcademicYearResource($year));
    }

    public function destroy(AcademicYear $academic_year): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($academic_year->school_id);

        $this->service->archive($academic_year);

        return ApiResponse::success('Année scolaire archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $year = AcademicYear::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($year->school_id);

        $year = $this->service->restore($year);

        return ApiResponse::success('Année scolaire restaurée.', new AcademicYearResource($year));
    }
}
