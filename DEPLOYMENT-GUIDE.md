# 🚀 Hubizz Deployment Guide - Hostinger Server

## 📋 Pre-Deployment Checklist

### ✅ What You Need Before Starting

#### 1. Hostinger Account Information
- [ ] Hostinger hosting plan (Business or Cloud recommended)
- [ ] cPanel/hPanel access credentials
- [ ] Domain name (e.g., yourdomain.com)
- [ ] FTP/SFTP credentials
- [ ] MySQL database access

#### 2. API Keys & Credentials
- [ ] **Perplexity AI API Key** - https://www.perplexity.ai/settings/api
- [ ] **Amazon PA-API 5.0 Credentials** (optional for affiliate)
  - Access Key ID
  - Secret Access Key
  - Partner Tag
- [ ] **Mail Service** (choose one):
  - SMTP credentials (Gmail, SendGrid, Mailgun)
  - OR use Hostinger's built-in mail

#### 3. Social Login (Optional)
- [ ] Facebook App ID & Secret
- [ ] Google Client ID & Secret
- [ ] Twitter API Keys

---

## 🔧 Step-by-Step Deployment Process

### Step 1: Prepare Your Hostinger Server

#### 1.1 Access hPanel/cPanel
1. Login to Hostinger: https://hpanel.hostinger.com
2. Navigate to your hosting account
3. Click on "File Manager" or "hPanel"

#### 1.2 Check PHP Version
```
Required: PHP 8.1 or higher
```

**To check/update PHP version:**
1. Go to **Advanced → PHP Configuration**
2. Select **PHP 8.1** or **PHP 8.2**
3. Save changes

#### 1.3 Enable Required PHP Extensions
Go to **PHP Configuration** and enable:
- [x] OpenSSL
- [x] PDO
- [x] Mbstring
- [x] Tokenizer
- [x] XML
- [x] Ctype
- [x] JSON
- [x] BCMath
- [x] Fileinfo
- [x] GD (for image processing)
- [x] CURL
- [x] ZIP

#### 1.4 Create MySQL Database
1. Go to **Databases → MySQL Databases**
2. Create new database:
   - **Database name**: `u123456789_hubizz` (example)
   - **Username**: `u123456789_hubizz`
   - **Password**: Generate strong password
   - **Save these credentials!** ⚠️

```
Database Host: localhost
Database Name: u123456789_hubizz
Database User: u123456789_hubizz
Database Password: [your generated password]
```

---

### Step 2: Upload Files to Hostinger

#### Option A: Using Git (Recommended)

**2.1 SSH Access** (if available on your plan)
```bash
# Connect via SSH
ssh u123456789@yourdomain.com

# Navigate to public_html
cd public_html

# Clone repository
git clone https://github.com/wolfcasaba/hubizz.git .

# Or if you want in subdirectory
git clone https://github.com/wolfcasaba/hubizz.git hubizz
```

#### Option B: Using FTP/SFTP (FileZilla)

**2.1 Download FileZilla**
- Download: https://filezilla-project.org/download.php?type=client

**2.2 Connect to Hostinger**
```
Host: ftp.yourdomain.com (or IP from Hostinger)
Username: u123456789 (from Hostinger FTP credentials)
Password: [your FTP password]
Port: 21 (FTP) or 22 (SFTP)
```

**2.3 Upload Files**
1. Download your GitHub repository as ZIP
2. Extract locally
3. Upload **all files** to `/public_html/` (or `/public_html/hubizz/`)
4. This will take 15-30 minutes for all 1,731 files

#### Option C: Using hPanel File Manager

**2.1 Download Repository**
1. Go to GitHub: https://github.com/wolfcasaba/hubizz
2. Click **Code → Download ZIP**
3. Save `hubizz-master.zip`

**2.2 Upload via hPanel**
1. In hPanel, go to **File Manager**
2. Navigate to `public_html`
3. Click **Upload** → Select `hubizz-master.zip`
4. After upload, **Extract** the ZIP file
5. Move all files from `hubizz-master` folder to `public_html` root

---

### Step 3: Configure Environment Variables

#### 3.1 Create .env File

**Via File Manager:**
1. Go to `public_html` in File Manager
2. Find `.env.example` file
3. Right-click → **Copy**
4. Rename copy to `.env`
5. Right-click `.env` → **Edit**

**Via SSH:**
```bash
cd /public_html
cp .env.example .env
nano .env
```

#### 3.2 Fill in .env Configuration

