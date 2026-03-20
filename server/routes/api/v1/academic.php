<?php

use App\Http\Controllers\Api\V1\Academic\AcademicPeriodController;
use App\Http\Controllers\Api\V1\Academic\AcademicYearController;
use App\Http\Controllers\Api\V1\Academic\AttendanceRecordController;
use App\Http\Controllers\Api\V1\Academic\BulletinController;
use App\Http\Controllers\Api\V1\Academic\ClassroomController;
use App\Http\Controllers\Api\V1\Academic\CurriculumController;
use App\Http\Controllers\Api\V1\Academic\EnrollmentController;
use App\Http\Controllers\Api\V1\Academic\EvaluationController;
use App\Http\Controllers\Api\V1\Academic\GradeController;
use App\Http\Controllers\Api\V1\Academic\LessonContentController;
use App\Http\Controllers\Api\V1\Academic\LessonSessionController;
use App\Http\Controllers\Api\V1\Academic\LevelController;
use App\Http\Controllers\Api\V1\Academic\ScheduleEntryController;
use App\Http\Controllers\Api\V1\Academic\SchoolCalendarController;
use App\Http\Controllers\Api\V1\Academic\SpecializationController;
use App\Http\Controllers\Api\V1\Academic\SubjectController;
use App\Http\Controllers\Api\V1\Academic\TeachingAssignmentController;
use App\Http\Controllers\Api\V1\Academic\TimetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'isolate.school', 'subscription.active'])
    ->prefix('academic')
    ->group(function (): void {

        // ── Academic Years ───────────────────────────────────
        Route::middleware('permission:academic-year.view')->group(function (): void {
            Route::get('years', [AcademicYearController::class, 'index']);
            Route::get('years/{academic_year}', [AcademicYearController::class, 'show']);
        });

        Route::middleware('permission:academic-year.manage')->group(function (): void {
            Route::post('years', [AcademicYearController::class, 'store']);
            Route::put('years/{academic_year}', [AcademicYearController::class, 'update']);
            Route::patch('years/{academic_year}/activate', [AcademicYearController::class, 'activate']);
            Route::patch('years/{academic_year}/close', [AcademicYearController::class, 'close']);
            Route::patch('years/{academic_year}/reopen', [AcademicYearController::class, 'reopen']);
            Route::delete('years/{academic_year}', [AcademicYearController::class, 'destroy']);
            Route::patch('years/{id}/restore', [AcademicYearController::class, 'restore']);
            Route::post('years/{academic_year}/periods/generate', [AcademicPeriodController::class, 'generate']);
        });

        // ── Academic Periods ─────────────────────────────────
        Route::middleware('permission:academic-period.view')->group(function (): void {
            Route::get('periods', [AcademicPeriodController::class, 'index']);
            Route::get('periods/{academic_period}', [AcademicPeriodController::class, 'show']);
        });

        Route::middleware('permission:academic-period.manage')->group(function (): void {
            Route::post('periods', [AcademicPeriodController::class, 'store']);
            Route::put('periods/{academic_period}', [AcademicPeriodController::class, 'update']);
            Route::patch('periods/{academic_period}/activate', [AcademicPeriodController::class, 'activate']);
            Route::patch('periods/{academic_period}/close', [AcademicPeriodController::class, 'close']);
            Route::delete('periods/{academic_period}', [AcademicPeriodController::class, 'destroy']);
            Route::patch('periods/{id}/restore', [AcademicPeriodController::class, 'restore']);
        });

        // ── School Calendar & Holidays ────────────────────────
        Route::middleware('permission:school-calendar.view')->group(function (): void {
            Route::get('school-calendars', [SchoolCalendarController::class, 'index']);
            Route::get('school-calendars/{school_calendar}', [SchoolCalendarController::class, 'show']);
        });

        Route::middleware('permission:school-calendar.manage')->group(function (): void {
            Route::post('school-calendars', [SchoolCalendarController::class, 'store']);
            Route::post('school-calendars/{school_calendar}/holidays', [SchoolCalendarController::class, 'storeHoliday']);
            Route::put('school-calendars/holidays/{holiday}', [SchoolCalendarController::class, 'updateHoliday']);
            Route::delete('school-calendars/holidays/{holiday}', [SchoolCalendarController::class, 'destroyHoliday']);
        });

        // ── Specializations ───────────────────────────────────
        Route::middleware('permission:specialization.view')->group(function (): void {
            Route::get('specializations', [SpecializationController::class, 'index']);
            Route::get('specializations/{specialization}', [SpecializationController::class, 'show']);
        });

        Route::middleware('permission:specialization.manage')->group(function (): void {
            Route::post('specializations', [SpecializationController::class, 'store']);
            Route::put('specializations/{specialization}', [SpecializationController::class, 'update']);
            Route::delete('specializations/{specialization}', [SpecializationController::class, 'destroy']);
            Route::patch('specializations/{id}/restore', [SpecializationController::class, 'restore']);
        });

        // ── Levels ────────────────────────────────────────────
        Route::middleware('permission:level.view')->group(function (): void {
            Route::get('levels', [LevelController::class, 'index']);
            Route::get('levels/{level}', [LevelController::class, 'show']);
        });

        Route::middleware('permission:level.manage')->group(function (): void {
            Route::post('levels', [LevelController::class, 'store']);
            Route::patch('levels/reorder', [LevelController::class, 'reorder']);
            Route::put('levels/{level}', [LevelController::class, 'update']);
            Route::delete('levels/{level}', [LevelController::class, 'destroy']);
            Route::patch('levels/{id}/restore', [LevelController::class, 'restore']);
        });

        // ── Classrooms ────────────────────────────────────────
        Route::middleware('permission:classroom.view')->group(function (): void {
            Route::get('classrooms', [ClassroomController::class, 'index']);
            Route::get('classrooms/{classroom}', [ClassroomController::class, 'show']);
        });

        Route::middleware('permission:classroom.manage')->group(function (): void {
            Route::post('classrooms', [ClassroomController::class, 'store']);
            Route::put('classrooms/{classroom}', [ClassroomController::class, 'update']);
            Route::patch('classrooms/{classroom}/open', [ClassroomController::class, 'open']);
            Route::patch('classrooms/{classroom}/close', [ClassroomController::class, 'close']);
            Route::patch('classrooms/{classroom}/principal-teacher', [ClassroomController::class, 'assignPrincipalTeacher']);
            Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy']);
            Route::patch('classrooms/{id}/restore', [ClassroomController::class, 'restore']);
        });

        // ── Subjects ──────────────────────────────────────────
        Route::middleware('permission:subject.view')->group(function (): void {
            Route::get('subjects', [SubjectController::class, 'index']);
            Route::get('subjects/{subject}', [SubjectController::class, 'show']);
        });

        Route::middleware('permission:subject.manage')->group(function (): void {
            Route::post('subjects', [SubjectController::class, 'store']);
            Route::put('subjects/{subject}', [SubjectController::class, 'update']);
            Route::delete('subjects/{subject}', [SubjectController::class, 'destroy']);
            Route::patch('subjects/{id}/restore', [SubjectController::class, 'restore']);
        });

        // ── Curriculums ───────────────────────────────────────
        Route::middleware('permission:curriculum.view')->group(function (): void {
            Route::get('curriculums', [CurriculumController::class, 'index']);
            Route::get('curriculums/{curriculum}', [CurriculumController::class, 'show']);
        });

        Route::middleware('permission:curriculum.manage')->group(function (): void {
            Route::post('curriculums', [CurriculumController::class, 'store']);
            Route::post('curriculums/duplicate', [CurriculumController::class, 'duplicate']);
            Route::put('curriculums/{curriculum}', [CurriculumController::class, 'update']);
            Route::delete('curriculums/{curriculum}', [CurriculumController::class, 'destroy']);
            Route::patch('curriculums/{id}/restore', [CurriculumController::class, 'restore']);
        });

        // ── Enrollments ───────────────────────────────────────
        Route::middleware('permission:enrollment.view')->group(function (): void {
            Route::get('enrollments', [EnrollmentController::class, 'index']);
            Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
            Route::get('classrooms/{classroom}/enrollments', [EnrollmentController::class, 'byClassroom']);
            Route::get('students/{student}/enrollments', [EnrollmentController::class, 'byStudent']);
        });

        Route::middleware('permission:enrollment.manage')->group(function (): void {
            Route::post('enrollments', [EnrollmentController::class, 'store']);
            Route::put('enrollments/{enrollment}', [EnrollmentController::class, 'update']);
            Route::patch('enrollments/{enrollment}/transfer', [EnrollmentController::class, 'transfer']);
            Route::patch('enrollments/{enrollment}/withdraw', [EnrollmentController::class, 'withdraw']);
            Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
            Route::patch('enrollments/{id}/restore', [EnrollmentController::class, 'restore']);
        });

        // ── Teaching Assignments ──────────────────────────────
        Route::middleware('permission:teaching-assignment.view')->group(function (): void {
            Route::get('teaching-assignments', [TeachingAssignmentController::class, 'index']);
            Route::get('teaching-assignments/{teaching_assignment}', [TeachingAssignmentController::class, 'show']);
            Route::get('teachers/{teacher}/workload', [TeachingAssignmentController::class, 'workload']);
        });

        Route::middleware('permission:teaching-assignment.manage')->group(function (): void {
            Route::post('teaching-assignments', [TeachingAssignmentController::class, 'store']);
            Route::put('teaching-assignments/{teaching_assignment}', [TeachingAssignmentController::class, 'update']);
            Route::patch('teaching-assignments/{teaching_assignment}/reassign', [TeachingAssignmentController::class, 'reassign']);
            Route::patch('teaching-assignments/{teaching_assignment}/close', [TeachingAssignmentController::class, 'close']);
            Route::delete('teaching-assignments/{teaching_assignment}', [TeachingAssignmentController::class, 'destroy']);
            Route::patch('teaching-assignments/{id}/restore', [TeachingAssignmentController::class, 'restore']);
        });

        // ── Timetables ────────────────────────────────────────
        Route::middleware('permission:timetable.view')->group(function (): void {
            Route::get('timetables', [TimetableController::class, 'index']);
            Route::get('timetables/{timetable}', [TimetableController::class, 'show']);
        });

        Route::middleware('permission:timetable.manage')->group(function (): void {
            Route::post('timetables', [TimetableController::class, 'store']);
            Route::put('timetables/{timetable}', [TimetableController::class, 'update']);
            Route::patch('timetables/{timetable}/activate', [TimetableController::class, 'activate']);
            Route::post('timetables/{timetable}/duplicate', [TimetableController::class, 'duplicate']);
            Route::delete('timetables/{timetable}', [TimetableController::class, 'destroy']);
            Route::patch('timetables/{id}/restore', [TimetableController::class, 'restore']);
        });

        // ── Schedule Entries ──────────────────────────────────
        Route::middleware('permission:schedule-entry.view')->group(function (): void {
            Route::get('schedule-entries', [ScheduleEntryController::class, 'index']);
            Route::get('schedule-entries/{schedule_entry}', [ScheduleEntryController::class, 'show']);
            Route::get('timetables/{timetable}/schedule-entries', [ScheduleEntryController::class, 'byTimetable']);
        });

        Route::middleware('permission:schedule-entry.manage')->group(function (): void {
            Route::post('schedule-entries', [ScheduleEntryController::class, 'store']);
            Route::put('schedule-entries/{schedule_entry}', [ScheduleEntryController::class, 'update']);
            Route::delete('schedule-entries/{schedule_entry}', [ScheduleEntryController::class, 'destroy']);
            Route::patch('schedule-entries/{id}/restore', [ScheduleEntryController::class, 'restore']);
            Route::post('schedule-entries/{schedule_entry}/generate-sessions', [ScheduleEntryController::class, 'generateSessions']);
            Route::post('schedule-entries/{schedule_entry}/regenerate', [ScheduleEntryController::class, 'regenerate']);
        });

        // ── Lesson Sessions ────────────────────────────────────
        Route::middleware('permission:lesson-session.view')->group(function (): void {
            Route::get('lesson-sessions', [LessonSessionController::class, 'index']);
            Route::get('lesson-sessions/today', [LessonSessionController::class, 'today']);
            Route::get('lesson-sessions/{lesson_session}', [LessonSessionController::class, 'show']);
        });

        Route::middleware('permission:lesson-session.manage')->group(function (): void {
            Route::post('lesson-sessions/{lesson_session}/start', [LessonSessionController::class, 'start']);
            Route::post('lesson-sessions/{lesson_session}/finish', [LessonSessionController::class, 'finish']);
            Route::post('lesson-sessions/{lesson_session}/cancel', [LessonSessionController::class, 'cancel']);
            Route::post('lesson-sessions/{lesson_session}/postpone', [LessonSessionController::class, 'postpone']);
            Route::post('lesson-sessions/{lesson_session}/validate', [LessonSessionController::class, 'validateSession']);
            Route::patch('lesson-sessions/{id}/restore', [LessonSessionController::class, 'restore']);
        });

        // ── Attendance Records ────────────────────────────────
        Route::middleware('permission:attendance-record.view')->group(function (): void {
            Route::get('attendance-records', [AttendanceRecordController::class, 'index']);
            Route::get('attendance-records/{attendance_record}', [AttendanceRecordController::class, 'show']);
            Route::get('lesson-sessions/{lesson_session}/attendance', [AttendanceRecordController::class, 'byLessonSession']);
            Route::get('enrollments/{enrollment}/attendance-summary', [AttendanceRecordController::class, 'attendanceSummary']);
        });

        Route::middleware('permission:attendance-record.manage')->group(function (): void {
            Route::post('attendance-records', [AttendanceRecordController::class, 'store']);
            Route::put('attendance-records/{attendance_record}', [AttendanceRecordController::class, 'update']);
            Route::patch('attendance-records/{attendance_record}/justify', [AttendanceRecordController::class, 'justify']);
            Route::patch('attendance-records/{attendance_record}/approve-justification', [AttendanceRecordController::class, 'approveJustification']);
            Route::patch('attendance-records/{attendance_record}/reject-justification', [AttendanceRecordController::class, 'rejectJustification']);
            Route::delete('attendance-records/{attendance_record}', [AttendanceRecordController::class, 'destroy']);
            Route::patch('attendance-records/{id}/restore', [AttendanceRecordController::class, 'restore']);
            Route::post('lesson-sessions/{lesson_session}/attendance/bulk', [AttendanceRecordController::class, 'bulkCreate']);
        });

        // ── Lesson Contents ───────────────────────────────────
        Route::middleware('permission:lesson-content.view')->group(function (): void {
            Route::get('lesson-contents', [LessonContentController::class, 'index']);
            Route::get('lesson-contents/{lesson_content}', [LessonContentController::class, 'show']);
            Route::get('lesson-sessions/{lesson_session}/contents', [LessonContentController::class, 'byLessonSession']);
        });

        Route::middleware('permission:lesson-content.manage')->group(function (): void {
            Route::post('lesson-contents', [LessonContentController::class, 'store']);
            Route::put('lesson-contents/{lesson_content}', [LessonContentController::class, 'update']);
            Route::delete('lesson-contents/{lesson_content}', [LessonContentController::class, 'destroy']);
            Route::patch('lesson-contents/{id}/restore', [LessonContentController::class, 'restore']);
        });

        // ── Evaluations ────────────────────────────────────────
        Route::middleware('permission:evaluation.view')->group(function (): void {
            Route::get('evaluations', [EvaluationController::class, 'index']);
            Route::get('evaluations/{evaluation}', [EvaluationController::class, 'show']);
            Route::get('teaching-assignments/{teaching_assignment}/evaluations', [EvaluationController::class, 'byTeachingAssignment']);
            Route::get('academic-periods/{academic_period}/evaluations', [EvaluationController::class, 'byAcademicPeriod']);
        });

        Route::middleware('permission:evaluation.manage')->group(function (): void {
            Route::post('evaluations', [EvaluationController::class, 'store']);
            Route::put('evaluations/{evaluation}', [EvaluationController::class, 'update']);
            Route::patch('evaluations/{evaluation}/open', [EvaluationController::class, 'open']);
            Route::patch('evaluations/{evaluation}/close', [EvaluationController::class, 'close']);
            Route::patch('evaluations/{evaluation}/lock', [EvaluationController::class, 'lock']);
            Route::delete('evaluations/{evaluation}', [EvaluationController::class, 'destroy']);
            Route::patch('evaluations/{id}/restore', [EvaluationController::class, 'restore']);
        });

        // ── Grades ─────────────────────────────────────────────
        Route::middleware('permission:grade.view')->group(function (): void {
            Route::get('grades', [GradeController::class, 'index']);
            Route::get('grades/{grade}', [GradeController::class, 'show']);
            Route::get('evaluations/{evaluation}/grades', [GradeController::class, 'byEvaluation']);
            Route::get('enrollments/{enrollment}/grades', [GradeController::class, 'byEnrollment']);
            Route::get('enrollments/{enrollment}/averages', [GradeController::class, 'averages']);
        });

        Route::middleware('permission:grade.manage')->group(function (): void {
            Route::post('grades', [GradeController::class, 'store']);
            Route::put('grades/{grade}', [GradeController::class, 'update']);
            Route::delete('grades/{grade}', [GradeController::class, 'destroy']);
            Route::patch('grades/{id}/restore', [GradeController::class, 'restore']);
            Route::post('evaluations/{evaluation}/grades/bulk', [GradeController::class, 'bulkCreate']);
        });

        // ── Bulletins ──────────────────────────────────────────
        Route::middleware('permission:bulletin.view')->group(function (): void {
            Route::get('bulletins', [BulletinController::class, 'index']);
            Route::get('bulletins/{bulletin}', [BulletinController::class, 'show']);
            Route::get('enrollments/{enrollment}/bulletins', [BulletinController::class, 'byEnrollment']);
            Route::get('academic-periods/{academic_period}/bulletins', [BulletinController::class, 'byAcademicPeriod']);
            Route::get('bulletins/{bulletin}/export-pdf', [BulletinController::class, 'exportPdf']);
            Route::get('classrooms/{classroom}/bulletin-summary', [BulletinController::class, 'classroomSummary']);
        });

        Route::middleware('permission:bulletin.manage')->group(function (): void {
            Route::post('bulletins/generate', [BulletinController::class, 'generate']);
            Route::post('bulletins/generate-class', [BulletinController::class, 'generateClass']);
            Route::patch('bulletins/{bulletin}/validate', [BulletinController::class, 'validateBulletin']);
            Route::patch('bulletins/{bulletin}/publish', [BulletinController::class, 'publish']);
            Route::patch('bulletins/{bulletin}/lock', [BulletinController::class, 'lock']);
            Route::delete('bulletins/{bulletin}', [BulletinController::class, 'destroy']);
            Route::patch('bulletins/{id}/restore', [BulletinController::class, 'restore']);
        });
    });
