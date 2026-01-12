#!/bin/bash

# Holibob Deployment Script
# This script deploys Holibob to production using Docker Compose

set -e

echo "🚀 Starting Holibob deployment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please copy .env.production.example to .env and configure it first."
    exit 1
fi

# Pull latest changes (if using git)
if [ -d .git ]; then
    echo "📥 Pulling latest changes from git..."
    git pull origin main
fi

# Build frontend assets
echo "🎨 Building frontend assets..."
npm install
npm run build

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker-compose -f docker-compose.prod.yml down

# Build and start containers
echo "🐳 Building and starting Docker containers..."
docker-compose -f docker-compose.prod.yml up -d --build

# Wait for database to be ready
echo "⏳ Waiting for database..."
sleep 10

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T php php artisan migrate --force

# Cache configuration
echo "⚡ Caching configuration..."
docker-compose -f docker-compose.prod.yml exec -T php php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T php php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T php php artisan view:cache

# Index properties in Meilisearch
echo "🔍 Indexing properties for search..."
docker-compose -f docker-compose.prod.yml exec -T php php artisan scout:import "App\\Models\\Property" || true

# Set correct permissions
echo "🔐 Setting permissions..."
docker-compose -f docker-compose.prod.yml exec -T php chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Deployment complete!"
echo ""
echo "Your application is now running at:"
echo "http://$(curl -s ifconfig.me)"
echo ""
echo "Useful commands:"
echo "  - View logs:        docker-compose -f docker-compose.prod.yml logs -f"
echo "  - Restart:          docker-compose -f docker-compose.prod.yml restart"
echo "  - Stop:             docker-compose -f docker-compose.prod.yml down"
echo "  - Run migrations:   docker-compose -f docker-compose.prod.yml exec php php artisan migrate"
echo "  - Clear cache:      docker-compose -f docker-compose.prod.yml exec php php artisan cache:clear"
