<?php
session_start();

$loggedIn = isset($_SESSION['user']);
$isAdmin = ($loggedIn && !empty($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true);

require_once __DIR__ . '/../includes/storage.php';

try {
    $carStorage = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
} catch (Exception $e) {
    die("Error initializing storages: " . $e->getMessage());
}

$brandFilter        = trim($_GET['brand'] ?? '');
$transmissionFilter = trim($_GET['transmission'] ?? '');
$minPassengers      = trim($_GET['min_passengers'] ?? '');
$priceMin           = trim($_GET['price_min'] ?? '');
$priceMax           = trim($_GET['price_max'] ?? '');
$startDateFilter    = trim($_GET['start_date'] ?? '');
$endDateFilter      = trim($_GET['end_date'] ?? '');

$startTs = $startDateFilter ? strtotime($startDateFilter) : 0;
$endTs   = $endDateFilter   ? strtotime($endDateFilter)   : 0;
$filterByDate = ($startTs && $endTs && $startTs <= $endTs);

$carsAssoc   = $carStorage->findAll();  
$allBookings = $bookingStorage->findAll(); 


$filteredCars = [];
foreach ($carsAssoc as $carId => $car) {
    if ($brandFilter !== '') {
        if (
            stripos($car['brand'], $brandFilter) === false &&
            stripos($car['model'], $brandFilter) === false
        ) {
            continue;
        }
    }

    if ($transmissionFilter !== '' && $car['transmission'] !== $transmissionFilter) {
        continue;
    }

    if ($minPassengers !== '' && is_numeric($minPassengers)) {
        if ($car['passengers'] < (int)$minPassengers) {
            continue;
        }
    }

    if ($priceMin !== '' && is_numeric($priceMin)) {
        if ($car['daily_price_huf'] < (int)$priceMin) {
            continue;
        }
    }

    if ($priceMax !== '' && is_numeric($priceMax)) {
        if ($car['daily_price_huf'] > (int)$priceMax) {
            continue;
        }
    }

    if ($filterByDate) {
        $carIsBooked = false;
        foreach ($allBookings as $bId => $bk) {
            if (($bk['car_id'] ?? '') !== $carId) {
                continue;
            }
            if (($bk['status'] ?? 'active') === 'canceled') {
                continue;
            }

            $bkStart = strtotime($bk['start_date'] ?? '');
            $bkEnd   = strtotime($bk['end_date'] ?? '');
            if ($startTs <= $bkEnd && $endTs >= $bkStart) {
                $carIsBooked = true;
                break;
            }
        }
        if ($carIsBooked) {
            continue;
        }
    }

    $filteredCars[$carId] = $car;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Home - iKarRental</title>
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
        <?php if ($isAdmin): ?>
          <a href="adminProfile.php" class="btn btn-warning">Admin Panel</a>
        <?php else: ?>
          <a href="profile.php" class="btn btn-primary">Profile</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="container mx-auto">
    <form method="GET" class="bg-base-100 p-4 rounded-lg shadow-lg mb-5" novalidate>
      <div class="flex flex-wrap items-end gap-4">
        <!-- BRAND/MODEL -->
        <div class="form-control flex-1 min-w-[160px]">
          <label for="brand" class="label"><span class="label-text">Brand / Model</span></label>
          <input
            type="text"
            name="brand"
            id="brand"
            class="input input-bordered w-full"
            placeholder="e.g. Honda"
            value="<?= htmlspecialchars($brandFilter) ?>"
          />
        </div>

        <!-- TRANSMISSION -->
        <div class="form-control flex-1 min-w-[160px]">
          <label for="transmission" class="label"><span class="label-text">Transmission</span></label>
          <select name="transmission" id="transmission" class="select select-bordered w-full">
            <option value="">Any</option>
            <option value="Manual"    <?= $transmissionFilter === 'Manual'    ? 'selected' : '' ?>>Manual</option>
            <option value="Automatic" <?= $transmissionFilter === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
          </select>
        </div>

        <!-- MIN PASSENGERS -->
        <div class="form-control flex-1 min-w-[120px]">
          <label for="min_passengers" class="label">
            <span class="label-text">Min Passengers</span>
          </label>
          <input
            type="number"
            name="min_passengers"
            id="min_passengers"
            class="input input-bordered w-full"
            placeholder="e.g. 5"
            value="<?= htmlspecialchars($minPassengers) ?>"
          />
        </div>

        <!-- PRICE MIN -->
        <div class="form-control flex-1 min-w-[100px]">
          <label for="price_min" class="label"><span class="label-text">Min Price</span></label>
          <input
            type="number"
            name="price_min"
            id="price_min"
            class="input input-bordered w-full"
            placeholder="0"
            value="<?= htmlspecialchars($priceMin) ?>"
          />
        </div>

        <!-- PRICE MAX -->
        <div class="form-control flex-1 min-w-[100px]">
          <label for="price_max" class="label"><span class="label-text">Max Price</span></label>
          <input
            type="number"
            name="price_max"
            id="price_max"
            class="input input-bordered w-full"
            placeholder="50000"
            value="<?= htmlspecialchars($priceMax) ?>"
          />
        </div>

        <!-- START DATE -->
        <div class="form-control flex-1 min-w-[120px]">
          <label for="start_date" class="label"><span class="label-text">Start Date</span></label>
          <input
            type="date"
            name="start_date"
            id="start_date"
            class="input input-bordered w-full"
            value="<?= htmlspecialchars($startDateFilter) ?>"
          />
        </div>

        <!-- END DATE -->
        <div class="form-control flex-1 min-w-[120px]">
          <label for="end_date" class="label"><span class="label-text">End Date</span></label>
          <input
            type="date"
            name="end_date"
            id="end_date"
            class="input input-bordered w-full"
            value="<?= htmlspecialchars($endDateFilter) ?>"
          />
        </div>

        <!-- APPLY BUTTON -->
        <div class="form-control flex-none">
          <button type="submit" class="btn btn-primary mt-6">
            Apply Filters
          </button>
        </div>
      </div>
    </form>

    <!-- CAR LIST -->
    <div id="car-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (count($filteredCars) === 0): ?>
        <div class="col-span-full text-center">
          <p>No cars match your filters.</p>
        </div>
      <?php else: ?>
        <?php foreach ($filteredCars as $thisId => $thisCar): ?>
          <div class="card bg-base-100 shadow-xl">
            <figure>
              <img
                src="<?= htmlspecialchars($thisCar['image']) ?>"
                alt="Car Image"
                class="object-cover"
              />
            </figure>
            <div class="card-body">
              <h2 class="card-title text-base-content">
                <?= htmlspecialchars($thisCar['brand']) ?>
                <?= htmlspecialchars($thisCar['model']) ?>
                (<?= htmlspecialchars($thisCar['year']) ?>)
              </h2>
              <div class="badge badge-outline">
                <?= htmlspecialchars($thisCar['transmission']) ?>
              </div>
              <div class="badge badge-outline">
                <?= htmlspecialchars($thisCar['passengers']) ?> Passengers
              </div>
              <p class="text-sm">
                Fuel: <?= htmlspecialchars($thisCar['fuel_type']) ?>
              </p>
              <p class="text-sm font-semibold">
                Price: <?= number_format($thisCar['daily_price_huf']) ?> HUF / day
              </p>
              <div class="card-actions mt-4">
                <a href="carDetails.php?id=<?= urlencode($thisId) ?>" class="btn btn-primary">
                  Details
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <footer class="mt-10 text-center">
    <p class="text-sm">&copy; <?= date("Y") ?> iKarRental by Rakib</p>
  </footer>
</body>
</html>
