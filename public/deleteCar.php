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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adminProfile.php");
    exit();
}

$data = [
    'car_id' => $_POST['car_id'] ?? null
];

if (!$data['car_id']) {
    header("Location: adminProfile.php");
    exit();
}

require_once __DIR__ . '/../includes/storage.php';

try {
    $carStorage = new Storage(new JsonIO(__DIR__ . '/../data/cars.json'));
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
} catch (Exception $e) {
    die("Error initializing storages: " . $e->getMessage());
}

$carStorage->delete($data['car_id']);

$allBookings = $bookingStorage->findAll();
foreach ($allBookings as $bId => $bk) {
    if (($bk['car_id'] ?? '') === $data['car_id']) {
        $bookingStorage->delete($bId);
    }
}

header("Location: adminProfile.php?deleted=1");
exit();
