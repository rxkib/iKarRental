<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../includes/storage.php';

try {
    $bookingStorage = new Storage(new JsonIO(__DIR__ . '/../data/bookings.json'));
} catch (Exception $e) {
    die("Error initializing booking storage: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit();
}

$errors = [];
$data = [
    'booking_id' => $_POST['booking_id'] ?? null
];

if (!$data['booking_id']) {
    $errors['booking_id'] = "No booking ID provided.";
}

if (count($errors) > 0) {
    header("Location: profile.php?error=InvalidBookingID");
    exit();
}

$booking = $bookingStorage->findById($data['booking_id']);
if (!$booking) {
    header("Location: profile.php?error=NoBookingFound");
    exit();
}

if (($booking['user_email'] ?? '') !== ($_SESSION['user']['email'] ?? '')) {
    header("Location: profile.php?error=Unauthorized");
    exit();
}

$booking['status'] = 'canceled';

$bookingStorage->update($data['booking_id'], $booking);

header("Location: profile.php?booking_canceled=1");
exit();