**Open .env and update these values:**

```env
# Application
APP_NAME=Hubizz
APP_ENV=production
APP_KEY=                          # ← Will generate in Step 4
APP_DEBUG=false                   # ← IMPORTANT: Set to false for production
APP_URL=https://yourdomain.com    # ← Your actual domain

# Database (from Step 1.4)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_hubizz     # ← Your database name
DB_USERNAME=u123456789_hubizz     # ← Your database username
DB_PASSWORD=your_db_password      # ← Your database password

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database         # ← Important for background jobs

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com      # ← Hostinger SMTP
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Hubizz Branding
HUBIZZ_NAME="Hubizz"
HUBIZZ_TAGLINE="Where Content Ignites!"
HUBIZZ_DOMAIN="yourdomain.com"    # ← Your domain

# AI Configuration (Perplexity API)
AI_ENABLED=true
AI_PROVIDER=perplexity
PERPLEXITY_API_KEY=pplx-YOUR_API_KEY_HERE  # ← Get from https://www.perplexity.ai/settings/api
AI_MODEL=sonar
AI_MAX_TOKENS=4000
AI_TEMPERATURE=0.7

# Affiliate - Amazon (Optional)
AMAZON_ACCESS_KEY=your_access_key
AMAZON_SECRET_KEY=your_secret_key
AMAZON_PARTNER_TAG=your_partner_tag
AMAZON_REGION=US                  # US, UK, DE, FR, etc.

# Social Login (Optional)
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT=

# reCAPTCHA (Optional)
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
```

**Save the file!** ⚠️

---

### Step 4: Run Installation Commands

#### Option A: Via SSH (Recommended)

```bash
# Navigate to project
cd /public_html

# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Clear and optimize caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R $USER:$USER storage bootstrap/cache
```

#### Option B: Via cPanel Terminal (if available)

1. Go to **Advanced → Terminal** in hPanel/cPanel
2. Run the same commands as Option A

#### Option C: Via Web Interface (if SSH not available)

**4.1 Install Composer Dependencies**
- Download Composer: https://getcomposer.org/composer.phar
- Upload to `/public_html`
- Create `install.php` file:

```php
<?php
// install.php - Upload to public_html and visit: yourdomain.com/install.php

// Run composer install
exec('php composer.phar install --optimize-autoloader --no-dev 2>&1', $output);
echo "<pre>" . implode("\n", $output) . "</pre>";

// Generate key
exec('php artisan key:generate --force 2>&1', $output2);
echo "<pre>" . implode("\n", $output2) . "</pre>";

// Run migrations
exec('php artisan migrate --force 2>&1', $output3);
echo "<pre>" . implode("\n", $output3) . "</pre>";

// Clear caches
exec('php artisan config:cache 2>&1', $output4);
exec('php artisan route:cache 2>&1', $output5);
exec('php artisan view:cache 2>&1', $output6);

echo "<h2>Installation Complete!</h2>";
echo "<p>Delete this file (install.php) for security!</p>";
```

Visit: `https://yourdomain.com/install.php`

**⚠️ Delete install.php after running!**

---

### Step 5: Configure Web Server (Important!)

#### 5.1 Set Document Root

**The Laravel public folder must be the web root!**

**Method 1: Via hPanel**
1. Go to **Advanced → .htaccess**
2. Add at the top:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

**Method 2: Change Document Root (Better)**
1. Go to **Domains** in hPanel
2. Click on your domain
3. Change **Document Root** from `/public_html` to `/public_html/public`
4. Save

**Method 3: Move public contents to root**
```bash
# NOT RECOMMENDED but works
mv public/* ./
mv public/.htaccess ./
```

#### 5.2 Update .htaccess in public folder

Ensure `/public/.htaccess` has:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### Step 6: Set File Permissions

**Critical for Laravel to work!**

```bash
# Via SSH
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public/uploads
chmod 644 .env

# If permission issues persist
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Via File Manager:**
1. Right-click `storage` folder → **Permissions**
2. Set to **755** (rwxr-xr-x)
3. Check **Recurse into subdirectories**
4. Repeat for `bootstrap/cache`

---

### Step 7: Setup Cron Jobs (For Scheduled Tasks)

**Important!** Laravel scheduler needs a cron job.

#### 7.1 Access Cron Jobs
1. Go to **Advanced → Cron Jobs** in hPanel
2. Click **Create Cron Job**

#### 7.2 Add Laravel Scheduler

```
Schedule: * * * * * (Every minute)
Command: cd /home/u123456789/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**Or with full path:**
```
* * * * * /usr/bin/php /home/u123456789/public_html/artisan schedule:run >> /dev/null 2>&1
```

