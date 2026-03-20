<?php

namespace App\Services\Api\V1\Platform;

use App\Enums\PersonnelStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Events\SchoolRegistrationApproved;
use App\Models\Personnel;
use App\Models\School;
use App\Models\SchoolRegistrationRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\RegistrationRejectedNotification;
use App\Repositories\Api\V1\SchoolRepository;
use App\Repositories\Api\V1\UserRepository;
use App\Services\Api\V1\Billing\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class SchoolApprovalService
{
    public function __construct(
        private UserRepository $users,
        private SchoolRepository $schools,
        private SubscriptionService $subscriptionService,
    ) {}

    public function approve(SchoolRegistrationRequest $request, User $reviewer, ?string $planId = null): array
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['registration_request' => 'Cette demande a deja ete traitee.']);
        }

        return DB::transaction(function () use ($request, $reviewer, $planId): array {

            // 1. Create the Owner user (global identity — NO school_id)
            $owner = $this->users->create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'phone_number' => $request->phone,
                'status' => UserStatusEnum::Active,
                'password' => null,
                'password_changed_at' => null,
            ]);

            // 2. Create the School
            $school = $this->schools->create([
                'owner_id' => $owner->id,
                'name' => $request->school_name,
                'code' => School::generateCode(),
                'ninea' => $request->ninea,
                'email' => $request->school_email,
                'is_school_setup' => false,
                'setup_step' => 0,
            ]);

            // 3. Create Personnel record (Owner is the first employee)
            // Generate employee number via the service
            $registrationNumbers = app(\App\Services\Api\V1\School\RegistrationNumberService::class);
            $employeeNumber = $registrationNumbers->generate($school->id, 'EMPLOYEE');

            $personnel = Personnel::create([
                'school_id' => $school->id,
                'user_id' => $owner->id,
                'registration_number' => $employeeNumber,
                'hire_date' => now()->toDateString(),
                'department' => 'Direction',
                'position' => 'Propriétaire',
                'status' => PersonnelStatusEnum::Active,
            ]);

            // 4. Assign OWNER role in this school
            UserRole::create([
                'user_id' => $owner->id,
                'school_id' => $school->id,
                'role' => RoleEnum::Owner,
                'assigned_by' => $reviewer->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);

            // 5. Create school details and subscription
            $this->schools->createDetails($school);
            $subscription = $this->subscriptionService->assignTrial($school);

            // 6. Approve the request and generate password reset token
            $request->approve($reviewer);
            $token = Password::broker()->createToken($owner);

            event(new SchoolRegistrationApproved($request, $owner, $school, $token));

            return compact('owner', 'school', 'personnel', 'subscription');
        });
    }

    public function reject(SchoolRegistrationRequest $request, User $reviewer, string $reason): SchoolRegistrationRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['registration_request' => 'Cette demande a deja ete traitee.']);
        }

        $request->reject($reviewer, $reason);
        $request->notify(new RegistrationRejectedNotification($reason));

        return $request->refresh();
    }
}
