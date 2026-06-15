<?php

require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$roomId = (int)($_POST['room_id'] ?? $_GET['id'] ?? 0);

if ($roomId <= 0) {
    header('Location: ' . BASE_URL . 'pages/rooms.php');
    exit;
}


$stmt = $conn->prepare("SELECT nama_kamar, gambar, gallery FROM rooms WHERE id=?");
$stmt->bind_param('i', $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    $_SESSION['flash_error'] = 'Room not found.';
    header('Location: ' . BASE_URL . 'pages/rooms.php');
    exit;
}


$stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
$stmt->bind_param('i', $roomId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    if ($room['gambar'] && !in_array($room['gambar'], ['default_room.jpg','room_suite.jpg','room_penthouse.jpg','room_villa.jpg','room_pavilion.jpg','room_loft.jpg'])) {
        $imgPath = __DIR__ . '/../assets/img/' . $room['gambar'];
        if (file_exists($imgPath)) @unlink($imgPath);
    }

    if (!empty($room['gallery'])) {
        $galleryFiles = array_filter(array_map('trim', explode(',', $room['gallery'])));
        foreach ($galleryFiles as $gfile) {
            $gPath = __DIR__ . '/../assets/img/' . $gfile;
            if (file_exists($gPath)) @unlink($gPath);
        }
    }
    $_SESSION['flash_success'] = "Room \"{$room['nama_kamar']}\" has been successfully deleted.";
} else {
    $_SESSION['flash_error'] = 'Failed to delete room. There may be active bookings associated with it.';
}
$stmt->close();

header('Location: ' . BASE_URL . 'pages/rooms.php');
exit;
