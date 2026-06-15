<?php

require_once __DIR__ . '/includes/config.php';

$featuredRooms = [];
$stmt = $conn->prepare("SELECT id, nama_kamar, tipe, deskripsi, harga_per_malam, kapasitas, status, gambar FROM rooms WHERE status = 'available' ORDER BY harga_per_malam DESC LIMIT 3");
$stmt->execute();
$featuredRooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── FETCH TESTIMONIALS (rating >= 4) ────────────────
$testStmt = $conn->prepare("
    SELECT r.*, u.full_name, rm.nama_kamar
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.rating >= 4
    ORDER BY r.created_at DESC
    LIMIT 3
");
$testStmt->execute();
$testimonials = $testStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$testStmt->close();


$pageTitle   = 'Understated Opulence';
$currentPage = 'home';


$bannerFiles = glob(__DIR__ . '/assets/img/Dashboard/Banner/*');
$bannerImg = !empty($bannerFiles) ? BASE_URL . 'assets/img/Dashboard/Banner/' . rawurlencode(basename($bannerFiles[0])) : '';

$spaFiles = glob(__DIR__ . '/assets/img/Dashboard/Holistic-Spa/*');
$spaImg = !empty($spaFiles) ? BASE_URL . 'assets/img/Dashboard/Holistic-Spa/' . rawurlencode(basename($spaFiles[0])) : '';

$diningFiles = glob(__DIR__ . '/assets/img/Dashboard/Fine-Dining/*');
$diningImg = !empty($diningFiles) ? BASE_URL . 'assets/img/Dashboard/Fine-Dining/' . rawurlencode(basename($diningFiles[0])) : '';

$charterFiles = glob(__DIR__ . '/assets/img/Dashboard/Private-charters/*');
$charterImg = !empty($charterFiles) ? BASE_URL . 'assets/img/Dashboard/Private-charters/' . rawurlencode(basename($charterFiles[0])) : '';

require_once __DIR__ . '/includes/header.php';
?>

<main>
<!-- ══════════════════════════════════════════
     HERO SECTION
══════════════════════════════════════════ -->
<section class="hero-section reveal" id="hero">
    <img class="hero-bg"
         src="<?= htmlspecialchars($bannerImg) ?>"
         alt="Elysian Reserve — Luxury tropical resort at dusk">
    <div class="hero-gradient"></div>

    <div class="hero-content">
        <h1 class="elysian-display text-white mb-3" style="max-width:640px;">
            Elegance<br>Reimagined.
        </h1>
        <p class="elysian-body-lg text-white mb-5" style="max-width:480px;opacity:0.9;">
            Experience a sanctuary of precision and tranquility where every detail is curated for the discerning traveler.
        </p>

        <!-- Booking Bar -->
        <div class="booking-bar d-flex flex-column flex-lg-row align-items-lg-center gap-2" style="max-width:860px;">
            <div class="flex-grow-1 d-grid d-lg-flex gap-3 p-3">
                <div class="booking-bar-field flex-fill">
                    <label for="hero_checkin">Check-In</label>
                    <input type="date" id="hero_checkin" placeholder="Select Date">
                </div>
                <div class="booking-bar-field flex-fill">
                    <label for="hero_checkout">Check-Out</label>
                    <input type="date" id="hero_checkout" placeholder="Select Date">
                </div>
                <div class="booking-bar-field flex-fill">
                    <label for="hero_guests">Guests</label>
                    <select id="hero_guests">
                        <option>2 Adults, 0 Children</option>
                        <option>1 Adult</option>
                        <option>2 Adults, 1 Child</option>
                        <option>Family (4+)</option>
                    </select>
                </div>
            </div>
            <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-primary" style="white-space:nowrap;padding:18px 36px;">
                Search Rooms
            </a>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     STATS & PHILOSOPHY
══════════════════════════════════════════ -->
<section class="section-gap section-cream" id="about">
    <div class="section-container">
        <div class="row g-5 align-items-center">
            <!-- Left: Philosophy -->
            <div class="col-lg-6 reveal">
                <div class="gold-line"></div>
                <p class="elysian-label-sm text-gold mb-3">OUR PHILOSOPHY</p>
                <h2 class="elysian-headline-lg mb-4">The Art of Quiet<br>Luxury and Precision</h2>
                <p class="elysian-body-lg text-muted-soft mb-5">
                    At Elysian Reserve, we believe true luxury doesn't shout. It whispers through flawless service, tactile materials, and the luxury of unhurried time.
                </p>
                <!-- Stats Row -->
                <div class="d-flex gap-5">
                    <div>
                        <div class="stat-number">15</div>
                        <p class="elysian-label-sm text-muted-soft mb-0">Private Villas</p>
                    </div>
                    <div>
                        <div class="stat-number">3:1</div>
                        <p class="elysian-label-sm text-muted-soft mb-0">Staff Ratio</p>
                    </div>
                    <div>
                        <div class="stat-number">24h</div>
                        <p class="elysian-label-sm text-muted-soft mb-0">Butler Care</p>
                    </div>
                </div>
            </div>

            <!-- Right: Service Cards -->
            <div class="col-lg-6 reveal">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="service-card">
                            <span class="material-symbols-outlined service-icon">spa</span>
                            <h3 class="elysian-headline-sm mb-2">Wellness Sanctuary</h3>
                            <p class="elysian-body-sm text-muted-soft mb-0">Bespoke holistic treatments designed to restore your equilibrium.</p>
                        </div>
                    </div>
                    <div class="col-6 mt-4">
                        <div class="service-card">
                            <span class="material-symbols-outlined service-icon">restaurant</span>
                            <h3 class="elysian-headline-sm mb-2">Gastronomy</h3>
                            <p class="elysian-body-sm text-muted-soft mb-0">Hyper-local ingredients met with world-class culinary precision.</p>
                        </div>
                    </div>
                    <div class="col-6 mt-n4">
                        <div class="service-card">
                            <span class="material-symbols-outlined service-icon">cleaning_services</span>
                            <h3 class="elysian-headline-sm mb-2">Private Concierge</h3>
                            <p class="elysian-body-sm text-muted-soft mb-0">Every whim anticipated and executed with absolute discretion.</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="service-card">
                            <span class="material-symbols-outlined service-icon">explore</span>
                            <h3 class="elysian-headline-sm mb-2">Curated Tours</h3>
                            <p class="elysian-body-sm text-muted-soft mb-0">Uncover hidden gems through our exclusive, private local routes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     FEATURED ACCOMMODATIONS
══════════════════════════════════════════ -->
<section class="section-gap section-white" id="amenities">
    <div class="section-container">
        <div class="d-flex justify-content-between align-items-end mb-5 reveal">
            <div>
                <p class="elysian-label-sm text-gold mb-2">HAND-PICKED FOR YOU</p>
                <h2 class="elysian-headline-lg mb-0">Featured Accommodations</h2>
            </div>
            <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-secondary d-none d-md-inline-block">
                View All Rooms
            </a>
        </div>

        <?php if (empty($featuredRooms)): ?>
        <div class="alert-elysian info text-center py-5">
            <p class="elysian-body-md mb-0">No rooms available at the moment. Please check back soon.</p>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredRooms as $i => $room): ?>
            <?php $imgSrc = getRoomImageSrc($room['gambar']); ?>
            <div class="col-12 <?= $i === 0 ? 'col-md-7' : 'col-md-5' ?> reveal">
                <a href="<?= BASE_URL ?>pages/booking.php?room_id=<?= (int)$room['id'] ?>" class="room-card">
                    <div class="room-card-img-wrap">
                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                             alt="<?= htmlspecialchars($room['nama_kamar']) ?>"
                             style="height:<?= $i === 0 ? '420px' : '280px' ?>;">
                        <div class="room-badge available"><?= htmlspecialchars($room['tipe']) ?></div>
                    </div>
                    <div class="room-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h3 class="elysian-headline-sm mb-0"><?= htmlspecialchars($room['nama_kamar']) ?></h3>
                            <div class="text-end ms-3">
                                <div class="room-card-price"><?= formatRupiah($room['harga_per_malam']) ?></div>
                                <div class="elysian-label-sm text-muted-soft">/ night</div>
                            </div>
                        </div>
                        <p class="elysian-body-sm text-muted-soft mb-0" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?= htmlspecialchars($room['deskripsi']) ?>
                        </p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mobile CTA -->
        <div class="text-center mt-5 d-md-none reveal">
            <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-primary">View All Rooms</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SERVICES (circles)
