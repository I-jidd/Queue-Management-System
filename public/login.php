<?php
/**
 * STAFF LOGIN PAGE
 * Allows staff to authenticate and access the dashboard
 */

require_once __DIR__ . '/../src/db_connect.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['staff_id'])) {
    header("Location: admin.php");
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $query = "SELECT * FROM staff WHERE username = :username AND is_active = TRUE LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['username' => $username]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff && password_verify($password, $staff['password_hash'])) {
                // Login successful
                $_SESSION['staff_id'] = $staff['id'];
                $_SESSION['staff_username'] = $staff['username'];
                $_SESSION['staff_name'] = $staff['full_name'];
                $_SESSION['staff_role'] = $staff['role'];
                
                header("Location: admin.php");
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
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
    <title>Staff Login - Registrar Queue System</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png" />

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
          <h1 class="text-2xl font-bold text-brand-dark">Staff Login</h1>
          <p class="text-neutral-DEFAULT mt-2">Access the Registrar Queue Dashboard</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
          <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php">
          <div class="mb-4">
            <label
              for="username"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Username
            </label>
            <input
              type="text"
              name="username"
              id="username"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="Enter your username"
            />
          </div>

          <div class="mb-6">
            <label
              for="password"
              class="block text-sm font-medium text-neutral-dark mb-2"
            >
              Password
            </label>
            <input
              type="password"
              name="password"
              id="password"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              placeholder="Enter your password"
            />
          </div>

          <button
            type="submit"
            class="w-full bg-brand-medium text-white font-bold py-3 px-4 rounded-lg hover:bg-brand-dark transition"
          >
            Login
          </button>
        </form>

        <!-- Admin Note -->
        <div class="mt-6 text-center">
          <p class="text-sm text-neutral-DEFAULT">
            Staff accounts can only be created by administrators.
            <br>
            <span class="text-xs text-gray-500">Contact your system administrator for access.</span>
          </p>
        </div>

        <!-- Back to Home -->
        <div class="mt-4 text-center">
          <a
            href="index.html"
            class="text-sm text-neutral-DEFAULT hover:text-brand-medium"
          >
            ← Back to Home
          </a>
        </div>
      </div>
    </div>
  </body>
</html>



