<?php

require_once __DIR__ . '/../includes/config.php';
requireAdmin();

// ── HANDLE QUICK ACTIONS ─────────────────────────────
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if (isset($_GET['booking_id'])) {
        $bookingId = (int)$_GET['booking_id'];
        if ($bookingId > 0 && in_array($action, ['confirm', 'complete', 'cancel'])) {
            $newStatus = '';
            if ($action === 'confirm')   $newStatus = 'confirmed';
            if ($action === 'complete')  $newStatus = 'completed';
            if ($action === 'cancel')    $newStatus = 'cancelled';

            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $newStatus, $bookingId);

            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Reservation #" . $bookingId . " status has been changed to " . ucfirst($newStatus) . ".";
            } else {
                $_SESSION['flash_error'] = "Failed to update reservation status.";
            }
            $stmt->close();
        }
        header('Location: ' . BASE_URL . 'pages/admin_dashboard.php');
        exit;
    }

    if ($action === 'delete_review' && isset($_GET['review_id'])) {
        $reviewId = (int)$_GET['review_id'];
        if ($reviewId > 0) {
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param('i', $reviewId);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Review #" . $reviewId . " has been successfully deleted.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete review.";
            }
            $stmt->close();
        }
        header('Location: ' . BASE_URL . 'pages/admin_dashboard.php');
        exit;
    }
}

// ── FETCH STATISTICS ────────────────────────────────

$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0] ?? 0;


$pendingCount = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetch_row()[0] ?? 0;


$activeGuests = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND CURRENT_DATE BETWEEN tanggal_checkin AND tanggal_checkout")->fetch_row()[0] ?? 0;


$totalRevenue = $conn->query("SELECT SUM(total_harga) FROM bookings WHERE status IN ('confirmed', 'completed')")->fetch_row()[0] ?? 0.0;


$totalRooms = $conn->query("SELECT COUNT(*) FROM rooms WHERE status != 'maintenance'")->fetch_row()[0] ?? 1;
if ($totalRooms <= 0) $totalRooms = 1;
$occupancyRate = round(($activeGuests / $totalRooms) * 100);


$monitorQuery = "
    SELECT b.id, b.nama_pemesan, b.tanggal_checkin, b.tanggal_checkout, b.jumlah_tamu, b.total_harga, b.status, b.catatan,
           r.nama_kamar, r.tipe,
           u.email, u.phone
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status IN ('pending', 'confirmed')
    ORDER BY CASE b.status WHEN 'pending' THEN 1 WHEN 'confirmed' THEN 2 END, b.tanggal_checkin ASC
";
$reservations = $conn->query($monitorQuery)->fetch_all(MYSQLI_ASSOC);

// ── FETCH ROOMS WITH ACTIVE STAYS ────────────────────
$roomsQuery = "
    SELECT r.id, r.nama_kamar, r.tipe, r.status AS room_status,
           b.nama_pemesan, b.tanggal_checkout, b.id AS booking_id
    FROM rooms r
    LEFT JOIN bookings b ON r.id = b.room_id 
        AND b.status = 'confirmed' 
        AND CURRENT_DATE BETWEEN b.tanggal_checkin AND b.tanggal_checkout
    ORDER BY r.tipe ASC, r.nama_kamar ASC
";
$roomsMap = $conn->query($roomsQuery)->fetch_all(MYSQLI_ASSOC);

// ── FETCH REVIEWS FOR MODERATION ─────────────────────
$allReviewsQuery = "
    SELECT r.*, u.full_name, rm.nama_kamar, rm.tipe
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
";
$allReviews = $conn->query($allReviewsQuery)->fetch_all(MYSQLI_ASSOC);


