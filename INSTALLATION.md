# Installation Guide - OBE System

Complete installation guide for OBE System with all infrastructure features.

## 📋 Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Testing](#testing)
6. [Production Deployment](#production-deployment)
7. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

- **PHP:** >= 8.3
- **PostgreSQL:** >= 14
- **Memory:** 512MB RAM
- **Disk Space:** 1GB
- **Composer:** Latest version

### Required PHP Extensions

```bash
php -m | grep -E "pdo|pgsql|json|mbstring|gd|zip"
```

Required extensions:
- `pdo`, `pdo_pgsql` - Database connectivity
- `json` - JSON processing
- `mbstring` - Multibyte string functions
- `gd` - Image processing for PDF
- `zip` - Excel export functionality

### Optional Requirements

- **Redis** - For persistent rate limiting (recommended for production)
- **Supervisor** - For background job processing
- **Nginx/Apache** - Web server (or use PHP built-in server for development)

---

## Installation Steps

### 1. Clone Repository

```bash
git clone <repository-url>
cd php-obe
```

### 2. Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

For development:
```bash
composer install
```

### 3. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` file with your configuration:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=obe_system
DB_USER=obe_user
DB_PASSWORD=your_secure_password

# JWT
JWT_SECRET=your_very_secure_secret_key_min_32_characters
JWT_EXPIRY=7200

# CORS
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With

# Logging
LOG_LEVEL=info
LOG_PATH=logs/app.log

# Upload
UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=pdf,docx,xlsx,pptx,jpg,jpeg,png
UPLOAD_PATH=storage/uploads

# Email
MAIL_ENABLED=true
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=OBE System

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60
```

### 4. Create Required Directories

```bash
mkdir -p storage/uploads
mkdir -p logs
chmod -R 775 storage logs
```

### 5. Setup File Permissions

```bash
# For Apache/Nginx
chown -R www-data:www-data storage logs
chmod -R 775 storage logs

# Make migration script executable
chmod +x migrate.php
```

---

## Database Setup

### Option 1: Using Migration System (Recommended)

```bash
# Create database
createdb obe_system

# Run migrations
php migrate.php migrate

# Seed database with sample data
php migrate.php seed

# Check migration status
php migrate.php status
```

### Option 2: Direct SQL Import

```bash
# Create database
createdb obe_system

# Import schema
psql -d obe_system -f OBE-Database-Schema-v3-WITH-KURIKULUM.sql
```

### Database User Setup

```sql
-- Create dedicated database user
CREATE USER obe_user WITH PASSWORD 'your_secure_password';

-- Grant permissions
GRANT ALL PRIVILEGES ON DATABASE obe_system TO obe_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO obe_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO obe_user;
```

---

## Configuration

### Web Server Configuration

#### Apache

Create virtual host `/etc/apache2/sites-available/obe-system.conf`:

```apache
<VirtualHost *:80>
    ServerName obe-system.your-domain.com
    DocumentRoot /var/www/php-obe/public

    <Directory /var/www/php-obe/public>
        AllowOverride All
        Require all granted

        # Enable rewrite
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>

    # Security headers (additional layer)
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    ErrorLog ${APACHE_LOG_DIR}/obe-error.log
    CustomLog ${APACHE_LOG_DIR}/obe-access.log combined
</VirtualHost>

# SSL Configuration (Recommended)
<VirtualHost *:443>
    ServerName obe-system.your-domain.com
    DocumentRoot /var/www/php-obe/public

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/php-obe/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable site:
```bash
a2ensite obe-system
a2enmod rewrite
systemctl reload apache2
```

#### Nginx

Create configuration `/etc/nginx/sites-available/obe-system`:

```nginx
server {
    listen 80;
    server_name obe-system.your-domain.com;
    root /var/www/php-obe/public;

    index index.php;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # Increase timeout for export operations
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }

    access_log /var/log/nginx/obe-access.log;
    error_log /var/log/nginx/obe-error.log;
}

# SSL Configuration (Recommended)
server {
    listen 443 ssl http2;
    server_name obe-system.your-domain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    root /var/www/php-obe/public;
    # ... rest of configuration same as above
}
```

Enable site:
```bash
ln -s /etc/nginx/sites-available/obe-system /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### PHP Configuration

Edit `/etc/php/8.3/fpm/php.ini` or `/etc/php/8.3/apache2/php.ini`:

```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
display_errors = Off
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php_errors.log
```

Restart PHP-FPM:
```bash
systemctl restart php8.3-fpm
```

---

## Testing

### Run Migrations Test

```bash
php migrate.php status
```

### Run PHPUnit Tests

```bash
# Run all tests
composer test

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration
```

### Test API Endpoints

```bash
# Health check
curl http://localhost:8000/api/health

# Detailed health check
curl http://localhost:8000/api/health/detailed

# Login test
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'
```

### Access API Documentation

Open in browser:
```
http://localhost:8000/api-docs.html
```

---

## Production Deployment

### Security Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate strong `JWT_SECRET` (min 32 characters)
- [ ] Use HTTPS (SSL/TLS certificates)
- [ ] Configure firewall (only allow ports 80, 443, 22)
- [ ] Set proper file permissions (775 for storage, 644 for files)
- [ ] Enable rate limiting (`RATE_LIMIT_ENABLED=true`)
- [ ] Configure proper CORS origins
- [ ] Set up database backup schedule
- [ ] Configure log rotation
- [ ] Review and update `.env` security settings

### Performance Optimization

```bash
# Enable OPcache
echo "opcache.enable=1" >> /etc/php/8.3/fpm/conf.d/10-opcache.ini
echo "opcache.memory_consumption=128" >> /etc/php/8.3/fpm/conf.d/10-opcache.ini

# Optimize Composer autoloader
composer dump-autoload --optimize --no-dev

# Restart PHP-FPM
systemctl restart php8.3-fpm
```

### Log Rotation

Create `/etc/logrotate.d/obe-system`:

```
/var/www/php-obe/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

### Database Backup

Setup automated backup:

```bash
#!/bin/bash
# /usr/local/bin/backup-obe-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/obe-system"
DB_NAME="obe_system"

mkdir -p $BACKUP_DIR

pg_dump $DB_NAME | gzip > $BACKUP_DIR/obe_system_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

Add to crontab:
```bash
0 2 * * * /usr/local/bin/backup-obe-db.sh
```

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed

**Error:** `could not connect to server: Connection refused`

**Solution:**
```bash
# Check PostgreSQL status
systemctl status postgresql

# Check connection
psql -U obe_user -d obe_system -h localhost
```

#### 2. File Upload Fails

**Error:** `Failed to move uploaded file`

**Solution:**
```bash
# Fix permissions
chmod -R 775 storage/uploads
chown -R www-data:www-data storage/uploads
```

#### 3. PDF Generation Fails

**Error:** `Class 'Mpdf\Mpdf' not found`

**Solution:**
```bash
# Install dependencies
composer install
composer dump-autoload
```

#### 4. High Memory Usage

**Solution:**
```ini
# Increase PHP memory limit
memory_limit = 512M
```

#### 5. Slow API Response

**Solution:**
```bash
# Check database connections
php migrate.php status

# Enable query logging
tail -f logs/app.log

# Check health metrics
curl http://localhost:8000/api/health/metrics
```

### Debug Mode

For debugging (development only):

```env
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

View logs:
```bash
tail -f logs/app.log
```

---

## Support

For issues and questions:
- Check logs: `logs/app.log`
- Review health check: `/api/health/detailed`
- Consult documentation: `/api-docs.html`
- Check migration status: `php migrate.php status`

---

**Version:** 1.0.0
**Last Updated:** November 2024
**Status:** Production Ready ✅
