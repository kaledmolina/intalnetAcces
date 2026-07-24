<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Department extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'name',
        'schedule_id',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
