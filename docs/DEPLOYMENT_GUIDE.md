# Holibob Deployment Guide - Ubuntu/Vultr

**Last Updated**: January 12, 2026

This guide will help you deploy Holibob to your Vultr Ubuntu server using Docker.

---

## Prerequisites

### Server Requirements

**Minimum Recommended**:
- **RAM**: 2GB (4GB recommended)
- **CPU**: 2 vCPUs
- **Disk**: 25GB SSD
- **OS**: Ubuntu 22.04 LTS or 24.04 LTS

**Vultr Plan Suggestion**: High Performance or Cloud Compute - $12/month+ plan

### What You'll Need

1. Fresh Ubuntu server with root access
2. Domain name (optional but recommended for SSL)
3. SSH access to your server

---

## Step 1: Initial Server Setup

### 1.1 Connect to Your Server

```bash
ssh root@YOUR_SERVER_IP
```

### 1.2 Update System Packages

```bash
apt update && apt upgrade -y
```

### 1.3 Install Docker

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Start Docker
systemctl start docker
systemctl enable docker

# Verify installation
docker --version
```

### 1.4 Install Docker Compose

```bash
# Download Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Make executable
chmod +x /usr/local/bin/docker-compose

# Verify installation
docker-compose --version
```

### 1.5 Install Git

```bash
apt install git -y
```

### 1.6 Install Node.js (for building frontend)

```bash
# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Verify installation
node --version
npm --version
```

---

## Step 2: Deploy the Application

### 2.1 Clone the Repository

**Important**: If your repository is private, you'll need to authenticate. GitHub no longer supports password authentication - you must use either:
1. **Personal Access Token (PAT)** - Recommended for private repos
2. **SSH Key** - Alternative method
3. **Make repo public temporarily** - Easiest for initial deployment

#### Option A: Using Personal Access Token (for private repos)

```bash
# Create app directory with proper permissions
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www

# Clone with token in URL (replace YOUR_TOKEN)
git clone https://YOUR_TOKEN@github.com/V4nish/holibob-core.git holibob
cd holibob

# Remove token from git config for security
git remote set-url origin https://github.com/V4nish/holibob-core.git
```

**To create a GitHub Personal Access Token:**
1. Go to https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Name it "Holibob Deployment"
4. Select scopes: `repo` (full control)
5. Click "Generate token" and copy it immediately

#### Option B: Using SSH Key (for private repos)

```bash
# Generate SSH key on server
ssh-keygen -t ed25519 -C "your_email@example.com"
# Press Enter for all prompts (use defaults)

# Display public key
cat ~/.ssh/id_ed25519.pub
# Copy the output

# Add to GitHub:
# Go to https://github.com/settings/keys
# Click "New SSH key", paste the key, save

# Clone with SSH
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www
git clone git@github.com:V4nish/holibob-core.git holibob
cd holibob
```

#### Option C: Public Repository (easiest)

```bash
# Make your repo public temporarily at:
# https://github.com/V4nish/holibob-core/settings

# Create app directory with proper permissions
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www

# Clone without authentication
git clone https://github.com/V4nish/holibob-core.git holibob
cd holibob

# Make private again after deployment
```

### 2.2 Configure Environment

```bash
# Copy production environment template
cp .env.production.example .env

# Edit environment file
nano .env
```

**Important: Update these values in `.env`:**

```env
# Generate a secure app key (we'll do this after Docker is running)
APP_KEY=

# Set your server's IP or domain
APP_URL=http://YOUR_SERVER_IP

# Database password (create a strong password!)
DB_PASSWORD=your_secure_database_password_here

# Redis password (create a strong password!)
REDIS_PASSWORD=your_secure_redis_password_here

