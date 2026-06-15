<?php

define('DB_HOST', 'DB_HOST');
define('DB_USER', 'DB_USER');
define('DB_PASS', 'DB_PASS');
define('DB_NAME', 'DB_NAME');
define('DB_CHARSET', 'utf8mb4');


$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);


$conn->set_charset(DB_CHARSET);


if ($conn->connect_error) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:40px;color:#ba1a1a;background:#ffdad6;border-radius:8px;margin:20px;">
        <strong>Database Connection Failed:</strong> ' . htmlspecialchars($conn->connect_error) . '
        <br><small>Please check your database credentials in includes/config.php</small>
    </div>');
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}


function isAdmin(): bool {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}


function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}


function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}


function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getRoomImageSrc(string $gambar): string {
    $localPath = __DIR__ . '/../assets/img/' . $gambar;
    if ($gambar && file_exists($localPath) && $gambar !== 'default_room.jpg') {
        return BASE_URL . 'assets/img/' . rawurlencode($gambar);
    }

    static $map = [
        'room_suite.jpg'     => 'Room/Celestial-Master-Suite/125467539613275900.jpg',
        'room_penthouse.jpg' => 'Room/Oceanic-Penthouse/14073817578887584.jpg',
        'room_villa.jpg'     => 'Room/Presidential-Sanctuary/1407443630411669.jpg',
        'room_pavilion.jpg'  => 'Room/Garden-Pavilion/300544975155270841.jpg',
        'room_loft.jpg'      => 'Room/Sky-Loft/790241065892872685.jpg',
        'default_room.jpg'   => 'Room/Celestial-Master-Suite/125467539613275900.jpg',
    ];

    if (isset($map[$gambar])) {
        return BASE_URL . 'assets/img/' . $map[$gambar];
    }

    return BASE_URL . 'assets/img/Room/Celestial-Master-Suite/125467539613275900.jpg';
}


define('BASE_URL', '/luxury-grand-hotel/');
