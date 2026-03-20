<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\CancelLessonSessionRequest;
use App\Http\Requests\Api\V1\Academic\PostponeLessonSessionRequest;
use App\Http\Requests\Api\V1\Academic\ValidateLessonSessionRequest;
use App\Http\Resources\Academic\LessonSessionResource;
use App\Models\LessonSession;
use App\Services\Api\V1\Academic\LessonSessionService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class LessonSessionController extends Controller
{
    public function __construct(
        private readonly LessonSessionService $service
    ) {}

    public function index(): JsonResponse
    {
        $sessions = LessonSession::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with('scheduleEntry')
            ->orderBy('session_date')
            ->paginate(20);

        $sessions->getCollection()->transform(fn (LessonSession $session) => $this->service->tick($session));

        return ApiResponse::success('Liste des séances.', LessonSessionResource::collection($sessions));
    }

    public function show(LessonSession $lessonSession): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $this->service->tick($lessonSession);

        return ApiResponse::success('Séance.', new LessonSessionResource($lessonSession->load('scheduleEntry')));
    }

    public function today(): JsonResponse
    {
        $sessions = LessonSession::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->today()
            ->with('scheduleEntry')
            ->get()
            ->map(fn (LessonSession $session) => $this->service->tick($session));

        return ApiResponse::success("Séances du jour.", LessonSessionResource::collection($sessions));
    }

    public function start(LessonSession $lessonSession): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $lessonSession = $this->service->start($lessonSession);

        return ApiResponse::success('Séance démarrée.', new LessonSessionResource($lessonSession));
    }

    public function finish(LessonSession $lessonSession): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $lessonSession = $this->service->finish($lessonSession);

        return ApiResponse::success('Séance terminée.', new LessonSessionResource($lessonSession));
    }

    public function cancel(CancelLessonSessionRequest $request, LessonSession $lessonSession): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $lessonSession = $this->service->cancel($lessonSession, $request->validated('reason'), $request->validated('comment'));

        return ApiResponse::success('Séance annulée.', new LessonSessionResource($lessonSession));
    }

    public function postpone(PostponeLessonSessionRequest $request, LessonSession $lessonSession): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $lessonSession = $this->service->postpone($lessonSession, $request->validated('comment'));

        return ApiResponse::success('Séance reportée.', new LessonSessionResource($lessonSession));
    }

    public function validateSession(ValidateLessonSessionRequest $request, LessonSession $lessonSession): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $lessonSession = $this->service->validate(
            $lessonSession,
            $request->validated('decision'),
            $request->validated('reason'),
            $request->validated('comment')
        );

        return ApiResponse::success('Séance validée administrativement.', new LessonSessionResource($lessonSession));
    }

    public function restore(string $id): JsonResponse
    {
        $lessonSession = LessonSession::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($lessonSession->school_id);

        $lessonSession = $this->service->restore($lessonSession);

        return ApiResponse::success('Séance restaurée.', new LessonSessionResource($lessonSession));
    }
}
