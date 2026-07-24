<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->string('employee_no');
            $table->string('card_no')->nullable();
            $table->dateTime('event_time');
            $table->string('attendance_type')->default('auto'); // check_in, check_out, auto
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->string('hikvision_event_id')->nullable()->index();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            // Índice para evitar registros duplicados por empleado y momento exacto de marcación
            $table->unique(['employee_no', 'event_time'], 'unique_emp_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
