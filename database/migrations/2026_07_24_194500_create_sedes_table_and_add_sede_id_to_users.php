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
        // 1. Crear tabla sedes
        if (!Schema::hasTable('sedes')) {
            Schema::create('sedes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // Identificador Único: SEDE-INTALNET
                $table->string('name'); // Nombre: Intalnet
                $table->text('description')->nullable();
                $table->timestamps();
            });

            // Poblar únicamente la Sede Intalnet
            DB::table('sedes')->insert([
                [
                    'code' => 'SEDE-INTALNET',
                    'name' => 'Intalnet',
                    'description' => 'Sede Principal Intalnet',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        // 2. Agregar sede_id a users
        if (!Schema::hasColumn('users', 'sede_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('sede_id')->nullable()->after('sede')->constrained('sedes')->onDelete('set null');
            });
        }

        // 3. Asignar explícitamente a kaledmoly@gmail.com la Sede Intalnet
        $superadminUser = DB::table('users')->where('email', 'kaledmoly@gmail.com')->first()
            ?? DB::table('users')->where('is_superadmin', true)->first();

        $intalnetSede = DB::table('sedes')->where('code', 'SEDE-INTALNET')->first();

        if ($superadminUser && $intalnetSede) {
            DB::table('users')->where('id', $superadminUser->id)->update([
                'sede_id' => $intalnetSede->id,
                'sede' => $intalnetSede->name,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropColumn('sede_id');
        });

        Schema::dropIfExists('sedes');
    }
};
