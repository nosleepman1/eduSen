<?php

namespace App\Events;

use App\Models\AcademicYear;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcademicYearActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AcademicYear $academicYear
    ) {}
}
