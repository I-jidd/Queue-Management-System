<?php
/**
 * Database Connection Configuration
 * Centralized database connection for the Registrar Queue System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'registrar_queue');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

/**
 * Helper function to sanitize input
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Helper function to generate batch number
 */
function generate_batch_number($type = 'standard') {
    global $conn;
    
    $prefix = ($type === 'standard') ? 'S' : 'Q';
    $date_suffix = date('ymd'); // YYMMDD format
    
    // Get the last batch number for today
    $query = "SELECT batch_number FROM bookings 
              WHERE batch_number LIKE '$prefix-$date_suffix-%' 
              ORDER BY id DESC LIMIT 1";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = $row['batch_number'];
        
        // Extract the sequence number
        $parts = explode('-', $last_number);
        $sequence = intval($parts[2]) + 1;
    } else {
        $sequence = 1;
    }
    
    // Format: S-241117-001 or Q-241117-001
    return sprintf("%s-%s-%03d", $prefix, $date_suffix, $sequence);
}

/**
 * Helper function to get next queue position
 */
function get_next_queue_position($service_type, $booking_date = null) {
    global $conn;
    
    if ($service_type === 'standard' && $booking_date) {
        $query = "SELECT MAX(queue_position) as max_pos FROM bookings 
                  WHERE service_type = 'standard' 
                  AND booking_date = '$booking_date'
                  AND status != 'cancelled'";
    } else {
        $query = "SELECT MAX(queue_position) as max_pos FROM bookings 
                  WHERE service_type = 'express' 
                  AND status IN ('waiting', 'pending')";
    }
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return intval($row['max_pos']) + 1;
    }
    
    return 1;
}

/**
 * Helper function to get service by key
 */
function get_service_by_key($service_key) {
    global $conn;
    
    $service_key = sanitize_input($service_key);
    $query = "SELECT * FROM services WHERE service_key = '$service_key'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Helper function to format date for display
 */
function format_date_display($date_string) {
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
    global $conn;
    
    $date = sanitize_input($date);
    $time_window = sanitize_input($time_window);
    
    $query = "SELECT COUNT(*) as count FROM bookings 
              WHERE booking_date = '$date' 
              AND time_window = '$time_window'
              AND status != 'cancelled'";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return intval($row['count']) < $max_per_slot;
    }
    
    return true;
}
?>