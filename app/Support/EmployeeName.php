<?php

namespace App\Support;

use Illuminate\Support\Str;

final class EmployeeName
{
    /**
     * Formato único de presentación para nombres de empleados.
     */
    public static function format(?string $name): string
    {
        return Str::upper(trim((string) $name));
    }

    /**
     * Iniciales para avatares, derivadas del nombre ya formateado.
     */
    public static function initials(?string $name): string
    {
        return Str::substr(self::format($name), 0, 2);
    }
}
