<?php

namespace App\Services\Api\V1\School;

use App\Notifications\SchoolSetupCompletedNotification;
use App\Models\School;
use App\Repositories\Api\V1\SchoolRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SchoolSetupService
{
    public function __construct(private SchoolRepository $schools) {}

    public function saveBasicInfo(School $school, array $data, ?UploadedFile $logo = null): array
    {
        if ($logo !== null) {
            $data['logo'] = $logo->store('schools/logos', 'public');
        }

        $details = $this->schools->updateDetails($school, $data);
        $school->forceFill(['setup_step' => max($school->setup_step, 1)])->save();

        return ['school' => $school->refresh(), 'details' => $details->refresh()];
    }

    

    public function saveContactInfo(School $school, array $data): array
    {
        $school->addresses()->updateOrCreate(['is_primary' => true], array_merge($data['address'], ['is_primary' => true]));
        $school->phones()->delete();
        $school->phones()->createMany($data['phones']);
        $school->forceFill(['setup_step' => max($school->setup_step, 2)])->save();

        return ['school' => $school->refresh()];
    }

    public function complete(School $school): School
    {
        if ($school->setup_step < 2) {
            throw ValidationException::withMessages(['setup_step' => 'Les deux etapes obligatoires doivent etre completees.']);
        }

        $school->forceFill([
            'is_school_setup' => true,
            'setup_completed_at' => now(),
        ])->save();

        if ($school->owner) {
            $school->owner->notify(new SchoolSetupCompletedNotification($school));
        }

        return $school->refresh();
    }
}
