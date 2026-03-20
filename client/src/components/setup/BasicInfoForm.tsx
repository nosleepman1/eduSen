import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FiUpload, FiCheckCircle } from 'react-icons/fi';
import { useSchoolSetup } from '../../hooks/useSchoolSetup';

const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
const ACCEPTED_IMAGE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/svg+xml"];

const schema = z.object({
  slogan: z.string().max(255).optional(),
  website: z.string().url('URL invalide').optional().or(z.literal('')),
  school_type: z.enum(['PRIMARY', 'MIDDLE', 'HIGH', 'MIXED'], { error: 'Le type est requis' }),
  sector: z.enum(['PRIVATE_SECULAR', 'PRIVATE_CATHOLIC', 'PRIVATE_ISLAMIC', 'FRANCO_ARABIC'], { error: 'Le secteur est requis' }),
  registration_number: z.string().optional(),
  founded_at: z.string().optional(),
});

type FormData = z.infer<typeof schema>;

const PALETTES = [
  { name: "Classique", primary: "#003366", secondary: "#FFB300" },
  { name: "Nature", primary: "#1B4332", secondary: "#D8F3DC" },
  { name: "Institution", primary: "#780000", secondary: "#FDF0D5" },
  { name: "Moderne", primary: "#3C096C", secondary: "#FF9E00" },
];

export default function BasicInfoForm({ onNext }: { onNext: () => void }) {
  const { saveBasicInfo, isSavingBasicInfo } = useSchoolSetup();
  const [logo, setLogo] = useState<File | null>(null);
  const [logoError, setLogoError] = useState<string | null>(null);
  const [selectedPalette, setSelectedPalette] = useState(PALETTES[0]);

  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      school_type: 'PRIMARY',
      sector: 'PRIVATE_SECULAR',
    }
  });

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > MAX_FILE_SIZE) {
        setLogoError("L'image ne doit pas dépasser 2MB.");
        setLogo(null);
      } else if (!ACCEPTED_IMAGE_TYPES.includes(file.type)) {
        setLogoError("Format non supporté (JPEG, PNG, SVG autorisés).");
        setLogo(null);
      } else {
        setLogoError(null);
        setLogo(file);
      }
    }
  };

  const onSubmit = async (data: FormData) => {
    const formData = new FormData();
    if (logo) formData.append('logo', logo);
    formData.append('primary_color', selectedPalette.primary);
    formData.append('secondary_color', selectedPalette.secondary);
    if (data.slogan) formData.append('slogan', data.slogan);
    if (data.website) formData.append('website', data.website);
    formData.append('school_type', data.school_type);
    formData.append('sector', data.sector);
    if (data.registration_number) formData.append('registration_number', data.registration_number);
    if (data.founded_at) formData.append('founded_at', data.founded_at);

    await saveBasicInfo(formData);
    onNext();
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {/* Logo Upload */}
        <div className="col-span-1 md:col-span-2">
          <Label className="mb-2 block">Logo de l'école (optionnel)</Label>
          <div className="flex items-center justify-center w-full">
            <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-primary/20 border-dashed rounded-lg cursor-pointer bg-muted/20 hover:bg-muted/50 transition-colors">
              <div className="flex flex-col items-center justify-center pt-5 pb-6">
                <FiUpload className="w-8 h-8 mb-3 text-muted-foreground" />
                <p className="mb-2 text-sm text-muted-foreground">
                  <span className="font-semibold text-primary">Cliquez pour télécharger</span> ou glissez-déposez
                </p>
                <p className="text-xs text-muted-foreground">SVG, PNG, JPG (MAX. 2MB)</p>
              </div>
              <Input type="file" className="hidden" accept=".jpg,.jpeg,.png,.svg" onChange={handleFileChange} />
            </label>
          </div>
          {logoError && <p className="text-sm text-destructive mt-1">{logoError}</p>}
          {logo && <p className="text-sm text-green-600 mt-1 flex items-center gap-1"><FiCheckCircle /> {logo.name} sélectionné</p>}
        </div>

        {/* Palettes */}
        <div className="col-span-1 md:col-span-2">
          <Label className="mb-2 block">Identité visuelle (Couleurs)</Label>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {PALETTES.map((palette) => (
              <div 
                key={palette.name}
                onClick={() => setSelectedPalette(palette)}
                className={`cursor-pointer rounded-lg border-2 p-3 transition-all ${selectedPalette.name === palette.name ? 'border-primary ring-2 ring-primary/20' : 'border-transparent hover:border-primary/30 bg-muted/20'}`}
              >
                <div className="text-sm font-medium mb-2 text-center">{palette.name}</div>
                <div className="flex h-8 rounded-md overflow-hidden">
                  <div className="w-1/2 h-full" style={{ backgroundColor: palette.primary }}></div>
                  <div className="w-1/2 h-full" style={{ backgroundColor: palette.secondary }}></div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Other fields */}
        <div className="space-y-2">
          <Label htmlFor="school_type">Niveau d'enseignement *</Label>
          <select 
            id="school_type" 
            {...register('school_type')}
            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option value="PRIMARY">Primaire</option>
            <option value="MIDDLE">Collège</option>
            <option value="HIGH">Lycée</option>
            <option value="MIXED">Mixte</option>
          </select>
          {errors.school_type && <p className="text-sm text-destructive">{errors.school_type.message}</p>}
        </div>

        <div className="space-y-2">
          <Label htmlFor="sector">Secteur *</Label>
          <select 
            id="sector" 
            {...register('sector')}
            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option value="PRIVATE_SECULAR">Privé Laïc</option>
            <option value="PRIVATE_CATHOLIC">Privé Catholique</option>
            <option value="PRIVATE_ISLAMIC">Privé Islamique</option>
            <option value="FRANCO_ARABIC">Franco-Arabe</option>
          </select>
          {errors.sector && <p className="text-sm text-destructive">{errors.sector.message}</p>}
        </div>

        <div className="space-y-2">
          <Label htmlFor="slogan">Slogan (optionnel)</Label>
          <Input id="slogan" {...register('slogan')} placeholder="L'excellence avant tout" />
          {errors.slogan && <p className="text-sm text-destructive">{errors.slogan.message}</p>}
        </div>

        <div className="space-y-2">
          <Label htmlFor="website">Site web (optionnel)</Label>
          <Input id="website" {...register('website')} placeholder="https://www.monecole.com" type="url" />
          {errors.website && <p className="text-sm text-destructive">{errors.website.message}</p>}
        </div>

        <div className="space-y-2">
          <Label htmlFor="registration_number">Numéro d'enregistrement (optionnel)</Label>
          <Input id="registration_number" {...register('registration_number')} placeholder="Ex: N° 1234/MEN" />
        </div>

        <div className="space-y-2">
          <Label htmlFor="founded_at">Date de fondation (optionnel)</Label>
          <Input id="founded_at" type="date" {...register('founded_at')} />
        </div>

      </div>

      <div className="flex justify-end pt-4">
        <Button type="submit" disabled={isSavingBasicInfo} className="bg-primary text-primary-foreground">
          Enregistrer et continuer
        </Button>
      </div>
    </form>
  );
}
