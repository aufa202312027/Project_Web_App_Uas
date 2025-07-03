# 📖 Installation Guide - Web Application

## 🔧 System Requirements

### Server Requirements
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.2+)
- **Web Server:** Apache, Nginx, or PHP Built-in Server
- **Extensions:** PDO, PDO_MySQL, Session, JSON

### Development Environment
- **Recommended:** XAMPP, WAMP, MAMP, atau Laragon
- **Alternative:** PHP Built-in Server (untuk testing)

## 📋 Installation Steps

### Step 1: Download & Extract
```bash
# Clone atau download project
git clone [repository-url] web-app
cd web-app

# Atau extract dari ZIP
unzip web-app.zip
cd web-app
```

### Step 2: Database Setup
```bash
# 1. Buat database baru
mysql -u root -p

# 2. Di MySQL console:
CREATE DATABASE web_app_db;
exit;

# 3. Import struktur database
mysql -u root -p web_app_db < database/create_database.sql

# 4. Import sample data (optional)
mysql -u root -p web_app_db < database/sample_data.sql
```

### Step 3: Configuration
```bash
# 1. Copy dan edit file konfigurasi
cp config/config.php.example config/config.php

# 2. Edit database credentials di config/database.php
nano config/database.php
```

### Step 4: Set Permissions
```bash
# Set permissions untuk folder yang perlu write access
chmod 755 assets/images/
chmod 755 logs/ (jika ada)

# Untuk Linux/Mac
chown -R www-data:www-data ./
```

### Step 5: Start Server

#### Option A: PHP Built-in Server (Development)
```bash
# Jalankan dari root folder project
php -S localhost:8000

# Akses di browser: http://localhost:8000
```

#### Option B: XAMPP/WAMP
```bash
# 1. Copy project ke htdocs/www folder
cp -r web-app /path/to/xampp/htdocs/

# 2. Start Apache & MySQL
# 3. Akses: http://localhost/web-app
```

#### Option C: Apache Virtual Host
```bash
# 1. Edit httpd.conf atau sites-available
<VirtualHost *:80>
    DocumentRoot "/path/to/web-app"
    ServerName webapp.local
    <Directory "/path/to/web-app">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# 2. Edit hosts file
echo "127.0.0.1 webapp.local" >> /etc/hosts

# 3. Restart Apache
sudo systemctl restart apache2
```

## 🔐 Default Login Credentials

### Administrator
- **Username:** `admin`
- **Password:** `password`
- **Email:** `admin@example.com`

### Regular User
- **Username:** `user1`
- **Password:** `password`
- **Email:** `user1@example.com`

⚠️ **IMPORTANT:** Ganti password default setelah instalasi!

## 🧪 Testing Installation

### 1. Database Connection Test
```bash
# Akses: http://localhost:8000/config/test_db.php
# Should show: "Database connection successful!"
```

### 2. Login Test
```bash
# Akses: http://localhost:8000
# Login dengan credentials di atas
```

### 3. Admin Panel Test
```bash
# Setelah login sebagai admin
# Akses: http://localhost:8000/admin/dashboard.php
```

## 🔧 Troubleshooting

### Database Connection Error
```bash
# Check database credentials di config/database.php
# Pastikan MySQL service running
sudo systemctl status mysql

# Test connection manual
mysql -u [username] -p[password] -h [host] [database]
```

### Permission Denied
```bash
# Fix file permissions
chmod -R 755 ./
chown -R www-data:www-data ./
```

### PHP Extensions Missing
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql php-pdo

# CentOS/RHEL
sudo yum install php-mysql php-pdo

# Check installed extensions
php -m | grep -i mysql
```

### Session Issues
```bash
# Check session.save_path in php.ini
php -i | grep session.save_path

# Make sure session directory writable
chmod 777 /tmp
```

## 📊 Verification Checklist

- [ ] Database created and imported successfully
- [ ] Configuration files updated
- [ ] File permissions set correctly
- [ ] Web server running
- [ ] Can access homepage (index.php)
- [ ] Can login with default credentials
- [ ] Admin dashboard accessible
- [ ] No PHP errors in logs

## 🚀 Next Steps

1. **Security:** Change default passwords
2. **Configuration:** Update app settings in config/config.php
3. **Customization:** Modify UI/UX sesuai kebutuhan
4. **Backup:** Setup database backup routine
5. **Production:** Configure for production environment

## 📞 Support

Jika mengalami masalah:
1. Check error logs (PHP error log)
2. Verify database connection
3. Check file permissions
4. Review configuration files
5. Consult technical documentation

---
**Installation Guide v1.0** | Created: June 2025