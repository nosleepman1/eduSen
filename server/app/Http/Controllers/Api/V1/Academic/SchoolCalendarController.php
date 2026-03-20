<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Requests\Api\V1\Academic\StoreHolidayEventRequest;
use App\Http\Requests\Api\V1\Academic\StoreSchoolCalendarRequest;
use App\Http\Requests\Api\V1\Academic\UpdateHolidayEventRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Academic\HolidayEventResource;
use App\Http\Resources\Academic\SchoolCalendarResource;
use App\Models\AcademicYear;
use App\Models\HolidayEvent;
use App\Models\SchoolCalendar;
use App\Services\Api\V1\Academic\SchoolCalendarService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class SchoolCalendarController extends Controller
{
    public function __construct(
        private readonly SchoolCalendarService $service
    ) {}

    public function index(): JsonResponse
    {
        $calendars = SchoolCalendar::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success('Liste des calendriers scolaires.', SchoolCalendarResource::collection($calendars));
    }

    public function show(SchoolCalendar $school_calendar): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($school_calendar->school_id);

        return ApiResponse::success('Calendrier scolaire.', new SchoolCalendarResource($school_calendar->load('holidayEvents')));
    }

    public function store(StoreSchoolCalendarRequest $request): JsonResponse
    {
        $year = AcademicYear::findOrFail($request->validated('academic_year_id'));

        $calendar = $this->service->createForYear($year, $request->validated('name'));

        return ApiResponse::success('Calendrier scolaire créé.', new SchoolCalendarResource($calendar), 201);
    }

    public function storeHoliday(StoreHolidayEventRequest $request, SchoolCalendar $school_calendar): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($school_calendar->school_id);

        $holiday = $this->service->addHoliday($school_calendar, $request->validated());

        return ApiResponse::success('Événement de calendrier créé.', new HolidayEventResource($holiday), 201);
    }

    public function updateHoliday(UpdateHolidayEventRequest $request, HolidayEvent $holiday): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($holiday->school_id);

        $holiday = $this->service->updateHoliday($holiday, $request->validated());

        return ApiResponse::success('Événement de calendrier mis à jour.', new HolidayEventResource($holiday));
    }

    public function destroyHoliday(HolidayEvent $holiday): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($holiday->school_id);

        $this->service->deleteHoliday($holiday);

        return ApiResponse::success('Événement de calendrier supprimé.');
    }
}
