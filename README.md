# Queue Management System

A PHP-based web application for managing registrar office queue system with batch number generation and appointment scheduling.

## Features

- **Get Batch Number**: Students can book appointments and get batch numbers for standard or express services
- **Queue Management**: Staff dashboard to manage and call next batches
- **Real-time Status**: Public queue status board showing current serving batches
- **Forms & FAQ**: Download forms and view frequently asked questions

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB database
- Web server (Apache, Nginx, or PHP built-in server)

## Setup Instructions

### 1. Install PHP and MySQL

**Windows:**
- Download and install [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/)
- This includes PHP, MySQL, and Apache web server

**Alternative (Windows):**
- Install PHP from [php.net](https://windows.php.net/download/)
- Install MySQL from [mysql.com](https://dev.mysql.com/downloads/installer/)
- Add PHP to your system PATH

### 2. Database Setup

1. Start your MySQL server
2. Create a new database:
   ```sql
   CREATE DATABASE registrar_queue;
   ```
3. Import the database schema:
   ```bash
   mysql -u root -p registrar_queue < database.sql
   ```
   Or use phpMyAdmin to import `database.sql`

### 3. Configure Database Connection

Edit `db_connect.php` and update the database credentials if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'registrar_queue');
```

### 4. Run the Application

**Option 1: Using PHP Built-in Server**
```bash
php -S localhost:8000
```
Then open `http://localhost:8000` in your browser.

**Option 2: Using XAMPP/WAMP**
1. Copy the project folder to `htdocs` (XAMPP) or `www` (WAMP)
2. Start Apache and MySQL from the control panel
3. Open `http://localhost/Queue-Management-System` in your browser

## Project Structure

- `index.html` - Homepage
- `get-batch.php` - Booking form to get batch number
- `process_booking.php` - Processes booking form submission
- `confirmation.php` - Booking confirmation page
- `admin.html` - Staff dashboard
- `status.html` - Public queue status board
- `forms.html` - Forms and FAQ page
- `db_connect.php` - Database connection and helper functions
- `database.sql` - Database schema and initial data

## Default Login

- **Username:** admin
- **Password:** admin123 (change in production!)

## Troubleshooting

### PHP not found
- Make sure PHP is installed and added to your system PATH
- On Windows, restart your terminal after installing PHP

### Database connection error
- Verify MySQL is running
- Check database credentials in `db_connect.php`
- Ensure the database `registrar_queue` exists

### Page shows blank or errors
- Check PHP error logs
- Ensure all PHP files have proper syntax
- Verify database connection is working

## License

This project is for educational purposes.
