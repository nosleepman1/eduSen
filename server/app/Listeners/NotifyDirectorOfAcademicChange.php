<?php

namespace App\Listeners;

use App\Events\AcademicPeriodActivated;
use App\Events\AcademicYearActivated;
use App\Events\AcademicYearClosed;
use App\Notifications\AcademicEventNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDirectorOfAcademicChange implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleAcademicYearActivated(AcademicYearActivated $event): void
    {
        $year = $event->academicYear;
        $year->loadMissing('school.owner');

        $year->school?->owner?->notify(new AcademicEventNotification(
            "Nouvelle année scolaire active — {$year->school->name}",
            "L'année scolaire <strong>{$year->name}</strong> est désormais l'année active de votre établissement.",
            ['Année scolaire' => $year->name, 'Du' => $year->start_date->format('d/m/Y'), 'Au' => $year->end_date->format('d/m/Y')]
        ));
    }

    public function handleAcademicYearClosed(AcademicYearClosed $event): void
    {
        $year = $event->academicYear;
        $year->loadMissing('school.owner');

        $year->school?->owner?->notify(new AcademicEventNotification(
            "Année scolaire clôturée — {$year->school->name}",
            "L'année scolaire <strong>{$year->name}</strong> a été clôturée et passe en lecture seule.",
            ['Année scolaire' => $year->name]
        ));
    }

    public function handleAcademicPeriodActivated(AcademicPeriodActivated $event): void
    {
        $period = $event->academicPeriod;
        $period->loadMissing('school.owner', 'academicYear');

        $period->school?->owner?->notify(new AcademicEventNotification(
            "Nouvelle période académique active — {$period->school->name}",
            "La période <strong>{$period->name}</strong> ({$period->academicYear->name}) est désormais active.",
            ['Période' => $period->name, 'Du' => $period->start_date->format('d/m/Y'), 'Au' => $period->end_date->format('d/m/Y')]
        ));
    }

    public function subscribe(): array
    {
        return [
            AcademicYearActivated::class => 'handleAcademicYearActivated',
            AcademicYearClosed::class => 'handleAcademicYearClosed',
            AcademicPeriodActivated::class => 'handleAcademicPeriodActivated',
        ];
    }
}
