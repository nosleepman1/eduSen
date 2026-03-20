import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/**
 * Tone keyed by meaning, not by any one enum's spelling — every backend status
 * enum (ACTIVE/INACTIVE/SUSPENDED, DRAFT/ACTIVE/CLOSED/ARCHIVED, PENDING/PAID/
 * FAILED...) maps onto these five buckets. A caller passes its own map from
 * enum value to Tone; StatusBadge only owns the color.
 */
export type Tone = 'success' | 'warning' | 'danger' | 'neutral' | 'info';

const TONE_CLASSES: Record<Tone, string> = {
  success: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
  warning: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
  danger: 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10',
  neutral: 'bg-muted text-muted-foreground ring-1 ring-inset ring-border',
  info: 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20',
};

interface StatusBadgeProps {
  label: string;
  tone: Tone;
  className?: string;
}

export function StatusBadge({ label, tone, className }: StatusBadgeProps) {
  return (
    <Badge
      variant="outline"
      className={cn('rounded-full border-0 font-medium', TONE_CLASSES[tone], className)}
    >
      {label}
    </Badge>
  );
}
