# 🚀 Hubizz - Hostinger Quick Deploy Guide

## Step 1: Deploy from Git Repository

You're on the right screen! Here's exactly what to fill in:

### Git Repository Deployment Form

```
Repository: https://github.com/wolfcasaba/hubizz.git
Branch: master
Directory: (leave empty)
```

**Important:** Leave the directory field **EMPTY** to deploy to `public_html` root.

### ⚠️ Before You Click "Create"

**Make sure your `public_html` folder is EMPTY!**

If you have files in `public_html`:
1. Go to **File Manager**
2. Select all files in `public_html`
3. **Delete** them OR move to a backup folder
4. Go back to Git deployment

---

## Step 2: After Git Deployment Completes

Once Hostinger finishes cloning the repository, follow these steps:

### 2.1 Create Database

1. Go to **Databases → MySQL Databases**
2. Click **Create Database**
3. Fill in:
   - **Database name**: `hubizz` (or auto-generated like `u123456789_hubizz`)
   - **Username**: `hubizz` (or auto-generated like `u123456789_hubizz`)
   - **Password**: Click "Generate" for strong password
   - **SAVE THESE CREDENTIALS!** 📝

```
✅ Database Created:
Host: localhost
Database: u123456789_hubizz
Username: u123456789_hubizz
Password: [copy this password]
```

---

### 2.2 Configure Environment File

1. Go to **File Manager**
2. Navigate to `public_html`
3. Find `.env.example`
4. Right-click → **Copy**
5. Rename the copy to `.env`
6. Right-click `.env` → **Edit**

**Update these critical values:**

```env
# Change from 'local' to 'production'
APP_ENV=production
APP_DEBUG=false

# Add your domain
APP_URL=https://yourdomain.com

# Database credentials (from Step 2.1)
DB_DATABASE=u123456789_hubizz
DB_USERNAME=u123456789_hubizz
DB_PASSWORD=paste_your_database_password_here

# Perplexity AI (get from https://www.perplexity.ai/settings/api)
PERPLEXITY_API_KEY=pplx-YOUR_API_KEY_HERE

# Mail (use Hostinger SMTP)
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

**Save the file!**

---

### 2.3 Run Installation via Terminal

**If you have SSH access:**

1. Go to **Advanced → Terminal** (or SSH client)
2. Connect and run:

```bash
cd public_html

# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
```

**If you DON'T have SSH/Terminal access:**

1. Create a file `setup.php` in `public_html`:

```php
<?php
// setup.php - Run this once, then DELETE it!

echo "<h2>Hubizz Setup</h2>";

// 1. Install Composer Dependencies
echo "<p>Installing Composer dependencies...</p>";
exec('composer install --optimize-autoloader --no-dev 2>&1', $output1);
echo "<pre>" . implode("\n", $output1) . "</pre><hr>";

// 2. Generate App Key
echo "<p>Generating application key...</p>";
exec('php artisan key:generate --force 2>&1', $output2);
echo "<pre>" . implode("\n", $output2) . "</pre><hr>";

// 3. Run Migrations
echo "<p>Running database migrations...</p>";
exec('php artisan migrate --force 2>&1', $output3);
echo "<pre>" . implode("\n", $output3) . "</pre><hr>";

// 4. Cache Config
echo "<p>Caching configuration...</p>";
exec('php artisan config:cache 2>&1', $output4);
exec('php artisan route:cache 2>&1', $output5);
exec('php artisan view:cache 2>&1', $output6);
echo "<pre>Caches generated successfully!</pre><hr>";

// 5. Fix Permissions
echo "<p>Setting permissions...</p>";
chmod(storage_path(), 0755);
chmod(base_path('bootstrap/cache'), 0755);
echo "<pre>Permissions set!</pre><hr>";

echo "<h2 style='color: green;'>✅ Setup Complete!</h2>";
echo "<p><strong>IMPORTANT: Delete this file (setup.php) now!</strong></p>";
echo "<p>Visit: <a href='/'>Your Website</a> | <a href='/admin'>Admin Panel</a></p>";
?>
```

2. Visit: `https://yourdomain.com/setup.php`
3. Wait for all steps to complete
4. **DELETE `setup.php`** immediately after!

---

### 2.4 Fix Document Root (Critical!)

Laravel needs the `/public` folder to be the web root.

**Option A: Change Document Root (Best)**
1. Go to **Domains** in hPanel
2. Click your domain
3. Change **Document Root** from:
   - `/public_html` → `/public_html/public`
4. Click **Save**