══════════════════════════════════════════ -->
<section class="section-gap section-cream" id="services">
    <div class="section-container">
        <h2 class="elysian-headline-lg text-center mb-5 reveal">Unrivaled Curated Services</h2>
        <div class="row g-4 text-center">
            <div class="col-12 col-md-4 reveal">
                <div class="service-circle-wrap">
                    <img src="<?= htmlspecialchars($spaImg) ?>" alt="Holistic Spa">
                </div>
                <h3 class="elysian-headline-sm mb-2">Holistic Spa</h3>
                <p class="elysian-body-sm text-muted-soft">Signature therapies using organic botanicals and ancient healing techniques.</p>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="service-circle-wrap">
                    <img src="<?= htmlspecialchars($diningImg) ?>" alt="Fine Dining">
                </div>
                <h3 class="elysian-headline-sm mb-2">Fine Dining</h3>
                <p class="elysian-body-sm text-muted-soft">A rotating seasonal menu curated by Michelin-starred culinary visionaries.</p>
            </div>
            <div class="col-12 col-md-4 reveal">
                <div class="service-circle-wrap">
                    <img src="<?= htmlspecialchars($charterImg) ?>" alt="Private Charters">
                </div>
                <h3 class="elysian-headline-sm mb-2">Private Charters</h3>
                <p class="elysian-body-sm text-muted-soft">Seamless transfers and bespoke air travel to any global destination.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     GUEST TESTIMONIALS
