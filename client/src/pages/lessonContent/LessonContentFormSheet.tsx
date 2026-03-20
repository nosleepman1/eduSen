import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLessonSessionList } from '@/hooks/useLessonSessions';
import { useCreateLessonContent, useUpdateLessonContent } from '@/hooks/useLessonContents';
import type { LessonContent } from '@/types/lessonContent.types';

const schema = z.object({
  lesson_session_id: z.string().uuid('Sélectionnez une séance.'),
  title: z.string().min(1, 'Le titre est obligatoire.').max(255),
  description: z.string().nullable().optional(),
  progress_number: z.coerce.number().int().min(0).nullable().optional(),
});

const KNOWN_FIELDS = ['lesson_session_id', 'title', 'description', 'progress_number'] as const;

interface LessonContentFormSheetProps {
  content: LessonContent | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/**
 * lesson_session_id is not editable: absent from UpdateLessonContentRequest.
 * The server only accepts a session that is InProgress or Completed
 * (RLC-002) — not filtered here, a 422 surfaces it if the picker's session
 * is still Scheduled.
 */
export function LessonContentFormSheet({ content, onOpenChange }: LessonContentFormSheetProps) {
  const isEditing = content !== null && content !== 'create';
  const { data: sessions } = useLessonSessionList(1);
  const create = useCreateLessonContent();
  const update = useUpdateLessonContent(isEditing ? content.id : '');

  return (
    <FormSheet
      open={content !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${content.title}` : 'Nouveau contenu de cours'}
      schema={schema}
      defaultValues={{
        lesson_session_id: isEditing ? content.lesson_session_id : '',
        title: isEditing ? content.title : '',
        description: isEditing ? content.description ?? '' : '',
        progress_number: isEditing ? content.progress_number ?? undefined : undefined,
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) =>
        (isEditing
          ? update.mutateAsync({ title: values.title, description: values.description, progress_number: values.progress_number })
          : create.mutateAsync(values)
        ).then(() => undefined)
      }
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="lesson_session_id">Séance</Label>
            <select
              id="lesson_session_id"
              {...form.register('lesson_session_id')}
              disabled={isEditing}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:opacity-60"
            >
              <option value="">Sélectionner...</option>
              {(sessions?.data ?? []).map((s) => (
                <option key={s.id} value={s.id}>
                  {s.session_date} — {s.scheduled_start_time.slice(0, 5)}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.lesson_session_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="title">Titre / chapitre</Label>
            <Input id="title" placeholder="Chapitre 1 — Fonctions linéaires" {...form.register('title')} />
            <FieldError error={form.formState.errors.title} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="progress_number">Progression (semaine n°)</Label>
            <Input id="progress_number" type="number" min={0} {...form.register('progress_number')} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <textarea
              id="description"
              {...form.register('description')}
              className="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
            />
          </div>
        </>
      )}
    </FormSheet>
  );
}
