<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    /**
     * Boot trait to automatically apply tenant scoping and auto-assignment.
     */
    protected static function bootBelongsToTenant(): void
    {
        // 1. Global Scope: Filtrar por tenant/sede excepto si es Superadmin o si no está autenticado
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->is_superadmin) {
                $user = auth()->user();

                // Si el usuario tiene una sede asignada, comparte la vista con los usuarios de esa misma sede
                if (!empty($user->sede_id)) {
                    $tenantUserIds = \App\Models\User::where('sede_id', $user->sede_id)->pluck('id');
                    $builder->whereIn($builder->getModel()->getTable() . '.user_id', $tenantUserIds);
                } elseif (!empty($user->sede)) {
                    $tenantUserIds = \App\Models\User::where('sede', $user->sede)->pluck('id');
                    $builder->whereIn($builder->getModel()->getTable() . '.user_id', $tenantUserIds);
                } else {
                    $builder->where($builder->getModel()->getTable() . '.user_id', $user->id);
                }
                // Si el usuario tiene un arreglo de departamentos asignados, restringir aún más
                if (!empty($user->assigned_departments) && is_array($user->assigned_departments) && count($user->assigned_departments) > 0) {
                    if (class_basename($builder->getModel()) === 'Employee') {
                        $builder->whereIn($builder->getModel()->getTable() . '.department_id', $user->assigned_departments);
                    } elseif (class_basename($builder->getModel()) === 'AttendanceRecord') {
                        $builder->whereHas('employee', function ($q) use ($user) {
                            $q->whereIn('department_id', $user->assigned_departments);
                        });
                    }
                }
            }
        });

        // 2. Evento Creating: Asignar automáticament el user_id del usuario logueado
        static::creating(function ($model) {
            if (auth()->check() && !$model->user_id) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * Relación con el Usuario / Tenant dueño del registro.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
