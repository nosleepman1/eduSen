import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FiLoader } from 'react-icons/fi';
import { academicYearService } from '@/services/academicYear.service';
import { academicPeriodService } from '@/services/academicPeriod.service';
import { GENERATION_MODE_LABELS } from '@/types/academicPeriod.types';
import { extractMessage } from '@/lib/apiErrors';

const currentYear = new Date().getFullYear();

const schema = z.object({
  name: z.string().min(1, "Le nom est requis"),
  start_date: z.string().min(1, 'La date de début est requise'),
  end_date: z.string().min(1, 'La date de fin est requise'),
  mode: z.enum(['SEMESTER', 'TRIMESTER', 'QUARTER']),
});

type FormData = z.infer<typeof schema>;

/**
 * Facultative : le backend n'exige que basicInfo + contactInfo pour finaliser
 * l'installation (SchoolSetupService::complete). Cette étape enchaîne
 * create -> activate -> generate-periods pour éviter au propriétaire de
 * refaire ces trois actions manuellement depuis trois écrans différents.
 */
export default function AcademicSetupForm({ onNext, onBack }: { onNext: () => void; onBack: () => void }) {
  const [isSubmitting, setIsSubmitting] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: `${currentYear}-${currentYear + 1}`,
      start_date: `${currentYear}-09-01`,
      end_date: `${currentYear + 1}-06-30`,
      mode: 'TRIMESTER',
    },
  });

  const onSubmit = async (data: FormData) => {
    setIsSubmitting(true);
    try {
      const created = await academicYearService.create({
        name: data.name,
        start_date: data.start_date,
        end_date: data.end_date,
      });
      const yearId = created.data.data.id;
      await academicYearService.activate(yearId);
      await academicPeriodService.generate(yearId, data.mode);
      toast.success('Année scolaire créée, activée et découpée en périodes.');
      onNext();
    } catch (error) {
      toast.error(extractMessage(error));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <p className="text-sm text-muted-foreground">
        Créez votre première année scolaire : elle sera activée et découpée en périodes automatiquement. Vous pourrez
        tout ajuster ensuite depuis "Années scolaires" et "Périodes académiques" — cette étape est facultative.
      </p>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2 md:col-span-2">
          <Label htmlFor="name">Nom de l'année *</Label>
          <Input id="name" {...register('name')} placeholder="Ex: 2026-2027" />
          {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
        </div>

        <div className="space-y-2">
          <Label htmlFor="start_date">Début *</Label>
          <Input id="start_date" type="date" {...register('start_date')} />
          {errors.start_date && <p className="text-sm text-destructive">{errors.start_date.message}</p>}
        </div>

        <div className="space-y-2">
          <Label htmlFor="end_date">Fin *</Label>
          <Input id="end_date" type="date" {...register('end_date')} />
          {errors.end_date && <p className="text-sm text-destructive">{errors.end_date.message}</p>}
        </div>

        <div className="space-y-2 md:col-span-2">
          <Label htmlFor="mode">Découpage des périodes *</Label>
          <select
            id="mode"
            {...register('mode')}
            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {Object.entries(GENERATION_MODE_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="flex justify-between pt-4">
        <Button type="button" variant="outline" onClick={onBack} disabled={isSubmitting}>
          Retour
        </Button>
        <div className="flex gap-2">
          <Button type="button" variant="ghost" onClick={onNext} disabled={isSubmitting}>
            Passer cette étape
          </Button>
          <Button type="submit" disabled={isSubmitting} className="bg-primary text-primary-foreground">
            {isSubmitting && <FiLoader className="mr-2 h-4 w-4 animate-spin" />}
            Créer et générer
          </Button>
        </div>
      </div>
    </form>
  );
}
