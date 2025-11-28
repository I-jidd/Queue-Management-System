<?php
/**
 * CONFIRMATION PAGE
 * Displays the booking confirmation and checklist
 */

require_once __DIR__ . '/../src/db_connect.php';

// Start session
session_start();

// Check if booking was successful
if (!isset($_SESSION['booking_success']) || !isset($_SESSION['booking_data'])) {
    header("Location: get-batch.php");
    exit();
}

// Get booking data from session
$booking = $_SESSION['booking_data'];

// Clear the session data (one-time display)
unset($_SESSION['booking_success']);
unset($_SESSION['booking_data']);

// Prepare checklist data
$checklists = [
    'add-drop' => [
        'name' => 'Add/Drop Subjects',
        'items' => [
            'Your printed class schedule (for reference).',
            'Add/Drop Form, signed by your Department Head.',
            'Your official University ID.'
        ]
    ],
    'inc-clearance' => [
        'name' => 'INC Clearance / Grade Correction',
        'items' => [
            'Completed INC Completion Form, signed by your Professor.',
            'Your official University ID.'
        ]
    ],
    'submit-form' => [
        'name' => 'Submit a Form',
        'items' => [
            'The fully-completed form you need to submit.',
            'Your official University ID.'
        ]
    ],
    'pickup-doc' => [
        'name' => 'Pick up a Document',
        'items' => [
            'Your official University ID.',
            'The claim stub or email confirmation (if you have one).'
        ]
    ],
    'quick-question' => [
        'name' => 'Ask a Quick Question',
        'items' => [
            'Your official University ID.',
            'Any relevant documents related to your question.'
        ]
    ],
    'default' => [
        'name' => 'General Visit',
        'items' => [
            'Your official University ID.',
            'Any forms or documents related to your visit.'
        ]
    ]
];

// Get the appropriate checklist
$service_key = $booking['service_key'];
$checklist = isset($checklists[$service_key]) ? $checklists[$service_key] : $checklists['default'];

// Format the time/location text
if ($booking['service_type'] === 'standard') {
    $date_display = format_date_display($booking['booking_date']);
    $time_display = $date_display . ', ' . $booking['time_window'];
} else {
    $time_display = 'Proceed to Express Window 1 Now';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Confirmation - Registrar Queue System</title>

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
      .checklist-item {
        display: flex;
        align-items: center;
        font-size: 1.1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
      }
      .checklist-item svg {
        color: #1e6033;
        width: 1.5rem;
        height: 1.5rem;
        margin-right: 0.75rem;
        flex-shrink: 0;
      }
      @media print {
        .no-print {
          display: none;
        }
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
          Registrar Queue System
        </h1>
      </header>

      <!-- Main Content -->
      <main>
        <!-- Success Message -->
        <div class="text-center mb-8">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-16 w-16 text-brand-medium mx-auto mb-3"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
          <h2 class="text-2xl md:text-3xl font-bold text-brand-dark">
            You're All Set!
          </h2>
          <p class="text-lg text-neutral-DEFAULT mt-1">
            Your batch number is confirmed.
          </p>
        </div>

        <!-- Your Ticket Section -->
        <div
          class="border-2 border-brand-dark border-dashed rounded-lg p-5 mb-8 text-center"
        >
          <p class="text-sm font-semibold text-neutral-DEFAULT uppercase">
            Your Service:
          </p>
          <h3 class="text-2xl font-bold text-brand-dark mb-4">
            <?php echo htmlspecialchars($booking['service_name']); ?>
          </h3>

          <p class="text-sm font-semibold text-neutral-DEFAULT uppercase">
            Your Batch Number:
          </p>
          <h3 class="text-5xl font-extrabold text-brand-medium mb-4">
            <?php echo htmlspecialchars($booking['batch_number']); ?>
          </h3>

          <p class="text-sm font-semibold text-neutral-DEFAULT uppercase">
            Your Time / Location:
          </p>
          <h3 class="text-xl font-bold text-neutral-dark">
            <?php echo htmlspecialchars($time_display); ?>
          </h3>
        </div>

        <!-- Pre-Appointment Checklist Section -->
        <section>
          <h2 class="text-2xl font-bold text-neutral-dark mb-4">
            Pre-Appointment Checklist
          </h2>
          <p class="text-neutral-DEFAULT mb-5">
            To ensure your transaction is completed in <strong>one visit</strong>, please
            bring the following:
          </p>

          <div class="space-y-2">
            <?php foreach ($checklist['items'] as $item): ?>
            <div class="checklist-item">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span><?php echo htmlspecialchars($item); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- Action Buttons -->
        <div class="mt-8 space-y-3 no-print">
          <button
            onclick="window.print()"
            class="block w-full text-center bg-brand-medium text-white font-bold py-3 px-4 rounded-lg hover:bg-brand-dark transition text-lg"
          >
            Print This Ticket
          </button>
          
          <a
            href="index.html"
            class="block w-full text-center bg-brand-olive text-white font-bold py-3 px-4 rounded-lg hover:bg-opacity-90 transition text-lg"
          >
            Back to Homepage
          </a>
        </div>
      </main>

      <!-- Footer -->
      <footer class="text-center mt-8 pt-6 border-t border-gray-200 text-sm text-gray-500 no-print">
        <p>
          &copy; <?php echo date('Y'); ?> Central Mindanao University
        </p>
      </footer>
    </div>
  </body>
</html>
