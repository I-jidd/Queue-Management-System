<?php
/**
 * CHECK STAFF ACCOUNTS
 * Diagnostic script to check staff accounts in the database
 * SECURED: Only admins can access this
 */

require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';

// Require admin login
require_admin_login();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Staff Accounts</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #1A472A; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .active { color: green; font-weight: bold; }
        .inactive { color: red; font-weight: bold; }
        .admin { background: #fff3cd; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        a { color: #1A472A; text-decoration: none; padding: 8px 16px; background: #1E6033; color: white; border-radius: 5px; display: inline-block; margin: 10px 0; }
        a:hover { background: #1A472A; }
    </style>
</head>
<body>
    <h1>Staff Accounts Check</h1>
    <a href="admin.php">← Back to Dashboard</a>
    
<?php
try {
    $query = "SELECT id, username, full_name, email, role, is_active, created_at FROM staff ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($staff_members)) {
        echo "<div class='error'>No staff accounts found in the database.</div>";
    } else {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Username</th>";
        echo "<th>Full Name</th>";
        echo "<th>Email</th>";
        echo "<th>Role</th>";
        echo "<th>Active</th>";
        echo "<th>Created At</th>";
        echo "</tr>";
        
        foreach ($staff_members as $staff) {
            $row_class = ($staff['role'] === 'admin') ? 'admin' : '';
            $active_class = $staff['is_active'] ? 'active' : 'inactive';
            $active_text = $staff['is_active'] ? '✓ Active' : '✗ Inactive';
            
            echo "<tr class='$row_class'>";
            echo "<td>" . htmlspecialchars($staff['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($staff['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($staff['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($staff['email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($staff['role']) . "</td>";
            echo "<td class='$active_class'>$active_text</td>";
            echo "<td>" . htmlspecialchars($staff['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Count active vs inactive
        $active_count = 0;
        $inactive_count = 0;
        foreach ($staff_members as $staff) {
            if ($staff['is_active']) {
                $active_count++;
            } else {
                $inactive_count++;
            }
        }
        
        echo "<div class='success'>";
        echo "<strong>Summary:</strong><br>";
        echo "Total accounts: " . count($staff_members) . "<br>";
        echo "Active: <span class='active'>$active_count</span><br>";
        echo "Inactive: <span class='inactive'>$inactive_count</span>";
        echo "</div>";
    }
    
    // Test password verification for a specific account
    if (isset($_GET['test_user'])) {
        $test_username = $_GET['test_user'];
        $test_query = "SELECT username, password_hash, is_active FROM staff WHERE username = :username LIMIT 1";
        $test_stmt = $pdo->prepare($test_query);
        $test_stmt->execute(['username' => $test_username]);
        $test_staff = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_staff) {
            echo "<div class='success'>";
            echo "<strong>Account Details for: " . htmlspecialchars($test_username) . "</strong><br>";
            echo "Active: " . ($test_staff['is_active'] ? 'Yes' : 'No') . "<br>";
            echo "Password Hash: " . htmlspecialchars(substr($test_staff['password_hash'], 0, 30)) . "...<br>";
            echo "Hash Length: " . strlen($test_staff['password_hash']) . " characters<br>";
            echo "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
</body>
</html>

