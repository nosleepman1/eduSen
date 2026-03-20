import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateStudent, useUpdateStudent } from '@/hooks/useStudents';
import type { Student } from '@/types/student.types';

const schema = z.object({
  firstname: z.string().min(1, 'Le prénom est obligatoire.').max(255),
  lastname: z.string().min(1, 'Le nom est obligatoire.').max(255),
  birth_date: z.string().nullable().optional(),
  birth_place: z.string().max(255).nullable().optional(),
  // Only MALE/FEMALE: StoreStudentRequest rejects OTHER even though GenderEnum has it.
  gender: z.union([z.literal('MALE'), z.literal('FEMALE'), z.literal('')]).nullable().optional(),
  nationality: z.string().max(255).nullable().optional(),
  blood_group: z.string().max(10).nullable().optional(),
  medical_info: z.string().nullable().optional(),
  address: z.string().max(255).nullable().optional(),
});

const KNOWN_FIELDS = [
  'firstname',
  'lastname',
  'birth_date',
  'birth_place',
  'gender',
  'nationality',
  'blood_group',
  'medical_info',
  'address',
] as const;

interface StudentFormSheetProps {
  /** null closes the sheet; undefined (vs. an object) means "create". */
  student: Student | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/** One sheet for create and edit — student_number/status/enrolled_at are
 *  server-generated and never appear here (StoreStudentRequest/UpdateStudentRequest
 *  don't accept them). */
export function StudentFormSheet({ student, onOpenChange }: StudentFormSheetProps) {
  const isEditing = student !== null && student !== 'create';
  const create = useCreateStudent();
  const update = useUpdateStudent(isEditing ? student.id : '');

  return (
    <FormSheet
      open={student !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${student.full_name}` : 'Nouvel élève'}
      schema={schema}
      defaultValues={{
        firstname: isEditing ? student.firstname : '',
        lastname: isEditing ? student.lastname : '',
        birth_date: isEditing ? student.birth_date ?? '' : '',
        birth_place: isEditing ? student.birth_place ?? '' : '',
        gender: isEditing ? student.gender ?? '' : '',
        nationality: isEditing ? student.nationality ?? '' : 'Sénégalaise',
        blood_group: isEditing ? student.blood_group ?? '' : '',
        medical_info: isEditing ? student.medical_info ?? '' : '',
        address: isEditing ? student.address ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => {
        const payload = { ...values, gender: values.gender || null };
        return (isEditing ? update.mutateAsync(payload) : create.mutateAsync(payload)).then(() => undefined);
      }}
    >
      {(form) => (
        <>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstname">Prénom</Label>
              <Input id="firstname" {...form.register('firstname')} />
              <FieldError error={form.formState.errors.firstname} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="lastname">Nom</Label>
              <Input id="lastname" {...form.register('lastname')} />
              <FieldError error={form.formState.errors.lastname} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="birth_date">Date de naissance</Label>
              <Input id="birth_date" type="date" {...form.register('birth_date')} />
              <FieldError error={form.formState.errors.birth_date} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="birth_place">Lieu de naissance</Label>
              <Input id="birth_place" {...form.register('birth_place')} />
              <FieldError error={form.formState.errors.birth_place} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="gender">Genre</Label>
              <select
                id="gender"
                {...form.register('gender')}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
              >
                <option value="">Non précisé</option>
                <option value="MALE">Masculin</option>
                <option value="FEMALE">Féminin</option>
              </select>
              <FieldError error={form.formState.errors.gender} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="blood_group">Groupe sanguin</Label>
              <Input id="blood_group" {...form.register('blood_group')} />
              <FieldError error={form.formState.errors.blood_group} />
            </div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="nationality">Nationalité</Label>
            <Input id="nationality" {...form.register('nationality')} />
            <FieldError error={form.formState.errors.nationality} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="address">Adresse</Label>
            <Input id="address" {...form.register('address')} />
            <FieldError error={form.formState.errors.address} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="medical_info">Informations médicales</Label>
            <textarea
              id="medical_info"
              {...form.register('medical_info')}
              className="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
            />
            <FieldError error={form.formState.errors.medical_info} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
