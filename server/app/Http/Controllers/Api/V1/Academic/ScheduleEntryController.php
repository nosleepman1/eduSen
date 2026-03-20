<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreScheduleEntryRequest;
use App\Http\Requests\Api\V1\Academic\UpdateScheduleEntryRequest;
use App\Http\Resources\Academic\LessonSessionResource;
use App\Http\Resources\Academic\ScheduleEntryResource;
use App\Models\ScheduleEntry;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Services\Api\V1\Academic\LessonSessionService;
use App\Services\Api\V1\Academic\ScheduleEntryService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class ScheduleEntryController extends Controller
{
    public function __construct(
        private readonly ScheduleEntryService $service,
        private readonly LessonSessionService $lessonSessions
    ) {}

    public function index(): JsonResponse
    {
        $entries = ScheduleEntry::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with('teachingAssignment')
            ->paginate(20);

        return ApiResponse::success('Liste des créneaux.', ScheduleEntryResource::collection($entries));
    }

    public function show(ScheduleEntry $scheduleEntry): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($scheduleEntry->school_id);

        return ApiResponse::success('Créneau.', new ScheduleEntryResource($scheduleEntry->load('teachingAssignment')));
    }

    public function byTimetable(string $timetableId): JsonResponse
    {
        $timetable = Timetable::findOrFail($timetableId);
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $entries = $timetable->scheduleEntries()->with('teachingAssignment')->get();

        return ApiResponse::success("Créneaux de l'emploi du temps.", ScheduleEntryResource::collection($entries));
    }

    public function store(StoreScheduleEntryRequest $request): JsonResponse
    {
        $timetable = Timetable::findOrFail($request->validated('timetable_id'));
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $assignment = TeachingAssignment::findOrFail($request->validated('teaching_assignment_id'));
        TenantHelper::assertOwnedByCurrentSchool($assignment->school_id);

        [$entry, $warning] = $this->service->create($request->validated(), $timetable, $assignment);

        return ApiResponse::success('Créneau créé.', [
            'entry' => new ScheduleEntryResource($entry),
            'volume_warning' => $warning,
        ], 201);
    }

    public function update(UpdateScheduleEntryRequest $request, ScheduleEntry $scheduleEntry): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($scheduleEntry->school_id);

        [$entry, $warning] = $this->service->update($scheduleEntry, $request->validated());

        return ApiResponse::success('Créneau mis à jour.', [
            'entry' => new ScheduleEntryResource($entry),
            'volume_warning' => $warning,
        ]);
    }

    public function destroy(ScheduleEntry $scheduleEntry): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($scheduleEntry->school_id);

        $this->service->archive($scheduleEntry);

        return ApiResponse::success('Créneau archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $scheduleEntry = ScheduleEntry::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($scheduleEntry->school_id);

        $scheduleEntry = $this->service->restore($scheduleEntry);

        return ApiResponse::success('Créneau restauré.', new ScheduleEntryResource($scheduleEntry));
    }

    public function generateSessions(ScheduleEntry $scheduleEntry): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($scheduleEntry->school_id);

        $result = $this->lessonSessions->generateFor($scheduleEntry);

        return ApiResponse::success('Séances générées.', [
            'created' => LessonSessionResource::collection($result['created']),
            'skipped_holiday' => $result['skipped_holiday'],
            'skipped_existing' => $result['skipped_existing'],
        ], 201);
    }

    public function regenerate(ScheduleEntry $scheduleEntry): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($scheduleEntry->school_id);

        $result = $this->lessonSessions->regenerateFor($scheduleEntry);

        return ApiResponse::success('Séances futures régénérées.', [
            'created' => LessonSessionResource::collection($result['created']),
            'skipped_holiday' => $result['skipped_holiday'],
            'skipped_existing' => $result['skipped_existing'],
        ]);
    }
}
