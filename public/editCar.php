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
    $carStorage = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
} catch (Exception $e) {
    die("Error initializing car storage: " . $e->getMessage());
}

$carId = $_GET['id'] ?? null;
if (!$carId) {
    header("Location: adminProfile.php");
    exit();
}

$car = $carStorage->findById($carId);
if (!$car) {
    header("Location: adminProfile.php?error=CarNotFound");
    exit();
}

$errors = [];

$input = [
    'brand'           => $car['brand']          ?? '',
    'model'           => $car['model']          ?? '',
    'year'            => $car['year']           ?? '',
    'transmission'    => $car['transmission']   ?? '',
    'fuel_type'       => $car['fuel_type']      ?? '',
    'passengers'      => $car['passengers']     ?? '',
    'daily_price_huf' => $car['daily_price_huf']?? '',
    'image'           => $car['image']          ?? ''
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'edit_car'
) {
    $input['brand']           = trim($_POST['brand']           ?? '');
    $input['model']           = trim($_POST['model']           ?? '');
    $input['year']            = trim($_POST['year']            ?? '');
    $input['transmission']    = trim($_POST['transmission']    ?? '');
    $input['fuel_type']       = trim($_POST['fuel_type']       ?? '');
    $input['passengers']      = trim($_POST['passengers']      ?? '');
    $input['daily_price_huf'] = trim($_POST['daily_price_huf'] ?? '');
    $input['image']           = trim($_POST['image']           ?? '');

    if ($input['brand'] === '') {
        $errors[] = "Brand is required.";
    }
    if ($input['model'] === '') {
        $errors[] = "Model is required.";
    }
    if (!ctype_digit($input['year']) || (int)$input['year'] < 1900) {
        $errors[] = "Year must be a valid integer >= 1900.";
    }
    if ($input['transmission'] !== 'Manual' && $input['transmission'] !== 'Automatic') {
        $errors[] = "Transmission must be 'Manual' or 'Automatic'.";
    }
    if ($input['fuel_type'] === '') {
        $errors[] = "Fuel type is required.";
    }
    if (!ctype_digit($input['passengers']) || (int)$input['passengers'] < 1) {
        $errors[] = "Passengers must be an integer >= 1.";
    }
    if (!ctype_digit($input['daily_price_huf']) || (int)$input['daily_price_huf'] < 1) {
        $errors[] = "Daily price must be an integer >= 1.";
    }
    if ($input['image'] === '') {
        $errors[] = "Image URL is required.";
    }

    if (count($errors) === 0) {
        $updatedCar = [
            'brand'           => $input['brand'],
            'model'           => $input['model'],
            'year'            => (int)$input['year'],
            'transmission'    => $input['transmission'],
            'fuel_type'       => $input['fuel_type'],
            'passengers'      => (int)$input['passengers'],
            'daily_price_huf' => (int)$input['daily_price_huf'],
            'image'           => $input['image']
        ];

        $carStorage->update($carId, $updatedCar);

        header("Location: adminProfile.php?edited=1");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Car</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">Edit Car</h1>
    <div class="space-x-2">
      <a href="adminProfile.php" class="btn btn-secondary">Back to Admin Panel</a>
      <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
    </div>
  </header>

  <main class="container mx-auto bg-base-100 p-6 rounded shadow-lg">
    <h2 class="text-xl font-bold mb-4">
      Edit Car (ID: <?= htmlspecialchars($carId) ?>)
    </h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error mb-4">
        <ul class="list-disc ml-4">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="editCar.php?id=<?= urlencode($carId) ?>" method="POST" novalidate>
      <input type="hidden" name="action" value="edit_car" />
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Brand -->
        <div class="form-control">
          <label for="brand" class="label"><span class="label-text">Brand</span></label>
          <input
            type="text"
            id="brand"
            name="brand"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['brand']) ?>"
          />
        </div>
        <!-- Model -->
        <div class="form-control">
          <label for="model" class="label"><span class="label-text">Model</span></label>
          <input
            type="text"
            id="model"
            name="model"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['model']) ?>"
          />
        </div>
        <!-- Year -->
        <div class="form-control">
          <label for="year" class="label"><span class="label-text">Year</span></label>
          <input
            type="number"
            id="year"
            name="year"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['year']) ?>"
          />
        </div>
        <!-- Transmission -->
        <div class="form-control">
          <label for="transmission" class="label"><span class="label-text">Transmission</span></label>
          <select id="transmission" name="transmission" class="select select-bordered">
            <option value="Manual"    <?= $input['transmission'] === 'Manual'    ? 'selected' : '' ?>>Manual</option>
            <option value="Automatic" <?= $input['transmission'] === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
          </select>
        </div>
        <!-- Fuel Type -->
        <div class="form-control">
          <label for="fuel_type" class="label"><span class="label-text">Fuel Type</span></label>
          <select id="fuel_type" name="fuel_type" class="select select-bordered">
            <option value="">--Select--</option>
            <option value="Petrol"   <?= $input['fuel_type'] === 'Petrol'   ? 'selected' : '' ?>>Petrol</option>
            <option value="Diesel"   <?= $input['fuel_type'] === 'Diesel'   ? 'selected' : '' ?>>Diesel</option>
            <option value="Electric" <?= $input['fuel_type'] === 'Electric' ? 'selected' : '' ?>>Electric</option>
          </select>
        </div>
        <!-- Passengers -->
        <div class="form-control">
          <label for="passengers" class="label"><span class="label-text">Passengers</span></label>
          <input
            type="number"
            id="passengers"
            name="passengers"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['passengers']) ?>"
          />
        </div>
        <!-- Daily Price -->
        <div class="form-control">
          <label for="daily_price_huf" class="label">
            <span class="label-text">Daily Price (HUF)</span>
          </label>
          <input
            type="number"
            id="daily_price_huf"
            name="daily_price_huf"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['daily_price_huf']) ?>"
          />
        </div>
        <!-- Image URL -->
        <div class="form-control">
          <label for="image" class="label"><span class="label-text">Image URL</span></label>
          <input
            type="text"
            id="image"
            name="image"
            class="input input-bordered"
            value="<?= htmlspecialchars($input['image']) ?>"
          />
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-4">Save Changes</button>
    </form>
  </main>
</body>
</html>
