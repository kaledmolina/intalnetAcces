<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    /**
     * Obtener la ruta absoluta de la base de datos SQLite activa.
     */
    protected function getDatabasePath()
    {
        return config('database.connections.sqlite.database');
    }

    /**
     * Mostrar la vista principal del panel de backups.
     */
    public function index()
    {
        $dbPath = $this->getDatabasePath();
        
        $exists = file_exists($dbPath);
        $size = $exists ? filesize($dbPath) : 0;
        $lastModified = $exists ? filemtime($dbPath) : null;

        // Formatear tamaño a un formato legible
        if ($size >= 1048576) {
            $formattedSize = number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            $formattedSize = number_format($size / 1024, 2) . ' KB';
        } else {
            $formattedSize = $size . ' bytes';
        }

        return view('backup.index', [
            'db_exists' => $exists,
            'db_size' => $formattedSize,
            'db_path' => $dbPath,
            'last_modified' => $lastModified ? date('d/m/Y H:i:s', $lastModified) : 'N/A'
        ]);
    }

    /**
     * Descargar la base de datos actual comprimida en un archivo ZIP.
     */
    public function download()
    {
        $dbPath = $this->getDatabasePath();

        if (!file_exists($dbPath)) {
            return back()->with('error', 'El archivo de base de datos no existe.');
        }

        // Crear un archivo ZIP temporal
        $tempZip = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';
        
        $zip = new \ZipArchive();
        if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            // Guardar dentro del ZIP como 'database_app.sqlite'
            $zip->addFile($dbPath, 'database_app.sqlite');
            $zip->close();
        } else {
            return back()->with('error', 'No se pudo generar el archivo comprimido ZIP.');
        }

        $filename = 'bioaccess_respaldo_' . date('Y-m-d_H-i-s') . '.zip';

        return response()->download($tempZip, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Restaurar un archivo de base de datos cargado (soporta .sqlite, .db o .zip).
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
        ]);

        $file = $request->file('backup_file');
        
        // 1. Validaciones básicas de tamaño (máx 50MB)
        if ($file->getSize() > 52428800) {
            return back()->with('error', 'El archivo es demasiado grande (máximo 50MB).');
        }

        $uploadedPath = $file->getRealPath();
        $tempSqlitePath = null;
        $isZip = false;

        // Comprobar si el archivo cargado es un ZIP
        $mime = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        if ($extension === 'zip' || $mime === 'application/zip' || $mime === 'application/x-zip-compressed') {
            $isZip = true;
            $zip = new \ZipArchive();
            if ($zip->open($uploadedPath) === true) {
                // Buscar algún archivo con extensión sqlite o db dentro del ZIP
                $targetFileIndex = -1;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $innerName = strtolower($stat['name']);
                    if (str_ends_with($innerName, '.sqlite') || str_ends_with($innerName, '.db')) {
                        $targetFileIndex = $i;
                        break;
                    }
                }

                if ($targetFileIndex === -1) {
                    $zip->close();
                    return back()->with('error', 'El archivo ZIP cargado no contiene ninguna base de datos SQLite (.sqlite o .db).');
                }

                // Extraer el archivo de la base de datos a un archivo temporal local
                $tempSqlitePath = tempnam(sys_get_temp_dir(), 'extracted_') . '.sqlite';
                file_put_contents($tempSqlitePath, $zip->getFromIndex($targetFileIndex));
                $zip->close();
                
                $tempPath = $tempSqlitePath;
            } else {
                return back()->with('error', 'No se pudo abrir el archivo ZIP cargado.');
            }
        } else {
            $tempPath = $uploadedPath;
        }

        // 2. Validación Estructural SQLite
        try {
            $db = new \SQLite3($tempPath, SQLITE3_OPEN_READONLY);
            
            // Probar integridad lógica
            $integrity = $db->querySingle('PRAGMA integrity_check;');
            if ($integrity !== 'ok') {
                throw new \Exception("El archivo de base de datos cargado está dañado o no es un SQLite válido.");
            }

            // Verificar la existencia de tablas críticas del sistema
            $tablesQuery = $db->query("SELECT name FROM sqlite_master WHERE type='table';");
            $tables = [];
            while ($row = $tablesQuery->fetchArray(SQLITE3_ASSOC)) {
                $tables[] = $row['name'];
            }

            $criticalTables = ['migrations', 'employees', 'attendance_records', 'devices', 'schedules'];
            foreach ($criticalTables as $table) {
                if (!in_array($table, $tables)) {
                    throw new \Exception("La base de datos no pertenece a este sistema (falta la tabla: '{$table}').");
                }
            }

            $db->close();
        } catch (\Exception $e) {
            if ($tempSqlitePath && file_exists($tempSqlitePath)) {
                @unlink($tempSqlitePath);
            }
            Log::error("Validación de base de datos cargada fallida: " . $e->getMessage());
            return back()->with('error', 'Archivo inválido: ' . $e->getMessage());
        }

        $dbPath = $this->getDatabasePath();
        $rollbackPath = $dbPath . '.bak';

        // 3. Crear copia de seguridad temporal de rollback antes de sobrescribir
        try {
            // Desconectar PDO para liberar los handles de archivo en Windows
            DB::disconnect();

            if (file_exists($dbPath)) {
                if (!copy($dbPath, $rollbackPath)) {
                    throw new \Exception("No se pudo crear el archivo de respaldo temporal para rollback.");
                }
            }

            // 4. Sobrescribir la base de datos activa con la nueva base de datos
            if (!copy($tempPath, $dbPath)) {
                throw new \Exception("No se pudo escribir el nuevo archivo de base de datos en la ruta activa.");
            }

            // Limpieza de archivos temporales
            if ($tempSqlitePath && file_exists($tempSqlitePath)) {
                @unlink($tempSqlitePath);
            }
            if (file_exists($rollbackPath)) {
                @unlink($rollbackPath);
            }

            // Limpiar cachés de Laravel
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            return redirect()->route('backups.index')->with('success', '¡Base de datos restaurada correctamente! El sistema ahora funciona con los datos del respaldo.');

        } catch (\Exception $e) {
            Log::error("Error crítico durante la restauración de base de datos: " . $e->getMessage());
            
            // Revertir de inmediato usando la copia temporal rollback
            if (file_exists($rollbackPath)) {
                @copy($rollbackPath, $dbPath);
                @unlink($rollbackPath);
            }

            if ($tempSqlitePath && file_exists($tempSqlitePath)) {
                @unlink($tempSqlitePath);
            }

            return back()->with('error', 'Error en la restauración: ' . $e->getMessage() . '. La base de datos anterior ha sido restaurada para mantener el servicio activo.');
        }
    }
}
