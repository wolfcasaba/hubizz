# 🔧 Fix 403 Forbidden Error - Hubizz on Hostinger

## Problem: 403 Forbidden Error

Your site https://hubizz.com/ shows:
```
403 Forbidden
Access to this resource on the server is denied!
```

This happens because the web server is pointing to the wrong directory. Laravel needs the `/public` folder to be the document root.

---

## ✅ SOLUTION (Choose One Method)

### **Method 1: Change Document Root (BEST SOLUTION)** ⭐

This is the proper Laravel way:

1. **Login to Hostinger hPanel**
2. Go to **"Websites"** or **"Domains"**
3. Find **hubizz.com**
4. Click **"Manage"** or the 3 dots menu
5. Look for **"Document Root"** or **"Change Website Root"**
6. Change from:
   ```
   FROM: /public_html
   TO:   /public_html/public
   ```
7. Click **"Save"** or **"Update"**
8. Wait 1-2 minutes for changes to apply
9. Visit https://hubizz.com/ again

**✅ This should fix it!**

---

### **Method 2: Add .htaccess Redirect (If Method 1 Doesn't Work)**

If you can't change the document root:

1. Go to **File Manager** in hPanel
2. Navigate to `/public_html`
3. Look for `.htaccess` file in the ROOT (not inside public folder)
4. If it exists, **edit it**
5. If it doesn't exist, **create it**
6. Add this code at the **VERY TOP**:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect to public folder
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L,QSA]
</IfModule>
```

7. **Save** the file
8. Visit https://hubizz.com/ again

---

### **Method 3: Move Files (Last Resort - Not Recommended)**

Only use this if Methods 1 & 2 don't work:

1. Go to **File Manager**
2. Navigate to `/public_html/public` folder
3. **Select ALL files** inside the public folder
4. **Move** them to `/public_html` root
5. Also move the `.htaccess` file from public folder to root
6. Edit `index.php` in the root and change:

**Find:**
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Replace with:**
```php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

7. Save and visit https://hubizz.com/

---

## 🔍 Additional Checks

### Check File Permissions

1. Go to **File Manager**
2. Right-click on `/public_html` folder → **"Permissions"**
3. Set to **755** (rwxr-xr-x)
4. Check **"Recurse into subdirectories"**
5. Click **"Change Permissions"**

### Check Index File Exists

1. Navigate to `/public_html/public/`
2. Make sure **index.php** exists
3. If missing, something went wrong with deployment

---

## 🎯 Quick Fix Command (If You Have SSH)

If you have SSH/Terminal access:

```bash
# Connect via SSH
ssh username@hubizz.com

# Navigate to project
cd public_html

# Fix permissions
chmod -R 755 .
chmod -R 755 public
chmod -R 775 storage bootstrap/cache

# Check if index.php exists in public
ls -la public/index.php
```

---

## 🔄 If Still Not Working

### Verify Deployment Completed

1. Go to **File Manager**
2. Check `/public_html` contains:
   - ✅ `public` folder
   - ✅ `vendor` folder
   - ✅ `app` folder
   - ✅ `config` folder
   - ✅ `.env.example` file
   - ✅ `composer.json` file

If any are missing, **re-deploy from Git**:
1. Delete everything in `/public_html`
2. Go to **Git → Deploy**
3. Deploy again from: https://github.com/wolfcasaba/hubizz.git

---

## 📞 Emergency Contact

If nothing works:

1. **Check Hostinger Knowledge Base**: https://support.hostinger.com/en/articles/1583245-how-to-fix-the-403-forbidden-error
2. **Contact Hostinger Support**: Live chat in hPanel
3. **Create GitHub Issue**: https://github.com/wolfcasaba/hubizz/issues

---

## ✅ Expected Result

After fixing, you should see:

- **Homepage**: Laravel welcome page or installation screen
- **OR**: "The stream or file could not be opened" error (means we need to continue setup)
- **NOT**: 403 Forbidden

Once you see something other than 403, continue with the setup steps from **HOSTINGER-QUICK-DEPLOY.md**!

---

**Try Method 1 first - it's the cleanest solution!** 🚀
