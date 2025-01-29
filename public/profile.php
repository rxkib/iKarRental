<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

require_once __DIR__ . '/../includes/storage.php';

try {
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
    $carStorage     = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
} catch (Exception $e) {
    die("Error initializing storages: " . $e->getMessage());
}

$userEmail = $user['email'] ?? '';

$allBookings = $bookingStorage->findAll(); 

$userBookings = [];
foreach ($allBookings as $bId => $bk) {
    if (($bk['user_email'] ?? '') === $userEmail && (($bk['status'] ?? 'active') !== 'canceled')) {
        $userBookings[$bId] = $bk;
    }
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <!-- Header -->
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-base-content">Profile</h1>
    <div class="space-x-2">
      <a href="index.php" class="btn btn-secondary">Home</a>
      <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container mx-auto">
    <div class="bg-base-100 shadow-lg rounded p-6 mb-8">
      <h2 class="text-2xl font-bold mb-2">User Details</h2>
      <p class="mb-1">
        <strong>Name:</strong>
        <?= htmlspecialchars($user['full_name'] ?? '') ?>
      </p>
      <p class="mb-3">
        <strong>Email:</strong>
        <?= htmlspecialchars($user['email'] ?? '') ?>
      </p>
      <a href="changePassword.php" class="btn btn-sm btn-outline">
        Change Password
      </a>
    </div>

    <!-- BOOKINGS SECTION -->
    <h3 class="text-xl font-bold mb-4">Your Bookings</h3>
    <?php if (count($userBookings) === 0): ?>
      <div class="alert alert-info">
        <span>You have no active bookings.</span>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($userBookings as $bId => $bk): ?>
          <?php
            $carId = $bk['car_id'] ?? null;
            $car   = $carId ? $carStorage->findById($carId) : null;
            if (!$car) {
                continue;
            }

            $brandModel = $car['brand'] . ' ' . $car['model'];
            $imageUrl   = $car['image'];

            $startTs = strtotime($bk['start_date'] ?? '');
            $endTs   = strtotime($bk['end_date'] ?? '');
            $totalCost = null;
            if ($startTs && $endTs && $startTs <= $endTs) {
                $days      = max(1, floor(($endTs - $startTs) / 86400) + 1);
                $totalCost = $days * ($car['daily_price_huf'] ?? 0);
            }
          ?>

          <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
            <input type="checkbox"/>
            <div class="collapse-title text-md font-bold">
              <?= htmlspecialchars($brandModel) ?>
            </div>
            <div class="collapse-content">
              <div class="flex flex-col sm:flex-row gap-4 items-start">
                <img
                  src="<?= htmlspecialchars($imageUrl) ?>"
                  alt="Car Image"
                  class="w-28 h-28 object-cover rounded"
                />
                <div>
                  <p><strong>Booking ID:</strong> <?= htmlspecialchars($bId) ?></p>
                  <p><strong>From:</strong> <?= htmlspecialchars($bk['start_date'] ?? '') ?></p>
                  <p><strong>To:</strong> <?= htmlspecialchars($bk['end_date'] ?? '') ?></p>
                  <?php if (!is_null($totalCost)): ?>
                    <p>
                      <strong>Total Cost:</strong>
                      <?= number_format($totalCost) ?> HUF
                    </p>
                  <?php endif; ?>
                  <div class="mt-2 flex space-x-2">
                    <a href="carDetails.php?id=<?= urlencode($carId) ?>"
                       class="btn btn-sm btn-primary"
                    >
                      View Car Details
                    </a>
                    <form
                      action="cancelBooking.php"
                      method="POST"
                      onsubmit="return confirm('Cancel this booking?')"
                      class="inline"
                    >
                      <input type="hidden" name="booking_id" value="<?= htmlspecialchars($bId) ?>"/>
                      <button type="submit" class="btn btn-sm btn-error btn-outline">
                        Cancel Booking
                      </button>
                    </form>
                  </div>
                </div>
              </div> 
            </div> 
          </div> 
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Footer -->
  <footer class="mt-10 text-center">
    <p class="text-sm text-base-content">&copy; <?= date("Y") ?> iKarRental by Rakib</p>
  </footer>
</body>
</html>
