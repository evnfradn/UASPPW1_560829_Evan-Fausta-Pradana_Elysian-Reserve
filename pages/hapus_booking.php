<?php

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$bookingId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$userId    = $_SESSION['user']['id'];

if ($bookingId <= 0) {
    header('Location: ' . BASE_URL . 'pages/my_bookings.php');
    exit;
}


if (isAdmin()) {
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
    $stmt->bind_param('i', $bookingId);
} else {
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $bookingId, $userId);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['flash_success'] = 'Booking has been successfully cancelled.';
} else {
    $_SESSION['flash_error'] = 'Booking not found or cannot be cancelled.';
}
$stmt->close();

header('Location: ' . BASE_URL . 'pages/my_bookings.php');
exit;