# Meilisearch key (create a 32+ character key)
MEILISEARCH_KEY=your_secure_meilisearch_master_key_min_32_chars
```

**Generate Secure Passwords**:
```bash
# Generate random passwords
openssl rand -base64 32
```

### 2.3 Install PHP Dependencies

```bash
# Install Composer globally (if not already installed)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install PHP dependencies for production
composer install --no-dev --optimize-autoloader --no-interaction
```

This installs Laravel and all required PHP packages.

### 2.4 Build Frontend Assets

```bash
# Install Node dependencies
npm install

# Build production assets
npm run build
```

This will take a few minutes. You should see output ending with:
```
✓ built in X.XXs
```

### 2.5 Start Docker Containers

```bash
# Build and start all containers
docker-compose -f docker-compose.prod.yml up -d --build
```

This will:
- Build the PHP container with optimizations
- Start Nginx, PostgreSQL, Redis, Meilisearch
- Start queue worker and scheduler

**First Build**: Takes 5-10 minutes. Subsequent builds are much faster.

### 2.6 Generate Application Key

```bash
# Generate Laravel application key
docker-compose -f docker-compose.prod.yml exec php php artisan key:generate
```

This updates your `.env` file with a secure APP_KEY.

### 2.7 Run Database Migrations

```bash
# Create database tables
docker-compose -f docker-compose.prod.yml exec php php artisan migrate --force
```

Answer `yes` when prompted.

### 2.8 Seed Initial Data (Optional)

```bash
# Seed amenities
docker-compose -f docker-compose.prod.yml exec php php artisan db:seed --class=AmenitySeeder
```

---

## Step 3: Verify Deployment

### 3.1 Check Container Status

```bash
docker-compose -f docker-compose.prod.yml ps
```

You should see 7 containers running:
- holibob-nginx (port 80)
- holibob-php
- holibob-postgres
- holibob-redis
- holibob-meilisearch
- holibob-queue
- holibob-scheduler

### 3.2 Check Application Logs

```bash
# View all logs
docker-compose -f docker-compose.prod.yml logs -f

# View specific service
docker-compose -f docker-compose.prod.yml logs -f nginx
docker-compose -f docker-compose.prod.yml logs -f php

# Press Ctrl+C to exit
```

### 3.3 Test the Application

Open your browser and visit:
```
http://YOUR_SERVER_IP
```

You should see the Holibob homepage!

---

## Step 4: Configure Firewall (Important!)

```bash
# Install UFW (Uncomplicated Firewall)
apt install ufw -y

# Allow SSH (IMPORTANT - do this first!)
ufw allow ssh
ufw allow 22/tcp

# Allow HTTP
ufw allow 80/tcp

# Allow HTTPS (for when you add SSL)
ufw allow 443/tcp

# Enable firewall
ufw enable

# Check status
ufw status
```

---

## Step 5: Setup Domain & SSL (Recommended)

### 5.1 Point Domain to Server

In your domain registrar (Namecheap, GoDaddy, Cloudflare, etc.):

1. Create an `A` record pointing to your server IP
   - Host: `@` or your subdomain
   - Value: `YOUR_SERVER_IP`
   - TTL: 300 (5 minutes)

2. Wait for DNS propagation (5-30 minutes)
   ```bash
   # Test DNS
   nslookup your-domain.com
   ```

### 5.2 Install Certbot for Free SSL

```bash
# Install Certbot
apt install certbot -y

# Stop Nginx temporarily
docker-compose -f docker-compose.prod.yml stop nginx

# Get SSL certificate
certbot certonly --standalone -d your-domain.com -d www.your-domain.com

# Follow the prompts and enter your email
```

### 5.3 Configure Nginx for HTTPS

```bash
# Create SSL directory
mkdir -p docker/nginx/ssl

# Copy certificates
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/nginx/ssl/
cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/nginx/ssl/

