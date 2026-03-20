<?php

namespace App\Services\Api\V1\School;

use App\Enums\PersonnelStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Events\InvitationAccepted;
use App\Events\InvitationCreated;
use App\Models\Invitation;
use App\Models\Personnel;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvitationService
{
    public function __construct(
        private RegistrationNumberService $registrationNumbers
    ) {}

    public function create(array $data, string $schoolId, User $createdBy): Invitation
    {
        $invitation = Invitation::create([
            'school_id' => $schoolId,
            'email' => $data['email'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'role' => RoleEnum::from($data['role']),
            'token' => hash('sha256', Str::random(40)),
            'status' => 'PENDING',
            'expires_at' => now()->addDays(7),
            'created_by' => $createdBy->id,
        ]);

        event(new InvitationCreated($invitation));

        return $invitation;
    }

    public function accept(string $token, string $password): array
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            abort(400, 'Cette invitation a expiré.');
        }

        if ($invitation->isAccepted()) {
            abort(400, 'Cette invitation a déjà été acceptée.');
        }

        return DB::transaction(function () use ($invitation, $password) {
            // Find existing user or create a new one
            $user = User::firstOrCreate(
                ['email' => $invitation->email],
                [
                    'firstname' => $invitation->firstname,
                    'lastname' => $invitation->lastname,
                    'password' => Hash::make($password),
                    'status' => UserStatusEnum::Active,
                    'password_changed_at' => now(), // They just set their password
                ]
            );

            // Generate employee number
            $employeeNumber = $this->registrationNumbers->generate($invitation->school_id, 'EMPLOYEE');

            // Create personnel record
            $personnel = Personnel::create([
                'school_id' => $invitation->school_id,
                'user_id' => $user->id,
                'registration_number' => $employeeNumber,
                'hire_date' => now()->toDateString(),
                'status' => PersonnelStatusEnum::Active,
            ]);

            // Assign role
            UserRole::create([
                'user_id' => $user->id,
                'school_id' => $invitation->school_id,
                'role' => $invitation->role,
                'assigned_by' => $invitation->created_by,
                'assigned_at' => now(),
                'is_active' => true,
            ]);

            $invitation->markAccepted();

            event(new InvitationAccepted($invitation, $user, $personnel));

            return compact('user', 'personnel');
        });
    }

    public function cancel(Invitation $invitation): void
    {
        $invitation->markCancelled();
    }

    public function resend(Invitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            abort(400, 'L\'invitation a déjà été acceptée.');
        }

        $invitation->forceFill([
            'token' => hash('sha256', Str::random(40)),
            'expires_at' => now()->addDays(7),
            'status' => 'PENDING',
        ])->save();

        event(new InvitationCreated($invitation));
    }
}
