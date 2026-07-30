<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_days', function (Blueprint $table) {
            $table->time('break_start_time')->nullable()->after('entry_time');
            $table->time('break_end_time')->nullable()->after('break_start_time');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('check_break_tardiness')->default(false)->after('tolerance_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('check_break_tardiness');
        });

        Schema::table('schedule_days', function (Blueprint $table) {
            $table->dropColumn(['break_start_time', 'break_end_time']);
        });
    }
};
