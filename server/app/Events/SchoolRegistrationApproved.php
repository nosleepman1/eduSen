<?php

namespace App\Events;

use App\Models\School;
use App\Models\SchoolRegistrationRequest;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SchoolRegistrationApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SchoolRegistrationRequest $registrationRequest,
        public User $owner,
        public School $school,
        public string $token
    ) {}
}
