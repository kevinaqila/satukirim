<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Staged audit values before update.
     *
     * @var array{old: array<string, mixed>, new: array<string, mixed>}|null
     */
    public ?array $pendingAuditUpdate = null;

    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            /** @var static $model */
            $ignored = $model->getIgnoredAuditAttributes();
            $new = collect($model->attributesToArray())->except($ignored)->all();

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'created',
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'old_values' => null,
                'new_values' => $new,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'created_at' => now(),
            ]);
        });

        static::updating(function (Model $model): void {
            /** @var static $model */
            $dirty = $model->getDirty();
            $ignored = $model->getIgnoredAuditAttributes();
            $auditableDirty = collect($dirty)->except($ignored);

            if ($auditableDirty->isEmpty()) {
                return;
            }

            $old = [];
            $new = [];

            foreach ($auditableDirty as $key => $newValue) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $newValue;
            }

            $model->pendingAuditUpdate = [
                'old' => $old,
                'new' => $new,
            ];
        });

        static::updated(function (Model $model): void {
            if (isset($model->pendingAuditUpdate) && ! empty($model->pendingAuditUpdate)) {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'event' => 'updated',
                    'auditable_type' => $model->getMorphClass(),
                    'auditable_id' => $model->getKey(),
                    'old_values' => $model->pendingAuditUpdate['old'],
                    'new_values' => $model->pendingAuditUpdate['new'],
                    'ip_address' => Request::ip() ?? '127.0.0.1',
                    'created_at' => now(),
                ]);

                unset($model->pendingAuditUpdate);
            }
        });

        static::deleted(function (Model $model): void {
            /** @var static $model */
            $ignored = $model->getIgnoredAuditAttributes();
            $old = collect($model->attributesToArray())->except($ignored)->all();

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'deleted',
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'old_values' => $old,
                'new_values' => null,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'created_at' => now(),
            ]);
        });
    }

    /**
     * List of attribute keys to exclude from audits.
     *
     * @return array<int, string>
     */
    public function getIgnoredAuditAttributes(): array
    {
        $defaultIgnored = [
            'updated_at',
            'created_at',
            'remember_token',
            'password',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'snap_token',
        ];

        $modelIgnored = property_exists($this, 'auditExclude') && is_array($this->auditExclude)
            ? $this->auditExclude
            : [];

        return array_unique(array_merge($defaultIgnored, $modelIgnored));
    }
}

