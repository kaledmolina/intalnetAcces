<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir department_id
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
        });

        // 2. Migrar los datos de department a la nueva tabla y asignar department_id
        $departments = DB::table('employees')->whereNotNull('department')->where('department', '!=', '')->distinct()->pluck('department');
        
        foreach ($departments as $deptName) {
            // Verificar si el departamento ya existe por si acaso
            $deptId = DB::table('departments')->where('name', $deptName)->value('id');
            if (!$deptId) {
                $deptId = DB::table('departments')->insertGetId([
                    'name' => $deptName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('employees')->where('department', $deptName)->update(['department_id' => $deptId]);
        }

        // 3. Eliminar la columna string department (SQLite > 3.35 lo soporta, Laravel 11 usa SQLite > 3.35 por defecto)
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department')->nullable();
        });

        $departments = DB::table('departments')->get();
        foreach ($departments as $dept) {
            DB::table('employees')->where('department_id', $dept->id)->update(['department' => $dept->name]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
