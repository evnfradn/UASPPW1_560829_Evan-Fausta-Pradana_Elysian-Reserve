<?php

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$userId = $_SESSION['user']['id'];

// ── CANCEL BOOKING (POST handler) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cancelId = (int)($_POST['id'] ?? 0);
    if ($cancelId > 0) {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $cancelId, $userId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $_SESSION['flash_success'] = 'Booking has been successfully cancelled.';
        } else {
            $_SESSION['flash_error'] = 'Booking not found or already cancelled.';
        }
        $stmt->close();
    }
    header('Location: ' . BASE_URL . 'pages/my_bookings.php');
    exit;
}

// ── UPDATE PROFILE ────────────────────────────────────
$profileMsg   = '';
$profileError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email']     ?? '');
    $phone    = trim($_POST['phone']     ?? '');

    if (strlen($fullName) < 2)           $profileError = 'Full name must be at least 2 characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $profileError = 'Invalid email format.';
    else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param('sssi', $fullName, $email, $phone, $userId);
        if ($stmt->execute()) {
            // Update session
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['email']     = $email;
            $profileMsg = 'Profile has been successfully updated.';
        } else {
            $profileError = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

// ── FETCH USER ────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$userProfile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── FETCH BOOKINGS ────────────────────────────────────
$stmt = $conn->prepare("
    SELECT b.*, r.nama_kamar, r.tipe, r.gambar, r.harga_per_malam,
           rv.id AS review_id, rv.rating AS review_rating, rv.komentar AS review_comment
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    LEFT JOIN reviews rv ON b.id = rv.booking_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$currentBooking = null;
$pastBookings   = [];

foreach ($bookings as $b) {
    if (in_array($b['status'], ['pending','confirmed']) && $currentBooking === null) {
        $currentBooking = $b;
    } else {
        $pastBookings[] = $b;
    }
}


$totalPending   = 0.0;  
$totalCompleted = 0.0;  
$activeBillings = [];   

foreach ($bookings as $b) {
    if (in_array($b['status'], ['pending', 'confirmed'])) {
        $totalPending += (float)$b['total_harga'];
        $activeBillings[] = $b;
    } elseif ($b['status'] === 'completed') {
        $totalCompleted += (float)$b['total_harga'];
    }
}


// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$pageTitle   = 'My Bookings';
$currentPage = 'bookings';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="section-gap-sm section-cream" style="min-height:calc(100vh - 80px);">
<div class="section-container">

    <!-- Flash -->
    <?php if ($flashSuccess): ?>
    <div class="alert-elysian success mb-4"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="alert-elysian error mb-4"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <!-- Welcome Header -->
    <section class="py-4 mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-end" style="border-bottom:1px solid var(--color-cream-high);">
        <div>
            <p class="elysian-label-sm text-gold mb-1">WELCOME BACK</p>
            <h1 class="elysian-headline-lg mb-0">Your Bespoke Journey</h1>
        </div>
        <div class="d-flex gap-5 mt-4 mt-md-0">
            <div>
                <p class="elysian-label-sm text-muted-soft mb-1">Member Status</p>
                <p class="elysian-headline-sm text-gold mb-0">Gold Reserve</p>
            </div>
            <div>
                <p class="elysian-label-sm text-muted-soft mb-1">Nights Stayed</p>
                <p class="elysian-headline-sm mb-0"><?= count(array_filter($bookings, fn($b) => $b['status'] === 'completed')) * 3 ?></p>
            </div>
        </div>
    </section>

    <div class="row g-5">
        <!-- LEFT: Bookings -->
        <div class="col-12 col-lg-8">

            <!-- Current Booking -->
            <?php if ($currentBooking): ?>
            <?php $img = getRoomImageSrc($currentBooking['gambar']); ?>
            <article class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="elysian-headline-sm mb-0">Current Reservation</h2>
                    <span class="status-badge status-<?= $currentBooking['status'] ?>"><?= ucfirst($currentBooking['status']) ?></span>
                </div>
                <div class="dashboard-card">
                    <div class="row g-0">
                        <div class="col-12 col-md-6" style="height:360px;overflow:hidden;">
                            <img src="<?= htmlspecialchars($img) ?>"
                                 alt="<?= htmlspecialchars($currentBooking['nama_kamar']) ?>"
                                 class="w-100 h-100" style="object-fit:cover;transition:transform 1s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform=''">
                        </div>
                        <div class="col-12 col-md-6 p-4 d-flex flex-column justify-content-between" style="background:#fff;">
                            <div>
                                <p class="elysian-label-sm text-gold mb-1"><?= htmlspecialchars($currentBooking['tipe']) ?></p>
                                <h3 class="elysian-headline-sm mb-4"><?= htmlspecialchars($currentBooking['nama_kamar']) ?></h3>
                                <div class="row g-3 py-3" style="border-top:1px solid var(--color-cream-high);border-bottom:1px solid var(--color-cream-high);">
                                    <div class="col-6">
                                        <p class="elysian-label-sm text-muted-soft mb-1">CHECK-IN</p>
                                        <p class="elysian-body-lg fw-bold mb-0"><?= date('M d, Y', strtotime($currentBooking['tanggal_checkin'])) ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="elysian-label-sm text-muted-soft mb-1">CHECK-OUT</p>
                                        <p class="elysian-body-lg fw-bold mb-0"><?= date('M d, Y', strtotime($currentBooking['tanggal_checkout'])) ?></p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <p class="elysian-label-sm text-muted-soft mb-0">TOTAL PAID</p>
                                    <p class="elysian-headline-sm mb-0"><?= formatRupiah($currentBooking['total_harga']) ?></p>
                                </div>
                            </div>
                            <div class="d-flex gap-3 mt-4">
                                <a href="<?= BASE_URL ?>pages/edit_booking.php?id=<?= (int)$currentBooking['id'] ?>" class="btn-elysian-secondary flex-fill text-center" style="padding:10px 4px; font-size:11px;">Modify Stay</a>
                                
                                <?php if ($currentBooking['review_id']): ?>
                                    <a href="<?= BASE_URL ?>pages/review.php?booking_id=<?= (int)$currentBooking['id'] ?>" class="btn-elysian-secondary flex-fill text-center" style="padding:10px 4px; font-size:11px; display:flex; align-items:center; justify-content:center; gap:2px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">edit</span>Edit Review
                                    </a>
                                <?php elseif ($currentBooking['status'] === 'confirmed'): ?>
                                    <a href="<?= BASE_URL ?>pages/review.php?booking_id=<?= (int)$currentBooking['id'] ?>" class="btn-elysian-gold flex-fill text-center" style="padding:10px 4px; font-size:11px; display:flex; align-items:center; justify-content:center; gap:2px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">rate_review</span>Review Stay
                                    </a>
                                <?php endif; ?>

                                <form method="POST" action="" style="flex:1;margin:0;padding:0;">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="id" value="<?= (int)$currentBooking['id'] ?>">
                                    <button type="submit" class="btn-elysian-danger w-full" style="padding:10px 4px; font-size:11px;"
                                        onclick="return confirm('Cancel this reservation? This action cannot be undone.')">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            <?php else: ?>
            <div class="mb-5 p-5 text-center" style="background:#fff;border:1px solid var(--color-cream-high);">
                <span class="material-symbols-outlined" style="font-size:48px;color:var(--color-outline-var);">hotel</span>
                <p class="elysian-headline-sm text-muted-soft mt-3">No active reservations</p>
                <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-primary mt-3">Browse Rooms</a>
            </div>
            <?php endif; ?>

            <!-- Past Bookings -->
            <?php if (!empty($pastBookings)): ?>
            <section>
                <h2 class="elysian-headline-sm mb-4">Past Experiences</h2>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($pastBookings as $pb): ?>
                    <?php $pbImg = getRoomImageSrc($pb['gambar']); ?>
                    <div class="booking-history-row">
                        <img src="<?= htmlspecialchars($pbImg) ?>" alt="<?= htmlspecialchars($pb['nama_kamar']) ?>" class="booking-history-img">
                        <div class="flex-grow-1">
                            <p class="elysian-label-sm text-muted-soft mb-1"><?= date('M Y', strtotime($pb['tanggal_checkin'])) ?></p>
                            <h4 class="elysian-body-md fw-semibold mb-0"><?= htmlspecialchars($pb['nama_kamar']) ?></h4>
                            <p class="elysian-body-sm text-muted-soft mb-0"><?= date('M d', strtotime($pb['tanggal_checkin'])) ?> — <?= date('M d, Y', strtotime($pb['tanggal_checkout'])) ?></p>
                            
                            <?php if ($pb['review_id']): ?>
                                <div class="mt-1 d-flex align-items-center gap-1 text-gold">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <span class="material-symbols-outlined" style="font-size:16px; font-variation-settings:'FILL' <?= $star <= $pb['review_rating'] ? '1' : '0' ?>;">star</span>
                                    <?php endfor; ?>
                                    <span class="elysian-body-sm text-muted-soft ms-1" style="font-size:12px; font-style:italic;">"<?= htmlspecialchars(substr($pb['review_comment'], 0, 40)) ?><?= strlen($pb['review_comment']) > 40 ? '...' : '' ?>"</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <p class="elysian-body-md fw-semibold mb-1"><?= formatRupiah($pb['total_harga']) ?></p>
                            <span class="status-badge status-<?= $pb['status'] ?>"><?= ucfirst($pb['status']) ?></span>
                        </div>
                        
                        <div class="d-flex flex-column gap-1">
                            <?php if (in_array($pb['status'], ['pending','confirmed'])): ?>
                                <a href="<?= BASE_URL ?>pages/edit_booking.php?id=<?= (int)$pb['id'] ?>" class="btn-elysian-secondary" style="padding:4px 12px;font-size:11px;">Edit</a>
                                <form method="POST" action="" style="margin:0;padding:0;">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="id" value="<?= (int)$pb['id'] ?>">
                                    <button type="submit" class="btn-elysian-danger" style="padding:4px 12px;font-size:11px;width:100%;"
                                        onclick="return confirm('Cancel this booking?')">Cancel</button>
                                </form>
                            <?php else: ?>
                                <?php if ($pb['review_id']): ?>
                                    <a href="<?= BASE_URL ?>pages/review.php?booking_id=<?= (int)$pb['id'] ?>" class="btn-elysian-secondary" style="padding:6px 12px;font-size:11px;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">edit</span>Edit Review
                                    </a>
                                <?php elseif ($pb['status'] === 'completed'): ?>
                                    <a href="<?= BASE_URL ?>pages/review.php?booking_id=<?= (int)$pb['id'] ?>" class="btn-elysian-gold" style="padding:6px 12px;font-size:11px;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                                        <span class="material-symbols-outlined" style="font-size:14px;">rate_review</span>Review Stay
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Admin: All bookings link -->
            <?php if (isAdmin()): ?>
            <div class="mt-5 p-4" style="background:var(--color-cream-low);border:1px solid var(--color-cream-high);">
                <p class="elysian-label-sm text-gold mb-2">ADMIN PANEL</p>
                <p class="elysian-body-sm text-muted-soft mb-3">Manage all hotel rooms and reservations.</p>
                <a href="<?= BASE_URL ?>pages/tambah_kamar.php" class="btn-elysian-primary me-2">+ Add Room</a>
                <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-secondary">View All Rooms</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Profile -->
        <aside class="col-12 col-lg-4">
            <!-- Profile Card -->
            <div class="dashboard-card p-4 mb-4">
                <h3 class="elysian-headline-sm mb-4">Your Profile</h3>

                <?php if ($profileMsg):    ?><div class="alert-elysian success mb-4"><?= htmlspecialchars($profileMsg) ?></div><?php endif; ?>
                <?php if ($profileError):  ?><div class="alert-elysian error mb-4"><?= htmlspecialchars($profileError) ?></div><?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="elysian-form-group">
                        <input type="text" id="full_name" name="full_name" class="elysian-form-control"
                               value="<?= htmlspecialchars($userProfile['full_name']) ?>"
                               data-validate="required" data-error-msg="Full name is required.">
                        <label for="full_name" class="elysian-form-label">Full Name</label>
                    </div>
                    <div class="elysian-form-group">
                        <input type="email" id="email" name="email" class="elysian-form-control"
                               value="<?= htmlspecialchars($userProfile['email']) ?>"
                               data-validate="email">
                        <label for="email" class="elysian-form-label">Email Address</label>
                    </div>
                    <div class="elysian-form-group">
                        <input type="text" id="phone" name="phone" class="elysian-form-control"
                               value="<?= htmlspecialchars($userProfile['phone'] ?? '') ?>"
                               placeholder="+62 ...">
                        <label for="phone" class="elysian-form-label">Contact Number</label>
                    </div>
                    <button type="submit" class="btn-elysian-primary w-full mt-3">Update Details</button>
                </form>

                <div class="mt-4 pt-4" style="border-top:1px solid var(--color-cream-high);">
                    <p class="elysian-label-sm text-muted-soft mb-2">PAYMENT METHOD</p>
                    <div class="d-flex align-items-center gap-3 p-3" style="border:1px solid var(--color-cream-high);">
                        <span class="material-symbols-outlined">credit_card</span>
                        <div class="flex-grow-1">
                            <p class="elysian-body-sm fw-bold mb-0">VISA ending in 4421</p>
                            <p class="mb-0" style="font-size:10px;color:var(--color-on-surface-v);">EXPIRES 08/26</p>
                        </div>
                        <span class="material-symbols-outlined text-gold" style="cursor:pointer;">edit</span>
                    </div>
                </div>
            </div>

            <!-- ── BILLING SUMMARY ── -->
            <div class="dashboard-card p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 class="elysian-headline-sm mb-0">Billing Summary</h3>
                    <span class="material-symbols-outlined text-gold">receipt_long</span>
                </div>

                <?php if (!empty($activeBillings)): ?>
                <!-- List of all active bookings -->
                <div class="d-flex flex-column gap-0 mb-3" style="border:1px solid var(--color-cream-high);">
                    <?php foreach ($activeBillings as $i => $ab): ?>
                    <div class="d-flex justify-content-between align-items-start px-3 py-3"
                         style="<?= $i > 0 ? 'border-top:1px solid var(--color-cream-high);' : '' ?>">
                        <div style="flex:1;min-width:0;">
                            <p class="elysian-body-sm fw-semibold mb-0" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($ab['nama_kamar']) ?>
                            </p>
                            <p class="mb-0" style="font-size:11px;color:var(--color-on-surface-v);">
                                <?= date('d M', strtotime($ab['tanggal_checkin'])) ?> &ndash; <?= date('d M Y', strtotime($ab['tanggal_checkout'])) ?>
                            </p>
                        </div>
                        <div class="text-end ms-2 flex-shrink-0">
                            <p class="elysian-body-sm fw-semibold mb-0 text-gold"><?= formatRupiah($ab['total_harga']) ?></p>
                            <span class="status-badge status-<?= $ab['status'] ?>" style="font-size:9px;padding:2px 8px;"><?= ucfirst($ab['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grand total -->
                <div class="d-flex justify-content-between align-items-center py-3"
                     style="border-top:2px solid var(--color-primary);">
                    <div>
                        <p class="elysian-label-sm mb-0">TOTAL AMOUNT DUE</p>
                        <p class="mb-0" style="font-size:11px;color:var(--color-on-surface-v);"><?= count($activeBillings) ?> active reservation(s)</p>
                    </div>
                    <p class="elysian-headline-sm text-gold mb-0 ms-2"><?= formatRupiah($totalPending) ?></p>
                </div>

                <?php else: ?>
                <div class="text-center py-4">
                    <span class="material-symbols-outlined" style="font-size:36px;color:var(--color-outline-var);">check_circle</span>
                    <p class="elysian-body-sm text-muted-soft mt-2 mb-0">No active bills.</p>
                </div>
                <?php endif; ?>

                <?php if ($totalCompleted > 0): ?>
                <!-- Payment history -->
                <div class="d-flex justify-content-between align-items-center pt-3 mt-3"
                     style="border-top:1px solid var(--color-cream-high);">
                    <p class="elysian-label-sm text-muted-soft mb-0">TOTAL PAID</p>
                    <p class="elysian-body-sm fw-semibold mb-0"><?= formatRupiah($totalCompleted) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Loyalty Card -->
            <div class="p-4" style="background:var(--color-primary);color:#fff;">
                <h3 class="elysian-headline-sm text-gold mb-3">Elysian Circle</h3>
                <p class="elysian-body-sm mb-4" style="opacity:0.8;">You are 1,200 points away from Platinum status and private jet transfers.</p>
                <div class="loyalty-bar mb-4">
                    <div class="loyalty-bar-fill" style="width:72%;"></div>
                </div>
                <button type="button" style="display:block;width:100%;background:transparent;color:#fed488;font-family:'Manrope',sans-serif;font-size:11px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;padding:12px 20px;border:1px solid #775a19;border-radius:0;cursor:pointer;text-align:center;transition:all 0.25s ease;"
                    onmouseover="this.style.background='#775a19';this.style.color='#fff';"
                    onmouseout="this.style.background='transparent';this.style.color='#fed488';">VIEW BENEFITS</button>
            </div>
        </aside>
    </div>

</div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
