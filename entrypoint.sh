#!/bin/sh

# Stop the script in case of an error
set -e

echo "Lets go to play with WEM on Docker"

# Grant the necessary permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Check if .env exists, otherwise copy the template
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

# Wait for the database to be available
timeout=30
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  echo "⏳ Waiting for database ($DB_HOST:$DB_PORT)..."
  sleep 5
  timeout=$((timeout - 5))
  if [ "$timeout" -le 0 ]; then
    echo "❌ Database is not available after 30 seconds. Exiting."
    exit 1
  fi
done


# Install dependencies if necessary
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d "node_modules" ]; then
    echo "📦 Installing NPM dependencies..."
    npm install
    npm run build
fi

# Generate application key if needed
if [ -z "$(grep APP_KEY .env | cut -d '=' -f2)" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Run migrations and seeders if needed
echo "📜 Waiting 10 seconds for MySQL to be ready..."
sleep 10
echo "📜 Running migrations..."
echo "✅ Lets gooooo !!!"
php artisan migrate --force

# Seeding the database
echo "🌱 Seeding the database...(PermissionTableSeeder / CreateAdminUserSeeder)"
php artisan db:seed --class=PermissionTableSeeder
php artisan db:seed --class=CreateAdminUserSeeder

echo "✅ You have strong user now !"

# Clear and cache the configuration
echo "⚡ Optimizing Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "⚙️ Optimizing Composer autoload..."
composer dump-autoload --optimize
echo "📌 All is ready !!!"
echo "⚡ Launching Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=8000
echo "✅ Good Job! WEM is ready to play!"