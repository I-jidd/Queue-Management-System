<?php
/**
 * AUTHENTICATION HELPER
 * Functions to check authentication and protect pages
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
 * Check if user is logged in as admin
 * Redirects to login page if not authenticated or not admin
 */
function require_admin_login() {
    if (!isset($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit();
    }
    
    if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] !== 'admin') {
        // Not an admin, redirect to dashboard
        header("Location: admin.php");
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
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>



