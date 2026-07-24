<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class AttendanceRecord extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'device_id',
        'employee_id',
        'employee_no',
        'card_no',
        'event_time',
        'attendance_type',
        'is_late',
        'late_minutes',
        'hikvision_event_id',
        'raw_data',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'raw_data' => 'array',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
