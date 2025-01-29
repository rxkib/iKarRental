<?php
session_start();

$input = $_POST;

$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_valid = validate($input, $errors, $data);

    if ($is_valid) {
        $jsonPath = __DIR__ . '/../data/users.json';

        $users = json_decode(file_get_contents($jsonPath), true) ?? [];

        $newUser = [
            'id'        => uniqid(),
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'is_admin'  => false
        ];

        $users[] = $newUser;
        file_put_contents($jsonPath, json_encode($users, JSON_PRETTY_PRINT));

        $_SESSION['user'] = [
            'id'        => $newUser['id'],
            'full_name' => $newUser['full_name'],
            'email'     => $newUser['email'],
            'is_admin'  => false
        ];

        header("Location: profile.php");
        exit();
    }
}

function validate($input, &$errors, &$data) {
    if (!isset($input['full_name']) || trim($input['full_name']) === '') {
        $errors['full_name'] = "Full Name is required.";
    } else {
        $data['full_name'] = trim($input['full_name']);
    }

    // Email
    if (!isset($input['email']) || trim($input['email']) === '') {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    } else {
        $data['email'] = trim($input['email']);
    }

    // Password
    if (!isset($input['password']) || trim($input['password']) === '') {
        $errors['password'] = "Password is required.";
    } else {
        $pwd = $input['password'];

        // Minimum length
        if (strlen($pwd) < 8) {
            $errors['password'] = "Password must be at least 8 characters.";
        }
        // Must contain uppercase
        elseif (!preg_match('/[A-Z]/', $pwd)) {
            $errors['password'] = "Password must contain at least one uppercase letter.";
        }
        // Must contain digit
        elseif (!preg_match('/\d/', $pwd)) {
            $errors['password'] = "Password must contain at least one digit.";
        }

        // Check confirmed password
        if (!isset($input['confirm_password']) || $input['confirm_password'] !== $pwd) {
            $errors['confirm_password'] = "Passwords do not match.";
        }

        // If password has no errors, we store it
        if (!isset($errors['password']) && !isset($errors['confirm_password'])) {
            $data['password'] = $pwd;
        }
    }

    return count($errors) === 0;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - iKarRental</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 h-screen flex flex-col">
  <header class="p-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">iKarRental</h1>
    <div class="space-x-2">
      <?php if (!isset($_SESSION['user'])): ?>
        <a href="index.php" class="btn btn-secondary">Home</a>
        <a href="login.php" class="btn btn-primary">Login</a>
      <?php else: ?>
        <a href="profile.php" class="btn btn-primary">Profile</a>
        <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Main content -->
  <main class="flex-grow flex items-center justify-center">
    <div class="card bg-base-100 shadow-xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold mb-4 text-center">Register</h1>

      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)): ?>
        <div class="alert alert-error mb-4">
          <ul class="list-disc ml-4">
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form action="register.php" method="POST" novalidate>
        <div class="form-control mb-4">
          <label for="full_name" class="label">
            <span class="label-text">Full Name</span>
          </label>
          <input
            type="text"
            name="full_name"
            id="full_name"
            class="input input-bordered w-full"
            placeholder="Enter your full name"
            value="<?= htmlspecialchars($input['full_name'] ?? '') ?>"
          />
        </div>

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
            value="<?= htmlspecialchars($input['email'] ?? '') ?>"
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
            placeholder="Create a password"
          />
          <small class="text-xs text-gray-400 mt-1">
            * Must be at least 8 chars, contains an uppercase and a digit
          </small>
        </div>

        <div class="form-control mb-4">
          <label for="confirm_password" class="label">
            <span class="label-text">Confirm Password</span>
          </label>
          <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            class="input input-bordered w-full"
            placeholder="Confirm your password"
          />
        </div>

        <button type="submit" class="btn btn-primary w-full">Register</button>
      </form>

      <div class="mt-4 text-center">
        <p class="text-sm">
          Already have an account?
          <a href="login.php" class="text-primary">Login here</a>.
        </p>
      </div>
    </div>
  </main>
</body>
</html>
