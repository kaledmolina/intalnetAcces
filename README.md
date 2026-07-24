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

## 📄 Licencia

Este proyecto está bajo la licencia MIT.
