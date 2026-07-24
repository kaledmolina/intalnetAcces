<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Device extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'name',
        'ip_address',
        'port',
        'protocol',
        'username',
        'password',
        'location',
        'status',
        'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function getBaseUrlAttribute(): string
    {
        return sprintf('%s://%s:%d', $this->protocol, $this->ip_address, $this->port);
    }
}
