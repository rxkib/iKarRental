<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../includes/storage.php';

try {
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
    $carStorage     = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
} catch (Exception $e) {
    die("Error initializing storages: " . $e->getMessage());
}

$errors = [];
$success = false;
$newBooking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'car_id'     => trim($_POST['car_id'] ?? ''),
        'start_date' => trim($_POST['start_date'] ?? ''),
        'end_date'   => trim($_POST['end_date'] ?? ''),
        'user_email' => $_SESSION['user']['email'] ?? ''
    ];

    if ($data['car_id'] === '') {
        $errors['car_id'] = "Invalid car ID.";
    }

    if ($data['start_date'] === '' || $data['end_date'] === '') {
        $errors['dates'] = "Start and end dates are required.";
    } else {
        $startTs = strtotime($data['start_date']);
        $endTs   = strtotime($data['end_date']);
        if (!$startTs || !$endTs) {
            $errors['dates'] = "Invalid date format.";
        } elseif ($startTs > $endTs) {
            $errors['dates'] = "End date must be after or equal to start date.";
        }
    }

    if (count($errors) === 0) {
        $car = $carStorage->findById($data['car_id']);
        if (!$car) {
            $errors['car_id'] = "Car not found. Cannot book.";
        } else {
            $allBookings = $bookingStorage->findAll(); 
            $startTs = strtotime($data['start_date']);
            $endTs   = strtotime($data['end_date']);

            foreach ($allBookings as $bId => $b) {
                if (($b['car_id'] ?? '') !== $data['car_id']) {
                    continue;
                }
                if (($b['status'] ?? 'active') === 'canceled') {
                    continue;
                }
                $bStart = strtotime($b['start_date']);
                $bEnd   = strtotime($b['end_date']);
                if ($startTs <= $bEnd && $endTs >= $bStart) {
                    $errors['overlap'] = "Booking conflict: Sorry, this car is already booked within the date range.";
                    break;
                }
            }
        }
    }

    if (count($errors) === 0) {
        $newBooking = [
            'car_id'     => $data['car_id'],
            'user_email' => $data['user_email'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'status'     => 'active' 
        ];
        $bookingStorage->add($newBooking);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Book Car</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">Car Booking</h1>
    <div class="space-x-2">
      <a href="index.php" class="btn btn-secondary">Home</a>
      <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
    </div>
  </header>

  <main class="container mx-auto">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error shadow-lg mb-4">
        <ul class="list-disc ml-4">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php if (!empty($data['car_id'])): ?>
        <a href="carDetails.php?id=<?= urlencode($data['car_id']) ?>" class="btn btn-primary">Go Back</a>
      <?php else: ?>
        <a href="index.php" class="btn btn-primary">Go Home</a>
      <?php endif; ?>

    <?php elseif ($success && $newBooking): ?>
      <div class="alert alert-success shadow-lg mb-4">
        <span>Booking Successful!</span>
      </div>
      <?php
        $startTs = strtotime($newBooking['start_date']);
        $endTs   = strtotime($newBooking['end_date']);
        $days = max(1, (int) floor(($endTs - $startTs) / 86400) + 1);
        $dailyPrice = $car['daily_price_huf'];
        $totalPrice = $days * $dailyPrice;
      ?>
      <div class="card bg-base-100 shadow-xl p-4 mb-4">
        <div class="card-body">
          <h2 class="card-title">
            <?= htmlspecialchars($car['brand']) ?>
            <?= htmlspecialchars($car['model']) ?>
          </h2>
          <p>Booking Period: <?= htmlspecialchars($newBooking['start_date']) ?> to <?= htmlspecialchars($newBooking['end_date']) ?></p>
          <p>Total days: <?= $days ?></p>
          <p>Daily Price: <?= number_format($dailyPrice) ?> HUF</p>
          <p class="font-bold">Total: <?= number_format($totalPrice) ?> HUF</p>
        </div>
      </div>
      <a href="index.php" class="btn btn-secondary">Back to Home</a>

    <?php else: ?>
      <div class="alert alert-info">No booking data received.</div>
      <a href="index.php" class="btn btn-secondary">Back to Home</a>
    <?php endif; ?>
  </main>
</body>
</html>
