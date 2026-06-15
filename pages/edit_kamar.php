<?php

require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$roomId = (int)($_GET['id'] ?? 0);
if ($roomId <= 0) {
    header('Location: ' . BASE_URL . 'pages/rooms.php');
    exit;
}

// Fetch room
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
$stmt->bind_param('i', $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header('Location: ' . BASE_URL . 'pages/rooms.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaKamar = trim($_POST['nama_kamar']     ?? '');
    $tipe      = $_POST['tipe']              ?? '';
    $deskripsi = trim($_POST['deskripsi']     ?? '');
    $harga     = (float)($_POST['harga_per_malam'] ?? 0);
    $kapasitas = (int)($_POST['kapasitas']    ?? 1);
    $fasilitas = trim($_POST['fasilitas']     ?? '');
    $status    = $_POST['status']            ?? 'available';
    $gambar    = $room['gambar']; 

    if (strlen($namaKamar) < 2)             $errors[] = 'Room name must be at least 2 characters.';
    if (!in_array($tipe, ['Suite','Villa','Penthouse','Pavilion','Loft'])) $errors[] = 'Invalid room type.';
    if (strlen($deskripsi) < 10)            $errors[] = 'Description must be at least 10 characters.';
    if ($harga <= 0)                        $errors[] = 'Price must be greater than 0.';
    if ($kapasitas < 1)                     $errors[] = 'Capacity must be at least 1.';


    if (!empty($_FILES['gambar']['name'])) {
        $allowedTypes = ['image/jpeg','image/png','image/webp'];
        if (!in_array($_FILES['gambar']['type'], $allowedTypes)) {
            $errors[] = 'Main image format must be JPG, PNG, or WebP.';
        } elseif ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Main image size must be under 5MB.';
        } else {
            $ext    = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $newImg = 'room_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest   = __DIR__ . '/../assets/img/' . $newImg;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                $gambar = $newImg;
            }
        }
    }

    $gallery = $room['gallery']; 
    if (!empty($_FILES['gallery']['name'][0])) {
        $galleryImages = [];
        $allowedTypes = ['image/jpeg','image/png','image/webp'];
        $maxSize      = 5 * 1024 * 1024;

        foreach ($_FILES['gallery']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['gallery']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }
            if (!in_array($_FILES['gallery']['type'][$key], $allowedTypes)) {
                $errors[] = 'Preview image #' . ($key+1) . ' format must be JPG, PNG, or WebP.';
                break;
            }
            if ($_FILES['gallery']['size'][$key] > $maxSize) {
                $errors[] = 'Preview image #' . ($key+1) . ' size must be under 5MB.';
                break;
            }
            $ext    = pathinfo($_FILES['gallery']['name'][$key], PATHINFO_EXTENSION);
            $filename = 'room_gallery_' . time() . '_' . rand(100,999) . '_' . $key . '.' . $ext;
            $dest   = __DIR__ . '/../assets/img/' . $filename;
            if (move_uploaded_file($tmpName, $dest)) {
                $galleryImages[] = $filename;
            } else {
                $errors[] = 'Failed to upload preview image #' . ($key+1) . '.';
                break;
            }
        }
        if (empty($errors) && !empty($galleryImages)) {
            $gallery = implode(',', $galleryImages);
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE rooms SET nama_kamar=?, tipe=?, deskripsi=?, harga_per_malam=?, kapasitas=?, fasilitas=?, status=?, gambar=?, gallery=? WHERE id=?");
        $stmt->bind_param('sssdissssi', $namaKamar, $tipe, $deskripsi, $harga, $kapasitas, $fasilitas, $status, $gambar, $gallery, $roomId);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Room \"$namaKamar\" has been successfully updated.";
            $stmt->close();
            header('Location: ' . BASE_URL . 'pages/rooms.php');
            exit;
        } else {
            $errors[] = 'Failed to update room data.';
            $stmt->close();
        }
    }


    $room = array_merge($room, [
        'nama_kamar'      => $namaKamar, 'tipe' => $tipe,
        'deskripsi'       => $deskripsi, 'harga_per_malam' => $harga,
        'kapasitas'       => $kapasitas, 'fasilitas' => $fasilitas,
        'status'          => $status,
    ]);
}

