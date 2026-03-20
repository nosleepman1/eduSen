import type { UseFormSetError, FieldValues, Path } from 'react-hook-form';

/**
 * A 422 from the server, as rendered by bootstrap/app.php:
 *   { success: false, message: "...", errors: { "field": ["msg", ...] } }
 * Laravel keys nested fields with dots (`address.city`), which is also how
 * react-hook-form addresses them, so keys map across without translation.
 */
interface ValidationErrorBody {
  message?: string;
  errors?: Record<string, string[]>;
}

interface ApiErrorLike {
  response?: { status?: number; data?: ValidationErrorBody };
  message?: string;
}

export function isValidationError(error: unknown): boolean {
  return (error as ApiErrorLike)?.response?.status === 422;
}

/**
 * Push a 422's field errors onto the form that submitted it, so each message
 * lands under its own input instead of in a generic toast.
 *
 * Returns the messages that could not be attached to a field — either because
 * the server keyed them to something the form does not render, or because the
 * failure was not a validation error at all. Show those separately; dropping
 * them silently leaves the user with a form that refuses to submit and says
 * nothing about why.
 */
export function applyValidationErrors<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
  knownFields?: ReadonlyArray<Path<T>>
): string[] {
  const body = (error as ApiErrorLike)?.response?.data;

  if (!isValidationError(error) || !body?.errors) {
    return [extractMessage(error)];
  }

  const unattached: string[] = [];

  for (const [field, messages] of Object.entries(body.errors)) {
    const message = messages[0];
    if (!message) {
      continue;
    }

    if (knownFields && !knownFields.includes(field as Path<T>)) {
      unattached.push(message);
      continue;
    }

    setError(field as Path<T>, { type: 'server', message });
  }

  return unattached;
}

export function extractMessage(error: unknown, fallback = 'Une erreur est survenue.'): string {
  const candidate = error as ApiErrorLike;
  return candidate?.response?.data?.message || candidate?.message || fallback;
}
