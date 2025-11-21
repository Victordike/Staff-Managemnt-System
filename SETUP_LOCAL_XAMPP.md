# Staff Management System - Local XAMPP Setup Guide

## Overview
This guide will help you set up the Federal Polytechnic of Oil and Gas Staff Management System locally on your computer using XAMPP server.

## Prerequisites
- XAMPP installed (with Apache, MySQL, and PHP 7.4+)
- Git (optional, for cloning)
- Text editor (VS Code, Sublime Text, etc.)
- Basic command line knowledge

## Step 1: Download and Install XAMPP

### Windows/Mac/Linux:
1. Go to https://www.apachefriends.org/
2. Download XAMPP for your operating system
3. Run the installer and follow the installation wizard
4. Choose components: Apache, MySQL, PHP, phpMyAdmin
5. Install to default location (usually C:\xampp on Windows)

## Step 2: Start XAMPP Services

### Windows:
1. Open XAMPP Control Panel
2. Click "Start" next to Apache
3. Click "Start" next to MySQL

### Mac/Linux:
```bash
sudo /Applications/XAMPP/xamppfiles/bin/xampp startapache
sudo /Applications/XAMPP/xamppfiles/bin/xampp startmysql
```

## Step 3: Set Up Database

### Method A: Using phpMyAdmin (GUI)
1. Open browser and go to `http://localhost/phpmyadmin`
2. Click "New" on the left sidebar
3. Create a new database named: **fpog_sms**
4. Click on the new database
5. Go to "Import" tab
6. Upload the `fpog_database_mysql.sql` file provided with this project
7. Click "Go" to import

### Method B: Using Command Line
```bash
# Open MySQL command line
mysql -u root -p

# Create database
CREATE DATABASE fpog_sms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Use the database
USE fpog_sms;

# Import the SQL file
SOURCE /path/to/fpog_database_mysql.sql;

# Exit
EXIT;
```

## Step 4: Copy Project Files

1. Locate your XAMPP htdocs folder:
   - **Windows:** `C:\xampp\htdocs`
   - **Mac:** `/Applications/XAMPP/xamppfiles/htdocs`
   - **Linux:** `/opt/lampp/htdocs`

2. Create a new folder: `fpog-sms`

3. Copy all project files into this folder:
   ```
   htdocs/
   └── fpog-sms/
       ├── assets/
       ├── includes/
       ├── api/
       ├── uploads/
       ├── index.php
       ├── admin_login.php
       ├── superadmin_login.php
       └── ... (other files)
   ```

## Step 5: Configure Database Connection

### Edit `includes/db.php`:

```php
<?php
class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        try {
            // Local MySQL Configuration
            $host = 'localhost';
            $dbname = 'fpog_sms';
            $user = 'root';
            $password = ''; // Default XAMPP MySQL password is empty
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // ... rest of the class
}
?>
```

## Step 6: Create Required Directories

Create these folders in your project root:
```bash
mkdir -p uploads/csv
mkdir -p uploads/memos
mkdir -p uploads/passport
mkdir -p uploads/profile_pictures
```

Set proper permissions:
```bash
chmod -R 755 uploads/
```

## Step 7: Create .env or config file (Optional)

Create a `config/config.php` file:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'fpog_sms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session Configuration
define('SESSION_SECRET', 'your-secure-random-secret-key-here');

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'csv']);

// Application Settings
define('APP_NAME', 'FPOG Staff Management System');
define('APP_URL', 'http://localhost/fpog-sms');
?>
```

## Step 8: Access the Application

1. Open your browser
2. Go to: `http://localhost/fpog-sms/`
3. You should see the Welcome page with login options

## Step 9: Default Credentials

### Super Admin Login:
- **URL:** `http://localhost/fpog-sms/superadmin_login.php`
- **Username:** Check the `users` table in your database (or insert one)
- **Default credentials:** (Contact your administrator)

### Admin Login:
- **URL:** `http://localhost/fpog-sms/admin_login.php`
- **Staff ID & Password:** As set during registration

## Step 10: Import Test Data (Optional)

If you need sample data, you can insert test records:

```sql
-- Insert sample Super Admin user
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@fpog.edu.ng', '$2y$10$...', 'superadmin');

-- Insert sample Admin User
INSERT INTO admin_users (firstname, surname, staff_id, password, position, department) VALUES 
('John', 'Doe', 'ADM001', '$2y$10$...', 'Registrar', 'Administration');
```

## Troubleshooting

### Issue: "Access Denied" Database Error
**Solution:**
1. Check MySQL is running in XAMPP Control Panel
2. Verify database name is `fpog_sms`
3. Check `includes/db.php` has correct credentials
4. Make sure `db.php` uses MySQL (PDO) not PostgreSQL

### Issue: Uploaded Files Not Saving
**Solution:**
1. Check `uploads/` folders exist and are writable
2. Run: `chmod -R 777 uploads/` in terminal
3. Check PHP file upload settings in `php.ini`

### Issue: Session/Login Problems
**Solution:**
1. Check PHP session folder is writable
2. Verify `includes/session.php` is properly configured
3. Clear browser cookies

### Issue: 404 - Page Not Found
**Solution:**
1. Ensure `.htaccess` is present (if using URL rewriting)
2. Check file paths are correct
3. Restart Apache from XAMPP Control Panel

### Issue: PHP Errors/Blank Page
**Solution:**
1. Enable error reporting in PHP:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check `php.ini` in XAMPP config folder
3. Check PHP error logs in `xampp/php/logs/`

## Performance Optimization

### For Development:
1. Enable PHP error logging in `php.ini`:
   ```
   error_log = logs/php_error.log
   ```

2. Set appropriate PHP memory limit:
   ```
   memory_limit = 256M
   ```

3. Increase upload size if needed:
   ```
   upload_max_filesize = 50M
   post_max_size = 50M
   ```

## Useful XAMPP Commands

### Windows (Command Prompt as Admin):
```cmd
cd C:\xampp

# Start services
xampp_start.exe

# Stop services
xampp_stop.exe

# Start individual services
apache_start.bat
mysql_start.bat
```

### Mac/Linux (Terminal):
```bash
# Start Apache
sudo /Applications/XAMPP/xamppfiles/bin/xampp startapache

# Start MySQL
sudo /Applications/XAMPP/xamppfiles/bin/xampp startmysql

# Stop services
sudo /Applications/XAMPP/xamppfiles/bin/xampp stop
```

## Database Backup

### Regular Backups:
```bash
# Windows
mysqldump -u root fpog_sms > backup_fpog_sms.sql

# Mac/Linux
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root fpog_sms > backup_fpog_sms.sql
```

### Restore from Backup:
```bash
mysql -u root fpog_sms < backup_fpog_sms.sql
```

## Next Steps

1. ✅ Create admin users for different institutional roles
2. ✅ Upload CSV for staff pre-verification
3. ✅ Configure memo settings as needed
4. ✅ Test all features in local environment
5. ✅ Deploy to production server when ready

## Support & Documentation

- **Admin Manual:** See `ADMIN_GUIDE.md`
- **Database Schema:** See `DATABASE_SCHEMA.md`
- **API Documentation:** See `API_DOCS.md`

## Important Notes

- **Security:** Change default passwords immediately
- **Backups:** Regular backup your database
- **SSL/HTTPS:** Use HTTPS in production (not needed locally)
- **Email:** Some features may require SMTP configuration

---

**Last Updated:** November 2025
**Project:** Federal Polytechnic of Oil and Gas - Staff Management System
