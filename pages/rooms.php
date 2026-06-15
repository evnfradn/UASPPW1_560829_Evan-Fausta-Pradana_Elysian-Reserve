<?php

require_once __DIR__ . '/../includes/config.php';


// ── SEARCH & FILTER ──────────────────────────────────
$search   = trim($_GET['q']    ?? '');
$tipeFilter = $_GET['tipe']   ?? '';
$kapFilter  = (int)($_GET['kapasitas'] ?? 0);
$perPage  = 6;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
$types  = '';

if ($search !== '') {
    $where   .= ' AND (nama_kamar LIKE ? OR tipe LIKE ? OR deskripsi LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
    $types   .= 'sss';
}
if ($tipeFilter !== '') {
    $where   .= ' AND tipe = ?';
    $params[] = $tipeFilter;
    $types   .= 's';
}
if ($kapFilter > 0) {
    $where   .= ' AND kapasitas >= ?';
    $params[] = $kapFilter;
    $types   .= 'i';
}

// Count total
$countStmt = $conn->prepare("SELECT COUNT(*) FROM rooms WHERE $where");
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_row()[0];
$totalPages = max(1, ceil($totalRows / $perPage));
$countStmt->close();

// Fetch rooms
$limitParams = array_merge($params, [$perPage, $offset]);
$limitTypes  = $types . 'ii';
$stmt = $conn->prepare("SELECT * FROM rooms WHERE $where ORDER BY status ASC, harga_per_malam DESC LIMIT ? OFFSET ?");
if ($limitTypes) $stmt->bind_param($limitTypes, ...$limitParams);
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Flash messages
$successMsg = $_SESSION['flash_success'] ?? '';
$errorMsg   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$pageTitle   = 'Rooms & Suites';
$currentPage = 'rooms';
require_once __DIR__ . '/../includes/header.php';
?>

<main>
<!-- ── PAGE HEADER ──────────────────────────────── -->
<header class="section-gap-sm section-white">
    <div class="section-container">
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4 pb-4" style="border-bottom:1px solid var(--color-cream-high);">
            <div>
                <p class="elysian-label-sm text-gold mb-2">EXQUISITE SANCTUARIES</p>
                <h1 class="elysian-headline-lg mb-3">Rooms &amp; Suites</h1>
                <p class="elysian-body-lg text-muted-soft mb-0">Every residence is a masterwork of tactile minimalism.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="?tipe=" class="btn-elysian-secondary <?= $tipeFilter === '' ? 'active' : '' ?>" style="<?= $tipeFilter === '' ? 'background:var(--color-primary);color:#fff!important;' : '' ?>">All</a>
                <a href="?tipe=Suite"     class="btn-elysian-secondary" style="<?= $tipeFilter === 'Suite'     ? 'background:var(--color-primary);color:#fff!important;' : '' ?>">Suites</a>
                <a href="?tipe=Villa"     class="btn-elysian-secondary" style="<?= $tipeFilter === 'Villa'     ? 'background:var(--color-primary);color:#fff!important;' : '' ?>">Villas</a>
                <a href="?tipe=Penthouse" class="btn-elysian-secondary" style="<?= $tipeFilter === 'Penthouse' ? 'background:var(--color-primary);color:#fff!important;' : '' ?>">Penthouses</a>
            </div>
        </div>
    </div>
</header>

<!-- ── FLASH MESSAGES ───────────────────────────── -->
<?php if ($successMsg): ?>
<div class="section-container mt-3"><div class="alert-elysian success"><?= htmlspecialchars($successMsg) ?></div></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="section-container mt-3"><div class="alert-elysian error"><?= htmlspecialchars($errorMsg) ?></div></div>
<?php endif; ?>

