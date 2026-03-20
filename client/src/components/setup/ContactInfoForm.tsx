import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FiPlus, FiTrash2 } from 'react-icons/fi';
import { useSchoolSetup } from '../../hooks/useSchoolSetup';

const schema = z.object({
  address: z.object({
    country: z.string().optional(),
    region: z.string().min(1, 'La région est requise'),
    department: z.string().optional(),
    city: z.string().min(1, 'La ville est requise'),
    commune: z.string().optional(),
    street: z.string().optional(),
  }),
  phones: z.array(z.object({
    phone_number: z.string().min(1, 'Le numéro est requis'),
    type: z.enum(['MOBILE', 'LANDLINE', 'WHATSAPP', 'EMERGENCY']),
    country_code: z.string().optional(),
    dial_code: z.string().optional(),
    is_primary: z.boolean().default(false),
  })).min(1, 'Au moins un numéro de téléphone est requis')
}).superRefine((data, ctx) => {
  const primaryCount = data.phones.filter(p => p.is_primary).length;
  if (primaryCount !== 1) {
    ctx.addIssue({
      code: z.ZodIssueCode.custom,
      message: 'Exactement un téléphone doit être marqué comme principal',
      path: ['phones']
    });
  }
});

type FormInput = z.input<typeof schema>;
type FormData = z.output<typeof schema>;

export default function ContactInfoForm({ onNext, onBack }: { onNext: () => void, onBack: () => void }) {
  const { saveContactInfo, isSavingContactInfo } = useSchoolSetup();

  const { register, control, handleSubmit, formState: { errors }, watch, setValue } = useForm<FormInput, unknown, FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      address: {
        country: 'Sénégal',
        region: '',
        city: '',
      },
      phones: [
        { phone_number: '', type: 'MOBILE', is_primary: true }
      ]
    }
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: 'phones'
  });

  const onSubmit = async (data: FormData) => {
    await saveContactInfo(data);
    onNext();
  };

  const setPrimary = (index: number) => {
    fields.forEach((_, i) => {
      setValue(`phones.${i}.is_primary`, i === index);
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      
      {/* Adresse */}
      <div className="space-y-4">
        <h3 className="text-lg font-semibold text-primary border-b pb-2">Adresse de l'établissement</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="region">Région *</Label>
            <Input id="region" {...register('address.region')} placeholder="Ex: Dakar" />
            {errors.address?.region && <p className="text-sm text-destructive">{errors.address.region.message}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="city">Ville *</Label>
            <Input id="city" {...register('address.city')} placeholder="Ex: Pikine" />
            {errors.address?.city && <p className="text-sm text-destructive">{errors.address.city.message}</p>}
          </div>

          <div className="space-y-2">
            <Label htmlFor="department">Département (optionnel)</Label>
            <Input id="department" {...register('address.department')} />
          </div>

          <div className="space-y-2">
            <Label htmlFor="commune">Commune (optionnel)</Label>
            <Input id="commune" {...register('address.commune')} />
          </div>

          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="street">Rue / Adresse détaillée (optionnel)</Label>
            <Input id="street" {...register('address.street')} placeholder="Ex: Quartier X, Rue Y" />
          </div>
        </div>
      </div>

      {/* Téléphones */}
      <div className="space-y-4">
        <div className="flex items-center justify-between border-b pb-2">
          <h3 className="text-lg font-semibold text-primary">Contacts Téléphoniques</h3>
          <Button 
            type="button" 
            variant="outline" 
            size="sm" 
            onClick={() => append({ phone_number: '', type: 'MOBILE', is_primary: false })}
            className="text-xs"
          >
            <FiPlus className="mr-1" /> Ajouter
          </Button>
        </div>
        
        {errors.phones?.root && <p className="text-sm text-destructive">{errors.phones.root.message}</p>}

        <div className="space-y-4">
          {fields.map((field, index) => {
            const isPrimary = watch(`phones.${index}.is_primary`);
            
            return (
              <div key={field.id} className={`p-4 rounded-lg border ${isPrimary ? 'border-primary bg-primary/5' : 'border-border bg-muted/20'} relative`}>
                <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                  
                  <div className="space-y-2 md:col-span-5">
                    <Label>Numéro de téléphone *</Label>
                    <Input {...register(`phones.${index}.phone_number`)} placeholder="Ex: +221 77 123 45 67" />
                    {errors.phones?.[index]?.phone_number && <p className="text-sm text-destructive">{errors.phones[index]?.phone_number?.message}</p>}
                  </div>

                  <div className="space-y-2 md:col-span-4">
                    <Label>Type *</Label>
                    <select 
                      {...register(`phones.${index}.type`)}
                      className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                      <option value="MOBILE">Mobile</option>
                      <option value="LANDLINE">Fixe</option>
                      <option value="WHATSAPP">WhatsApp</option>
                      <option value="EMERGENCY">Urgence</option>
                    </select>
                  </div>

                  <div className="md:col-span-3 flex items-center justify-end gap-2 pb-2">
                    <label className="flex items-center space-x-2 text-sm cursor-pointer mr-2">
                      <input 
                        type="radio" 
                        name="primary_phone" 
                        checked={isPrimary}
                        onChange={() => setPrimary(index)}
                        className="text-primary focus:ring-primary h-4 w-4"
                      />
                      <span>Principal</span>
                    </label>
                    
                    {fields.length > 1 && (
                      <Button 
                        type="button" 
                        variant="ghost" 
                        size="icon" 
                        onClick={() => remove(index)}
                        className="text-destructive hover:bg-destructive/10"
                      >
                        <FiTrash2 />
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      <div className="flex justify-between pt-4">
        <Button type="button" variant="outline" onClick={onBack}>
          Retour
        </Button>
        <Button type="submit" disabled={isSavingContactInfo} className="bg-primary text-primary-foreground">
          Enregistrer et continuer
        </Button>
      </div>
    </form>
  );
}
