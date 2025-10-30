# Fix 500 Internal Server Error - Complete Guide

## 🐛 Problem
```
GET https://admsserver.amaravathijuniorcollege.com/attendance 500 (Internal Server Error)
```

## ✅ Root Cause
The `.env` file was missing from the production server, causing Laravel to fail during bootstrap.

---

## 🔧 FIXES APPLIED

### **1. Created `.env` File**
✅ Created `.env` file with production-ready configuration

**File:** `.env`
```env
APP_NAME="Attendance System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://admsserver.amaravathijuniorcollege.com

LOG_CHANNEL=null
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_db
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=array
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
SESSION_LIFETIME=120
```

---

## 🚀 REQUIRED STEPS TO COMPLETE THE FIX

### **Step 1: Update Database Credentials in `.env`**

Edit the `.env` file and update these values with your actual database credentials:

```bash
nano .env
```

Update these lines:
```env
DB_DATABASE=your_actual_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

**Example:**
```env
DB_DATABASE=amaravathi_attendance
DB_USERNAME=amaravathi_user
DB_PASSWORD=YourSecurePassword123
```

---

### **Step 2: Generate Application Key**

Run this command to generate a secure APP_KEY:

```bash
php artisan key:generate
```

This will automatically update the `APP_KEY=` line in your `.env` file.

**Expected output:**
```
Application key set successfully.
```

---

### **Step 3: Clear All Caches**

Clear any cached configuration:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

### **Step 4: Set Proper File Permissions**

Ensure Laravel can write to necessary directories:

```bash
# Set ownership (replace 'www-data' with your web server user if different)
chown -R www-data:www-data /www/wwwroot/admsserver.amaravathijuniorcollege.com

# Set directory permissions
find /www/wwwroot/admsserver.amaravathijuniorcollege.com -type d -exec chmod 755 {} \;

# Set file permissions
find /www/wwwroot/admsserver.amaravathijuniorcollege.com -type f -exec chmod 644 {} \;

# Make bootstrap/cache writable (if it exists)
chmod -R 775 /www/wwwroot/admsserver.amaravathijuniorcollege.com/bootstrap/cache

# Secure .env file
chmod 600 /www/wwwroot/admsserver.amaravathijuniorcollege.com/.env
```

---

### **Step 5: Verify Database Connection**

Test the database connection:

```bash
php artisan migrate:status
```

If you get a connection error, double-check your database credentials in `.env`.

---

### **Step 6: Test the Application**

Visit your application:
```
https://admsserver.amaravathijuniorcollege.com/attendance
```

You should now see the attendance records page without the 500 error.

---

## 🔍 TROUBLESHOOTING

### **If you still get 500 error:**

#### **1. Enable Debug Mode Temporarily**

Edit `.env`:
```env
APP_DEBUG=true
```

Then visit the page again to see the actual error message.

**IMPORTANT:** Set `APP_DEBUG=false` after fixing the issue!

---

#### **2. Check PHP Error Logs**

```bash
# Check Laravel logs (if logging was enabled)
tail -f /www/wwwroot/admsserver.amaravathijuniorcollege.com/storage/logs/laravel.log

# Check web server error logs
tail -f /var/log/nginx/error.log
# OR for Apache:
tail -f /var/log/apache2/error.log
```

---

#### **3. Verify PHP Extensions**

Ensure required PHP extensions are installed:

```bash
php -m | grep -E 'pdo|mysql|mbstring|tokenizer|xml|ctype|json|bcmath'
```

Required extensions:
- ✅ PDO
- ✅ pdo_mysql
- ✅ mbstring
- ✅ tokenizer
- ✅ xml
- ✅ ctype
- ✅ json
- ✅ bcmath

---

#### **4. Check Web Server Configuration**

**For Nginx:**

Ensure your Nginx configuration has the correct document root:

```nginx
server {
    listen 80;
    server_name admsserver.amaravathijuniorcollege.com;
    root /www/wwwroot/admsserver.amaravathijuniorcollege.com/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**For Apache:**

Ensure `.htaccess` exists in the `public/` directory and `mod_rewrite` is enabled:

```bash
a2enmod rewrite
systemctl restart apache2
```

---

#### **5. Verify Composer Autoload**

Regenerate the autoloader:

```bash
composer dump-autoload --optimize
```

---

## ✅ VERIFICATION CHECKLIST

After completing all steps, verify:

- [ ] `.env` file exists and has correct database credentials
- [ ] `APP_KEY` is generated (not empty)
- [ ] Database connection works
- [ ] File permissions are correct
- [ ] Web server configuration points to `public/` directory
- [ ] PHP extensions are installed
- [ ] Composer autoload is up to date
- [ ] Caches are cleared
- [ ] `/attendance` page loads without 500 error
- [ ] Biometric devices can POST to `/iclock/cdata`

---

## 📋 QUICK COMMAND SUMMARY

Run these commands in order:

```bash
# 1. Navigate to project directory
cd /www/wwwroot/admsserver.amaravathijuniorcollege.com

# 2. Update .env with your database credentials (use nano or vi)
nano .env

# 3. Generate application key
php artisan key:generate

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 5. Set permissions
chown -R www-data:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 .env

# 6. Regenerate autoloader
composer dump-autoload --optimize

# 7. Test database connection
php artisan migrate:status

# 8. Test the application
curl -I https://admsserver.amaravathijuniorcollege.com/attendance
```

---

## 🎯 EXPECTED RESULT

After completing all steps:

✅ **GET /attendance** → 200 OK (displays attendance records)
✅ **GET /iclock/cdata** → 200 OK (device handshake)
✅ **POST /iclock/cdata** → 200 OK (stores attendance data)
✅ **GET /** → 302 Redirect to /attendance

---

## 🔒 SECURITY NOTES

### **Production Security Checklist:**

- ✅ `APP_DEBUG=false` (never enable in production)
- ✅ `APP_ENV=production`
- ✅ `.env` file has `chmod 600` permissions
- ✅ Strong database password
- ✅ `APP_KEY` is generated and secure
- ✅ Web server doesn't expose `.env` file
- ✅ Directory listing is disabled
- ✅ HTTPS is enabled (SSL certificate)

---

## 📞 NEED HELP?

If you still encounter issues after following all steps:

1. **Enable debug mode temporarily:**
   ```env
   APP_DEBUG=true
   ```

2. **Visit the page and copy the full error message**

3. **Check the error logs:**
   ```bash
   tail -100 /var/log/nginx/error.log
   ```

4. **Share the error details for further assistance**

---

## ✅ SUCCESS!

Once all steps are completed, your attendance system should be fully operational at:

🌐 **https://admsserver.amaravathijuniorcollege.com/attendance**

The 500 error will be resolved! 🚀

