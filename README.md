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
   mysql -u root -p registrar_queue < database/schema.sql
   ```
   Or use phpMyAdmin to import `database/schema.sql`

### 3. Configure Database Connection

Edit `src/db_connect.php` and update the database credentials if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'registrar_queue');
```

### 4. Run the Application

**Option 1: Using PHP Built-in Server**
```bash
php -S localhost:8000 -t public public/index.php
```
Then open `http://localhost:8000` in your browser.

**Option 2: Using XAMPP/WAMP**
1. Copy the project folder to `htdocs` (XAMPP) or `www` (WAMP)
2. Start Apache and MySQL from the control panel
3. Open `http://localhost/Queue-Management-System/public` in your browser

## Deploying to Render

1. **Prepare the repository**
   - Commit the new `Dockerfile` and `render.yaml`.
   - Push the repository to GitHub/GitLab so Render can access it.

2. **Create the PostgreSQL database**
   - In Render, click **New > Blueprint** and select this repository. The blueprint defines a `queue-management-db` PostgreSQL instance on the free plan.
   - After the database is provisioned, copy the external connection string from the Render dashboard.
   - Import the schema:
     ```bash
     psql "postgres://user:password@host:port/registrar_queue" -f database/schema.sql
     ```
     Replace the values in the connection string with the credentials shown by Render, or run the command inside the database’s **psql shell** in the dashboard.

3. **Deploy the web service**
   - The same blueprint also provisions a Docker-based web service that uses the official `php:8.2-apache` image.
   - Render automatically builds the Docker image and starts Apache on port 80.
   - Environment variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` are injected from the managed database via the blueprint, so no manual secrets management is needed.

4. **Verify and monitor**
   - Once the deployment is live, open the Render service URL to confirm the app loads.
   - Use the Render **Shell** tab if you need to inspect logs or run `php artisan`-style commands (not required for this project).
   - Re-run the `psql` import if you need to seed new data after redeployments.

Auto Deploy is enabled by default, so pushing to the default branch triggers a rebuild. Disable it in Render if you prefer manual deployments.

## Project Structure

- `public/` - Web root served by Apache or `php -S`
  - `index.html`, `status.html`, `forms.html` - Public-facing pages
  - `get-batch.php`, `process_booking.php`, `confirmation.php` - Booking flow
  - `admin.php`, `api_queue.php`, `login.php`, `signup.php`, `logout.php` - Staff tools and API endpoints
  - `assets/images/logo.png` - Static assets
- `src/` - Reusable PHP helpers loaded by the public entry points
  - `db_connect.php` - PDO connection + helper functions
  - `auth.php` - Session helpers for staff authentication
- `database/schema.sql` - Database schema and seed data
- `Dockerfile`, `render.yaml` - Deployment configuration

## Default Login

- **Username:** admin
- **Password:** admin123 (change in production!)

## Troubleshooting

### PHP not found
- Make sure PHP is installed and added to your system PATH
- On Windows, restart your terminal after installing PHP

### Database connection error
- Verify MySQL is running
- Check database credentials in `src/db_connect.php`
- Ensure the database `registrar_queue` exists

### Page shows blank or errors
- Check PHP error logs
- Ensure all PHP files have proper syntax
- Verify database connection is working

## License

This project is for educational purposes.
