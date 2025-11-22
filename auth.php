<?php
/**
 * AUTHENTICATION HELPER
 * Functions to check authentication and protect pages
 */

session_start();

/**
 * Check if user is logged in as staff
 * Redirects to login page if not authenticated
 */
function require_staff_login() {
    if (!isset($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Get current staff information
 */
function get_current_staff() {
    if (isset($_SESSION['staff_id'])) {
        return [
            'id' => $_SESSION['staff_id'],
            'username' => $_SESSION['staff_username'] ?? '',
            'name' => $_SESSION['staff_name'] ?? '',
            'role' => $_SESSION['staff_role'] ?? 'staff'
        ];
    }
    return null;
}

/**
 * Logout function
 */
function staff_logout() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>


