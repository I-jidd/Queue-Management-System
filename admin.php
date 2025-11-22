<?php
/**
 * STAFF DASHBOARD
 * Protected admin page that displays and manages the queue
 */

require_once 'db_connect.php';
require_once 'auth.php';

// Require staff login
require_staff_login();

$staff = get_current_staff();

// Load bookings from database
try {
    // Standard bookings
    $standard_query = "SELECT b.*, s.service_name, s.service_key 
                      FROM bookings b 
                      JOIN services s ON b.service_id = s.id 
                      WHERE b.service_type = 'standard' 
                      AND b.status != 'cancelled'
                      ORDER BY b.booking_date ASC, b.time_window ASC, b.queue_position ASC";
    $standard_stmt = $pdo->prepare($standard_query);
    $standard_stmt->execute();
    $standard_bookings = $standard_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Express bookings
    $express_query = "SELECT b.*, s.service_name, s.service_key 
                     FROM bookings b 
                     JOIN services s ON b.service_id = s.id 
                     WHERE b.service_type = 'express' 
                     AND b.status IN ('waiting', 'pending', 'now_serving')
                     ORDER BY b.queue_position ASC, b.created_at ASC";
    $express_stmt = $pdo->prepare($express_query);
    $express_stmt->execute();
    $express_bookings = $express_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading bookings: " . $e->getMessage());
    $standard_bookings = [];
    $express_bookings = [];
}

// Helper function to get status badge class
function get_status_badge_class($status) {
    switch ($status) {
        case 'completed':
            return 'text-green-800 bg-green-200';
        case 'now_serving':
            return 'text-blue-800 bg-blue-200';
        case 'waiting':
        case 'pending':
            return 'text-yellow-800 bg-yellow-200';
        default:
            return 'text-gray-800 bg-gray-200';
    }
}