# Edit Nginx config
nano docker/nginx/conf.d/prod.conf
```

In `prod.conf`:
1. Uncomment the HTTPS server block (lines starting with #)
2. Replace `your-domain.com` with your actual domain
3. In the HTTP server block, uncomment the redirect line:
   ```nginx
   return 301 https://$host$request_uri;
   ```

Save and exit (Ctrl+X, Y, Enter).

```bash
# Update APP_URL in .env
nano .env
```

Change:
```env
APP_URL=https://your-domain.com
```

```bash
# Restart containers
docker-compose -f docker-compose.prod.yml restart nginx
```

### 5.4 Auto-Renew SSL Certificates

```bash
# Create renewal script
cat > /etc/cron.daily/certbot-renew << 'EOF'
#!/bin/bash
docker-compose -f /var/www/holibob/docker-compose.prod.yml stop nginx
certbot renew --quiet
cp /etc/letsencrypt/live/your-domain.com/fullchain.pem /var/www/holibob/docker/nginx/ssl/
cp /etc/letsencrypt/live/your-domain.com/privkey.pem /var/www/holibob/docker/nginx/ssl/
docker-compose -f /var/www/holibob/docker-compose.prod.yml start nginx
EOF

chmod +x /etc/cron.daily/certbot-renew
```

---

## Step 6: Create Test Property (Optional)

```bash
# Access tinker
docker-compose -f docker-compose.prod.yml exec php php artisan tinker
```

```php
// Create test data
$provider = \App\Models\AffiliateProvider::create([
    'name' => 'Demo Provider',
    'slug' => 'demo',
    'adapter_class' => \Holibob\Affiliates\Providers\SykesProvider::class,
    'is_active' => true,
]);

$location = \App\Models\Location::create([
    'name' => 'Cornwall',
    'slug' => 'cornwall',
    'type' => 'county',
]);

for ($i = 1; $i <= 10; $i++) {
    \App\Models\Property::create([
        'affiliate_provider_id' => $provider->id,
        'location_id' => $location->id,
        'external_id' => 'DEMO' . str_pad($i, 3, '0', STR_PAD_LEFT),
        'name' => 'Beautiful Cottage ' . $i . ' in Cornwall',
        'slug' => 'cottage-' . $i . '-cornwall-demo' . $i,
        'description' => 'A stunning cottage with beautiful sea views and modern amenities. Perfect for families looking for a peaceful getaway.',
        'short_description' => 'Stunning cottage with sea views',
        'property_type' => ['cottage', 'hotel', 'caravan', 'yurt'][array_rand([0,1,2,3])],
        'sleeps' => rand(2, 10),
        'bedrooms' => rand(1, 5),
        'bathrooms' => rand(1, 3),
        'price_from' => rand(400, 2000),
        'price_currency' => 'GBP',
        'affiliate_url' => 'https://www.sykescottages.co.uk/cottage/DEMO' . $i,
        'is_active' => true,
        'featured' => $i <= 3,
    ]);
}

echo "Created 10 test properties!\n";
exit
```

Then index them for search:
```bash
docker-compose -f docker-compose.prod.yml exec php php artisan scout:import "App\\Models\\Property"
```

---

## Step 7: Maintenance Commands

### View Logs

```bash
# All services
docker-compose -f docker-compose.prod.yml logs -f

# Specific service
docker-compose -f docker-compose.prod.yml logs -f php
docker-compose -f docker-compose.prod.yml logs -f nginx
docker-compose -f docker-compose.prod.yml logs -f queue
```

### Restart Services

```bash
# Restart all
docker-compose -f docker-compose.prod.yml restart

# Restart specific service
docker-compose -f docker-compose.prod.yml restart nginx
docker-compose -f docker-compose.prod.yml restart php
```

### Run Commands

```bash
# Run artisan commands
docker-compose -f docker-compose.prod.yml exec php php artisan [command]

# Examples:
docker-compose -f docker-compose.prod.yml exec php php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec php php artisan migrate
docker-compose -f docker-compose.prod.yml exec php php artisan search:stats
docker-compose -f docker-compose.prod.yml exec php php artisan affiliate:sync sykes
```

### Update Application

```bash
cd /var/www/holibob

