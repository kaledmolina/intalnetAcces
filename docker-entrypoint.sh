#!/bin/sh
set -e

echo "🚀 Iniciando contenedor IntalnetAcces..."

# Asegurar archivo de base de datos SQLite si está configurado SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo "📦 Creando archivo de base de datos database.sqlite..."
        touch /var/www/html/database/database.sqlite
    fi
fi

# Ajustar permisos de almacenamiento y public
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/public
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/public

# Ejecutar enlace de almacenamiento
echo "🔗 Creando enlaces de almacenamiento..."
php artisan storage:link || true

# Publicar assets de Livewire
echo "🎨 Publicando assets de Livewire..."
php artisan livewire:publish --assets || true

# Ejecutar migraciones automáticas
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force

# Limpiar y optimizar cachés en producción
echo "⚡ Optimizando caché de Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ IntalnetAcces listo. Iniciando PHP-FPM..."
exec "$@"
