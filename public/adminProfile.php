<?php
session_start();

//Checking for admin
if (
    !isset($_SESSION['user']) ||
    empty($_SESSION['user']['is_admin']) ||
    $_SESSION['user']['is_admin'] !== true
) {
    header("Location: index.php");
    exit();
}

$loggedIn = true;
$isAdmin = true;

require_once __DIR__ . '/../includes/storage.php';

try {
    $carStorage = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
} catch (Exception $e) {
    die("Error initializing storages: " . $e->getMessage());
}

$errors = [];

// Adding new cars
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_car') {
    $data = [
        'brand'        => trim($_POST['brand']        ?? ''),
        'model'        => trim($_POST['model']        ?? ''),
        'year'         => trim($_POST['year']         ?? ''),
        'transmission' => trim($_POST['transmission'] ?? ''),
        'fuel_type'    => trim($_POST['fuel_type']    ?? ''),
        'passengers'   => trim($_POST['passengers']   ?? ''),
        'daily_price'  => trim($_POST['daily_price_huf'] ?? ''),
        'image'        => trim($_POST['image']        ?? '')
    ];

    //Data Validation
    if ($data['brand'] === '') {
        $errors['brand'] = "Brand is required.";
    }

    if ($data['model'] === '') {
        $errors['model'] = "Model is required.";
    }

    if (!filter_var($data['year'], FILTER_VALIDATE_INT)) {
        $errors['year'] = "Year must be an integer.";
    } else {
        $yearInt = (int) $data['year'];
        if ($yearInt < 1900) {
            $errors['year'] = "Year must be >= 1900.";
        }
    }

    if ($data['transmission'] !== 'Manual' && $data['transmission'] !== 'Automatic') {
        $errors['transmission'] = "Transmission must be Manual or Automatic.";
    }

    if ($data['fuel_type'] === '') {
        $errors['fuel_type'] = "Fuel type is required.";
    }

    if (!filter_var($data['passengers'], FILTER_VALIDATE_INT)) {
        $errors['passengers'] = "Passengers must be an integer.";
    } else {
        $passInt = (int) $data['passengers'];
        if ($passInt < 1) {
            $errors['passengers'] = "Passengers must be >= 1.";
        }
    }

    if (!filter_var($data['daily_price'], FILTER_VALIDATE_INT)) {
        $errors['daily_price'] = "Daily price must be an integer.";
    } else {
        $priceInt = (int) $data['daily_price'];
        if ($priceInt < 1) {
            $errors['daily_price'] = "Daily price must be >= 1.";
        }
    }

    if ($data['image'] === '') {
        $errors['image'] = "Image URL is required.";
    }

    if (count($errors) === 0) {
        $newCar = [
            'brand'           => $data['brand'],
            'model'           => $data['model'],
            'year'            => (int)$data['year'],
            'transmission'    => $data['transmission'],
            'fuel_type'       => $data['fuel_type'],
            'passengers'      => (int)$data['passengers'],
            'daily_price_huf' => (int)$data['daily_price'],
            'image'           => $data['image']
        ];
        $carStorage->add($newCar);
        header("Location: adminProfile.php?added=1");
        exit();
    }
}


// Load all data
$cars = $carStorage->findAll();      
$bookings = $bookingStorage->findAll(); 
?>


