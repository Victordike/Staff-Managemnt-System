# Database Connection Troubleshooting Guide

## Error: "Connection refused"
This means **MySQL/MariaDB is not running or not accessible**

### Step-by-Step Fix:

#### 1. XAMPP Control Panel
- **Windows**: Open `C:\xampp\xampp-control.exe`
- **Mac**: Open XAMPP application
- **Linux**: Run `sudo /opt/lampp/manager-linux-x64.run`

#### 2. Check MySQL Status
- Look for **MySQL** row in the control panel
- Check if it shows **"Running"** with a **green indicator**
- If it shows **"Stopped"**, click the **"Start"** button

#### 3. If MySQL Won't Start
Try these steps:
1. Click **"Stop"** to stop MySQL
2. Wait **5 seconds**
3. Click **"Start"** again
4. Wait 10 seconds for it to fully start

#### 4. If Still Not Starting
- Check if port **3306** is already in use:
  - **Windows**: `netstat -ano | findstr :3306`
  - **Mac/Linux**: `lsof -i :3306`
- Restart your computer
- Try uninstalling and reinstalling XAMPP

#### 5. Verify Connection
- Go to: `http://localhost/fpog-sms/test_connection.php`
- This will show you:
  - ✓ Which hosts/ports MySQL is listening on
  - ✓ If the `fpog_sms` database exists
  - ✓ Detailed error messages if connection fails

#### 6. Check phpMyAdmin
- Go to: `http://localhost/phpmyadmin`
- If this page loads, MySQL IS running
- If this doesn't load, MySQL is NOT running

### Important Notes:
- **You must start XAMPP EVERY TIME** before using the application
- MySQL doesn't auto-start - you have to click "Start"
- Close XAMPP properly when done
- Don't restart your computer in the middle of registration (you'll lose your data)

### If Everything Fails:
1. Delete the current installation of XAMPP
2. Download XAMPP from: https://www.apachefriends.org/
3. Install fresh with all components (Apache, MySQL, PHP)
4. Re-import the database file: `fpog_database_mysql.sql`

