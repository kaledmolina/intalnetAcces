# IntalnetAcces - Plataforma SaaS Multi-tenant de Control de Asistencia Biométrica Hikvision ISAPI

IntalnetAcces es un sistema integral corporativo de control de asistencia y acceso biométrico desarrollado en Laravel + Tailwind CSS + Livewire + React. Permite la gestión multi-inquilino (SaaS), sincronización en tiempo real con huelleros biométricos Hikvision vía protocolo ISAPI, control por sedes compartidas, programación de horarios, tolerancia y gestión de respaldos.

## 🚀 Características Principales

- 🔐 **Arquitectura SaaS Multi-Tenant & Sedes Compartidas:**
  - Registros de empresas/inquilinos aislados por `BelongsToTenant`.
  - Soporte para Sedes con Identificador Único (`SEDE-001`, `SEDE-002`, etc.) permitiendo a múltiples usuarios compartir la administración de una misma sede.
  - Panel de SuperAdmin para activación/desactivación de cuentas, cambio de contraseñas y asignación de sedes.

- 📟 **Integración Biométrica Hikvision ISAPI:**
  - Comunicación directa por protocolo HTTP Digest/ISAPI con terminales biométricas Hikvision.
  - Importación automática de empleados y huellas desde los huelleros.
  - Prueba de conectividad en vivo (*Ping Test*).
  - Sincronización en tiempo real y diferida de marcaciones.

- 👥 **Gestión Completa de Personal & Departamentos:**
  - Registro de empleados con huella digital integrada.
  - Regla estricta: Cada empleado pertenece a 1 solo departamento.
  - Asignación masiva de horarios por departamento con prioridad sobre horarios individuales.

- ⏰ **Horarios y Control de Tardanzas:**
  - Definición de jornadas laborales, minutos de tolerancia e intervalos.
  - Cálculo automático de tardanzas, horas trabajadas y ausencias.

- 💾 **Respaldos y Seguridad:**
  - Módulo de respaldos de base de datos (`.sqlite` / `.sql`) descargables y restaurables desde el panel.
  - Modales de confirmación con advertencias de seguridad para borrado en cascada.

## 💻 Tecnologías Utilizadas

- **Backend:** PHP 8.3+, Laravel 11/13, SQLite/MySQL
- **Frontend:** Blade, Tailwind CSS, Flowbite, Lucide Icons
- **Interactividad & Gráficos:** React 18, Recharts, Livewire 3
- **Comunicación Hardware:** Guzzle HTTP (Autenticación Digest ISAPI Hikvision)

