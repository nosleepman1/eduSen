<?php

namespace App\Events;

use App\Models\SchoolRegistrationRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SchoolRegistrationRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SchoolRegistrationRequest $registrationRequest
    ) {}
}
