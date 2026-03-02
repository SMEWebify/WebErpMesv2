#!/bin/sh
set -e

echo "Lets go to play with WEM on Docker"

cd /app

# Permissions (Laravel)
chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
chmod -R 775 /app/storage /app/bootstrap/cache || true

# .env
if [ ! -f ".env" ]; then
  echo "📌 .env file not found! Copying .env.example..."
  cp .env.example .env
else
  echo "✅ .env file already exists. Keeping current settings."
fi

echo "🔍 Checking environment variables..."
echo "🔍 DB_HOST: $DB_HOST"
echo "🔍 DB_PORT: $DB_PORT"
echo "🔍 DB_DATABASE: $DB_DATABASE"
echo "🔍 DB_USERNAME: $DB_USERNAME"

# Wait for DB
timeout=60
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  echo "⏳ Waiting for database ($DB_HOST:$DB_PORT)..."
  sleep 3
  timeout=$((timeout - 3))
  if [ "$timeout" -le 0 ]; then
    echo "❌ Database is not available after 60 seconds. Exiting."
    exit 1
  fi
done
echo "✅ Database reachable!"

# Dependencies (attention: avec tes volumes, vendor/node_modules existent dans des volumes docker)
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor 2>/dev/null)" ]; then
  echo "📦 Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
  echo "📦 Installing NPM dependencies..."
  npm install
  npm run build
fi

# APP_KEY
if ! grep -q "^APP_KEY=base64:" .env; then
  echo "🔑 Generating application key..."
  php artisan key:generate
fi

# Migrate/seed
echo "📜 Running migrations..."
php artisan migrate --force

echo "🌱 Seeding the database...(PermissionTableSeeder / CreateAdminUserSeeder)"
php artisan db:seed --class=PermissionTableSeeder || true
php artisan db:seed --class=CreateAdminUserSeeder || true

# Dev-friendly caches: clear only (NO route:cache)
echo "⚡ Clearing Laravel caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

echo "📌 All is ready !!!"
echo "⚡ Starting PHP-FPM..."
exec php-fpm -F