## 🔧 Instalación Local

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/kaledmolina/intalnetAcces.git
   cd intalnetAcces
   ```

2. Instalar dependencias de PHP y Node:
   ```bash
   composer install
   npm install
   ```

3. Configurar archivo `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```

5. Compilar assets y servidor de desarrollo:
   ```bash
   npm run build
   php artisan serve
   ```

## 🐳 Despliegue con Docker en VPS (`access.intalnet.com`)

El proyecto incluye configuración lista de Docker con NGINX, PHP 8.3 FPM, compilación de assets Vite y soporte SSL.

1. **Clonar repositorio en tu servidor VPS:**
   ```bash
   git clone https://github.com/kaledmolina/intalnetAcces.git
   cd intalnetAcces
   ```

2. **Crear archivo de variables de entorno `.env`:**
   ```bash
   cp .env.docker.example .env
   ```
   *Asegúrate de generar una clave `APP_KEY` válida con `php artisan key:generate --show` y pegarla en `.env`.*

3. **Desplegar contenedores con Docker Compose:**
   ```bash
   docker compose up -d --build
   ```

4. **Configurar SSL Gratuito con Certbot (Let's Encrypt):**
   ```bash
   sudo apt update && sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d access.intalnet.com
   ```

## 🤖 Servidor MCP de solo lectura (`/mcp/attendance`)

Expone consultas de asistencia a clientes de IA mediante el paquete oficial `laravel/mcp` (transporte Streamable HTTP) protegido con tokens Sanctum.

### Cómo funciona

- **Solo lectura:** no existe ninguna herramienta que cree, actualice, borre o restaure datos.
- **Tenant y sede derivados del token:** el usuario autenticado determina la empresa y la sede. Ninguna herramienta acepta `tenant_id`, `company_id` ni similares. Si se pide una sede que el usuario no administra, la respuesta es una lista vacía con un mensaje claro.
- **Tardanzas reutilizadas:** se leen `attendance_records.is_late` y `late_minutes`, calculados por `App\Services\HikvisionService` durante la sincronización. No se recalcula la tardanza en el MCP.
- **Justificación:** se marca `justificada = true` cuando existe una nota en `employee_daily_notes` para ese empleado y esa fecha (la "justificación" que ya registra el panel).
- **Sede del empleado:** se deriva del usuario dueño del empleado (`users.sede_id` / `users.sede`), que es como el sistema agrupa las sedes compartidas.
- **Zona horaria:** las horas se devuelven en ISO 8601 con `APP_TIMEZONE` (por defecto `America/Bogota`), la zona usada por la aplicación. La tabla `sedes` no tiene zona horaria propia.
- **Límites:** máximo 200 filas por respuesta (`total` y `truncated` indican el total real) y 60 peticiones por minuto por token. Cada llamada se registra en el log (`mcp.attendance.tool_called`) con herramienta, usuario y filas devueltas.

### Herramientas

| Herramienta | Parámetros | Devuelve |
| --- | --- | --- |
| `tardanzas_del_dia` | `fecha` (opcional, por defecto hoy), `sede` (opcional) | Empleado, sede, departamento, hora de entrada, minutos y justificada |
| `entradas_del_dia` | `fecha` (opcional, por defecto hoy), `sede`, `departamento`, `limite` | Todos los empleados ordenados por su primera marcación, con horario, diferencia en minutos, resumen del primero/último y `empleados_sin_marcacion` |
| `tardanzas_por_rango` | `desde`, `hasta` (obligatorios, máx. 92 días), `sede`, `departamento` | Detalle + `resumen_por_empleado` con total y minutos acumulados |
| `resumen_asistencia_por_sede` | `fecha` o `desde`/`hasta` | Por sede: presentes, tardanzas, ausencias y minutos totales |
| `marcaciones_de_empleado` | `empleado` (id, documento o número), `desde`, `hasta` | Solo hora, tipo de marcación y sede |
| `buscar_empleado` | `texto` (nombre, documento o número) | Id, nombre, documento, departamento y sede |
| `horario_de_empleado` | `empleado` | Jornada, tolerancia e intervalos vigentes |

### Arranque

```bash
# 1) Dependencias y migraciones (incluye personal_access_tokens)
composer install
php artisan migrate

# 2) Generar un token para un usuario; se muestra UNA sola vez
php artisan mcp:token usuario@empresa.com --name=freebuff
#    También desde el panel: /admin/mcp (superadmin) o /mi-mcp (cada usuario, solo sus tokens)

# 3) Servidor web habitual (Docker/NGINX o local)
php artisan serve
```

La URL pública del conector es:

```
https://access.intalnet.com/mcp/attendance
```

### Variables de entorno

```env
APP_URL=https://access.intalnet.com
APP_TIMEZONE=America/Bogota
# Base de datos ya usada por la plataforma (SQLite o MySQL)
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Sanctum no requiere variables adicionales para tokens Bearer: no se usa Passport ni OAuth. Los tokens no caducan salvo que se configure `SANCTUM_EXPIRATION`; se revocan desde el panel o borrando la fila de `personal_access_tokens`.

> Si el MCP se consume desde un navegador, agregue el origen en `config/mcp.php` (`allowed_origins`); los clientes de IA que envían solo `Authorization: Bearer` no envían `Origin` y no necesitan esa lista. El dominio de `APP_URL` ya está permitido.

### Conector para Freebuff

```json
{
  "mcpServers": {
    "intalnet-attendance": {
      "type": "http",
      "url": "https://access.intalnet.com/mcp/attendance",
      "headers": {
        "Authorization": "Bearer <token-generado-con-mcp:token>"
      }
    }
  }
}
```

Para depurar localmente con el inspector incluido en el paquete:

```bash
php artisan mcp:inspector /mcp/attendance
```

El comando del inspector genera una configuración sin cabeceras, por lo que contra esta ruta protegida hay que agregar manualmente `Authorization: Bearer <token>` en la conexión del Inspector (o probar contra una copia local sin `auth:sanctum`).

### Pruebas

```bash
php artisan test tests/Feature/McpAttendanceToolsTest.php
```

Los tests cubren las seis herramientas (token correcto), el aislamiento con un token de otra sede (resultados vacíos y sin datos de la otra empresa) y el rechazo con `401` cuando falta el token.

## 📄 Licencia

Este proyecto está bajo la licencia MIT.
