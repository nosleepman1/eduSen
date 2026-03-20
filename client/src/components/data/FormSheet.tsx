import { useEffect } from 'react';
import {
  useForm,
  type DefaultValues,
  type FieldError as RHFFieldError,
  type FieldValues,
  type Path,
  type UseFormReturn,
} from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { z, ZodType } from 'zod';
import { toast } from 'sonner';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Form } from '@/components/ui/form';
import { applyValidationErrors } from '@/lib/apiErrors';

// T is inferred from the zod schema (z.infer) rather than passed explicitly:
// zodResolver's input/output types diverge just enough (zod 4 + @hookform/
// resolvers 5) that pinning useForm<T> to the schema's output type produces a
// generic-variance error. Letting inference flow from `schema` alone sidesteps it.
interface FormSheetProps<Schema extends ZodType<FieldValues>> {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  schema: Schema;
  defaultValues: DefaultValues<z.infer<Schema>>;
  /** Field names the server can return a 422 for. Used to route unmapped
   *  server errors (e.g. a field this form doesn't render) to a toast instead
   *  of silently dropping them. */
  knownFields: ReadonlyArray<Path<z.infer<Schema>>>;
  onSubmit: (values: z.infer<Schema>) => Promise<void>;
  /** Render prop so each domain screen supplies its own fields while sharing
   *  layout, validation wiring, and 422 mapping. */
  children: (form: UseFormReturn<z.infer<Schema>>) => React.ReactNode;
  submitLabel?: string;
}

/**
 * Side-panel create/edit form: zod validation mirroring the backend rules,
 * 422 errors mapped field-by-field, list screen stays visible behind it.
 * One instance of this component is meant to serve both create and edit —
 * pass different defaultValues and onSubmit for each case.
 */
export function FormSheet<Schema extends ZodType<FieldValues>>({
  open,
  onOpenChange,
  title,
  description,
  schema,
  defaultValues,
  knownFields,
  onSubmit,
  children,
  submitLabel = 'Enregistrer',
}: FormSheetProps<Schema>) {
  type T = z.infer<Schema>;

  // zod 4's ZodType and @hookform/resolvers 5's Resolver disagree on the input
  // generic in a way TypeScript can't reconcile through inference alone (the
  // resolver wants Input to extend FieldValues too, which a generic Schema
  // param can't promise). The runtime behavior is correct — zodResolver has
  // handled zod 4 schemas since resolvers 3.4 — only the type-level unification
  // fails, so this narrows the resolver's own return type rather than `any`.
  const resolver = zodResolver(schema as unknown as ZodType<T, T>) as import('react-hook-form').Resolver<T>;

  const form = useForm<T>({
    resolver,
    defaultValues,
  });

  // Re-seed the form whenever the sheet reopens on a (possibly different) row,
  // rather than carrying over the previous edit's values or dirty state.
  useEffect(() => {
    if (open) {
      form.reset(defaultValues);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  const handleSubmit = form.handleSubmit(async (values) => {
    try {
      await onSubmit(values);
      onOpenChange(false);
    } catch (error) {
      const unattached = applyValidationErrors(error, form.setError, knownFields);
      unattached.forEach((message) => toast.error(message));
    }
  });

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="overflow-y-auto">
        <SheetHeader>
          <SheetTitle>{title}</SheetTitle>
          {description ? <SheetDescription>{description}</SheetDescription> : null}
        </SheetHeader>

        <Form {...form}>
          <form onSubmit={handleSubmit} className="flex flex-1 flex-col gap-4 overflow-y-auto px-4">
            {children(form)}
          </form>
        </Form>

        <SheetFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={form.formState.isSubmitting}>
            Annuler
          </Button>
          <Button onClick={handleSubmit} disabled={form.formState.isSubmitting}>
            {form.formState.isSubmitting ? 'Enregistrement...' : submitLabel}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}

/**
 * setError() (applyValidationErrors, above) attaches the message to
 * form.formState.errors but renders nothing by itself — every field must
 * still display it. This is that display, factored out so each domain form
 * doesn't repeat the same conditional paragraph under every input.
 */
export function FieldError({ error }: { error?: RHFFieldError }) {
  if (!error?.message) {
    return null;
  }

  return <p className="text-sm text-destructive">{error.message}</p>;
}
