<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'Elysian Reserve';
$currentPage = $currentPage ?? '';


$isHome = ($currentPage === 'home');
$isRooms = ($currentPage === 'rooms');
$isBookings = ($currentPage === 'bookings');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Elysian Reserve — Understated opulence, effortless serenity. Book your luxury sanctuary today.">
    <title><?= htmlspecialchars($pageTitle) ?> | Elysian Reserve</title>

    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- ===== TOP NAVIGATION ===== -->
<nav class="navbar navbar-expand-md elysian-navbar fixed-top" id="mainNav">
    <div class="container-fluid px-nav">

        <!-- Brand -->
        <a class="navbar-brand elysian-brand" href="<?= BASE_URL ?>index.php">
            ELYSIAN RESERVE
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler elysian-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                aria-controls="navbarMenu" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <!-- Nav Links -->
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav mx-auto gap-md-4">
                <li class="nav-item">
                    <a class="nav-link elysian-navlink <?= $isRooms ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/rooms.php">Rooms &amp; Suites</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link elysian-navlink" href="<?= BASE_URL ?>index.php#services">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link elysian-navlink" href="<?= BASE_URL ?>index.php#amenities">Amenities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link elysian-navlink" href="<?= BASE_URL ?>index.php#about">About</a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link elysian-navlink <?= $isBookings ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/my_bookings.php">My Bookings</a>
                </li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link elysian-navlink <?= ($currentPage === 'admin_dashboard') ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>pages/admin_dashboard.php">Admin Dashboard</a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Right Side Actions -->
            <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                <?php if (isLoggedIn()): ?>
                    <div class="d-flex align-items-center gap-2 elysian-user-badge">
                        <span class="material-symbols-outlined" style="font-size:20px;">account_circle</span>
                        <span class="elysian-label-sm"><?= htmlspecialchars(strtoupper(explode(' ', $_SESSION['user']['full_name'])[0])) ?></span>
                        <?php if (isAdmin()): ?>
                            <span class="badge-gold ms-1">ADMIN</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>logout.php" class="btn-elysian-secondary">Sign Out</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="btn-elysian-secondary">Sign In</a>
                    <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-primary">Book Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navbar -->
<div style="height: 80px;"></div>
