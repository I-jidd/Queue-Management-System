<?php
/**
 * PROCESS BOOKING
 * Handles the booking form submission and creates the booking
 */

require_once 'db_connect.php';

// Start session
session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: get-batch.php");
    exit();
}

// Get and validate form data
$service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$booking_date = isset($_POST['booking_date']) ? sanitize_input($_POST['booking_date']) : null;
$time_window = isset($_POST['time_window']) ? sanitize_input($_POST['time_window']) : null;
$student_name = isset($_POST['student_name']) ? sanitize_input($_POST['student_name']) : null;
$student_id = isset($_POST['student_id']) ? sanitize_input($_POST['student_id']) : null;
$student_email = isset($_POST['student_email']) ? sanitize_input($_POST['student_email']) : null;

// Validate service ID
if ($service_id <= 0) {
    header("Location: get-batch.php?error=invalid_service");
    exit();
}

// Get service information
$service_query = "SELECT * FROM services WHERE id = $service_id LIMIT 1";
$service_result = $conn->query($service_query);

if (!$service_result || $service_result->num_rows === 0) {
    header("Location: get-batch.php?error=service_not_found");
    exit();
}

$service = $service_result->fetch_assoc();
$service_type = $service['service_type'];

// Validate standard service requirements
if ($service_type === 'standard') {
    if (empty($booking_date) || empty($time_window)) {
        header("Location: get-batch.php?error=missing_date_time");
        exit();
    }

    // Check if time slot is available
    if (!is_time_slot_available($booking_date, $time_window)) {
        header("Location: get-batch.php?error=slot_full");
        exit();
    }
}

// Generate batch number
$batch_number = generate_batch_number($service_type);

// Get queue position
$queue_position = get_next_queue_position($service_type, $booking_date);

// Insert booking into database
$status = ($service_type === 'express') ? 'waiting' : 'pending';

$insert_query = "INSERT INTO bookings (
    batch_number,
    service_id,
    service_type,
    booking_date,
    time_window,
    student_name,
    student_id,
    student_email,
    status,
    queue_position
) VALUES (
    '$batch_number',
    $service_id,
    '$service_type',
    " . ($booking_date ? "'$booking_date'" : "NULL") . ",
    " . ($time_window ? "'$time_window'" : "NULL") . ",
    " . ($student_name ? "'$student_name'" : "NULL") . ",
    " . ($student_id ? "'$student_id'" : "NULL") . ",
    " . ($student_email ? "'$student_email'" : "NULL") . ",
    '$status',
    $queue_position
)";

if ($conn->query($insert_query)) {
    // Store booking data in session for confirmation page
    $_SESSION['booking_success'] = true;
    $_SESSION['booking_data'] = [
        'batch_number' => $batch_number,
        'service_name' => $service['service_name'],
        'service_key' => $service['service_key'],
        'service_type' => $service_type,
        'booking_date' => $booking_date,
        'time_window' => $time_window,
        'queue_position' => $queue_position
    ];

    // Redirect to confirmation page
    header("Location: confirmation.php");
    exit();
} else {
    // Database error
    header("Location: get-batch.php?error=database_error");
    exit();
}
?>
