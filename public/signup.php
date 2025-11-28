<?php
/**
 * STAFF SIGNUP PAGE
 * Allows admins to create new staff accounts
 * SECURED: Only admins can access this page
 */

require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';

// Require admin login - only admins can create staff accounts
require_admin_login();

$staff = get_current_staff();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validation
    if (empty($username) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check if username already exists
            $check_query = "SELECT id FROM staff WHERE username = :username LIMIT 1";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute(['username' => $username]);
            
            if ($check_stmt->fetch()) {
                $error = 'Username already exists. Please choose another.';
            } else {
                // Create new staff account
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO staff (username, password_hash, full_name, email, role) 
                                VALUES (:username, :password_hash, :full_name, :email, 'staff')";
                
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([
                    'username' => $username,
                    'password_hash' => $password_hash,
                    'full_name' => $full_name,
                    'email' => $email ?: null
                ]);
                
                $success = 'Staff account created successfully! The new staff member can now login with their credentials.';
            }
        } catch (PDOException $e) {
            error_log("Signup error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Signup - Registrar Queue System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ["Inter", "sans-serif"],
            },
            colors: {
              brand: {
                dark: "#1A472A",
                medium: "#1E6033",
                olive: "#8A9A5B",
                gold: "#FDCB0A",
              },
              neutral: {
                light: "#f4f7f6",
                DEFAULT: "#495057",
                dark: "#333333",
              },
            },
          },
        },
      };
    </script>

    <style>
      body {
        background-color: #f4f7f6;
      }
    </style>
  </head>
  <body class="font-sans antialiased text-neutral-DEFAULT">
    <div class="min-h-screen flex items-center justify-center p-4">
      <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <img
            src="assets/images/logo.png"
            alt="University Logo"
            class="h-16 w-16 object-contain rounded-full mx-auto mb-4"
          />
          <h1 class="text-2xl font-bold text-brand-dark">Create Staff Account</h1>
          <p class="text-neutral-DEFAULT mt-2">Admin Only - Create a new staff account</p>
          <p class="text-xs text-gray-500 mt-1">Logged in as: <?php echo htmlspecialchars($staff['name'] ?? 'Admin'); ?></p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
          <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
          <?php echo htmlspecialchars($success); ?>
          <div class="mt-3 flex gap-2">
            <a
              href="signup.php"
              class="px-4 py-2 bg-brand-medium text-white font-semibold rounded-lg hover:bg-brand-dark transition text-sm"
            >
              Create Another Account
            </a>
            <a
              href="admin.php"
              class="px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition text-sm"
            >
              Back to Dashboard
            </a>
          </div>
        </div>
        <?php endif; ?>

        <!-- Signup Form -->
        <?php if (!$success): ?>
        <form method="POST" action="signup.php">
          <div class="mb-4">
            <label
              for="username"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Username *
            </label>
            <input
              type="text"
              name="username"
              id="username"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="Choose a username"
            />
          </div>

          <div class="mb-4">
            <label
              for="full_name"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Full Name *
            </label>
            <input
              type="text"
              name="full_name"
              id="full_name"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="Enter your full name"
            />
          </div>

          <div class="mb-4">
            <label
              for="email"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Email (Optional)
            </label>
            <input
              type="email"
              name="email"
              id="email"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="Enter your email"
            />
          </div>

          <div class="mb-4">
            <label
              for="password"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Password *
            </label>
            <input
              type="password"
              name="password"
              id="password"
              required
              minlength="6"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="At least 6 characters"
            />
          </div>

          <div class="mb-6">
            <label
              for="confirm_password"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Confirm Password *
            </label>
            <input
              type="password"
              name="confirm_password"
              id="confirm_password"
              required
              minlength="6"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="Confirm your password"
            />
          </div>

          <button
            type="submit"
            class="w-full bg-brand-medium text-white font-bold py-3 px-4 rounded-lg hover:bg-brand-dark transition"
          >
            Create Account
          </button>
        </form>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <div class="mt-6 text-center">
          <a
            href="admin.php"
            class="text-sm text-brand-medium hover:text-brand-dark font-semibold"
          >
            ← Back to Dashboard
          </a>
        </div>
      </div>
    </div>
  </body>
</html>



