<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreLessonContentRequest;
use App\Http\Requests\Api\V1\Academic\UpdateLessonContentRequest;
use App\Http\Resources\Academic\LessonContentResource;
use App\Models\LessonContent;
use App\Models\LessonSession;
use App\Services\Api\V1\Academic\LessonContentService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class LessonContentController extends Controller
{
    public function __construct(
        private readonly LessonContentService $service
    ) {}

    public function index(): JsonResponse
    {
        $contents = LessonContent::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->latest()
            ->paginate(20);

        return ApiResponse::success('Liste des contenus pédagogiques.', LessonContentResource::collection($contents));
    }

    public function show(LessonContent $lessonContent): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonContent->school_id);

        return ApiResponse::success('Contenu pédagogique.', new LessonContentResource($lessonContent));
    }

    public function byLessonSession(string $lessonSessionId): JsonResponse
    {
        $session = LessonSession::findOrFail($lessonSessionId);
        TenantHelper::assertOwnedByCurrentSchool($session->school_id);

        $contents = $session->lessonContents()->get();

        return ApiResponse::success('Contenus de la séance.', LessonContentResource::collection($contents));
    }

    public function store(StoreLessonContentRequest $request): JsonResponse
    {
        $session = LessonSession::findOrFail($request->validated('lesson_session_id'));
        TenantHelper::assertOwnedByCurrentSchool($session->school_id);

        $content = $this->service->create($request->validated(), $session);

        return ApiResponse::success('Contenu pédagogique enregistré.', new LessonContentResource($content), 201);
    }

    public function update(UpdateLessonContentRequest $request, LessonContent $lessonContent): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonContent->school_id);

        $lessonContent = $this->service->update($lessonContent, $request->validated());

        return ApiResponse::success('Contenu pédagogique mis à jour.', new LessonContentResource($lessonContent));
    }

    public function destroy(LessonContent $lessonContent): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonContent->school_id);

        $this->service->archive($lessonContent);

        return ApiResponse::success('Contenu pédagogique archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $lessonContent = LessonContent::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($lessonContent->school_id);

        $lessonContent = $this->service->restore($lessonContent);

        return ApiResponse::success('Contenu pédagogique restauré.', new LessonContentResource($lessonContent));
    }
}
