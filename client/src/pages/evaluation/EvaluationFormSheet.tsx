import { useMemo } from 'react';
import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAcademicPeriodList } from '@/hooks/useAcademicPeriods';
import { useTeachingAssignmentList } from '@/hooks/useTeachingAssignments';
import { useTeacherList } from '@/hooks/useTeachers';
import { useCurriculumList } from '@/hooks/useCurriculums';
import { useSubjectList } from '@/hooks/useSubjects';
import { useCreateEvaluation } from '@/hooks/useEvaluations';
import { EVALUATION_TYPE_LABELS } from '@/types/evaluation.types';

const schema = z.object({
  academic_period_id: z.string().uuid('Sélectionnez une période.'),
  teaching_assignment_id: z.string().uuid('Sélectionnez une affectation.'),
  type: z.enum(['DEVOIR', 'EXAMEN']),
  title: z.string().min(1, 'Le titre est obligatoire.').max(255),
  evaluation_date: z.string().min(1, 'La date est obligatoire.'),
  max_score: z.coerce.number().min(0.01, 'Le barème doit être positif.'),
  // An empty optional number field submits '' — z.coerce.number() would
  // coerce that to 0 and then fail .min(0.01), blocking submission with no
  // visible error (FieldError only renders when the field itself is
  // touched). Route '' to undefined before coercion so leaving it blank
  // actually means "no coefficient" instead of "invalid coefficient".
  coefficient: z.preprocess(
    (val) => (val === '' || val === null ? undefined : val),
    z.coerce.number().min(0.01, 'Le coefficient doit être positif.').optional()
  ),
});

const KNOWN_FIELDS = [
  'academic_period_id',
  'teaching_assignment_id',
  'type',
  'title',
  'description',
  'evaluation_date',
  'max_score',
  'coefficient',
] as const;

interface EvaluationFormSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * The server checks the period is Active and the date falls within it
 * (REV-004/REV-005/REV-007) at submit time — not pre-validated here. A
 * second Examen for the same subject/period is a non-blocking warning
 * (toast), not a refusal.
 */
export function EvaluationFormSheet({ open, onOpenChange }: EvaluationFormSheetProps) {
  const { data: periods } = useAcademicPeriodList(1);
  const { data: assignments } = useTeachingAssignmentList(1);
  const { data: teachers } = useTeacherList(1);
  const { data: curriculums } = useCurriculumList(1);
  const { data: subjects } = useSubjectList();
  const create = useCreateEvaluation();

  const teacherNames = useMemo(() => new Map((teachers?.data ?? []).map((t) => [t.id, t.full_name])), [teachers]);
  const subjectNames = useMemo(() => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])), [subjects]);
  const curriculumSubject = useMemo(
    () => new Map((curriculums?.data ?? []).map((c) => [c.id, subjectNames.get(c.subject_id) ?? c.subject_id])),
    [curriculums, subjectNames]
  );

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Nouvelle évaluation"
      schema={schema}
      defaultValues={{
        academic_period_id: '',
        teaching_assignment_id: '',
        type: 'DEVOIR',
        title: '',
        evaluation_date: '',
        max_score: 20,
        coefficient: undefined,
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => create.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="academic_period_id">Période</Label>
            <select
              id="academic_period_id"
              {...form.register('academic_period_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(periods?.data ?? []).map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.academic_period_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="teaching_assignment_id">Affectation (matière / enseignant)</Label>
            <select
              id="teaching_assignment_id"
              {...form.register('teaching_assignment_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(assignments?.data ?? []).map((a) => (
                <option key={a.id} value={a.id}>
                  {curriculumSubject.get(a.curriculum_id) ?? a.curriculum_id} — {teacherNames.get(a.teacher_id) ?? a.teacher_id}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.teaching_assignment_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="type">Type</Label>
            <select
              id="type"
              {...form.register('type')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              {Object.entries(EVALUATION_TYPE_LABELS).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-2">
            <Label htmlFor="title">Titre</Label>
            <Input id="title" placeholder="Devoir 1" {...form.register('title')} />
            <FieldError error={form.formState.errors.title} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="evaluation_date">Date</Label>
            <Input id="evaluation_date" type="date" {...form.register('evaluation_date')} />
            <FieldError error={form.formState.errors.evaluation_date} />
          </div>
          <div className="flex gap-4">
            <div className="flex-1 space-y-2">
              <Label htmlFor="max_score">Barème</Label>
              <Input id="max_score" type="number" step="0.5" min={0.01} {...form.register('max_score')} />
              <FieldError error={form.formState.errors.max_score} />
            </div>
            <div className="flex-1 space-y-2">
              <Label htmlFor="coefficient">Coefficient (optionnel)</Label>
              <Input id="coefficient" type="number" step="0.5" min={0.01} {...form.register('coefficient')} />
              <FieldError error={form.formState.errors.coefficient} />
            </div>
          </div>
        </>
      )}
    </FormSheet>
  );
}