function getRemainingDays(string $checkin, string $checkout, string $status): array {
    $today = new DateTime(date('Y-m-d'));
    $in = new DateTime($checkin);
    $out = new DateTime($checkout);

    if ($status === 'cancelled') {
        return ['text' => 'Cancelled', 'class' => 'status-cancelled', 'progress' => 0, 'alert' => false];
    }
    if ($status === 'completed') {
        return ['text' => 'Completed', 'class' => 'status-completed', 'progress' => 100, 'alert' => false];
    }
    if ($status === 'pending') {
        return ['text' => 'Awaiting Confirmation', 'class' => 'status-pending', 'progress' => 0, 'alert' => false];
    }

    if ($today < $in) {
        $days = $today->diff($in)->days;
        return ['text' => "Check-in in $days day(s)", 'class' => 'status-pending', 'progress' => 0, 'alert' => false];
    }
    if ($today > $out) {
        return ['text' => 'Requires Check-out', 'class' => 'status-completed', 'progress' => 100, 'alert' => true];
    }

    // Active Stay
    $totalDays = $in->diff($out)->days;
    if ($totalDays <= 0) $totalDays = 1;
    $elapsed = $in->diff($today)->days;
    $remaining = $today->diff($out)->days;

    $progress = round(($elapsed / $totalDays) * 100);

    if ($remaining === 0) {
        return ['text' => 'Check-out today!', 'class' => 'status-cancelled', 'progress' => $progress, 'alert' => true];
    }
    return ['text' => "$remaining day(s) remaining", 'class' => 'status-confirmed', 'progress' => $progress, 'alert' => false];
}