**Option B: Add .htaccess Redirect**
1. Go to **File Manager**
2. In `public_html` root, edit `.htaccess`
3. Add this at the TOP:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

### 2.5 Setup Cron Jobs (Required!)

1. Go to **Advanced → Cron Jobs**
2. Click **Create Cron Job**

**Add this cron job:**

```
Type: Custom
Minute: *
Hour: *
Day: *
Month: *
Weekday: *

Command: cd /home/u123456789/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**Replace `u123456789`** with your actual Hostinger username!

**To find your username:**
- Check File Manager path (shows at top)
- OR check hPanel → Account → Username

---

### 2.6 Enable SSL Certificate

1. Go to **SSL** in hPanel
2. Select your domain
3. Click **Install SSL Certificate**
4. Choose **Let's Encrypt** (Free)
5. Wait 5-10 minutes for activation

**After SSL is active:**

1. Edit `.env` again:
```env
APP_URL=https://yourdomain.com  # ← Must have https://
```

2. Clear cache:
```bash
php artisan config:cache
```

---

### 2.7 Create Admin User

**Via SSH/Terminal:**
```bash
php artisan tinker

# In tinker:
$user = new App\User();
$user->username = 'admin';
$user->email = 'admin@yourdomain.com';
$user->password = bcrypt('YourSecurePassword123!');
$user->role = 'admin';
$user->active = 1;
$user->save();
exit
```

**Via phpMyAdmin:**
1. Go to **Databases → phpMyAdmin**
2. Select your `hubizz` database
3. Click `users` table
4. Click **Insert** tab
5. Fill in:
   - `username`: admin
   - `email`: admin@yourdomain.com
   - `password`: (Use https://bcrypt-generator.com/ to hash your password)
   - `role`: admin
   - `active`: 1
   - `created_at`: (current timestamp)
   - `updated_at`: (current timestamp)
6. Click **Go**

---

## ✅ Verification Checklist

After completing all steps, verify:

- [ ] Homepage loads: `https://yourdomain.com`
- [ ] No errors displayed
- [ ] Admin panel accessible: `https://yourdomain.com/admin`
- [ ] Can login with admin credentials
- [ ] SSL works (green padlock 🔒)
- [ ] Database tables created (check phpMyAdmin)
- [ ] Cron job is active (check Cron Jobs page)

---

## 🎯 Post-Deployment Configuration

### Access Admin Panel
```
URL: https://yourdomain.com/admin
Username: admin
Password: (what you set in Step 2.7)
```

### Configure Hubizz Features

1. **Add Perplexity API Key**
   - Already in `.env` file
   - Test: Try generating AI content in admin

2. **Setup RSS Feeds**
   - Go to: Admin → Hubizz → RSS Feeds
   - Add feed URLs (e.g., TechCrunch, Mashable, etc.)
   - Set intervals: 15min, hourly, or daily

3. **Configure Affiliate Networks**
   - Go to: Admin → Affiliate → Networks
   - Add Amazon credentials if you have them
   - Enable networks

4. **Create Categories**
   - Go to: Admin → Categories
   - Create: Tech, Viral, News, Life, Biz, Daily Izz

5. **Configure Daily Izz**
   - Go to: Admin → Hubizz → Daily Izz
   - Set curation time
   - Auto-curate or manually select top posts

---

## 🔧 Troubleshooting

### Error: "The stream or file could not be opened"
```bash
# Fix permissions
chmod -R 755 storage bootstrap/cache
```

### Error: "No application encryption key"
```bash
php artisan key:generate
```

### Error: Database connection failed
- Check database credentials in `.env`
- Verify database exists in phpMyAdmin
- Ensure `DB_HOST=localhost`

### Cron Jobs Not Running
- Verify cron command path is correct
- Check logs: `storage/logs/laravel.log`

### 500 Error
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerate
php artisan config:cache
php artisan route:cache
```

---

## 📞 Need Help?

- **Hostinger Support**: https://support.hostinger.com
- **Hubizz GitHub**: https://github.com/wolfcasaba/hubizz/issues
- **Laravel Docs**: https://laravel.com/docs/10.x

---

## 🎉 You're Live!

Once everything is working:

✅ Your Hubizz platform is live at: **https://yourdomain.com**
✅ Admin panel accessible at: **https://yourdomain.com/admin**
✅ Ready to create viral content!

---

**🔥 HUBIZZ - WHERE CONTENT IGNITES!** 🔥

**Deployment Status**: ✅ **LIVE ON HOSTINGER**
