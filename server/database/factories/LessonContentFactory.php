<?php

namespace Database\Factories;

use App\Models\LessonContent;
use App\Models\LessonSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonContentFactory extends Factory
{
    protected $model = LessonContent::class;

    public function definition(): array
    {
        $session = LessonSession::factory()->create(['status' => 'IN_PROGRESS']);

        return [
            'school_id' => $session->school_id,
            'lesson_session_id' => $session->id,
            'title' => 'Chapitre 1 — Fonctions linéaires',
            'description' => null,
            'progress_number' => 1,
            'status' => 'ACTIVE',
        ];
    }
}
