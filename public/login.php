<?php
session_start();
$loggedIn = isset($_SESSION['user']);

$errors = [];

require_once __DIR__ . '/../includes/storage.php';

try {
    $userStorage = new Storage(new JsonIO(__DIR__ . '/../data/users.json'));
} catch (Exception $e) {
    die("Error initializing user storage: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email'    => trim($_POST['email']    ?? ''),
        'password' => trim($_POST['password'] ?? '')
    ];

    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }
    if ($data['password'] === '') {
        $errors[] = "Please enter your password.";
    }

    if (count($errors) === 0) {
        $foundUser = $userStorage->findOne(['email' => $data['email']]);
        if (!$foundUser) {
            $errors[] = "No account found with that email.";
        } else {
            if ($data['password'] !== ($foundUser['password'] ?? '')) {
                $errors[] = "Incorrect password.";
            } else {
                $_SESSION['user'] = [
                    'id'        => $foundUser['id'],
                    'full_name' => $foundUser['full_name'],
                    'email'     => $foundUser['email'],
                    'is_admin'  => $foundUser['is_admin'] ?? false
                ];

                header("Location: index.php");
                exit();
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 h-screen flex flex-col">
  <header class="p-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">iKarRental</h1>
    <div class="space-x-2">
      <?php if (!$loggedIn): ?>
        <a href="index.php" class="btn btn-secondary">Home</a>
      <?php else: ?>
        <a href="profile.php" class="btn btn-primary">Profile</a>
        <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="flex-grow flex items-center justify-center">
    <div class="card bg-base-100 shadow-xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold mb-4 text-center">Login</h1>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error mb-4">
          <ul class="list-disc ml-4">
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST" novalidate>
        <div class="form-control mb-4">
          <label for="email" class="label">
            <span class="label-text">Email</span>
          </label>
          <input
            type="email"
            name="email"
            id="email"
            class="input input-bordered w-full"
            placeholder="Enter your email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          />
        </div>

        <div class="form-control mb-4">
          <label for="password" class="label">
            <span class="label-text">Password</span>
          </label>
          <input
            type="password"
            name="password"
            id="password"
            class="input input-bordered w-full"
            placeholder="Enter your password"
          />
        </div>

        <button type="submit" class="btn btn-primary w-full">Login</button>
      </form>

      <div class="mt-4 text-center text-sm">
        Don't have an account?
        <a href="register.php" class="text-primary">Register here</a>.
      </div>
    </div>
  </main>
</body>
</html>
