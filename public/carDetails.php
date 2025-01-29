<?php
session_start();
$loggedIn = isset($_SESSION['user']);

require_once __DIR__ . '/../includes/storage.php';

try {
    $carStorage = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
} catch (Exception $e) {
    die("Error initializing car storage: " . $e->getMessage());
}

$data = [
    'car_id' => $_GET['id'] ?? null
];

$car = null;
if ($data['car_id'] !== null) {
    $car = $carStorage->findById($data['car_id']);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Car Details</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <!-- Header -->
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">iKarRental</h1>
    <div class="space-x-2">
      <?php if (!$loggedIn): ?>
        <a href="login.php" class="btn btn-primary">Login</a>
        <a href="register.php" class="btn btn-secondary">Register</a>
      <?php else: ?>
        <a href="profile.php" class="btn btn-primary">Profile</a>
        <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="container mx-auto">
    <?php if (!$car): ?>
      <div class="alert alert-error shadow-lg mb-4">
        <span>Car not found or invalid ID.</span>
      </div>
    <?php else: ?>
      <div class="card card-bordered bg-base-100 shadow-xl">
        <figure>
          <img src="<?= htmlspecialchars($car['image']) ?>" alt="Car Image" />
        </figure>
        <div class="card-body">
          <h2 class="card-title text-base-content">
            <?= htmlspecialchars($car['brand']) ?>
            <?= htmlspecialchars($car['model']) ?>
            (<?= htmlspecialchars($car['year']) ?>)
          </h2>
          <p>Fuel: <?= htmlspecialchars($car['fuel_type']) ?></p>
          <p>Transmission: <?= htmlspecialchars($car['transmission']) ?></p>
          <p>Passengers: <?= htmlspecialchars($car['passengers']) ?></p>
          <p>Price: <?= number_format($car['daily_price_huf']) ?> HUF / day</p>

          <?php if (!$loggedIn): ?>
            <div class="alert alert-warning mt-4">
              <span>You must log in to book this car.</span>
            </div>
          <?php else: ?>
            <form action="bookCar.php" method="POST" class="bg-base-200 p-4 rounded-lg mt-4" novalidate>
              <input type="hidden" name="car_id" value="<?= htmlspecialchars($data['car_id']) ?>" />
              <div class="form-control mb-2">
                <label for="start_date" class="label">
                  <span class="label-text">Start Date</span>
                </label>
                <input type="date" name="start_date" id="start_date" class="input input-bordered" />
              </div>
              <div class="form-control mb-2">
                <label for="end_date" class="label">
                  <span class="label-text">End Date</span>
                </label>
                <input type="date" name="end_date" id="end_date" class="input input-bordered" />
              </div>
              <button type="submit" class="btn btn-primary mt-3">Book This Car</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <footer class="mt-10 text-center">
    <p class="text-sm">&copy; <?= date("Y") ?> iKarRental by Rakib</p>
  </footer>
</body>
</html>
