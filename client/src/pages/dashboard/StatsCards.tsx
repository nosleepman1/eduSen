import { RiUser3Line, RiTeamLine, RiBuildingLine, RiMoneyDollarCircleLine } from 'react-icons/ri';
import { Card, CardContent } from '@/components/ui/card';

interface StatsCardsProps {
  counts: {
    students: number;
    teachers: number;
    personnel: number;
    classes: number;
    monthly_payments: number;
  };
}

export default function StatsCards({ counts }: StatsCardsProps) {
  const stats = [
    { 
      name: 'Élèves', 
      value: counts.students, 
      icon: RiUser3Line,
      color: 'blue'
    },
    { 
      name: 'Enseignants', 
      value: counts.teachers, 
      icon: RiTeamLine,
      color: 'indigo'
    },
    { 
      name: 'Personnel', 
      value: counts.personnel, 
      icon: RiBuildingLine,
      color: 'purple'
    },
    { 
      name: 'Paiements (Mois)', 
      value: new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(counts.monthly_payments), 
      icon: RiMoneyDollarCircleLine,
      color: 'emerald'
    },
  ];

  return (
    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      {stats.map((item) => {
        const Icon = item.icon;
        
        return (
          <Card key={item.name} className="relative overflow-hidden border-none shadow-md bg-gradient-to-br from-white to-slate-50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
            {/* Background Icon Decoration */}
            <div className={`absolute -right-6 -top-6 p-4 opacity-[0.03] group-hover:opacity-[0.08] transition-opacity group-hover:scale-110 duration-500`}>
              <Icon className="w-32 h-32" />
            </div>

            <CardContent className="p-6 relative z-10">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500 mb-1">{item.name}</p>
                  <h3 className="text-3xl font-black text-slate-800 tracking-tight">{item.value}</h3>
                </div>
                <div className={`p-3 rounded-2xl bg-${item.color}-100 text-${item.color}-600 shadow-sm ring-1 ring-${item.color}-500/10`}>
                  <Icon className="h-6 w-6" aria-hidden="true" />
                </div>
              </div>
              <div className="mt-4 flex items-center text-sm">
                <span className={`text-${item.color}-600 font-medium flex items-center bg-${item.color}-50 px-2 py-0.5 rounded-md`}>
                  En temps réel
                </span>
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}
