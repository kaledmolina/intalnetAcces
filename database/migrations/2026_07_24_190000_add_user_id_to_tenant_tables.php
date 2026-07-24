<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna is_active a usuarios
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('is_superadmin');
            });
        }

        // Obtener el ID del primer usuario (Superadmin) o 1 por defecto
        $superadminId = DB::table('users')->where('is_superadmin', true)->value('id') ?? 1;

        // 2. Agregar user_id a employees
        if (!Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
            DB::table('employees')->whereNull('user_id')->update(['user_id' => $superadminId]);
        }

        // 3. Agregar user_id a departments
        if (!Schema::hasColumn('departments', 'user_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
            DB::table('departments')->whereNull('user_id')->update(['user_id' => $superadminId]);
        }

        // 4. Agregar user_id a schedules
        if (!Schema::hasColumn('schedules', 'user_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
            DB::table('schedules')->whereNull('user_id')->update(['user_id' => $superadminId]);
        }

        // 5. Agregar user_id a devices
        if (!Schema::hasColumn('devices', 'user_id')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
            DB::table('devices')->whereNull('user_id')->update(['user_id' => $superadminId]);
        }

        // 6. Agregar user_id a attendance_records
        if (!Schema::hasColumn('attendance_records', 'user_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
            DB::table('attendance_records')->whereNull('user_id')->update(['user_id' => $superadminId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