**Replace:**
- `u123456789` with your actual Hostinger username
- `/public_html` with your actual path

#### 7.3 Verify Cron is Working
Check after 5 minutes:
```bash
tail -f storage/logs/laravel.log
```

---

### Step 8: Setup Queue Worker (For Background Jobs)

**Important!** Hubizz uses queues for AI generation, RSS processing, etc.

#### Option A: Supervisor (Best - requires SSH/VPS)
```bash
# Install supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/hubizz-worker.conf
```

```ini
[program:hubizz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/u123456789/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=u123456789
numprocs=2
redirect_stderr=true
stdout_logfile=/home/u123456789/public_html/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hubizz-worker:*
```

#### Option B: Cron Job (Shared Hosting)
Add another cron job:

```
Schedule: */5 * * * * (Every 5 minutes)
Command: cd /home/u123456789/public_html && php artisan queue:work --stop-when-empty --tries=3
```

---

### Step 9: Create Admin User

#### Via SSH:
```bash
php artisan tinker

# In tinker console:
$user = new App\User();
$user->username = 'admin';
$user->email = 'admin@yourdomain.com';
$user->password = bcrypt('your_secure_password');
$user->role = 'admin';
$user->active = 1;
$user->save();
exit
```

#### Via Database (phpMyAdmin):
1. Go to **Databases → phpMyAdmin**
2. Select your database
3. Click on `users` table
4. Click **Insert**
5. Fill in:
   - username: `admin`
   - email: `admin@yourdomain.com`
   - password: Use online bcrypt generator: https://bcrypt-generator.com/
   - role: `admin`
   - active: `1`
6. Click **Go**

---

### Step 10: SSL Certificate (HTTPS)

**Hostinger provides free SSL!**

#### 10.1 Enable SSL
1. Go to **SSL** in hPanel
2. Select your domain
3. Click **Install SSL**
4. Choose **Free SSL** (Let's Encrypt)
5. Wait 5-10 minutes for installation

#### 10.2 Force HTTPS
Add to `/public/.htaccess` (at the top):

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST%{REQUEST_URI} [L,R=301]
```

#### 10.3 Update .env
```env
APP_URL=https://yourdomain.com  # ← Must use https://
```

Then clear cache:
```bash
php artisan config:cache
```

---

## ✅ Final Verification Checklist

### Test Your Installation

- [ ] **Homepage loads**: https://yourdomain.com
- [ ] **Admin panel accessible**: https://yourdomain.com/admin
- [ ] **Can login** with admin credentials
- [ ] **Database connected** (check admin dashboard)
- [ ] **Images upload** (test in admin)
- [ ] **Cron jobs running** (check logs)
- [ ] **Queue working** (create test job)
- [ ] **SSL working** (green padlock in browser)
- [ ] **Mail sending** (test contact form)
- [ ] **API working** (test Perplexity AI if configured)

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

---

## 🔧 Common Issues & Solutions

### Issue 1: 500 Internal Server Error
**Solutions:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate cache
php artisan config:cache
php artisan route:cache
```

### Issue 2: Permission Denied
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Issue 3: Database Connection Failed
- Verify database credentials in `.env`
- Check database exists in phpMyAdmin
- Ensure DB_HOST is `localhost` not `127.0.0.1`

### Issue 4: Composer Not Found
```bash
# Download composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### Issue 5: Queue Not Processing
- Check cron job is running: `crontab -l`
- Manually run: `php artisan queue:work`
- Check `jobs` table in database

---

## 📞 Support Resources

- **Hostinger Support**: https://www.hostinger.com/tutorials
- **Laravel Documentation**: https://laravel.com/docs/10.x/deployment
- **Hubizz Documentation**: See GitHub repository
- **Community**: Create issue on GitHub

---

## 🎉 Deployment Complete!

Your Hubizz installation should now be live at:
- **Frontend**: https://yourdomain.com
- **Admin Panel**: https://yourdomain.com/admin

**Next Steps:**
1. Configure RSS feeds in admin
2. Setup affiliate networks
3. Add Perplexity API key
4. Create categories
5. Start creating content!

---

**🔥 HUBIZZ - WHERE CONTENT IGNITES!** 🔥

**Deployed Successfully!** ✅
