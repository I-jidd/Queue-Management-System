<?php
/**
 * QUEUE MANAGEMENT API
 * Handles queue operations for the staff dashboard
 */

require_once 'db_connect.php';
require_once 'auth.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'get_queue_status':
            // Get current queue status for status.html (public access)
            $query = "SELECT * FROM queue_status";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $status = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($status as $row) {
                $result[$row['queue_type']] = [
                    'batch_number' => $row['current_batch_number'],
                    'time_window' => $row['current_time_window']
                ];
            }
            
            echo json_encode(['success' => true, 'status' => $result]);
            break;
            
        case 'get_bookings':
            // Require staff login for protected actions
            require_staff_login();
            // Get all bookings
            $service_type = isset($_GET['type']) ? $_GET['type'] : '';
            
            if ($service_type === 'standard') {
                $query = "SELECT b.*, s.service_name, s.service_key 
                         FROM bookings b 
                         JOIN services s ON b.service_id = s.id 
                         WHERE b.service_type = 'standard' 
                         AND b.status != 'cancelled'
                         ORDER BY b.booking_date ASC, b.time_window ASC, b.queue_position ASC";
            } elseif ($service_type === 'express') {
                $query = "SELECT b.*, s.service_name, s.service_key 
                         FROM bookings b 
                         JOIN services s ON b.service_id = s.id 
                         WHERE b.service_type = 'express' 
                         AND b.status IN ('waiting', 'pending', 'now_serving')
                         ORDER BY b.queue_position ASC, b.created_at ASC";
            } else {
                throw new Exception('Invalid service type');
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'bookings' => $bookings]);
            break;
            
        case 'call_next':
            // Require staff login
            require_staff_login();
            
            // Call next batch
            $queue_type = isset($_POST['queue_type']) ? $_POST['queue_type'] : '';
            
            if ($queue_type === 'standard') {
                // Get next pending standard booking
                $query = "SELECT b.*, s.service_name 
                         FROM bookings b 
                         JOIN services s ON b.service_id = s.id 
                         WHERE b.service_type = 'standard' 
                         AND b.status = 'pending'
                         AND b.booking_date >= CURRENT_DATE
                         ORDER BY b.booking_date ASC, b.time_window ASC, b.queue_position ASC
                         LIMIT 1";
            } elseif ($queue_type === 'express') {
                // Get next waiting express booking
                $query = "SELECT b.*, s.service_name 
                         FROM bookings b 
                         JOIN services s ON b.service_id = s.id 
                         WHERE b.service_type = 'express' 
                         AND b.status IN ('waiting', 'pending')
                         ORDER BY b.queue_position ASC, b.created_at ASC
                         LIMIT 1";
            } else {
                throw new Exception('Invalid queue type');
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking) {
                // Update booking status to now_serving
                $update_query = "UPDATE bookings SET status = 'now_serving', called_at = CURRENT_TIMESTAMP WHERE id = :id";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute(['id' => $booking['id']]);
                
                // Update queue_status table
                $time_window = $booking['time_window'] ?: '';
                $queue_update = "UPDATE queue_status 
                                SET current_batch_number = :batch_number, 
                                    current_time_window = :time_window,
                                    last_updated = CURRENT_TIMESTAMP
                                WHERE queue_type = :queue_type";
                $queue_stmt = $pdo->prepare($queue_update);
                $queue_stmt->execute([
                    'batch_number' => $booking['batch_number'],
                    'time_window' => $time_window,
                    'queue_type' => $queue_type
                ]);
                
                echo json_encode([
                    'success' => true,
                    'booking' => $booking,
                    'batch_number' => $booking['batch_number'],
                    'time_window' => $time_window
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No bookings in queue']);
            }
            break;
            
        case 'complete':
            // Require staff login
            require_staff_login();
            
            // Mark booking as completed
            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            
            if ($booking_id > 0) {
                $query = "UPDATE bookings SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['id' => $booking_id]);
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Invalid booking ID');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

