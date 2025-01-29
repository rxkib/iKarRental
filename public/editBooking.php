<?php
session_start();

if (
    !isset($_SESSION['user']) ||
    empty($_SESSION['user']['is_admin']) ||
    $_SESSION['user']['is_admin'] !== true
) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../includes/storage.php';

try {
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
    $carStorage     = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
    $userStorage    = new Storage(new JsonIO(__DIR__ . '/../data/users.json')); // if needed
} catch (Exception $e) {
    die("Error initializing storages: " . $e->getMessage());
}

$bookingId = $_GET['id'] ?? null;
if (!$bookingId) {
    header("Location: adminProfile.php");
    exit();
}

$booking = $bookingStorage->findById($bookingId);
if (!$booking) {
    header("Location: adminProfile.php?error=BookingNotFound");
    exit();
}

$input = [
    'car_id'     => $booking['car_id']     ?? '',
    'user_email' => $booking['user_email'] ?? '',
    'start_date' => $booking['start_date'] ?? '',
    'end_date'   => $booking['end_date']   ?? ''
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['car_id']     = trim($_POST['car_id']     ?? '');
    $input['user_email'] = trim($_POST['user_email'] ?? '');
    $input['start_date'] = trim($_POST['start_date'] ?? '');
    $input['end_date']   = trim($_POST['end_date']   ?? '');

    if ($input['car_id'] === '') {
        $errors[] = "Car ID is required.";
    } else {
        $carCheck = $carStorage->findById($input['car_id']);
        if (!$carCheck) {
            $errors[] = "Car not found with that ID.";
        }
    }

    if ($input['user_email'] === '') {
        $errors[] = "User email is required.";
    } else {
        // $checkUser = $userStorage->findOne(['email' => $input['user_email']]);
        // if (!$checkUser) {
        // $errors[] = "No user found with that email.";
    }

    if ($input['start_date'] === '' || $input['end_date'] === '') {
        $errors[] = "Both start and end date are required.";
    } else {
        $startTs = strtotime($input['start_date']);
        $endTs   = strtotime($input['end_date']);
        if (!$startTs || !$endTs) {
            $errors[] = "Invalid date format.";
        } elseif ($startTs > $endTs) {
            $errors[] = "Start date cannot be after end date.";
        }
    }

    if (count($errors) === 0) {
        $updatedBooking = [
            'car_id'     => $input['car_id'],
            'user_email' => $input['user_email'],
            'start_date' => $input['start_date'],
            'end_date'   => $input['end_date']
        ];

        $bookingStorage->update($bookingId, $updatedBooking);

        header("Location: adminProfile.php?booking_edited=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">Edit Booking (ID: <?= htmlspecialchars($bookingId) ?>)</h1>
    <div class="space-x-2">
      <a href="adminProfile.php" class="btn btn-secondary">Admin Panel</a>
      <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
    </div>
  </header>

  <main class="container mx-auto bg-base-100 p-6 rounded shadow-lg">
    <h2 class="text-xl font-bold mb-4">Update Booking</h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error shadow-lg mb-4">
        <ul class="list-disc ml-4">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="editBooking.php?id=<?= urlencode($bookingId) ?>" method="POST" novalidate>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-control">
          <label for="car_id" class="label">
            <span class="label-text">Car ID</span>
          </label>
          <input
            type="text"
            id="car_id"
            name="car_id"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['car_id']) ?>"
          />
        </div>
        <div class="form-control">
          <label for="user_email" class="label">
            <span class="label-text">User Email</span>
          </label>
          <input
            type="email"
            id="user_email"
            name="user_email"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['user_email']) ?>"
          />
        </div>
        <div class="form-control">
          <label for="start_date" class="label">
            <span class="label-text">Start Date</span>
          </label>
          <input
            type="date"
            id="start_date"
            name="start_date"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['start_date']) ?>"
          />
        </div>
        <div class="form-control">
          <label for="end_date" class="label">
            <span class="label-text">End Date</span>
          </label>
          <input
            type="date"
            id="end_date"
            name="end_date"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['end_date']) ?>"
          />
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-4">Save Changes</button>
    </form>
  </main>
</body>
</html>
