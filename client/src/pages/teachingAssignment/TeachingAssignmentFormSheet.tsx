import { useMemo } from 'react';
import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Label } from '@/components/ui/label';
import { useClassroomList } from '@/hooks/useClassrooms';
import { useCurriculumList } from '@/hooks/useCurriculums';
import { useSubjectList } from '@/hooks/useSubjects';
import { useTeacherList } from '@/hooks/useTeachers';
import { useCreateTeachingAssignment } from '@/hooks/useTeachingAssignments';
import { ROLE_LABELS } from '@/types/teachingAssignment.types';

const schema = z.object({
  classroom_id: z.string().uuid('Sélectionnez une classe.'),
  curriculum_id: z.string().uuid('Sélectionnez une matière.'),
  teacher_id: z.string().uuid('Sélectionnez un enseignant.'),
  role: z.enum(['PRINCIPAL', 'SUBSTITUTE', 'CO_TEACHER']),
});

const KNOWN_FIELDS = ['classroom_id', 'curriculum_id', 'teacher_id', 'role', 'started_at'] as const;

interface TeachingAssignmentFormSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * The server checks classroom/curriculum compatibility (same year, level,
 * specialization — RTA-005) at submit time; this picker doesn't pre-filter
 * curriculums by the chosen classroom, so an incompatible pair is still
 * possible to select and will come back as a 422 rather than being blocked
 * client-side. Curriculum has no subject name of its own — resolved via
 * /academic/subjects, same as CurriculumPage's own list.
 */
export function TeachingAssignmentFormSheet({ open, onOpenChange }: TeachingAssignmentFormSheetProps) {
  const { data: classrooms } = useClassroomList(1);
  const { data: curriculums } = useCurriculumList(1);
  const { data: subjects } = useSubjectList();
  const { data: teachers } = useTeacherList(1);
  const create = useCreateTeachingAssignment();

  const subjectNames = useMemo(() => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])), [subjects]);
  const activeClassrooms = (classrooms?.data ?? []).filter((c) => c.status === 'ACTIVE');

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Nouvelle affectation"
      schema={schema}
      defaultValues={{ classroom_id: '', curriculum_id: '', teacher_id: '', role: 'PRINCIPAL' }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => create.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="classroom_id">Classe</Label>
            <select
              id="classroom_id"
              {...form.register('classroom_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {activeClassrooms.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.full_name}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.classroom_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="curriculum_id">Matière</Label>
            <select
              id="curriculum_id"
              {...form.register('curriculum_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(curriculums?.data ?? []).map((c) => (
                <option key={c.id} value={c.id}>
                  {subjectNames.get(c.subject_id) ?? c.subject_id}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.curriculum_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="teacher_id">Enseignant</Label>
            <select
              id="teacher_id"
              {...form.register('teacher_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(teachers?.data ?? []).map((t) => (
                <option key={t.id} value={t.id}>
                  {t.full_name} {t.speciality ? `(${t.speciality})` : ''}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.teacher_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="role">Rôle</Label>
            <select
              id="role"
              {...form.register('role')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              {Object.entries(ROLE_LABELS).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </div>
        </>
      )}
    </FormSheet>
  );
}
