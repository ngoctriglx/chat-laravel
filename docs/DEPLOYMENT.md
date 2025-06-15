# Deployment Guide

This guide covers deploying the Laravel Chat Application to production environments, including server setup, optimization, and maintenance.

## 🚀 Production Deployment Overview

### Deployment Options

1. **Traditional VPS/Server** - Full control, manual setup
2. **Cloud Platforms** - AWS, Google Cloud, DigitalOcean
3. **Container Platforms** - Docker, Kubernetes
4. **Platform as a Service** - Heroku, Vercel, Railway

### Recommended Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Load Balancer │    │   Web Server    │    │   Database      │
│   (Nginx)       │───▶│   (Laravel)     │───▶│   (MySQL/PostgreSQL) │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   Redis Cache   │
                       │   & Queue       │
                       └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   WebSocket     │
                       │   Server        │
                       │   (Reverb)      │
                       └─────────────────┘
```

## 📋 Prerequisites

### Server Requirements

- **Operating System**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+
- **PHP**: 8.2 or higher with required extensions
- **Web Server**: Nginx or Apache
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / MariaDB 10.5+
- **Redis**: 6.0 or higher
- **Memory**: Minimum 4GB RAM (8GB recommended)
- **Storage**: At least 10GB free space
- **SSL Certificate**: For HTTPS

### Required PHP Extensions

```bash
# Install PHP extensions
sudo apt-get install php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl \
    php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath \
    php8.2-intl php8.2-soap php8.2-ldap php8.2-imap
```

## 🔧 Server Setup

### 1. Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Required Software

```bash
# Install Nginx
sudo apt install nginx -y

# Install MySQL
sudo apt install mysql-server -y

# Install Redis
sudo apt install redis-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Configure MySQL

```bash
sudo mysql_secure_installation
```

Create database and user:
```sql
CREATE DATABASE chat_laravel;
CREATE USER 'chat_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON chat_laravel.* TO 'chat_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Configure Redis

Edit `/etc/redis/redis.conf`:
```conf
# Enable persistence
save 900 1
save 300 10
save 60 10000

# Set memory limit
maxmemory 256mb
maxmemory-policy allkeys-lru

# Security
requirepass your_redis_password
```

Restart Redis:
```bash
sudo systemctl restart redis
```

## 📦 Application Deployment

### 1. Clone Application

```bash
cd /var/www
sudo git clone <repository-url> chat-laravel
sudo chown -R www-data:www-data chat-laravel
cd chat-laravel
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies (for asset compilation)
npm install
npm run build
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Configure `.env` for production:
```env
APP_NAME="Laravel Chat"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_laravel
DB_USERNAME=chat_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https

# Queue and Cache
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# File Storage
FILESYSTEM_DISK=public

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

### 4. Database Setup

```bash
# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link

# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 5. Optimize Application

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

## 🌐 Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/chat-laravel`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Root directory
    root /var/www/chat-laravel/public;
    index index.php index.html index.htm;
    
    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }
    
    # Handle Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/chat-laravel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 🔄 Queue Worker Setup

### Supervisor Configuration

Install Supervisor:
```bash
sudo apt install supervisor -y
```

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/chat-laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/chat-laravel/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 🌐 WebSocket Server Setup

### Systemd Service for Reverb

Create `/etc/systemd/system/reverb.service`:

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/chat-laravel
ExecStart=/usr/bin/php artisan reverb:start
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```bash
sudo systemctl enable reverb
sudo systemctl start reverb
```

## 🔒 SSL Certificate Setup

### Let's Encrypt (Recommended)

Install Certbot:
```bash
sudo apt install certbot python3-certbot-nginx -y
```

Obtain certificate:
```bash
sudo certbot --nginx -d your-domain.com
```

Auto-renewal:
```bash
sudo crontab -e
# Add this line:
0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 Monitoring and Logging

### Application Logs

Configure log rotation in `/etc/logrotate.d/laravel`:

```
/var/www/chat-laravel/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    notifempty
    create 644 www-data www-data
}
```

### Health Monitoring

Create a monitoring script:

```bash
#!/bin/bash
# /usr/local/bin/health-check.sh

# Check application health
curl -f https://your-domain.com/api/health || exit 1

# Check database connection
php /var/www/chat-laravel/artisan tinker --execute="DB::connection()->getPdo();" || exit 1

# Check Redis connection
php /var/www/chat-laravel/artisan tinker --execute="Redis::ping();" || exit 1
```

## 🔧 Maintenance Commands

### Regular Maintenance

```bash
# Clear expired cache
php artisan cache:clear

# Clear expired sessions
php artisan session:table
php artisan session:gc

# Optimize database
php artisan db:monitor

# Backup database
mysqldump -u chat_user -p chat_laravel > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Update Application

```bash
# Pull latest changes
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Run migrations
php artisan migrate

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo supervisorctl restart laravel-worker:*
sudo systemctl restart reverb
```

## 🚨 Security Checklist

### Server Security

- [ ] Firewall configured (UFW)
- [ ] SSH key authentication only
- [ ] Regular security updates
- [ ] Fail2ban installed and configured
- [ ] SSL certificate installed
- [ ] Security headers configured

### Application Security

- [ ] Debug mode disabled
- [ ] Strong database passwords
- [ ] Redis password set
- [ ] File permissions correct
- [ ] Rate limiting enabled
- [ ] CORS properly configured

### Data Protection

- [ ] Database backups automated
- [ ] File uploads validated
- [ ] Sensitive data encrypted
- [ ] Log files secured
- [ ] Error reporting disabled

## 📈 Performance Optimization

### PHP-FPM Configuration

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
memory_limit = 512M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
```

### MySQL Optimization

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 64M
query_cache_type = 1
```

## 🔄 Backup Strategy

### Automated Backups

Create backup script `/usr/local/bin/backup.sh`:

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/chat-laravel"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u chat_user -p'secure_password' chat_laravel > $BACKUP_DIR/db_$DATE.sql

# File backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/chat-laravel/storage/app/public

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

Add to crontab:
```bash
0 2 * * * /usr/local/bin/backup.sh
```

---

## 📞 Support and Troubleshooting

### Common Issues

1. **Permission Errors**: Check file ownership and permissions
2. **Database Connection**: Verify credentials and network access
3. **WebSocket Issues**: Check firewall and port availability
4. **Queue Failures**: Monitor supervisor logs

### Useful Commands

```bash
# Check application status
sudo systemctl status nginx php8.2-fpm mysql redis

# View logs
sudo tail -f /var/www/chat-laravel/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log

# Monitor queue workers
sudo supervisorctl status

# Check WebSocket server
sudo systemctl status reverb
```

For more information, see the [Installation Guide](./INSTALLATION.md) and [Configuration Guide](./CONFIGURATION.md). 