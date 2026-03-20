<?php

namespace App\Events;

use App\Models\Personnel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonnelCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Personnel $personnel
    ) {}
}
