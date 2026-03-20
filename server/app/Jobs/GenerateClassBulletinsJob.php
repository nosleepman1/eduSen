<?php

namespace App\Jobs;

use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Services\Api\V1\Academic\BulletinService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * RB-010: mass bulletin generation for a whole classroom runs as a queued
 * job rather than inline in the request — the spec is explicit ("Job
 * Redis (traitement asynchrone)"). Runs on whatever QUEUE_CONNECTION is
 * configured; a queue:work process must be running to consume it in an
 * environment with a real queue driver, same as every other Laravel job.
 */
class GenerateClassBulletinsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $classroomId,
        private readonly string $academicPeriodId
    ) {}

    public function handle(BulletinService $service): void
    {
        $classroom = Classroom::findOrFail($this->classroomId);
        $period = AcademicPeriod::findOrFail($this->academicPeriodId);

        $service->generateForClass($classroom, $period);
    }
}
