import { useLocation, Navigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FaSchool } from 'react-icons/fa';
import { FiChevronRight, FiLoader } from 'react-icons/fi';
import type { SchoolChoice } from '../types/auth.types';

interface LocationState {
  tempToken: string;
  schools: SchoolChoice[];
}

export default function SelectSchoolPage() {
  const location = useLocation();
  const state = location.state as LocationState | null;
  const { selectSchool, isSelectingSchool } = useAuth();

  if (!state?.tempToken || !state?.schools?.length) {
    return <Navigate to="/login" replace />;
  }

  const { tempToken, schools } = state;

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/10 via-background to-secondary/10 p-4">
      <div className="w-full max-w-md">
        <div className="flex justify-center mb-8">
          <div className="bg-primary p-3 rounded-2xl shadow-lg shadow-primary/20">
            <FaSchool className="h-10 w-10 text-primary-foreground" />
          </div>
        </div>

        <Card className="shadow-2xl border-primary/10 backdrop-blur-sm bg-card/95">
          <CardHeader className="space-y-1 text-center">
            <CardTitle className="text-2xl font-bold tracking-tight text-primary">Choisir un établissement</CardTitle>
            <CardDescription className="text-muted-foreground">
              Votre compte est associé à plusieurs écoles. Sélectionnez celle avec laquelle vous souhaitez travailler.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            {schools.map((school) => (
              <button
                key={school.id}
                type="button"
                disabled={isSelectingSchool}
                onClick={() => selectSchool({ tempToken, schoolId: school.id })}
                className="w-full flex items-center justify-between gap-3 rounded-lg border border-border bg-background/60 px-4 py-3 text-left transition hover:border-primary/50 hover:bg-primary/5 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                <div>
                  <p className="font-medium text-foreground">{school.name}</p>
                  <p className="text-sm text-muted-foreground">{school.code}{school.role ? ` · ${school.role}` : ''}</p>
                </div>
                {isSelectingSchool ? (
                  <FiLoader className="h-4 w-4 animate-spin text-muted-foreground shrink-0" />
                ) : (
                  <FiChevronRight className="h-4 w-4 text-muted-foreground shrink-0" />
                )}
              </button>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
