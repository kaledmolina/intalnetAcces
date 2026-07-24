<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 1 = Lunes ... 7 = Domingo
            $table->boolean('is_working_day')->default(true);
            $table->time('entry_time')->nullable();
            $table->time('exit_time')->nullable();
            $table->timestamps();
        });

        // Migrate existing data
        $schedules = DB::table('schedules')->get();
        foreach ($schedules as $schedule) {
            for ($i = 1; $i <= 7; $i++) {
                DB::table('schedule_days')->insert([
                    'schedule_id' => $schedule->id,
                    'day_of_week' => $i,
                    'is_working_day' => true,
                    'entry_time' => $schedule->entry_time,
                    'exit_time' => $schedule->exit_time,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop old columns
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['entry_time', 'exit_time']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->time('entry_time')->nullable();
            $table->time('exit_time')->nullable();
        });

        // Restore first available day data
        $days = DB::table('schedule_days')->where('day_of_week', 1)->get();
        foreach ($days as $day) {
            DB::table('schedules')->where('id', $day->schedule_id)->update([
                'entry_time' => $day->entry_time,
                'exit_time' => $day->exit_time,
            ]);
        }

        Schema::dropIfExists('schedule_days');
    }
};