// Helper function to format date
function format_date($date) {
    if (!$date) return '';
    return date('M j, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Dashboard - Registrar Queue</title>

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
      .tab-button {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        color: #495057;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
      }
      .tab-button.active {
        color: #1a472a;
        border-bottom-color: #1a472a;
      }
      .tab-button:hover:not(.active) {
        background-color: #f9f9f9;
      }
      .tab-content {
        display: none;
      }
      .tab-content.active {
        display: block;
      }
      .queue-row.completed {
        background-color: #f9f9f9;
        opacity: 0.6;
        text-decoration: line-through;
      }
      .queue-row.now-serving {
        background-color: #fefbec;
        border-left: 4px solid #fdcb0a;
      }
    </style>
  </head>
  <body class="font-sans antialiased text-neutral-DEFAULT p-4">
    <div class="container max-w-6xl w-full mx-auto my-5">
      <header
        class="flex items-center justify-between p-4 bg-brand-dark text-white rounded-lg shadow-md"
      >
        <div>
          <h1 class="text-2xl font-bold">Staff Dashboard</h1>
          <p class="text-sm opacity-90">
            Welcome, <?php echo htmlspecialchars($staff['name']); ?> | 
            <a href="logout.php" class="hover:underline">Logout</a>
          </p>
        </div>
        <a
          href="index.html"
          class="font-semibold text-white hover:text-gray-200 hover:underline"
        >
          &larr; Back to Student Site
        </a>
      </header>

      <main class="mt-6 bg-white rounded-xl shadow-lg p-6 md:p-8">
        <div class="border-b border-gray-200 mb-6">
          <nav class="flex -mb-px">
            <button id="tab-standard-btn" class="tab-button active">
              Standard Batch Queue (<?php echo count($standard_bookings); ?>)
            </button>
            <button id="tab-express-btn" class="tab-button">
              Express Queue (<?php echo count($express_bookings); ?>)
            </button>
          </nav>
        </div>

        <!-- Standard Queue Tab -->
        <div id="tab-standard-content" class="tab-content active">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-neutral-dark">
              Standard Batch Queue
            </h2>
            <button
              id="call-next-standard"
              class="bg-brand-dark text-white font-bold py-2 px-5 rounded-lg hover:bg-brand-medium transition"
            >
              Call Next Batch
            </button>
          </div>

          <div class="overflow-x-auto rounded-lg border">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time Window
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Batch #
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Service
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Student
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody id="standard-queue-body" class="bg-white divide-y divide-gray-200">
                <?php if (empty($standard_bookings)): ?>
                <tr>
                  <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    No standard bookings found
                  </td>
                </tr>
                <?php else: ?>
                <?php foreach ($standard_bookings as $booking): ?>
                <tr class="queue-row <?php echo $booking['status'] === 'completed' ? 'completed' : ''; ?> <?php echo $booking['status'] === 'now_serving' ? 'now-serving' : ''; ?>" 
                    data-batch="<?php echo htmlspecialchars($booking['batch_number']); ?>"
                    data-id="<?php echo $booking['id']; ?>">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php echo format_date($booking['booking_date']); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap font-medium text-brand-dark">
                    <?php echo htmlspecialchars($booking['time_window'] ?: 'N/A'); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap font-medium">
                    <?php echo htmlspecialchars($booking['batch_number']); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php echo htmlspecialchars($booking['service_name']); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php echo htmlspecialchars($booking['student_name'] ?: 'Anonymous'); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="status-badge px-2 py-1 text-xs font-semibold rounded-full <?php echo get_status_badge_class($booking['status']); ?>">
                      <?php echo ucfirst($booking['status']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($booking['status'] !== 'completed'): ?>
                    <button
                      class="action-btn-complete text-brand-medium hover:text-brand-dark font-semibold"
                      data-booking-id="<?php echo $booking['id']; ?>"
                    >
                      Complete
                    </button>
                    <?php else: ?>
                    <span class="text-gray-400">Completed</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Express Queue Tab -->
        <div id="tab-express-content" class="tab-content">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-neutral-dark">
              Express Queue
            </h2>
            <button
              id="call-next-express"
              class="bg-brand-olive text-white font-bold py-2 px-5 rounded-lg hover:bg-opacity-90 transition"
            >
              Call Next
            </button>
          </div>

          <div class="overflow-x-auto rounded-lg border">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Batch #
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Service
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Student
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody id="express-queue-body" class="bg-white divide-y divide-gray-200">
                <?php if (empty($express_bookings)): ?>
                <tr>
                  <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    No express bookings found
                  </td>
                </tr>
                <?php else: ?>
                <?php foreach ($express_bookings as $booking): ?>
                <tr class="queue-row <?php echo $booking['status'] === 'completed' ? 'completed' : ''; ?> <?php echo $booking['status'] === 'now_serving' ? 'now-serving' : ''; ?>" 
                    data-batch="<?php echo htmlspecialchars($booking['batch_number']); ?>"
                    data-id="<?php echo $booking['id']; ?>">
                  <td class="px-6 py-4 whitespace-nowrap font-medium">
                    <?php echo htmlspecialchars($booking['batch_number']); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php echo htmlspecialchars($booking['service_name']); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php echo htmlspecialchars($booking['student_name'] ?: 'Anonymous'); ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="status-badge px-2 py-1 text-xs font-semibold rounded-full <?php echo get_status_badge_class($booking['status']); ?>">
                      <?php echo ucfirst($booking['status']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($booking['status'] !== 'completed'): ?>
                    <button
                      class="action-btn-complete text-brand-medium hover:text-brand-dark font-semibold"
                      data-booking-id="<?php echo $booking['id']; ?>"
                    >
                      Complete
                    </button>
                    <?php else: ?>
                    <span class="text-gray-400">Completed</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Tab Logic
        const standardBtn = document.getElementById("tab-standard-btn");
        const expressBtn = document.getElementById("tab-express-btn");
        const standardContent = document.getElementById("tab-standard-content");
        const expressContent = document.getElementById("tab-express-content");

        standardBtn.addEventListener("click", function () {
          standardBtn.classList.add("active");
          standardContent.classList.add("active");
          expressBtn.classList.remove("active");
          expressContent.classList.remove("active");
        });

        expressBtn.addEventListener("click", function () {
          expressBtn.classList.add("active");
          expressContent.classList.add("active");
          standardBtn.classList.remove("active");
          standardContent.classList.remove("active");
        });

        // Complete Button Logic
        document.querySelectorAll(".action-btn-complete").forEach((button) => {
          button.addEventListener("click", function () {
            const bookingId = this.dataset.bookingId;
            const row = this.closest(".queue-row");
            
            if (row && !row.classList.contains("completed")) {
              fetch("api_queue.php?action=complete", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "booking_id=" + bookingId
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  row.classList.add("completed");
                  const statusBadge = row.querySelector(".status-badge");
                  if (statusBadge) {
                    statusBadge.textContent = "Completed";
                    statusBadge.className = "status-badge px-2 py-1 text-xs font-semibold rounded-full text-green-800 bg-green-200";
                  }
                  this.disabled = true;
                  this.textContent = "Completed";
                  this.classList.add("text-gray-400");
                  this.classList.remove("text-brand-medium", "hover:text-brand-dark");
                }
              })
              .catch(error => console.error("Error:", error));
            }
          });
        });

        // Call Next Batch - Standard
        document.getElementById("call-next-standard").addEventListener("click", function () {
          fetch("api_queue.php?action=call_next", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "queue_type=standard"
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update UI
              const row = document.querySelector(`[data-batch="${data.batch_number}"]`);
              if (row) {
                document.querySelectorAll(".now-serving").forEach(r => r.classList.remove("now-serving"));
                row.classList.add("now-serving");
                const statusBadge = row.querySelector(".status-badge");
                if (statusBadge) {
                  statusBadge.textContent = "Now Serving";
                  statusBadge.className = "status-badge px-2 py-1 text-xs font-semibold rounded-full text-blue-800 bg-blue-200";
                }
              }
              this.textContent = `Now Serving (${data.batch_number})`;
              // Update status.html via localStorage
              localStorage.setItem("current_standard_batch", data.batch_number);
              localStorage.setItem("current_standard_time", data.time_window || "");
              // Trigger storage event for status.html
              window.dispatchEvent(new Event('storage'));
            } else {
              alert(data.message || "No bookings in queue");
            }
          })
          .catch(error => {
            console.error("Error:", error);
            alert("Error calling next batch");
          });
        });

        // Call Next Batch - Express
        document.getElementById("call-next-express").addEventListener("click", function () {
          fetch("api_queue.php?action=call_next", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "queue_type=express"
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const row = document.querySelector(`[data-batch="${data.batch_number}"]`);
              if (row) {
                document.querySelectorAll(".now-serving").forEach(r => r.classList.remove("now-serving"));
                row.classList.add("now-serving");
                const statusBadge = row.querySelector(".status-badge");
                if (statusBadge) {
                  statusBadge.textContent = "Now Serving";
                  statusBadge.className = "status-badge px-2 py-1 text-xs font-semibold rounded-full text-blue-800 bg-blue-200";
                }
              }
              this.textContent = `Now Serving (${data.batch_number})`;
              // Update status.html via localStorage
              localStorage.setItem("current_express_batch", data.batch_number);
              // Trigger storage event for status.html
              window.dispatchEvent(new Event('storage'));
            } else {
              alert(data.message || "No bookings in queue");
            }
          })
          .catch(error => {
            console.error("Error:", error);
            alert("Error calling next batch");
          });
        });

        // Auto-refresh every 5 seconds
        setInterval(function() {
          location.reload();
        }, 30000); // Refresh every 30 seconds
      });
    </script>
  </body>
</html>


