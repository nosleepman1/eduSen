<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\ReorderLevelsRequest;
use App\Http\Requests\Api\V1\Academic\StoreLevelRequest;
use App\Http\Requests\Api\V1\Academic\UpdateLevelRequest;
use App\Http\Resources\Academic\LevelResource;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Services\Api\V1\Academic\LevelService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class LevelController extends Controller
{
    public function __construct(
        private readonly LevelService $service
    ) {}

    public function index(): JsonResponse
    {
        $levels = Level::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderBy('display_order')
            ->paginate(20);

        return ApiResponse::success('Liste des niveaux.', LevelResource::collection($levels));
    }

    public function show(Level $level): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($level->school_id);

        return ApiResponse::success('Niveau.', new LevelResource($level));
    }

    public function store(StoreLevelRequest $request): JsonResponse
    {
        $year = AcademicYear::findOrFail($request->validated('academic_year_id'));

        $level = $this->service->create($request->validated(), $year);

        return ApiResponse::success('Niveau créé.', new LevelResource($level), 201);
    }

    public function update(UpdateLevelRequest $request, Level $level): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($level->school_id);

        $level = $this->service->update($level, $request->validated());

        return ApiResponse::success('Niveau mis à jour.', new LevelResource($level));
    }

    public function destroy(Level $level): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($level->school_id);

        $this->service->archive($level);

        return ApiResponse::success('Niveau archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $level = Level::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($level->school_id);

        $level = $this->service->restore($level);

        return ApiResponse::success('Niveau restauré.', new LevelResource($level));
    }

    public function reorder(ReorderLevelsRequest $request): JsonResponse
    {
        $year = AcademicYear::findOrFail($request->validated('academic_year_id'));

        $levels = $this->service->reorder($request->validated('ordered_ids'), $year);

        return ApiResponse::success('Niveaux réordonnés.', LevelResource::collection($levels));
    }
}
