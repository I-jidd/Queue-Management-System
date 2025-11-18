<?php
/**
 * Database Connection Configuration
 * Centralized database connection for the Registrar Queue System
 * Using PDO with PostgreSQL
 */

// Database configuration - supports environment variables for Render
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'registrar_queue';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: 'queue_management';

// Check if PostgreSQL PDO driver is available
if (!in_array('pgsql', PDO::getAvailableDrivers())) {
    die("
    <h2>PostgreSQL PDO Driver Not Found</h2>
    <p><strong>Error:</strong> PHP PDO PostgreSQL driver (pdo_pgsql) is not installed or enabled.</p>
    <h3>How to Fix:</h3>
    <h4>For XAMPP (Windows):</h4>
    <ol>
        <li>Open <code>php.ini</code> file (usually in <code>C:\\xampp\\php\\php.ini</code>)</li>
        <li>Find the line: <code>;extension=pdo_pgsql</code></li>
        <li>Remove the semicolon to uncomment it: <code>extension=pdo_pgsql</code></li>
        <li>Also uncomment: <code>extension=pgsql</code></li>
        <li>Save the file and restart Apache</li>
    </ol>
    <h4>For WAMP (Windows):</h4>
    <ol>
        <li>Click WAMP icon → PHP → PHP Extensions</li>
        <li>Check <code>pdo_pgsql</code> and <code>pgsql</code></li>
        <li>Restart all services</li>
    </ol>
    <h4>For Linux (Ubuntu/Debian):</h4>
    <pre>sudo apt-get install php-pgsql</pre>
    <h4>For macOS (Homebrew):</h4>
    <pre>brew install php-pgsql</pre>
    <p><strong>Note:</strong> After enabling the extension, restart your web server.</p>
    ");
}

// Create DSN for PostgreSQL
$dsn = "pgsql:host=$host;port=5432;dbname=$dbname;";

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    $error_message = $e->getMessage();
    
    // Provide more helpful error messages
    if (strpos($error_message, 'could not find driver') !== false) {
        die("
        <h2>PostgreSQL PDO Driver Not Found</h2>
        <p><strong>Error:</strong> PHP PDO PostgreSQL driver (pdo_pgsql) is not installed or enabled.</p>
        <h3>How to Fix:</h3>
        <h4>For XAMPP (Windows):</h4>
        <ol>
            <li>Open <code>php.ini</code> file (usually in <code>C:\\xampp\\php\\php.ini</code>)</li>
            <li>Find the line: <code>;extension=pdo_pgsql</code></li>
            <li>Remove the semicolon to uncomment it: <code>extension=pdo_pgsql</code></li>
            <li>Also uncomment: <code>extension=pgsql</code></li>
            <li>Save the file and restart Apache</li>
        </ol>
        <h4>For WAMP (Windows):</h4>
        <ol>
            <li>Click WAMP icon → PHP → PHP Extensions</li>
            <li>Check <code>pdo_pgsql</code> and <code>pgsql</code></li>
            <li>Restart all services</li>
        </ol>
        <h4>For Linux (Ubuntu/Debian):</h4>
        <pre>sudo apt-get install php-pgsql</pre>
        <h4>For macOS (Homebrew):</h4>
        <pre>brew install php-pgsql</pre>
        <p><strong>Note:</strong> After enabling the extension, restart your web server.</p>
        ");
    } else {
        die("
        <h2>Database Connection Failed</h2>
        <p><strong>Error:</strong> " . htmlspecialchars($error_message) . "</p>
        <h3>Please check:</h3>
        <ul>
            <li>PostgreSQL server is running</li>
            <li>Database name is correct: <code>$dbname</code></li>
            <li>Username and password are correct</li>
            <li>Database exists and schema is imported</li>
        </ul>
        ");
    }
}

/**
 * Helper function to sanitize input
 * Note: With PDO prepared statements, this is mainly for display purposes
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Helper function to generate batch number
 */
function generate_batch_number($type = 'standard') {
    global $pdo;
    
    $prefix = ($type === 'standard') ? 'S' : 'Q';
    $date_suffix = date('ymd'); // YYMMDD format
    
    // Get the last batch number for today
    $query = "SELECT batch_number FROM bookings 
              WHERE batch_number LIKE :pattern 
              ORDER BY id DESC LIMIT 1";
    
    $pattern = $prefix . '-' . $date_suffix . '-%';
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute(['pattern' => $pattern]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $last_number = $row['batch_number'];
            // Extract the sequence number
            $parts = explode('-', $last_number);
            $sequence = intval($parts[2]) + 1;
        } else {
            $sequence = 1;
        }
        
        // Format: S-241117-001 or Q-241117-001
        return sprintf("%s-%s-%03d", $prefix, $date_suffix, $sequence);
    } catch (PDOException $e) {
        error_log("Error generating batch number: " . $e->getMessage());
        // Fallback: use timestamp-based number
        return sprintf("%s-%s-%03d", $prefix, $date_suffix, 1);
    }
}

/**
 * Helper function to get next queue position
 */
function get_next_queue_position($service_type, $booking_date = null) {
    global $pdo;
    
    try {
        if ($service_type === 'standard' && $booking_date) {
            $query = "SELECT MAX(queue_position) as max_pos FROM bookings 
                      WHERE service_type = :service_type 
                      AND booking_date = :booking_date
                      AND status != 'cancelled'";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'service_type' => $service_type,
                'booking_date' => $booking_date
            ]);
        } else {
            $query = "SELECT MAX(queue_position) as max_pos FROM bookings 
                      WHERE service_type = :service_type 
                      AND status IN ('waiting', 'pending')";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(['service_type' => $service_type]);
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['max_pos'] !== null) {
            return intval($row['max_pos']) + 1;
        }
        
        return 1;
    } catch (PDOException $e) {
        error_log("Error getting queue position: " . $e->getMessage());
        return 1;
    }
}

/**
 * Helper function to get service by key
 */
function get_service_by_key($service_key) {
    global $pdo;
    
    $service_key = sanitize_input($service_key);
    
    try {
        $query = "SELECT * FROM services WHERE service_key = :service_key LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['service_key' => $service_key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result : null;
    } catch (PDOException $e) {
        error_log("Error getting service by key: " . $e->getMessage());
        return null;
    }
}

/**
 * Helper function to format date for display
 */
function format_date_display($date_string) {
    if (!$date_string) {
        return '';
    }
    
    $date = new DateTime($date_string);
    $today = new DateTime();
    $tomorrow = new DateTime('tomorrow');
    
    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
        return "Today";
    } elseif ($date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
        return "Tomorrow";
    } else {
        return $date->format('F j, Y');
    }
}

/**
 * Helper function to check if time slot is available
 */
function is_time_slot_available($date, $time_window, $max_per_slot = 10) {
    global $pdo;
    
    $date = sanitize_input($date);
    $time_window = sanitize_input($time_window);
    
    try {
        $query = "SELECT COUNT(*) as count FROM bookings 
                  WHERE booking_date = :booking_date 
                  AND time_window = :time_window
                  AND status != 'cancelled'";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'booking_date' => $date,
            'time_window' => $time_window
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return intval($row['count']) < $max_per_slot;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error checking time slot availability: " . $e->getMessage());
        return true; // Default to available on error
    }
}
?>