<!-- ── MAIN CONTENT: SIDEBAR + GRID ─────────────── -->
<section class="section-gap-sm section-white">
    <div class="section-container">
        <div class="row g-5">

            <!-- FILTER SIDEBAR -->
            <aside class="col-12 col-lg-3">
                <div class="filter-sidebar">

                    <!-- Search -->
                    <form method="GET" action="" class="mb-5" id="searchForm">
                        <div class="filter-title">Search</div>
                        <div class="search-wrapper">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" id="searchInput" name="q"
                                   class="search-input"
                                   placeholder="Room name..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <input type="hidden" name="tipe" value="<?= htmlspecialchars($tipeFilter) ?>">
                        <input type="hidden" name="kapasitas" value="<?= $kapFilter ?>">
                    </form>

                    <!-- Room Type Filter -->
                    <div class="mb-5">
                        <div class="filter-title">Room Type</div>
                        <?php foreach (['Suite','Villa','Penthouse','Pavilion','Loft'] as $t): ?>
                        <label class="filter-check">
                            <input type="radio" name="tipe_radio" <?= $tipeFilter === $t ? 'checked' : '' ?>
                                   onchange="document.querySelector('[name=tipe]').value='<?= $t ?>'; document.getElementById('searchForm').submit();">
                            <span><?= $t ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php if ($tipeFilter): ?>
                        <a href="?" class="elysian-label-sm text-gold d-block mt-2">× Clear filter</a>
                        <?php endif; ?>
                    </div>

                    <!-- Capacity Filter -->
                    <div class="mb-5">
                        <div class="filter-title">Capacity</div>
                        <?php foreach ([2 => 'Up to 2 Guests', 4 => 'Up to 4 Guests', 6 => '6+ Guests'] as $cap => $label): ?>
                        <label class="filter-check">
                            <input type="radio" name="kap_radio" <?= $kapFilter === $cap ? 'checked' : '' ?>
                                   onchange="document.querySelector('[name=kapasitas]').value=<?= $cap ?>; document.getElementById('searchForm').submit();">
                            <span><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Admin: Add Room Button -->
                    <?php if (isAdmin()): ?>
                    <div class="pt-4" style="border-top:1px solid var(--color-cream-high);">
                        <div class="filter-title">Admin</div>
                        <a href="<?= BASE_URL ?>pages/tambah_kamar.php" class="btn-elysian-primary w-full text-center">+ Add New Room</a>
                    </div>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- ROOM CARDS GRID -->
            <div class="col-12 col-lg-9">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="elysian-body-sm text-muted-soft mb-0">
                        Showing <strong><?= count($rooms) ?></strong> of <strong><?= $totalRows ?></strong> residences
                        <?= $search ? 'for "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>
                    </p>
                </div>

                <?php if (empty($rooms)): ?>
                <div class="text-center py-5">
                    <span class="material-symbols-outlined" style="font-size:56px;color:var(--color-outline-var);">hotel</span>
                    <p class="elysian-headline-sm text-muted-soft mt-3">No rooms found</p>
                    <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-secondary mt-3">Clear Filters</a>
                </div>
                <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($rooms as $room): ?>
                    <?php $imgSrc = getRoomImageSrc($room['gambar']); ?>
                    <div class="col-12 col-sm-6 reveal">
                        <div class="position-relative">
                            <a href="<?= BASE_URL ?>pages/booking.php?room_id=<?= (int)$room['id'] ?>" class="room-card">
                                <div class="room-card-img-wrap">
                                    <img src="<?= htmlspecialchars($imgSrc) ?>"
                                         alt="<?= htmlspecialchars($room['nama_kamar']) ?>">
                                    <div class="room-badge <?= htmlspecialchars($room['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($room['status'])) ?>
                                    </div>
                                </div>
                                <div class="room-card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h2 class="elysian-headline-sm mb-0" style="font-size:20px;"><?= htmlspecialchars($room['nama_kamar']) ?></h2>
                                        <div class="text-end ms-2 flex-shrink-0">
                                            <div class="room-card-price" style="font-size:20px;"><?= formatRupiah($room['harga_per_malam']) ?></div>
                                            <div class="elysian-label-sm text-muted-soft">/ night</div>
                                        </div>
                                    </div>
                                    <p class="elysian-body-sm text-muted-soft mb-0" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        <?= htmlspecialchars($room['deskripsi']) ?>
                                    </p>
                                    <div class="room-card-meta">
                                        <div class="d-flex gap-3">
                                            <span class="room-meta-item">
                                                <span class="material-symbols-outlined">group</span>
                                                <?= (int)$room['kapasitas'] ?> Guests
                                            </span>
                                            <span class="room-meta-item">
                                                <span class="material-symbols-outlined">hotel_class</span>
                                                <?= htmlspecialchars($room['tipe']) ?>
                                            </span>
                                        </div>
                                        <span class="elysian-label-sm text-gold">VIEW DETAILS →</span>
                                    </div>
                                </div>
                            </a>

                            <!-- Admin controls -->
                            <?php if (isAdmin()): ?>
                            <div class="d-flex gap-2 mt-2">
                                <a href="<?= BASE_URL ?>pages/edit_kamar.php?id=<?= (int)$room['id'] ?>" class="btn-elysian-secondary" style="padding:6px 16px;font-size:11px;">Edit</a>
                                <form method="POST" action="<?= BASE_URL ?>pages/hapus_kamar.php" style="margin:0;padding:0;display:inline;">
                                    <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                                    <button type="submit" class="btn-elysian-danger" style="padding:6px 16px;font-size:11px;"
                                        onclick="return confirm('Delete room \"<?= htmlspecialchars(addslashes($room['nama_kamar'])) ?>\"? This action cannot be undone.')">Delete</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-5">
                    <nav class="elysian-pagination" aria-label="Room pagination">
                        <!-- Prev -->
                        <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&tipe=<?= urlencode($tipeFilter) ?>&kapasitas=<?= $kapFilter ?>"
                           class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                            <span class="material-symbols-outlined" style="font-size:18px;">chevron_left</span>
                        </a>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&tipe=<?= urlencode($tipeFilter) ?>&kapasitas=<?= $kapFilter ?>"
                           class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        <!-- Next -->
                        <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&tipe=<?= urlencode($tipeFilter) ?>&kapasitas=<?= $kapFilter ?>"
                           class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <span class="material-symbols-outlined" style="font-size:18px;">chevron_right</span>
                        </a>
                    </nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
