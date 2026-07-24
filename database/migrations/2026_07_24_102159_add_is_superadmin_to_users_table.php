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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('password');
        });

        // Insertar el superadmin por defecto
        Illuminate\Support\Facades\DB::table('users')->insert([
            'name' => 'Super Administrador',
            'email' => 'kaledmoly@gmail.com',
            'password' => Illuminate\Support\Facades\Hash::make('Colombia2026++'),
            'is_superadmin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }
};