══════════════════════════════════════════ -->
<section class="section-gap section-white" id="testimonials">
    <div class="section-container">
        <div class="text-center mb-5 reveal">
            <p class="elysian-label-sm text-gold mb-2">GUEST STORIES</p>
            <h2 class="elysian-headline-lg mb-0">Testimonials</h2>
        </div>

        <?php if (empty($testimonials)): ?>
        <div class="alert-elysian info text-center py-4">
            <p class="elysian-body-md mb-0">No guest stories shared yet.</p>
        </div>
        <?php else: ?>
        <div class="row g-4 justify-content-center">
            <?php foreach ($testimonials as $t): ?>
            <div class="col-12 col-md-4 reveal">
                <div class="p-5 h-100 d-flex flex-column justify-content-between" style="background:var(--color-cream); border:1px solid var(--color-cream-high); box-shadow:var(--shadow-soft);">
                    <div>
                        <div class="d-flex gap-1 text-gold mb-3">
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' <?= $star <= $t['rating'] ? '1' : '0' ?>;">star</span>
                            <?php endfor; ?>
                        </div>
                        <p class="elysian-body-md text-muted-soft mb-4" style="font-style:italic; line-height:1.7;">
                            "<?= htmlspecialchars($t['komentar']) ?>"
                        </p>
                    </div>
                    <div class="pt-3" style="border-top:1px solid var(--color-cream-high);">
                        <h4 class="elysian-body-sm fw-bold mb-0"><?= htmlspecialchars($t['full_name']) ?></h4>
                        <p class="mb-0 text-gold" style="font-size:11px; font-weight:600; letter-spacing:0.05em; text-transform:uppercase;"><?= htmlspecialchars($t['nama_kamar']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════
     NEWSLETTER / CTA
══════════════════════════════════════════ -->
<section class="section-gap" id="newsletter">
    <div class="section-container">
        <div class="cta-section reveal">
            <div class="text-center" style="max-width:560px;margin:0 auto;position:relative;z-index:1;">
                <p class="elysian-label-sm text-gold mb-3">EXCLUSIVE ACCESS</p>
                <h2 class="elysian-headline-lg text-white mb-3">Join the Reserve</h2>
                <p class="elysian-body-lg text-white mb-5" style="opacity:0.7;">
                    Receive exclusive access to seasonal suites and private retreat invitations.
                </p>
                <form class="d-flex flex-column flex-sm-row gap-3" onsubmit="return false;">
                    <input type="email" class="cta-email-input flex-grow-1" placeholder="Email Address">
                    <button type="button" class="btn-elysian-gold" style="white-space:nowrap;">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
