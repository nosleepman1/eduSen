<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Academic\StoreTimetableRequest;
use App\Http\Requests\Api\V1\Academic\UpdateTimetableRequest;
use App\Http\Resources\Academic\TimetableResource;
use App\Models\Classroom;
use App\Models\Timetable;
use App\Services\Api\V1\Academic\TimetableService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;

class TimetableController extends Controller
{
    public function __construct(
        private readonly TimetableService $service
    ) {}

    public function index(): JsonResponse
    {
        $timetables = Timetable::query()
            ->where('school_id', TenantHelper::currentSchoolId())
            ->with('classroom')
            ->latest()
            ->paginate(20);

        return ApiResponse::success('Liste des emplois du temps.', TimetableResource::collection($timetables));
    }

    public function show(Timetable $timetable): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        return ApiResponse::success('Emploi du temps.', new TimetableResource($timetable->load('classroom')));
    }

    public function store(StoreTimetableRequest $request): JsonResponse
    {
        $classroom = Classroom::findOrFail($request->validated('classroom_id'));
        TenantHelper::assertOwnedByCurrentSchool($classroom->school_id);

        $timetable = $this->service->create($request->validated(), $classroom);

        return ApiResponse::success('Emploi du temps créé.', new TimetableResource($timetable), 201);
    }

    public function update(UpdateTimetableRequest $request, Timetable $timetable): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $timetable = $this->service->update($timetable, $request->validated());

        return ApiResponse::success('Emploi du temps mis à jour.', new TimetableResource($timetable));
    }

    public function activate(Timetable $timetable): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $timetable = $this->service->activate($timetable);

        return ApiResponse::success('Emploi du temps activé.', new TimetableResource($timetable));
    }

    public function duplicate(Timetable $timetable): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $duplicate = $this->service->duplicate($timetable);

        return ApiResponse::success('Emploi du temps dupliqué.', new TimetableResource($duplicate), 201);
    }

    public function destroy(Timetable $timetable): JsonResponse
    {
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $this->service->archive($timetable);

        return ApiResponse::success('Emploi du temps archivé.');
    }

    public function restore(string $id): JsonResponse
    {
        $timetable = Timetable::withTrashed()->findOrFail($id);
        TenantHelper::assertOwnedByCurrentSchool($timetable->school_id);

        $timetable = $this->service->restore($timetable);

        return ApiResponse::success('Emploi du temps restauré.', new TimetableResource($timetable));
    }
}
