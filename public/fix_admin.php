<?php
/**
 * FIX ADMIN ACCOUNT
 * This script will update the admin password hash to ensure it works
 * Run this once to fix the admin account, then delete this file
 */

require_once __DIR__ . '/../src/db_connect.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Account</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Fix Admin Account</h1>
<?php

// Generate a fresh password hash for 'admin123'
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

echo "<div class='info'><strong>Generated password hash:</strong><br><pre>" . htmlspecialchars($password_hash) . "</pre></div>";

// Update the admin account
try {
    $query = "UPDATE staff SET password_hash = :password_hash WHERE username = 'admin'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['password_hash' => $password_hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'><strong>✓ Admin account password updated successfully!</strong><br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong></div>";
        
        // Verify the account exists and is active
        $check_query = "SELECT username, full_name, role, is_active FROM staff WHERE username = 'admin'";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute();
        $admin = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<div class='info'><strong>Admin account details:</strong><br>";
            echo "  Username: " . htmlspecialchars($admin['username']) . "<br>";
            echo "  Full Name: " . htmlspecialchars($admin['full_name']) . "<br>";
            echo "  Role: " . htmlspecialchars($admin['role']) . "<br>";
            echo "  Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "</div>";
        }
        
        // Test password verification
        $test_query = "SELECT password_hash FROM staff WHERE username = 'admin'";
        $test_stmt = $pdo->prepare($test_query);
        $test_stmt->execute();
        $result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && password_verify('admin123', $result['password_hash'])) {
            echo "<div class='success'><strong>✓ Password verification test: PASSED</strong></div>";
        } else {
            echo "<div class='error'><strong>✗ Password verification test: FAILED</strong></div>";
        }
        
    } else {
        // Admin doesn't exist, create it
        echo "<div class='info'>Admin account not found. Creating new admin account...</div>";
        $insert_query = "INSERT INTO staff (username, password_hash, full_name, email, role) 
                        VALUES ('admin', :password_hash, 'System Administrator', 'admin@university.edu', 'admin')";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute(['password_hash' => $password_hash]);
        
        echo "<div class='success'><strong>✓ Admin account created successfully!</strong><br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong></div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit(1);
}

echo "<div class='success'><strong>Done! You can now login with:</strong><br>";
echo "  Username: <strong>admin</strong><br>";
echo "  Password: <strong>admin123</strong></div>";
echo "<div class='warning'><strong>⚠️ Security Warning:</strong> Remember to delete this file (fix_admin.php) after use!</div>";
?>
</body>
</html>

