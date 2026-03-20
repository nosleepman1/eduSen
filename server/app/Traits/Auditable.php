<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Support\TenantHelper;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::recordAudit($model, 'CREATED', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            // Only record changed fields
            $changes = $model->getChanges();
            if (empty($changes)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);
            self::recordAudit($model, 'UPDATED', $original, $changes);
        });

        static::deleted(function (Model $model) {
            self::recordAudit($model, 'DELETED', $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                self::recordAudit($model, 'RESTORED', null, $model->getAttributes());
            });
        }
    }

    protected static function recordAudit(Model $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        $schoolId = TenantHelper::currentSchoolId();

        // If the model itself has a school_id and there's no context, use it
        if (! $schoolId && isset($model->school_id)) {
            $schoolId = $model->school_id;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'school_id' => $schoolId,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
