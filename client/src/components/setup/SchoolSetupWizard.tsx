import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FiInfo, FiMapPin, FiCalendar, FiCheckCircle } from 'react-icons/fi';
import BasicInfoForm from './BasicInfoForm';
import ContactInfoForm from './ContactInfoForm';
import AcademicSetupForm from './AcademicSetupForm';
import CompleteSetup from './CompleteSetup';

const STEPS = [
  { id: 1, title: 'Informations de base', icon: FiInfo },
  { id: 2, title: 'Coordonnées', icon: FiMapPin },
  { id: 3, title: 'Année scolaire', icon: FiCalendar },
  { id: 4, title: 'Finalisation', icon: FiCheckCircle },
];

export default function SchoolSetupWizard() {
  const [currentStep, setCurrentStep] = useState(1);

  const nextStep = () => setCurrentStep((prev) => Math.min(prev + 1, STEPS.length));
  const prevStep = () => setCurrentStep((prev) => Math.max(prev - 1, 1));

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary/5 via-background to-secondary/5 py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center">
      <div className="max-w-4xl w-full space-y-8">
        
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-primary">Configuration de votre École</h2>
          <p className="mt-2 text-sm text-muted-foreground">
            Veuillez compléter ces quelques étapes pour finaliser la configuration de votre établissement.
          </p>
        </div>

        {/* Barre de progression */}
        <div className="relative">
          <div className="absolute inset-0 flex items-center" aria-hidden="true">
            <div className="w-full border-t border-border" />
          </div>
          <div className="relative flex justify-between">
            {STEPS.map((step) => {
              const Icon = step.icon;
              const isCompleted = currentStep > step.id;
              const isCurrent = currentStep === step.id;
              
              return (
                <div key={step.id} className="flex flex-col items-center">
                  <div 
                    className={`flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors ${
                      isCompleted 
                        ? 'bg-primary border-primary text-primary-foreground' 
                        : isCurrent 
                          ? 'bg-background border-primary text-primary ring-4 ring-primary/10' 
                          : 'bg-background border-muted-foreground text-muted-foreground'
                    }`}
                  >
                    <Icon className="w-5 h-5" />
                  </div>
                  <span className={`mt-2 text-xs font-medium ${isCurrent || isCompleted ? 'text-foreground' : 'text-muted-foreground'}`}>
                    {step.title}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Contenu de l'étape */}
        <Card className="shadow-xl border-primary/10 bg-card/95 backdrop-blur-sm mt-8">
          <CardHeader className="border-b border-border/50 bg-muted/20">
            <CardTitle className="text-xl text-primary">
              {STEPS[currentStep - 1].title}
            </CardTitle>
            <CardDescription>
              {currentStep === 1 && "Les informations générales et l'identité visuelle de votre établissement."}
              {currentStep === 2 && "Vos adresses et numéros de contact professionnels."}
              {currentStep === 3 && "Créez et découpez votre première année scolaire (facultatif)."}
              {currentStep === 4 && "Vérification et validation finale."}
            </CardDescription>
          </CardHeader>
          <CardContent className="pt-6">
            {currentStep === 1 && <BasicInfoForm onNext={nextStep} />}
            {currentStep === 2 && <ContactInfoForm onNext={nextStep} onBack={prevStep} />}
            {currentStep === 3 && <AcademicSetupForm onNext={nextStep} onBack={prevStep} />}
            {currentStep === 4 && <CompleteSetup onBack={prevStep} />}
          </CardContent>
        </Card>

      </div>
    </div>
  );
}
