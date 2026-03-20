<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\JustifyAttendanceRecordRequest;
use App\Http\Requests\Api\V1\Academic\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\Academic\UpdateAttendanceRecordRequest;
use App\Http\Resources\Academic\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\LessonSession;
use App\Services\Api\V1\Academic\AttendanceRecordService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class AttendanceRecordController extends Controller
{
    public function __construct(
        private readonly AttendanceRecordService $service
    ) {}

    public function index(): JsonResponse
    {
        $records = AttendanceRecord::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with('enrollment')
            ->paginate(20);

        return ApiResponse::success('Liste des présences.', AttendanceRecordResource::collection($records));
    }

    public function show(AttendanceRecord $attendanceRecord): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        return ApiResponse::success('Présence.', new AttendanceRecordResource($attendanceRecord->load('enrollment')));
    }

    public function byLessonSession(string $lessonSessionId): JsonResponse
    {
        $session = LessonSession::findOrFail($lessonSessionId);
        TenantHelper::assertOwnedByCurrentSchool($session->school_id);

        $records = $session->attendanceRecords()->with('enrollment')->get();

        return ApiResponse::success('Présences de la séance.', AttendanceRecordResource::collection($records));
    }

    public function bulkCreate(string $lessonSessionId): JsonResponse
    {
        $session = LessonSession::findOrFail($lessonSessionId);
        TenantHelper::assertOwnedByCurrentSchool($session->school_id);

        $created = $this->service->bulkCreate($session);

        return ApiResponse::success('Appel généré.', AttendanceRecordResource::collection($created), 201);
    }

    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $session = LessonSession::findOrFail($request->validated('lesson_session_id'));
        TenantHelper::assertOwnedByCurrentSchool($session->school_id);

        $enrollment = Enrollment::findOrFail($request->validated('enrollment_id'));
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        $record = $this->service->create($request->validated(), $session, $enrollment);

        return ApiResponse::success('Présence enregistrée.', new AttendanceRecordResource($record), 201);
    }

    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        $attendanceRecord = $this->service->update($attendanceRecord, $request->validated());

        return ApiResponse::success('Présence mise à jour.', new AttendanceRecordResource($attendanceRecord));
    }

    public function justify(JustifyAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        $attendanceRecord = $this->service->justify($attendanceRecord, $request->validated('justification'));

        return ApiResponse::success('Justification soumise.', new AttendanceRecordResource($attendanceRecord));
    }

    public function approveJustification(AttendanceRecord $attendanceRecord): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        $attendanceRecord = $this->service->approveJustification($attendanceRecord);

        return ApiResponse::success('Justification approuvée.', new AttendanceRecordResource($attendanceRecord));
    }

    public function rejectJustification(AttendanceRecord $attendanceRecord): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        $attendanceRecord = $this->service->rejectJustification($attendanceRecord);

        return ApiResponse::success('Justification rejetée.', new AttendanceRecordResource($attendanceRecord));
    }

    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        $this->service->archive($attendanceRecord);

        return ApiResponse::success('Présence archivée.');
    }

    public function restore(string $id): JsonResponse
    {
        $attendanceRecord = AttendanceRecord::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($attendanceRecord->school_id);

        $attendanceRecord = $this->service->restore($attendanceRecord);

        return ApiResponse::success('Présence restaurée.', new AttendanceRecordResource($attendanceRecord));
    }

    public function attendanceSummary(string $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        TenantHelper::assertOwnedByCurrentSchool($enrollment->school_id);

        return ApiResponse::success('Taux de présence.', $this->service->attendanceSummary($enrollment));
    }
}
