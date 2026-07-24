<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Schedule extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'name',
        'tolerance_minutes',
        'is_default',
    ];

    public function days()
    {
        return $this->hasMany(ScheduleDay::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    protected $casts = [
        'is_default' => 'boolean',
        'tolerance_minutes' => 'integer',
    ];

    public function employeeSchedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function getFormattedSummaryAttribute(): string
    {
        $workingDays = $this->days()->where('is_working_day', true)->get();
        
        if ($workingDays->isEmpty()) {
            return 'Sin días laborables';
        }

        $first = $workingDays->first();
        $allSame = $workingDays->every(function($day) use ($first) {
            return $day->entry_time === $first->entry_time && $day->exit_time === $first->exit_time;
        });

        if ($allSame) {
            $daysCount = $workingDays->count();
            $timeStr = substr($first->entry_time, 0, 5) . ' - ' . substr($first->exit_time, 0, 5);
            if ($daysCount === 7) {
                return "Lun a Dom: $timeStr";
            } elseif ($daysCount === 5 && $workingDays->pluck('day_of_week')->diff([1,2,3,4,5])->isEmpty()) {
                return "Lun a Vie: $timeStr";
            } else {
                $diasCortos = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
                $labels = $workingDays->pluck('day_of_week')->map(fn($d) => $diasCortos[$d])->implode(',');
                return "[$labels]: $timeStr";
            }
        }

        $diasCortos = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
        $summaries = [];
        foreach ($workingDays->sortBy('day_of_week') as $day) {
            $summaries[] = $diasCortos[$day->day_of_week] . ': ' . substr($day->entry_time, 0, 5);
        }
        return implode(' | ', $summaries);
    }
}