$pageTitle   = 'Edit Room';
$currentPage = 'rooms';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="section-gap-sm section-cream" style="min-height:calc(100vh - 80px);">
<div class="section-container" style="max-width:760px;">

    <nav class="elysian-breadcrumb mb-4">
        <a href="<?= BASE_URL ?>pages/rooms.php">Rooms &amp; Suites</a>
        <span class="sep material-symbols-outlined" style="font-size:18px;">chevron_right</span>
        <span class="current">Edit Room</span>
    </nav>

    <div class="login-card mx-0" style="max-width:100%;">
        <div class="gold-line"></div>
        <p class="elysian-label-sm text-gold mb-2">ADMIN — EDIT ROOM</p>
        <h1 class="elysian-headline-md mb-5"><?= htmlspecialchars($room['nama_kamar']) ?></h1>

        <?php if (!empty($errors)): ?>
        <div class="alert-elysian error mb-4">
            <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form id="editRoomForm" method="POST" action="" enctype="multipart/form-data" novalidate>

            <div class="row g-4">
                <div class="col-12 col-sm-8">
                    <div class="elysian-input-wrap">
                        <label for="nama_kamar">Room Name *</label>
                        <input type="text" id="nama_kamar" name="nama_kamar" required
                               value="<?= htmlspecialchars($room['nama_kamar']) ?>">
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="elysian-input-wrap">
                        <label for="tipe">Room Type *</label>
                        <select id="tipe" name="tipe" required>
                            <?php foreach (['Suite','Villa','Penthouse','Pavilion','Loft'] as $t): ?>
                            <option value="<?= $t ?>" <?= $room['tipe'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="elysian-input-wrap">
                <label for="deskripsi">Description *</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" required><?= htmlspecialchars($room['deskripsi']) ?></textarea>
            </div>

            <div class="row g-4">
                <div class="col-12 col-sm-6">
                    <div class="elysian-input-wrap">
                        <label for="harga_per_malam">Price per Night (IDR) *</label>
                        <input type="number" id="harga_per_malam" name="harga_per_malam" required min="1"
                               value="<?= htmlspecialchars($room['harga_per_malam']) ?>"
                               data-validate="positive-number">
                    </div>
                </div>
                <div class="col-12 col-sm-3">
                    <div class="elysian-input-wrap">
                        <label for="kapasitas">Max Guests *</label>
                        <input type="number" id="kapasitas" name="kapasitas" required min="1" max="20"
                               value="<?= htmlspecialchars($room['kapasitas']) ?>">
                    </div>
                </div>
                <div class="col-12 col-sm-3">
                    <div class="elysian-input-wrap">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (['available','booked','maintenance'] as $s): ?>
                            <option value="<?= $s ?>" <?= $room['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="elysian-input-wrap">
                <label for="fasilitas">Amenities (comma-separated)</label>
                <input type="text" id="fasilitas" name="fasilitas"
                       value="<?= htmlspecialchars($room['fasilitas'] ?? '') ?>">
            </div>

            <div class="row g-4 mt-2">
                <div class="col-12 col-md-6">
                    <div class="elysian-input-wrap">
                        <label for="gambar">Replace Main Image (optional)</label>
                        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp"
                               style="border-bottom:1px solid var(--color-outline-var);padding:8px 0;background:transparent;width:100%;font-size:14px;">
                        <div class="mt-3">
                            <p class="elysian-label-sm text-gold mb-1">Current/New Main Image:</p>
                            <img id="mainImagePreview" src="<?= htmlspecialchars(getRoomImageSrc($room['gambar'])) ?>" alt="Main Image" 
                                 style="max-width:100%;height:180px;object-fit:cover;border:1px solid var(--color-gold);border-radius:4px;">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="elysian-input-wrap">
                        <label for="gallery">Replace Gallery/Preview Images (optional)</label>
                        <input type="file" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple
                               style="border-bottom:1px solid var(--color-outline-var);padding:8px 0;background:transparent;width:100%;font-size:14px;">
                        <div class="mt-3">
                            <p class="elysian-label-sm text-gold mb-1">Current/New Gallery Images:</p>
                            <div id="galleryPreviews" class="d-flex flex-wrap gap-2">
                                <?php
                                if (!empty($room['gallery'])) {
                                    $galleryFiles = array_filter(array_map('trim', explode(',', $room['gallery'])));
                                    foreach ($galleryFiles as $gfile) {
                                        echo '<img src="' . htmlspecialchars(getRoomImageSrc($gfile)) . '" style="width:80px;height:80px;object-fit:cover;border:1px solid var(--color-cream-high);border-radius:4px;">';
                                    }
                                } else {
                                    echo '<small class="text-muted-soft">No custom gallery uploaded yet.</small>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn-elysian-primary flex-fill">Update Room</button>
                <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-secondary flex-fill text-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainInput = document.getElementById('gambar');
    const mainPreview = document.getElementById('mainImagePreview');

    const galleryInput = document.getElementById('gallery');
    const galleryPreviews = document.getElementById('galleryPreviews');

    // Main image preview
    mainInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                mainPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Gallery images preview
    galleryInput.addEventListener('change', function() {
        galleryPreviews.innerHTML = '';
        if (this.files && this.files.length > 0) {
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.border = '1px solid var(--color-cream-high)';
                    img.style.borderRadius = '4px';
                    galleryPreviews.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    });
});
</script>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
