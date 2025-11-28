<?php
/**
 * TEST LOGIN
 * Diagnostic script to test login for a specific user
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
    <title>Test Login</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        input, button { padding: 10px; margin: 5px; }
        a { color: #1A472A; text-decoration: none; padding: 8px 16px; background: #1E6033; color: white; border-radius: 5px; display: inline-block; margin: 10px 0; }
        a:hover { background: #1A472A; }
    </style>
</head>
<body>
    <h1>Test Staff Login</h1>
    <a href="admin.php">← Back to Dashboard</a>
    
<?php
if (isset($_POST['test_username']) && isset($_POST['test_password'])) {
    $test_username = trim($_POST['test_username']);
    $test_password = $_POST['test_password'];
    
    echo "<div class='info'><strong>Testing login for:</strong> " . htmlspecialchars($test_username) . "</div>";
    
    try {
        // Get the staff account
        $query = "SELECT * FROM staff WHERE username = :username LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $test_username]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            echo "<div class='error'><strong>✗ Account not found:</strong> No staff account with username '" . htmlspecialchars($test_username) . "' exists in the database.</div>";
        } else {
            echo "<div class='info'>";
            echo "<strong>Account Found:</strong><br>";
            echo "ID: " . htmlspecialchars($staff['id']) . "<br>";
            echo "Username: " . htmlspecialchars($staff['username']) . "<br>";
            echo "Full Name: " . htmlspecialchars($staff['full_name']) . "<br>";
            echo "Role: " . htmlspecialchars($staff['role']) . "<br>";
            echo "Active: " . ($staff['is_active'] ? '✓ Yes' : '✗ No') . "<br>";
            echo "Password Hash: " . htmlspecialchars(substr($staff['password_hash'], 0, 50)) . "...<br>";
            echo "Hash Length: " . strlen($staff['password_hash']) . " characters<br>";
            echo "</div>";
            
            // Check if account is active
            if (!$staff['is_active']) {
                echo "<div class='error'><strong>✗ Account is INACTIVE:</strong> The account exists but is_active = FALSE. This will prevent login.</div>";
            }
            
            // Test password verification
            echo "<div class='info'><strong>Testing Password Verification:</strong></div>";
            
            if (password_verify($test_password, $staff['password_hash'])) {
                echo "<div class='success'><strong>✓ Password Verification: PASSED</strong><br>The password you entered matches the hash in the database.</div>";
                
                // Check if login would succeed
                if ($staff['is_active']) {
                    echo "<div class='success'><strong>✓ Login Should Work:</strong> Account is active and password is correct. Login should succeed.</div>";
                } else {
                    echo "<div class='error'><strong>✗ Login Will Fail:</strong> Password is correct but account is inactive.</div>";
                }
            } else {
                echo "<div class='error'><strong>✗ Password Verification: FAILED</strong><br>The password you entered does NOT match the hash in the database.</div>";
                echo "<div class='info'>";
                echo "<strong>Possible reasons:</strong><br>";
                echo "1. Wrong password entered<br>";
                echo "2. Password hash was corrupted during creation<br>";
                echo "3. Password was changed after account creation<br>";
                echo "</div>";
                
                // Show what the password should be (for debugging)
                echo "<div class='info'>";
                echo "<strong>Debug Info:</strong><br>";
                echo "Entered password: '" . htmlspecialchars($test_password) . "'<br>";
                echo "Password length: " . strlen($test_password) . " characters<br>";
                echo "Stored hash starts with: " . htmlspecialchars(substr($staff['password_hash'], 0, 20)) . "...<br>";
                echo "</div>";
            }
        }
    } catch (PDOException $e) {
        echo "<div class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

    <form method="POST" style="background: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h2>Test Login Credentials</h2>
        <div>
            <label>Username:</label><br>
            <input type="text" name="test_username" value="<?php echo isset($_POST['test_username']) ? htmlspecialchars($_POST['test_username']) : 'staff1'; ?>" required style="width: 300px;">
        </div>
        <div>
            <label>Password:</label><br>
            <input type="password" name="test_password" value="<?php echo isset($_POST['test_password']) ? htmlspecialchars($_POST['test_password']) : 'staff1'; ?>" required style="width: 300px;">
        </div>
        <button type="submit" style="background: #1E6033; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Test Login</button>
    </form>
</body>
</html>

