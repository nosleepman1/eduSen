<?php

namespace App\Events;

use App\Models\AcademicPeriod;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcademicPeriodActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AcademicPeriod $academicPeriod
    ) {}
}
