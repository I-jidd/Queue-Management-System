<?php
/**
 * GET BATCH NUMBER PAGE
 * Form to select service and get a batch number
 */

require_once __DIR__ . '/../src/db_connect.php';

// Get all available services
$services = [];
try {
    $query = "SELECT * FROM services ORDER BY service_type, service_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
    $services = [];
}

// Generate time slots for standard services
$time_slots = [
    '08:00-08:30',
    '08:30-09:00',
    '09:00-09:30',
    '09:30-10:00',
    '10:00-10:30',
    '10:30-11:00',
    '11:00-11:30',
    '11:30-12:00',
    '13:00-13:30',
    '13:30-14:00',
    '14:00-14:30',
    '14:30-15:00',
    '15:00-15:30',
    '15:30-16:00',
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Get Batch Number - Registrar Queue System</title>

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
  <body class="font-sans antialiased text-neutral-DEFAULT p-4">
    <div
      class="container max-w-2xl w-full mx-auto my-5 p-6 md:p-8 bg-white rounded-xl shadow-lg"
    >
      <!-- Header -->
      <header
        class="flex items-center border-b-2 border-gray-200 pb-4 mb-6 md:mb-8"
      >
        <img
          src="assets/images/logo.png"
          alt="University Logo"
          class="h-12 w-12 md:h-14 md:w-14 object-contain rounded-full"
        />
        <h1 class="text-xl md:text-2xl font-bold text-brand-dark ml-4">
          Get Your Batch Number
        </h1>
      </header>

      <!-- Main Content -->
      <main>
        <a
          href="index.html"
          class="inline-flex items-center mb-6 text-brand-medium font-semibold hover:text-brand-dark hover:underline"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Back to Home
        </a>

        <form action="process_booking.php" method="POST" id="booking-form">
          <!-- Service Selection -->
          <div class="mb-6">
            <label
              for="service_id"
              class="block text-lg font-semibold text-neutral-dark mb-3"
            >
              Select Your Service
            </label>
            <select
              name="service_id"
              id="service_id"
              required
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium text-lg"
            >
              <option value="">-- Please select a service --</option>
              <?php foreach ($services as $service): ?>
              <option
                value="<?php echo htmlspecialchars($service['id']); ?>"
                data-type="<?php echo htmlspecialchars($service['service_type']); ?>"
                data-key="<?php echo htmlspecialchars($service['service_key']); ?>"
              >
                <?php echo htmlspecialchars($service['service_name']); ?>
                (<?php echo $service['service_type'] === 'express' ? 'Express' : 'Standard'; ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Standard Service Fields (Date & Time) -->
          <div id="standard-fields" class="mb-6 hidden">
            <div class="mb-4">
              <label
                for="booking_date"
                class="block text-lg font-semibold text-neutral-dark mb-3"
              >
                Select Date
              </label>
              <input
                type="date"
                name="booking_date"
                id="booking_date"
                min="<?php echo date('Y-m-d'); ?>"
                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium text-lg"
              />
            </div>

            <div>
              <label
                for="time_window"
                class="block text-lg font-semibold text-neutral-dark mb-3"
              >
                Select Time Window
              </label>
              <select
                name="time_window"
                id="time_window"
                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium text-lg"
              >
                <option value="">-- Select time --</option>
                <?php foreach ($time_slots as $slot): ?>
                <option value="<?php echo htmlspecialchars($slot); ?>">
                  <?php echo htmlspecialchars($slot); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Optional Student Information -->
          <div class="mb-6 border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-neutral-dark mb-4">
              Optional Information
            </h3>
            <p class="text-sm text-neutral-DEFAULT mb-4">
              You can provide your information (optional) to help us serve you better.
            </p>

            <div class="mb-4">
              <label
                for="student_name"
                class="block text-sm font-medium text-neutral-DEFAULT mb-2"
              >
                Your Name
              </label>
              <input
                type="text"
                name="student_name"
                id="student_name"
                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              />
            </div>

            <div class="mb-4">
              <label
                for="student_id"
                class="block text-sm font-medium text-neutral-DEFAULT mb-2"
              >
                Student ID
              </label>
              <input
                type="text"
                name="student_id"
                id="student_id"
                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              />
            </div>

            <div>
              <label
                for="student_email"
                class="block text-sm font-medium text-neutral-DEFAULT mb-2"
              >
                Email (optional)
              </label>
              <input
                type="email"
                name="student_email"
                id="student_email"
                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium"
              />
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="w-full bg-brand-medium text-white font-bold py-4 px-6 rounded-lg hover:bg-brand-dark transition text-lg"
          >
            Get My Batch Number
          </button>
        </form>
      </main>

      <!-- Footer -->
      <footer class="text-center mt-8 pt-6 border-t border-gray-200 text-sm text-gray-500">
        <p>
          &copy; <?php echo date('Y'); ?> Central Mindanao University | Registrar Queue Management System
        </p>
      </footer>
    </div>

    <script>
      // Show/hide standard service fields based on service selection
      const serviceSelect = document.getElementById('service_id');
      const standardFields = document.getElementById('standard-fields');
      const bookingDate = document.getElementById('booking_date');
      const timeWindow = document.getElementById('time_window');

      serviceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const serviceType = selectedOption.getAttribute('data-type');

        if (serviceType === 'standard') {
          standardFields.classList.remove('hidden');
          bookingDate.setAttribute('required', 'required');
          timeWindow.setAttribute('required', 'required');
        } else {
          standardFields.classList.add('hidden');
          bookingDate.removeAttribute('required');
          timeWindow.removeAttribute('required');
          bookingDate.value = '';
          timeWindow.value = '';
        }
      });
    </script>
  </body>
</html>
