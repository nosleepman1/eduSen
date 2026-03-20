<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\DuplicateCurriculumRequest;
use App\Http\Requests\Api\V1\Academic\StoreCurriculumRequest;
use App\Http\Requests\Api\V1\Academic\UpdateCurriculumRequest;
use App\Http\Resources\Academic\CurriculumResource;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Level;
use App\Services\Api\V1\Academic\CurriculumService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class CurriculumController extends Controller
{
    public function __construct(
        private readonly CurriculumService $service
    ) {}

    public function index(): JsonResponse
    {
        $curriculums = Curriculum::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderBy('display_order')
            ->paginate(20);

        return ApiResponse::success('Liste des programmes pédagogiques.', CurriculumResource::collection($curriculums));
    }

    public function show(Curriculum $curriculum): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($curriculum->school_id);

        return ApiResponse::success('Programme pédagogique.', new CurriculumResource($curriculum));
    }

    public function store(StoreCurriculumRequest $request): JsonResponse
    {
        $level = Level::findOrFail($request->validated('level_id'));

        $curriculum = $this->service->create($request->validated(), $level);

        return ApiResponse::success('Programme pédagogique créé.', new CurriculumResource($curriculum), 201);
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($curriculum->school_id);

        $curriculum = $this->service->update($curriculum, $request->validated());

        return ApiResponse::success('Programme pédagogique mis à jour.', new CurriculumResource($curriculum));
    }

    public function destroy(Curriculum $curriculum): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($curriculum->school_id);

        $this->service->archive($curriculum);

        return ApiResponse::success('Programme pédagogique archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $curriculum = Curriculum::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($curriculum->school_id);

        $curriculum = $this->service->restore($curriculum);

        return ApiResponse::success('Programme pédagogique restauré.', new CurriculumResource($curriculum));
    }

    public function duplicate(DuplicateCurriculumRequest $request): JsonResponse
    {
        $from = AcademicYear::findOrFail($request->validated('from_academic_year_id'));
        $to = AcademicYear::findOrFail($request->validated('to_academic_year_id'));

        $result = $this->service->duplicate($from, $to);

        return ApiResponse::success('Programme pédagogique dupliqué.', [
            'created' => CurriculumResource::collection($result['created']),
            'skipped' => $result['skipped'],
        ]);
    }
}