# Pull latest code (will use SSH key or token if configured)
git pull origin main

# Rebuild frontend
npm install
npm run build

# Use deployment script
./deploy.sh
```

**Note**: If you cloned with a Personal Access Token, you'll need to either:
- Set up SSH key (recommended for future updates)
- Use `git pull` with token: `git pull https://YOUR_TOKEN@github.com/V4nish/holibob-core.git main`
- Make repo public for easier updates

---

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs [service-name]

# Rebuild container
docker-compose -f docker-compose.prod.yml up -d --build [service-name]
```

### Database Connection Failed

Check `.env` file:
- `DB_HOST=postgres` (not localhost!)
- `DB_PASSWORD` matches in both `.env` and docker-compose

```bash
# Restart database
docker-compose -f docker-compose.prod.yml restart postgres
```

### Website Shows 500 Error

```bash
# Check PHP logs
docker-compose -f docker-compose.prod.yml logs php

# Clear cache
docker-compose -f docker-compose.prod.yml exec php php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec php php artisan config:clear
```

### Permission Issues

```bash
docker-compose -f docker-compose.prod.yml exec php chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec php chmod -R 775 storage bootstrap/cache
```

### Search Not Working

```bash
# Check Meilisearch
docker-compose -f docker-compose.prod.yml logs meilisearch

# Reindex properties
docker-compose -f docker-compose.prod.yml exec php php artisan scout:import "App\\Models\\Property"
```

---

## Performance Tips

### 1. Enable OPcache (Already Configured)

OPcache is enabled in the production Dockerfile for better PHP performance.

### 2. Use Redis for Sessions and Cache

Already configured in `.env`:
```env
SESSION_DRIVER=redis
CACHE_STORE=redis
```

### 3. Queue Background Jobs

Queue worker is already running. Sync jobs are automatically queued.

### 4. Monitor Resources

```bash
# Check Docker resource usage
docker stats

# Check server resources
htop  # Install with: apt install htop
```

---

## Security Checklist

- [ ] Change all default passwords in `.env`
- [ ] Enable firewall (UFW)
- [ ] Install SSL certificate (Let's Encrypt)
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Use strong `APP_KEY` (auto-generated)
- [ ] Regularly update system packages: `apt update && apt upgrade`
- [ ] Regularly update Docker images: `docker-compose pull && docker-compose up -d`
- [ ] Keep Git repository private (contains sensitive config)
- [ ] Set up automated backups (database and `.env`)

---

## Backup Strategy

### Database Backup

```bash
# Manual backup
docker-compose -f docker-compose.prod.yml exec postgres pg_dump -U holibob holibob > backup-$(date +%Y%m%d).sql

# Restore from backup
docker-compose -f docker-compose.prod.yml exec -T postgres psql -U holibob holibob < backup-20260112.sql
```

### Automated Daily Backups

```bash
# Create backup script
cat > /root/backup-holibob.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/root/holibob-backups"
mkdir -p $BACKUP_DIR
cd /var/www/holibob
docker-compose -f docker-compose.prod.yml exec -T postgres pg_dump -U holibob holibob > $BACKUP_DIR/db-$(date +%Y%m%d-%H%M%S).sql
find $BACKUP_DIR -name "db-*.sql" -mtime +7 -delete
EOF

chmod +x /root/backup-holibob.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /root/backup-holibob.sh") | crontab -
```

---

## Support

For issues:
1. Check logs: `docker-compose -f docker-compose.prod.yml logs`
2. Review this guide carefully
3. Check GitHub issues: https://github.com/V4nish/holibob-core/issues

---

**Congratulations!** 🎉

Your Holibob application should now be live and accessible!

Visit: `http://YOUR_SERVER_IP` (or `https://your-domain.com` if SSL configured)
