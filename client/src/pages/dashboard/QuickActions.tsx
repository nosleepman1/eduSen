import { RiUserAddLine, RiMoneyDollarCircleLine, RiBuildingLine, RiSettings4Line } from 'react-icons/ri';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';

const ACTIONS = [
  { name: 'Nouvel élève', icon: RiUserAddLine, to: '/students/new', color: 'blue' },
  { name: 'Payer abonnement', icon: RiMoneyDollarCircleLine, to: '/subscription', color: 'emerald' },
  { name: 'Gérer les classes', icon: RiBuildingLine, to: '/classes', color: 'indigo' },
  { name: 'Paramètres', icon: RiSettings4Line, to: '/settings', color: 'slate' },
];

export default function QuickActions() {
  return (
    <Card className="border-none shadow-md bg-gradient-to-b from-slate-800 to-slate-900 text-white overflow-hidden relative">
      <div className="absolute top-0 right-0 -mr-16 -mt-16 w-48 h-48 rounded-full bg-white/5 blur-3xl"></div>
      <div className="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 rounded-full bg-white/5 blur-3xl"></div>
      
      <CardHeader className="relative z-10 border-b border-white/10 pb-4">
        <CardTitle className="text-lg font-bold text-white">Actions Rapides</CardTitle>
      </CardHeader>
      <CardContent className="p-6 relative z-10">
        <div className="grid grid-cols-1 gap-4">
          {ACTIONS.map((action) => (
            <Button
              key={action.name}
              variant="secondary"
              asChild
              className={`w-full justify-start py-6 bg-white/10 hover:bg-white/20 border-0 text-white transition-all hover:translate-x-1 group`}
            >
              <Link to={action.to}>
                <div className={`p-2 rounded-md bg-${action.color}-500/20 text-${action.color}-300 mr-3 group-hover:scale-110 transition-transform`}>
                  <action.icon className="h-5 w-5" aria-hidden="true" />
                </div>
                <span className="font-medium">{action.name}</span>
              </Link>
            </Button>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
