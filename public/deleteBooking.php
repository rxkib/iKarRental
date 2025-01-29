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
    'booking_id' => $_POST['booking_id'] ?? null
];

if (!$data['booking_id']) {
    header("Location: adminProfile.php");
    exit();
}

require_once __DIR__ . '/../includes/storage.php';

try {
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
} catch (Exception $e) {
    die("Error initializing booking storage: " . $e->getMessage());
}

$bookingStorage->delete($data['booking_id']);

header("Location: adminProfile.php?booking_deleted=1");
exit();
