<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use App\Traits\BelongsToTenant;

class Employee extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'employee_no',
        'name',
        'document_id',
        'department_id',
        'position',
        'phone',
        'email',
        'is_active',
        'has_fingerprint',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_fingerprint' => 'boolean',
    ];

    /**
     * Relación con asignación de horarios.
     */
    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    /**
     * Relación con Departamento.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relación con marcaciones de asistencia.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Obtener el horario activo actual del empleado.
     */
    public function currentSchedule(): ?Schedule
    {
        // PRIORIDAD 1: Horario del departamento (si el empleado pertenece a uno y este tiene horario)
        if ($this->department_id && $this->department && $this->department->schedule_id) {
            return $this->department->schedule;
        }

        // PRIORIDAD 2: Asignación de horario individual directa
        $activeAssign = $this->employeeSchedules()
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->latest('start_date')
            ->first();

        if ($activeAssign) {
            return $activeAssign->schedule;
        }

        // PRIORIDAD 3: Horario general por defecto de la empresa
        return Schedule::where('is_default', true)->first();
    }
}