// Flash messages
$successMsg = $_SESSION['flash_success'] ?? '';
$errorMsg   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$pageTitle   = 'Admin Dashboard';
$currentPage = 'admin_dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="section-gap-sm section-cream" style="min-height:calc(100vh - 80px);">
    <div class="section-container">
        
        <!-- Dashboard Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4 pb-4 mb-5" style="border-bottom:1px solid var(--color-cream-high);">
            <div>
                <p class="elysian-label-sm text-gold mb-2">MANAGEMENT SUITE</p>
                <h1 class="elysian-headline-lg mb-2">Admin Dashboard</h1>
                <p class="elysian-body-lg text-muted-soft mb-0">Monitor active stays, pending requests, and real-time room occupancy status.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>pages/tambah_kamar.php" class="btn-elysian-primary">+ Add New Room</a>
            </div>
        </div>

        <!-- Flash Alerts -->
        <?php if ($successMsg): ?>
            <div class="alert-elysian success mb-4"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert-elysian error mb-4"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- KPI Metrics Grid -->
        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="dashboard-card p-4 h-100 text-center">
                    <span class="material-symbols-outlined text-gold" style="font-size:36px;margin-bottom:8px;">group</span>
                    <p class="elysian-label-sm text-muted-soft mb-1">Active Guests</p>
                    <h2 class="elysian-headline-md mb-0"><?= $activeGuests ?></h2>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="dashboard-card p-4 h-100 text-center">
                    <span class="material-symbols-outlined text-gold" style="font-size:36px;margin-bottom:8px;">door_sliding</span>
                    <p class="elysian-label-sm text-muted-soft mb-1">Occupancy Rate</p>
                    <h2 class="elysian-headline-md mb-0"><?= $occupancyRate ?>%</h2>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="dashboard-card p-4 h-100 text-center">
                    <span class="material-symbols-outlined text-gold" style="font-size:36px;margin-bottom:8px;">payments</span>
                    <p class="elysian-label-sm text-muted-soft mb-1">Total Revenue</p>
                    <h2 class="elysian-headline-md mb-0" style="font-size:24px;"><?= formatRupiah($totalRevenue) ?></h2>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="dashboard-card p-4 h-100 text-center">
                    <span class="material-symbols-outlined text-gold" style="font-size:36px;margin-bottom:8px;">notifications_active</span>
                    <p class="elysian-label-sm text-muted-soft mb-1">Pending Approvals</p>
                    <h2 class="elysian-headline-md mb-0 <?= $pendingCount > 0 ? 'text-danger fw-bold' : '' ?>"><?= $pendingCount ?></h2>
                </div>
            </div>
        </div>

        <!-- Customer Stay Monitor -->
        <div class="dashboard-card p-4 mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-gold" style="font-size:24px;">hail</span>
                <h2 class="elysian-headline-sm mb-0">Customer Stay Monitor</h2>
            </div>
            
            <?php if (empty($reservations)): ?>
                <div class="text-center py-5">
                    <span class="material-symbols-outlined text-muted-soft" style="font-size:48px;">check_circle</span>
                    <p class="elysian-body-md text-muted-soft mt-3">No active or pending customer stays to monitor.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle" style="border-collapse:separate;border-spacing:0 8px;">
                        <thead>
                            <tr class="elysian-label-sm text-muted-soft" style="border:none;">
                                <th style="background:transparent;border:none;padding:12px 16px;">Booking ID</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Guest / Contact</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Room</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Stay Period</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Remaining Stay</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Status</th>
                                <th style="background:transparent;border:none;padding:12px 16px;text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                                <?php 
                                $stay = getRemainingDays($res['tanggal_checkin'], $res['tanggal_checkout'], $res['status']);
                                ?>
                                <tr style="background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:transform 0.2s;">
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);border-left:1px solid rgba(196,199,199,0.2);font-weight:600;">
                                        #<?= $res['id'] ?>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);">
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($res['nama_pemesan']) ?></div>
                                        <div class="text-muted-soft" style="font-size:12px;"><?= htmlspecialchars($res['email']) ?></div>
                                        <div class="text-muted-soft" style="font-size:12px;"><?= htmlspecialchars($res['phone']) ?></div>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);">
                                        <div class="fw-semibold"><?= htmlspecialchars($res['nama_kamar']) ?></div>
                                        <div class="elysian-label-sm text-gold" style="font-size:10px;"><?= htmlspecialchars($res['tipe']) ?></div>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);">
                                        <div style="font-size:13px;"><?= date('M d, Y', strtotime($res['tanggal_checkin'])) ?></div>
                                        <div style="font-size:13px;" class="text-muted-soft">to <?= date('M d, Y', strtotime($res['tanggal_checkout'])) ?></div>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);min-width:180px;">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="badge status-badge <?= $stay['class'] ?> <?= $stay['alert'] ? 'fw-bold border border-danger' : '' ?>" style="font-size:10px;letter-spacing:0.05em;padding:2px 8px;">
                                                <?= $stay['text'] ?>
                                            </span>
                                            <?php if ($res['status'] === 'confirmed'): ?>
                                                <span style="font-size:11px;" class="text-muted-soft"><?= $stay['progress'] ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($res['status'] === 'confirmed'): ?>
                                            <div class="progress" style="height:4px;border-radius:0;background:rgba(196,199,199,0.2);">
                                                <div class="progress-bar <?= $stay['alert'] ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width:<?= $stay['progress'] ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);">
                                        <span class="status-badge status-<?= $res['status'] ?>"><?= ucfirst($res['status']) ?></span>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);border-right:1px solid rgba(196,199,199,0.2);text-align:right;">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if ($res['status'] === 'pending'): ?>
                                                <a href="?action=confirm&booking_id=<?= $res['id'] ?>" class="btn-elysian-gold" style="padding:6px 14px;font-size:11px;">Confirm</a>
                                            <?php elseif ($res['status'] === 'confirmed'): ?>
                                                <a href="?action=complete&booking_id=<?= $res['id'] ?>" class="btn-elysian-secondary" style="padding:6px 14px;font-size:11px;background:#1b1c1c;color:#fff!important;">Check Out</a>
                                            <?php endif; ?>
                                            
                                            <a href="?action=cancel&booking_id=<?= $res['id'] ?>" class="btn-elysian-danger" style="padding:6px 14px;font-size:11px;" 
                                               onclick="return confirm('Are you sure you want to cancel this reservation?')">Cancel</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Room Status & Occupancy Grid -->
        <div class="dashboard-card p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-gold" style="font-size:24px;">door_open</span>
                <h2 class="elysian-headline-sm mb-0">Room Status &amp; Occupancy Grid</h2>
            </div>
            
            <div class="row g-4">
                <?php foreach ($roomsMap as $rm): ?>
                    <?php 
                    $isOccupied = !empty($rm['nama_pemesan']);
                    $cardClass = 'border-success';
                    $badgeText = 'Available';
                    $badgeClass = 'bg-success text-white';
                    
                    if ($rm['room_status'] === 'maintenance') {
                        $cardClass = 'border-secondary opacity-75';
                        $badgeText = 'Maintenance';
                        $badgeClass = 'bg-secondary text-white';
                    } elseif ($isOccupied) {
                        $cardClass = 'border-warning shadow-sm';
                        $badgeText = 'Occupied';
                        $badgeClass = 'bg-warning text-dark';
                    }
                    ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 border-top-3" style="border:1px solid rgba(196,199,199,0.3);border-radius:4px;background:#fff;border-top:3px solid <?= $isOccupied ? 'var(--color-gold)' : ($rm['room_status'] === 'maintenance' ? 'var(--color-outline)' : 'var(--color-success)') ?>;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between" style="min-height:160px;">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="elysian-body-md fw-bold mb-0" style="font-size:16px;"><?= htmlspecialchars($rm['nama_kamar']) ?></h4>
                                        <span class="badge" style="font-size:10px;padding:3px 8px;border-radius:100px;background-color:<?= $isOccupied ? '#fed488' : ($rm['room_status'] === 'maintenance' ? '#e1e3e3' : '#d1e7dd') ?>;color:<?= $isOccupied ? '#775a19' : ($rm['room_status'] === 'maintenance' ? '#1b1c1c' : '#0f5132') ?>;">
                                            <?= $badgeText ?>
                                        </span>
                                    </div>
                                    <p class="elysian-label-sm text-muted-soft mb-2" style="font-size:10px;"><?= htmlspecialchars($rm['tipe']) ?></p>
                                </div>
                                
                                <div class="pt-2" style="border-top:1px solid rgba(196,199,199,0.15);">
                                    <?php if ($isOccupied): ?>
                                        <div style="font-size:12px;">
                                            <span class="text-muted-soft">Guest:</span> <strong class="text-primary"><?= htmlspecialchars($rm['nama_pemesan']) ?></strong>
                                        </div>
                                        <div style="font-size:11px;" class="text-muted-soft mt-1">
                                            Out: <?= date('M d, Y', strtotime($rm['tanggal_checkout'])) ?>
                                        </div>
                                    <?php elseif ($rm['room_status'] === 'maintenance'): ?>
                                        <div style="font-size:12px;" class="text-muted-soft">
                                            <span class="material-symbols-outlined" style="font-size:14px;vertical-align:text-bottom;">build</span> Under repair
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:12px;" class="text-success fw-semibold">
                                            <span class="material-symbols-outlined" style="font-size:14px;vertical-align:text-bottom;">check_circle</span> Ready to book
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Guest Reviews Moderation Panel -->
        <div class="dashboard-card p-4 mt-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-gold" style="font-size:24px;">rate_review</span>
                <h2 class="elysian-headline-sm mb-0">Guest Reviews Moderation</h2>
            </div>
            
            <?php if (empty($allReviews)): ?>
                <div class="text-center py-5">
                    <span class="material-symbols-outlined text-muted-soft" style="font-size:48px;">reviews</span>
                    <p class="elysian-body-md text-muted-soft mt-3">No reviews have been posted yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle" style="border-collapse:separate;border-spacing:0 8px;">
                        <thead>
                            <tr class="elysian-label-sm text-muted-soft" style="border:none;">
                                <th style="background:transparent;border:none;padding:12px 16px;">Review ID</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Guest</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Room</th>
                                <th style="background:transparent;border:none;padding:12px 16px;">Rating & Comment</th>
                                <th style="background:transparent;border:none;padding:12px 16px;text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allReviews as $rv): ?>
                                <tr style="background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);border-left:1px solid rgba(196,199,199,0.2);font-weight:600;">
                                        #<?= $rv['id'] ?>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);">
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($rv['full_name']) ?></div>
                                        <div class="text-muted-soft" style="font-size:11px;"><?= date('M d, Y', strtotime($rv['created_at'])) ?></div>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);">
                                        <div class="fw-semibold"><?= htmlspecialchars($rv['nama_kamar']) ?></div>
                                        <div class="elysian-label-sm text-gold" style="font-size:10px;"><?= htmlspecialchars($rv['tipe']) ?></div>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);max-width:300px;">
                                        <div class="d-flex gap-1 text-gold mb-1">
                                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                                <span class="material-symbols-outlined" style="font-size:16px; font-variation-settings:'FILL' <?= $star <= $rv['rating'] ? '1' : '0' ?>;">star</span>
                                            <?php endfor; ?>
                                        </div>
                                        <div style="font-size:13px; font-style:italic; line-height:1.4; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($rv['komentar']) ?>">
                                            "<?= htmlspecialchars($rv['komentar']) ?>"
                                        </div>
                                    </td>
                                    <td style="padding:16px;border-top:1px solid rgba(196,199,199,0.2);border-bottom:1px solid rgba(196,199,199,0.2);border-right:1px solid rgba(196,199,199,0.2);text-align:right;">
                                        <a href="?action=delete_review&review_id=<?= $rv['id'] ?>" class="btn-elysian-danger" style="padding:6px 14px;font-size:11px;" 
                                           onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
