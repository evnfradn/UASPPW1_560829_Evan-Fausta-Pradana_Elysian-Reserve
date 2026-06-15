<?php

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) {
    header('Location: ' . BASE_URL . 'pages/my_bookings.php');
    exit;
}

$userId = $_SESSION['user']['id'];
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT b.*, r.nama_kamar, r.harga_per_malam, r.kapasitas FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.id=?");
    $stmt->bind_param('i', $bookingId);
} else {
    $stmt = $conn->prepare("SELECT b.*, r.nama_kamar, r.harga_per_malam, r.kapasitas FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.id=? AND b.user_id=?");
    $stmt->bind_param('ii', $bookingId, $userId);
}
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header('Location: ' . BASE_URL . 'pages/my_bookings.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_pemesan']   ?? '');
    $checkin  = $_POST['tanggal_checkin']  ?? '';
    $checkout = $_POST['tanggal_checkout'] ?? '';
    $tamu     = (int)($_POST['jumlah_tamu'] ?? 1);
    $catatan  = trim($_POST['catatan'] ?? '');
    $status   = $_POST['status'] ?? $booking['status'];

    if (strlen($nama) < 3)      $errors[] = 'Name must be at least 3 characters.';
    if (!$checkin)               $errors[] = 'Check-in date is required.';
    if (!$checkout)              $errors[] = 'Check-out date is required.';
    if ($checkin && $checkout && $checkout <= $checkin) $errors[] = 'Check-out must be after check-in.';
    if ($tamu < 1 || $tamu > $booking['kapasitas']) $errors[] = "Number of guests must be between 1 and {$booking['kapasitas']}.";

    if (empty($errors)) {
        // Recalculate total
        $nights = ceil((strtotime($checkout) - strtotime($checkin)) / 86400);
        $subtotal = $nights * $booking['harga_per_malam'];
        $total = $subtotal + ($subtotal * 0.12);

        $stmt = $conn->prepare("UPDATE bookings SET nama_pemesan=?, tanggal_checkin=?, tanggal_checkout=?, jumlah_tamu=?, total_harga=?, status=?, catatan=? WHERE id=?");
        $stmt->bind_param('sssidssi', $nama, $checkin, $checkout, $tamu, $total, $status, $catatan, $bookingId);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Booking has been successfully updated.';
            $stmt->close();
            header('Location: ' . BASE_URL . 'pages/my_bookings.php');
            exit;
        } else {
            $errors[] = 'Failed to update booking.';
            $stmt->close();
        }
    }


    $booking['nama_pemesan']   = $nama;
    $booking['tanggal_checkin'] = $checkin;
    $booking['tanggal_checkout'] = $checkout;
    $booking['jumlah_tamu']    = $tamu;
    $booking['catatan']        = $catatan;
    $booking['status']         = $status;
}

$pageTitle   = 'Edit Booking';
$currentPage = 'bookings';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="section-gap-sm section-cream" style="min-height:calc(100vh - 80px);">
<div class="section-container" style="max-width:700px;">

    <!-- Breadcrumb -->
    <nav class="elysian-breadcrumb mb-4">
        <a href="<?= BASE_URL ?>pages/my_bookings.php">My Bookings</a>
        <span class="sep material-symbols-outlined" style="font-size:18px;">chevron_right</span>
        <span class="current">Edit Reservation</span>
    </nav>

    <div class="login-card mx-0" style="max-width:100%;">
        <div class="gold-line"></div>
        <p class="elysian-label-sm text-gold mb-2">MODIFY RESERVATION</p>
        <h1 class="elysian-headline-md mb-1"><?= htmlspecialchars($booking['nama_kamar']) ?></h1>
        <p class="elysian-body-sm text-muted-soft mb-5">Booking #<?= $bookingId ?></p>

        <?php if (!empty($errors)): ?>
        <div class="alert-elysian error mb-4">
            <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form id="editBookingForm" method="POST" action="">

            <div class="elysian-input-wrap">
                <label for="nama_pemesan">Guest Name</label>
                <input type="text" id="nama_pemesan" name="nama_pemesan" required
                       value="<?= htmlspecialchars($booking['nama_pemesan']) ?>">
            </div>

            <div class="row g-4">
                <div class="col-6">
                    <div class="elysian-input-wrap">
                        <label for="tanggal_checkin">Check-In</label>
                        <input type="date" id="tanggal_checkin" name="tanggal_checkin" required
                               value="<?= htmlspecialchars($booking['tanggal_checkin']) ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="elysian-input-wrap">
                        <label for="tanggal_checkout">Check-Out</label>
                        <input type="date" id="tanggal_checkout" name="tanggal_checkout" required
                               value="<?= htmlspecialchars($booking['tanggal_checkout']) ?>">
                    </div>
                </div>
            </div>

            <div class="elysian-input-wrap">
                <label for="jumlah_tamu">Guests (max <?= (int)$booking['kapasitas'] ?>)</label>
                <select id="jumlah_tamu" name="jumlah_tamu">
                    <?php for ($g = 1; $g <= $booking['kapasitas']; $g++): ?>
                    <option value="<?= $g ?>" <?= (int)$booking['jumlah_tamu'] === $g ? 'selected' : '' ?>>
                        <?= $g ?> Guest<?= $g > 1 ? 's' : '' ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php if (isAdmin()): ?>
            <div class="elysian-input-wrap">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $booking['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="elysian-input-wrap">
                <label for="catatan">Special Requests</label>
                <textarea id="catatan" name="catatan" rows="3"><?= htmlspecialchars($booking['catatan'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn-elysian-primary flex-fill">Save Changes</button>
                <a href="<?= BASE_URL ?>pages/my_bookings.php" class="btn-elysian-secondary flex-fill text-center">Cancel</a>
            </div>
        </form>
    </div>
</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
