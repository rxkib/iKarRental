<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userSession = $_SESSION['user'];
$userId = $userSession['id'];

require_once __DIR__ . '/../includes/storage.php';

try {
    $userStorage = new Storage(new JsonIO(__DIR__ . '/../data/users.json'));
} catch (Exception $e) {
    die("Error initializing user storage: " . $e->getMessage());
}

$fullUserRecord = $userStorage->findById($userId);
if (!$fullUserRecord) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'old_password' => trim($_POST['old_password']  ?? ''),
        'new_password' => trim($_POST['new_password']  ?? ''),
        'confirm_new'  => trim($_POST['confirm_new']   ?? '')
    ];

    if ($data['old_password'] === '' || $data['new_password'] === '' || $data['confirm_new'] === '') {
        $errors[] = "All fields are required.";
    } else {
        if ($data['old_password'] !== $fullUserRecord['password']) {
            $errors[] = "Old password is incorrect.";
        }

        if ($data['new_password'] !== $data['confirm_new']) {
            $errors[] = "New passwords do not match.";
        }
    }

    if (count($errors) === 0) {
        $fullUserRecord['password'] = $data['new_password'];

        $userStorage->update($userId, $fullUserRecord);

        $_SESSION['user']['password'] = $data['new_password'];

        $success = "Password changed successfully!";
    }
}
?>


<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Change Password</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 p-5">
  <header class="container mx-auto mb-5 flex justify-between items-center">
    <h1 class="text-3xl font-bold">Change Password</h1>
    <div class="space-x-2">
      <a href="profile.php" class="btn btn-secondary">Profile</a>
      <a href="logout.php" class="btn btn-error btn-outline">Logout</a>
    </div>
  </header>

  <main class="container mx-auto max-w-md">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error mb-4">
        <ul class="list-disc ml-4">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
      <div class="alert alert-success mb-4">
        <span><?= htmlspecialchars($success) ?></span>
      </div>
    <?php endif; ?>

    <form action="changePassword.php" method="POST" class="bg-base-100 p-4 shadow-lg rounded" novalidate>
      <div class="form-control mb-4">
        <label for="old_password" class="label"><span class="label-text">Old Password</span></label>
        <input
          type="password"
          name="old_password"
          id="old_password"
          class="input input-bordered w-full"
          required
        />
      </div>
      <div class="form-control mb-4">
        <label for="new_password" class="label"><span class="label-text">New Password</span></label>
        <input
          type="password"
          name="new_password"
          id="new_password"
          class="input input-bordered w-full"
          required
        />
      </div>
      <div class="form-control mb-4">
        <label for="confirm_new" class="label"><span class="label-text">Confirm New Password</span></label>
        <input
          type="password"
          name="confirm_new"
          id="confirm_new"
          class="input input-bordered w-full"
          required
        />
      </div>
      <button type="submit" class="btn btn-primary w-full">Change Password</button>
    </form>
  </main>
</body>
</html>
