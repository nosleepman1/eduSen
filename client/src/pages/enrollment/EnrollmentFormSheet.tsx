import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Label } from '@/components/ui/label';
import { useClassroomList } from '@/hooks/useClassrooms';
import { useStudentList } from '@/hooks/useStudents';
import { useCreateEnrollment } from '@/hooks/useEnrollments';

const schema = z.object({
  classroom_id: z.string().uuid('Sélectionnez une classe.'),
  student_id: z.string().uuid('Sélectionnez un élève.'),
});

const KNOWN_FIELDS = ['classroom_id', 'student_id', 'enrolled_at'] as const;

interface EnrollmentFormSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * Only ACTIVE classrooms are offered — the server refuses a non-active
 * classroom anyway (RE-006), filtering here just avoids a guaranteed 422.
 * Both pickers only load their first page (same limitation as Classroom's
 * level picker) — no search box exists yet for either list in a dropdown.
 */
export function EnrollmentFormSheet({ open, onOpenChange }: EnrollmentFormSheetProps) {
  const { data: classrooms } = useClassroomList(1);
  const { data: students } = useStudentList(1, '');
  const create = useCreateEnrollment();

  const activeClassrooms = (classrooms?.data ?? []).filter((c) => c.status === 'ACTIVE');

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Nouvelle inscription"
      schema={schema}
      defaultValues={{ classroom_id: '', student_id: '' }}
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
            <Label htmlFor="student_id">Élève</Label>
            <select
              id="student_id"
              {...form.register('student_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(students?.data ?? []).map((s) => (
                <option key={s.id} value={s.id}>
                  {s.full_name} ({s.student_number})
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.student_id} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
