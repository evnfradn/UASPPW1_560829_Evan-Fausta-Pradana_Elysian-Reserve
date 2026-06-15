<?php

require_once __DIR__ . '/../includes/config.php';
requireLogin();


// ── GET ROOM ─────────────────────────────────────────
$roomId = (int)($_GET['room_id'] ?? 0);
if ($roomId <= 0) {
    header('Location: ' . BASE_URL . 'pages/rooms.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param('i', $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header('Location: ' . BASE_URL . 'pages/rooms.php');
    exit;
}

// ── FETCH ROOM REVIEWS & STATS ───────────────────────
$statsStmt = $conn->prepare("
    SELECT COALESCE(ROUND(AVG(rating), 1), 0) AS avg_rating, COUNT(*) AS num_reviews
    FROM reviews
    WHERE room_id = ?
");
$statsStmt->bind_param('i', $roomId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

$avgRating = (float)$stats['avg_rating'];
$numReviews = (int)$stats['num_reviews'];

$reviewsStmt = $conn->prepare("
    SELECT r.*, u.full_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.room_id = ?
    ORDER BY r.created_at DESC
");
$reviewsStmt->bind_param('i', $roomId);
$reviewsStmt->execute();
$roomReviews = $reviewsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reviewsStmt->close();

// Check if logged in user has booked this room (completed or confirmed)
$userBooking = null;
if (isLoggedIn()) {
    $userId = $_SESSION['user']['id'];
    $ubStmt = $conn->prepare("
        SELECT b.id, rv.id AS review_id
        FROM bookings b
        LEFT JOIN reviews rv ON b.id = rv.booking_id
        WHERE b.user_id = ? AND b.room_id = ? AND b.status IN ('confirmed', 'completed')
        ORDER BY b.tanggal_checkout DESC
        LIMIT 1
    ");
    $ubStmt->bind_param('ii', $userId, $roomId);
    $ubStmt->execute();
    $userBooking = $ubStmt->get_result()->fetch_assoc();
    $ubStmt->close();
}

// ── HANDLE BOOKING SUBMIT ────────────────────────────
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama_pemesan']   ?? '');
    $checkin    = $_POST['tanggal_checkin']  ?? '';
    $checkout   = $_POST['tanggal_checkout'] ?? '';
    $tamu       = (int)($_POST['jumlah_tamu'] ?? 1);
    $catatan    = trim($_POST['catatan'] ?? '');
    $totalHarga = (float)($_POST['total_harga'] ?? 0);


    if (strlen($nama) < 3)         $errors[] = 'Guest name must be at least 3 characters.';
    if (!$checkin)                  $errors[] = 'Check-in date is required.';
    if (!$checkout)                 $errors[] = 'Check-out date is required.';
    if ($checkin && $checkout && $checkout <= $checkin) $errors[] = 'Check-out must be after check-in.';
    if ($tamu < 1 || $tamu > $room['kapasitas']) $errors[] = "Number of guests must be between 1 and {$room['kapasitas']}.";
    if ($room['status'] !== 'available') $errors[] = 'This room is currently unavailable.';

    if (empty($errors)) {
        $userId = $_SESSION['user']['id'];
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, nama_pemesan, tanggal_checkin, tanggal_checkout, jumlah_tamu, total_harga, status, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param('iisssiis', $userId, $roomId, $nama, $checkin, $checkout, $tamu, $totalHarga, $catatan);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Booking successful! Your reservation is pending confirmation.";
            $stmt->close();
            header('Location: ' . BASE_URL . 'pages/my_bookings.php');
            exit;
        } else {
            $errors[] = 'Failed to save booking. Please try again.';
            $stmt->close();
        }
    }
}

// ── RELATED ROOMS ────────────────────────────────────
$relStmt = $conn->prepare("SELECT id, nama_kamar, harga_per_malam, gambar FROM rooms WHERE id != ? AND status = 'available' ORDER BY RAND() LIMIT 3");
$relStmt->bind_param('i', $roomId);
$relStmt->execute();
$relatedRooms = $relStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$relStmt->close();

$imgSrc = getRoomImageSrc($room['gambar']);


$gallerySrcs = [];
if (!empty($room['gallery'])) {
    $galleryFiles = array_filter(array_map('trim', explode(',', $room['gallery'])));
    foreach ($galleryFiles as $file) {
        $gallerySrcs[] = getRoomImageSrc($file);
    }
}

if (count($gallerySrcs) < 3) {
    $typeMap = [
        'Suite'     => 'Celestial-Master-Suite',
        'Penthouse' => 'Oceanic-Penthouse',
        'Villa'     => 'Presidential-Sanctuary',
        'Pavilion'  => 'Garden-Pavilion',
        'Loft'      => 'Sky-Loft',
    ];
    $folder = $typeMap[$room['tipe']] ?? 'Celestial-Master-Suite';
    $folderPath = __DIR__ . '/../assets/img/Room/' . $folder . '/*';
    $localFiles = glob($folderPath);
    if (!empty($localFiles)) {
        foreach ($localFiles as $lf) {
            $filename = basename($lf);
            $src = BASE_URL . 'assets/img/Room/' . $folder . '/' . rawurlencode($filename);

            if (rawurlencode($filename) !== rawurlencode(basename($imgSrc)) && !in_array($src, $gallerySrcs)) {
                $gallerySrcs[] = $src;
            }
        }
    }
}


while (count($gallerySrcs) < 3) {
    $gallerySrcs[] = $imgSrc;
}

$morePhotosCount = max(0, count($gallerySrcs) - 3);

$amenities = array_filter(array_map('trim', explode(',', $room['fasilitas'] ?? '')));


$pageTitle   = htmlspecialchars($room['nama_kamar']);
$currentPage = 'rooms';
require_once __DIR__ . '/../includes/header.php';
?>

<main>
<div class="section-container py-5">

    <!-- Breadcrumb -->
    <nav class="elysian-breadcrumb mb-4" aria-label="breadcrumb">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <span class="sep material-symbols-outlined" style="font-size:18px;">chevron_right</span>
        <a href="<?= BASE_URL ?>pages/rooms.php">Rooms &amp; Suites</a>
        <span class="sep material-symbols-outlined" style="font-size:18px;">chevron_right</span>
        <span class="current"><?= htmlspecialchars($room['nama_kamar']) ?></span>
    </nav>

    <!-- Title -->
    <div class="mb-4">
        <h1 class="elysian-headline-lg mb-2"><?= htmlspecialchars($room['nama_kamar']) ?></h1>
        <div class="d-flex align-items-center gap-4 text-muted-soft">
            <span class="d-flex align-items-center gap-1">
                <span class="material-symbols-outlined text-gold" style="font-variation-settings:'FILL' <?= $numReviews > 0 ? '1' : '0' ?>;">star</span>
                <span class="elysian-label-sm">
                    <?= $numReviews > 0 ? htmlspecialchars($avgRating) . ' (' . $numReviews . ' Review' . ($numReviews > 1 ? 's' : '') . ')' : 'No Reviews' ?>
                </span>
            </span>
            <span class="d-flex align-items-center gap-1">
                <span class="material-symbols-outlined" style="font-size:18px;">hotel_class</span>
                <span class="elysian-label-sm"><?= htmlspecialchars($room['tipe']) ?></span>
            </span>
            <span class="status-badge status-<?= $room['status'] === 'available' ? 'confirmed' : ($room['status'] === 'booked' ? 'cancelled' : 'completed') ?>">
                <?= ucfirst(htmlspecialchars($room['status'])) ?>
            </span>
        </div>
    </div>

    <!-- ERROR ALERTS -->
    <?php if (!empty($errors)): ?>
    <div class="alert-elysian error mb-4">
        <?php foreach ($errors as $e): ?>
        <div>• <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- MAIN GRID: Content + Booking Panel -->
    <div class="row g-5">

        <!-- LEFT: Gallery + Details -->
        <div class="col-12 col-lg-8">

            <!-- Gallery Grid -->
            <div class="row g-3 mb-5" style="height:480px;overflow:hidden;">
                <div class="col-7 h-100">
                    <img id="mainGalleryImg" src="<?= htmlspecialchars($imgSrc) ?>"
                         alt="<?= htmlspecialchars($room['nama_kamar']) ?>"
                         class="w-100 h-100" style="object-fit:cover;cursor:pointer;transition:opacity 0.3s;">
                </div>
                <div class="col-5 h-100 d-flex flex-column gap-3">
                    <img src="<?= htmlspecialchars($gallerySrcs[0]) ?>"
                         alt="Bathroom" class="gallery-thumb w-100" style="height:232px;object-fit:cover;cursor:pointer;transition:transform 0.4s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform=''">
                    <div class="row g-3" style="height:232px;">
                        <div class="col-6 h-100">
                            <img src="<?= htmlspecialchars($gallerySrcs[1]) ?>"
                                 alt="Lounge" class="gallery-thumb w-100 h-100" style="object-fit:cover;cursor:pointer;">
                        </div>
                        <div class="col-6 h-100" style="cursor:pointer;">
                            <div class="position-relative h-100 w-100">
                                <img src="<?= htmlspecialchars($gallerySrcs[2]) ?>"
                                     alt="Pool" class="gallery-thumb w-100 h-100" style="object-fit:cover;">
                                <?php if ($morePhotosCount > 0): ?>
                                <div class="position-absolute inset-0 d-flex align-items-center justify-content-center" style="background:rgba(0,0,0,0.45);">
                                    <span class="elysian-label-sm text-white">+<?= $morePhotosCount ?> Photos</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <section class="mb-5">
                <h2 class="elysian-headline-sm mb-3" style="border-bottom:1px solid var(--color-cream-high);padding-bottom:12px;">The Experience</h2>
                <p class="elysian-body-lg text-muted-soft"><?= nl2br(htmlspecialchars($room['deskripsi'])) ?></p>
                <div class="d-flex gap-4 mt-4">
                    <span class="room-meta-item">
                        <span class="material-symbols-outlined">group</span>
                        Up to <?= (int)$room['kapasitas'] ?> Guests
                    </span>
                    <span class="room-meta-item">
                        <span class="material-symbols-outlined">hotel_class</span>
                        <?= htmlspecialchars($room['tipe']) ?>
                    </span>
                </div>
            </section>

            <!-- Amenities -->
            <?php if (!empty($amenities)): ?>
            <section class="mb-5">
                <h2 class="elysian-headline-sm mb-4" style="border-bottom:1px solid var(--color-cream-high);padding-bottom:12px;">Bespoke Amenities</h2>
                <div class="row g-4">
                    <?php
                    $iconMap = [
                        'pool'  => 'pool', 'chef' => 'restaurant', 'chef available' => 'restaurant',
                        'wifi'  => 'wifi', 'spa'  => 'spa',   'butler' => 'concierge',
                        'beach' => 'beach_access', 'cinema' => 'theaters', 'terrace' => 'deck',
                        'jacuzzi' => 'bathtub', 'yoga' => 'self_improvement', 'bar' => 'local_bar',
                        'garden' => 'park', 'forest' => 'forest',
                    ];
                    foreach ($amenities as $amenity):
                        $icon = 'check_circle';
                        foreach ($iconMap as $kw => $ic) {
                            if (str_contains(strtolower($amenity), $kw)) { $icon = $ic; break; }
                        }
                    ?>
                    <div class="col-12 col-sm-6">
                        <div class="amenity-item">
                            <span class="material-symbols-outlined"><?= $icon ?></span>
                            <div>
                                <strong class="elysian-body-sm"><?= htmlspecialchars($amenity) ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($userBooking): ?>
                <div class="p-4 mb-4 d-flex justify-content-between align-items-center" style="background:var(--color-cream-low); border:1px solid var(--color-gold);">
                    <div>
                        <h4 class="elysian-body-md fw-bold mb-1">Share Your Experience</h4>
                        <p class="elysian-body-sm text-muted-soft mb-0">You have a reservation for this room. We would love to hear your feedback!</p>
                    </div>
                    <?php if ($userBooking['review_id']): ?>
                        <a href="<?= BASE_URL ?>pages/review.php?booking_id=<?= (int)$userBooking['id'] ?>" class="btn-elysian-secondary" style="padding:10px 20px; font-size:11px;">Edit Review</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>pages/review.php?booking_id=<?= (int)$userBooking['id'] ?>" class="btn-elysian-gold" style="padding:10px 20px; font-size:11px;">Write Review</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Guest Reviews Section -->
            <section class="mb-5 mt-5">
                <h2 class="elysian-headline-sm mb-4" style="border-bottom:1px solid var(--color-cream-high);padding-bottom:12px;">Guest Reviews</h2>
                
                <?php if (empty($roomReviews)): ?>
                    <div class="p-4 text-center" style="background:var(--color-cream-low); border:1px solid var(--color-cream-high);">
                        <p class="elysian-body-sm text-muted-soft mb-0">No reviews have been left for this room yet.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($roomReviews as $rv): ?>
                            <div class="p-4" style="background:#fff; border:1px solid var(--color-cream-high); box-shadow:var(--shadow-soft);">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h4 class="elysian-body-md fw-bold mb-0"><?= htmlspecialchars($rv['full_name']) ?></h4>
                                        <p class="mb-0 text-muted-soft" style="font-size:11px;"><?= date('F d, Y', strtotime($rv['created_at'])) ?></p>
                                    </div>
                                    <div class="d-flex gap-1 text-gold">
                                        <?php for ($star = 1; $star <= 5; $star++): ?>
                                            <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' <?= $star <= $rv['rating'] ? '1' : '0' ?>;">star</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="elysian-body-sm text-muted-soft mb-0" style="font-style:italic; line-height:1.6; color:var(--color-on-surface-v);">
                                    "<?= htmlspecialchars($rv['komentar']) ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- RIGHT: Booking Panel -->
        <div class="col-12 col-lg-4">
            <div class="booking-panel">
                <div class="d-flex justify-content-between align-items-baseline mb-4">
                    <div class="room-card-price" style="font-size:28px;"><?= formatRupiah($room['harga_per_malam']) ?></div>
                    <span class="elysian-label-sm text-muted-soft">per night</span>
                </div>

                <?php if ($room['status'] !== 'available'): ?>
                <div class="alert-elysian error mb-4">This room is currently unavailable.</div>
                <?php else: ?>
                <form id="bookingForm" method="POST" action="" novalidate>
                    <!-- Hidden: room price for JS calculator -->
                    <input type="hidden" id="price_per_night" value="<?= (float)$room['harga_per_malam'] ?>">
                    <input type="hidden" id="total_harga" name="total_harga" value="">

                    <div class="elysian-input-wrap">
                        <label for="nama_pemesan">Full Name</label>
                        <input type="text" id="nama_pemesan" name="nama_pemesan" required
                               value="<?= htmlspecialchars($_POST['nama_pemesan'] ?? $_SESSION['user']['full_name']) ?>"
                               placeholder="Your full name">
                    </div>

                    <div class="elysian-input-wrap">
                        <label for="tanggal_checkin">Check-In Date</label>
                        <input type="date" id="tanggal_checkin" name="tanggal_checkin" required
                               value="<?= htmlspecialchars($_POST['tanggal_checkin'] ?? '') ?>">
                    </div>

                    <div class="elysian-input-wrap">
                        <label for="tanggal_checkout">Check-Out Date</label>
                        <input type="date" id="tanggal_checkout" name="tanggal_checkout" required
                               value="<?= htmlspecialchars($_POST['tanggal_checkout'] ?? '') ?>">
                    </div>

                    <div class="elysian-input-wrap">
                        <label for="jumlah_tamu">Guests (max <?= (int)$room['kapasitas'] ?>)</label>
                        <select id="jumlah_tamu" name="jumlah_tamu">
                            <?php for ($g = 1; $g <= $room['kapasitas']; $g++): ?>
                            <option value="<?= $g ?>" <?= (int)($_POST['jumlah_tamu'] ?? 2) === $g ? 'selected' : '' ?>>
                                <?= $g ?> Guest<?= $g > 1 ? 's' : '' ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="elysian-input-wrap">
                        <label for="catatan">Special Requests (Optional)</label>
                        <textarea id="catatan" name="catatan" rows="3" placeholder="Any special requests..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="price-breakdown my-4">
                        <div class="price-row">
                            <span class="elysian-body-sm text-muted-soft"><?= formatRupiah($room['harga_per_malam']) ?> × <span id="calc_nights">—</span></span>
                            <span class="elysian-body-sm" id="calc_subtotal">—</span>
                        </div>
                        <div class="price-row">
                            <span class="elysian-body-sm text-muted-soft">Service Fee (12%)</span>
                            <span class="elysian-body-sm" id="calc_service">—</span>
                        </div>
                        <div class="price-row total">
                            <span>Total</span>
                            <span id="calc_total">—</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-elysian-primary w-full mb-3">
                        Confirm Reservation
                    </button>
                    <p class="elysian-label-sm text-muted-soft text-center">You won't be charged yet</p>

                    <!-- Trust indicators -->
                    <div class="d-flex justify-content-center gap-4 mt-4 opacity-50">
                        <span class="material-symbols-outlined">verified_user</span>
                        <span class="material-symbols-outlined">payments</span>
                        <span class="material-symbols-outlined">lock</span>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COMPARABLE ROOMS -->
    <?php if (!empty($relatedRooms)): ?>
    <section class="mt-5 pt-5" style="border-top:1px solid var(--color-cream-high);">
        <h2 class="elysian-headline-md mb-4">Comparable Residences</h2>
        <div class="row g-4">
            <?php foreach ($relatedRooms as $rel): ?>
            <?php $relImg = getRoomImageSrc($rel['gambar']); ?>
            <div class="col-12 col-md-4 reveal">
                <a href="<?= BASE_URL ?>pages/booking.php?room_id=<?= (int)$rel['id'] ?>" class="room-card">
                    <div class="room-card-img-wrap">
                        <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['nama_kamar']) ?>" style="height:260px;">
                    </div>
                    <div class="room-card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h3 class="elysian-headline-sm" style="font-size:18px;"><?= htmlspecialchars($rel['nama_kamar']) ?></h3>
                            <span class="elysian-label-sm text-gold ms-2"><?= formatRupiah($rel['harga_per_malam']) ?> / night</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
