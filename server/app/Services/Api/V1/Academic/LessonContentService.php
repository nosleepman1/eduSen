<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\LessonContentStatusEnum;
use App\Enums\LessonSessionStatusEnum;
use App\Models\LessonContent;
use App\Models\LessonSession;
use Illuminate\Validation\ValidationException;

class LessonContentService
{
    private const CREATABLE_SESSION_STATUSES = [
        LessonSessionStatusEnum::InProgress,
        LessonSessionStatusEnum::Completed,
    ];

    private function assertCreatable(LessonSession $session): void
    {
        if (! in_array($session->status, self::CREATABLE_SESSION_STATUSES, true)) {
            throw ValidationException::withMessages([
                'lesson_session_id' => 'Le contenu ne peut être renseigné que pendant ou après une séance (RLC-002).',
            ]);
        }
    }

    private function assertMutable(LessonContent $content): void
    {
        $content->loadMissing('lessonSession');

        if ($content->lessonSession->isArchived()) {
            throw ValidationException::withMessages([
                'content' => 'Cette séance est archivée, son contenu ne peut plus être modifié (RLC-003).',
            ]);
        }
    }

    public function create(array $data, LessonSession $session): LessonContent
    {
        $this->assertCreatable($session);

        return LessonContent::create([
            'school_id' => $session->school_id,
            'lesson_session_id' => $session->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'progress_number' => $data['progress_number'] ?? null,
            'status' => LessonContentStatusEnum::Active,
        ]);
    }

    public function update(LessonContent $content, array $data): LessonContent
    {
        $this->assertMutable($content);

        $content->update([
            'title' => $data['title'] ?? $content->title,
            'description' => $data['description'] ?? $content->description,
            'progress_number' => $data['progress_number'] ?? $content->progress_number,
        ]);

        return $content;
    }

    public function archive(LessonContent $content): void
    {
        $content->update(['status' => LessonContentStatusEnum::Archived]);
        $content->delete();
    }

    public function restore(LessonContent $content): LessonContent
    {
        $content->restore();
        $content->update(['status' => LessonContentStatusEnum::Active]);

        return $content;
    }
}