<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <!-- Header -->
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">Admin Panel</h1>
    <div class="space-x-2">
      <a href="./index.php" class="btn btn-secondary">Home</a>
      <a href="./logout.php" class="btn btn-error btn-outline">Logout</a>
    </div>
  </header>

  <main class="container mx-auto">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error shadow-lg mb-4">
        <ul class="list-disc ml-4">
          <?php foreach ($errors as $field => $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
      <div class="alert alert-success shadow-lg mb-4">
        <span>New car added successfully!</span>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['edited']) && $_GET['edited'] == 1): ?>
      <div class="alert alert-success shadow-lg mb-4">
        <span>Car updated successfully!</span>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
      <div class="alert alert-success shadow-lg mb-4">
        <span>Car deleted successfully!</span>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['booking_deleted']) && $_GET['booking_deleted'] == 1): ?>
      <div class="alert alert-success shadow-lg mb-4">
        <span>Booking deleted successfully!</span>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['booking_edited']) && $_GET['booking_edited'] == 1): ?>
      <div class="alert alert-success shadow-lg mb-4">
        <span>Booking updated successfully!</span>
      </div>
    <?php endif; ?>

    <!-- Add New Car -->
    <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box mb-8">
      <input type="checkbox" />
      <div class="collapse-title text-lg font-bold">
        Add New Car
      </div>
      <div class="collapse-content">
        <form action="adminProfile.php" method="POST" class="p-4" novalidate>
          <input type="hidden" name="action" value="add_car"/>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Brand -->
            <div class="form-control">
              <label for="brand" class="label"><span class="label-text">Brand</span></label>
              <input
                type="text"
                id="brand"
                name="brand"
                class="input input-bordered"
                value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>"
              />
              <?php if (isset($errors['brand'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['brand']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Model -->
            <div class="form-control">
              <label for="model" class="label"><span class="label-text">Model</span></label>
              <input
                type="text"
                id="model"
                name="model"
                class="input input-bordered"
                value="<?= htmlspecialchars($_POST['model'] ?? '') ?>"
              />
              <?php if (isset($errors['model'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['model']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Year -->
            <div class="form-control">
              <label for="year" class="label"><span class="label-text">Year</span></label>
              <input
                type="number"
                id="year"
                name="year"
                class="input input-bordered"
                value="<?= htmlspecialchars($_POST['year'] ?? '') ?>"
              />
              <?php if (isset($errors['year'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['year']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Transmission -->
            <div class="form-control">
              <label for="transmission" class="label"><span class="label-text">Transmission</span></label>
              <select id="transmission" name="transmission" class="select select-bordered">
                <option value="Manual"    <?= (($_POST['transmission'] ?? '') === 'Manual') ? 'selected' : '' ?>>Manual</option>
                <option value="Automatic" <?= (($_POST['transmission'] ?? '') === 'Automatic') ? 'selected' : '' ?>>Automatic</option>
              </select>
              <?php if (isset($errors['transmission'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['transmission']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Fuel Type -->
            <div class="form-control">
              <label for="fuel_type" class="label"><span class="label-text">Fuel Type</span></label>
              <select id="fuel_type" name="fuel_type" class="select select-bordered">
                <option value="">--Select--</option>
                <option value="Petrol"   <?= (($_POST['fuel_type'] ?? '') === 'Petrol')   ? 'selected' : '' ?>>Petrol</option>
                <option value="Diesel"   <?= (($_POST['fuel_type'] ?? '') === 'Diesel')   ? 'selected' : '' ?>>Diesel</option>
                <option value="Electric" <?= (($_POST['fuel_type'] ?? '') === 'Electric') ? 'selected' : '' ?>>Electric</option>
              </select>
              <?php if (isset($errors['fuel_type'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['fuel_type']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Passengers -->
            <div class="form-control">
              <label for="passengers" class="label"><span class="label-text">Passengers</span></label>
              <input
                type="number"
                id="passengers"
                name="passengers"
                class="input input-bordered"
                value="<?= htmlspecialchars($_POST['passengers'] ?? '') ?>"
              />
              <?php if (isset($errors['passengers'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['passengers']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Daily Price -->
            <div class="form-control">
              <label for="daily_price_huf" class="label"><span class="label-text">Daily Price (HUF)</span></label>
              <input
                type="number"
                id="daily_price_huf"
                name="daily_price_huf"
                class="input input-bordered"
                value="<?= htmlspecialchars($_POST['daily_price_huf'] ?? '') ?>"
              />
              <?php if (isset($errors['daily_price'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['daily_price']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Image -->
            <div class="form-control">
              <label for="image" class="label"><span class="label-text">Image URL</span></label>
              <input
                type="text"
                id="image"
                name="image"
                class="input input-bordered"
                value="<?= htmlspecialchars($_POST['image'] ?? '') ?>"
              />
              <?php if (isset($errors['image'])): ?>
                <p class="text-red-400 mt-1"><?= htmlspecialchars($errors['image']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-4">Add Car</button>
        </form>
      </div>
    </div>

    <!-- All Bookings Section -->
    <div class="bg-base-100 p-4 rounded shadow-lg mb-8">
      <h2 class="text-xl font-bold mb-4 text-center">All Bookings</h2>
      <?php if (count($bookings) === 0): ?>
        <div class="alert alert-info">No bookings found.</div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="table w-full">
            <thead>
              <tr>
                <th>Car</th>
                <th>User Email</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $bId => $bk): ?>
                <?php
                  $carId   = $bk['car_id'];
                  $carName = 'Unknown Car';
                  if (isset($cars[$carId])) {
                      $carObj = $cars[$carId];
                      $carName = $carObj['brand'] . ' ' . $carObj['model'];
                  }
                  // If status not set, we assume it's 'active'
                  $status = $bk['status'] ?? 'active';
                ?>
                <tr class="<?= $status === 'canceled' ? 'opacity-50' : '' ?>">
                  <td><?= htmlspecialchars($carName) ?></td>
                  <td><?= htmlspecialchars($bk['user_email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($bk['start_date'] ?? '') ?></td>
                  <td><?= htmlspecialchars($bk['end_date'] ?? '') ?></td>
                  <td>
                    <?php if ($status === 'canceled'): ?>
                      <span class="text-red-500 font-bold">Canceled</span>
                    <?php else: ?>
                      <span class="text-green-600 font-semibold">Active</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="editBooking.php?id=<?= urlencode($bId) ?>" class="btn btn-sm btn-warning">Modify</a>
                    <form
                      action="deleteBooking.php"
                      method="POST"
                      class="inline"
                      onsubmit="return confirm('Permanently delete this booking?')"
                    >
                      <input type="hidden" name="booking_id" value="<?= htmlspecialchars($bId) ?>"/>
                      <button type="submit" class="btn btn-sm btn-error btn-outline">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Manage Cars Section -->
    <div class="mb-8">
      <h2 class="text-xl font-bold mb-4">Manage Cars</h2>
      <?php if (count($cars) === 0): ?>
        <div class="alert alert-info">No cars found.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($cars as $carId => $car): ?>
            <div class="card bg-base-100 sha dow-xl">
              <figure>
                <img src="<?= htmlspecialchars($car['image']) ?>" alt="Car" class="object-cover"/>
              </figure>
              <div class="card-body">
                <h2 class="card-title">
                  <?= htmlspecialchars($car['brand']) ?>
                  <?= htmlspecialchars($car['model']) ?>
                  (<?= htmlspecialchars($car['year']) ?>)
                </h2>
                <p>Transmission: <?= htmlspecialchars($car['transmission']) ?></p>
                <p>Fuel: <?= htmlspecialchars($car['fuel_type']) ?></p>
                <p>Passengers: <?= htmlspecialchars($car['passengers']) ?></p>
                <p>Price: <?= number_format($car['daily_price_huf']) ?> HUF/day</p>
                <div class="card-actions mt-4">
                  <a href="editCar.php?id=<?= urlencode($carId) ?>" class="btn btn-warning">Edit</a>
                  <form
                    action="deleteCar.php"
                    method="POST"
                    onsubmit="return confirm('Delete this car?')"
                  >
                    <input type="hidden" name="car_id" value="<?= htmlspecialchars($carId) ?>"/>
                    <button type="submit" class="btn btn-error btn-outline">Delete</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <footer class="mt-10 text-center">
    <p class="text-sm">&copy; <?= date("Y") ?> iKarRental by Rakib</p>
  </footer>
</body>
</html>
