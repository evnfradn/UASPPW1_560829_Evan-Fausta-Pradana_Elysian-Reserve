<?php

require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaKamar  = trim($_POST['nama_kamar']     ?? '');
    $tipe       = $_POST['tipe']              ?? '';
    $deskripsi  = trim($_POST['deskripsi']     ?? '');
    $harga      = (float)($_POST['harga_per_malam'] ?? 0);
    $kapasitas  = (int)($_POST['kapasitas']    ?? 1);
    $fasilitas  = trim($_POST['fasilitas']     ?? '');
    $status     = $_POST['status']            ?? 'available';
    $gambar     = 'default_room.jpg';

 
    if (strlen($namaKamar) < 2)             $errors[] = 'Room name must be at least 2 characters.';
    if (!in_array($tipe, ['Suite','Villa','Penthouse','Pavilion','Loft'])) $errors[] = 'Invalid room type.';
    if (strlen($deskripsi) < 10)            $errors[] = 'Description must be at least 10 characters.';
    if ($harga <= 0)                        $errors[] = 'Price must be greater than 0.';
    if ($kapasitas < 1 || $kapasitas > 20) $errors[] = 'Capacity must be between 1 and 20.';


    if (!empty($_FILES['gambar']['name'])) {
        $allowedTypes = ['image/jpeg','image/png','image/webp'];
        $maxSize      = 5 * 1024 * 1024; 

        if (!in_array($_FILES['gambar']['type'], $allowedTypes)) {
            $errors[] = 'Main image format must be JPG, PNG, or WebP.';
        } elseif ($_FILES['gambar']['size'] > $maxSize) {
            $errors[] = 'Main image size must be under 5MB.';
        } else {
            $ext    = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar = 'room_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest   = __DIR__ . '/../assets/img/' . $gambar;
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                $errors[] = 'Failed to upload main image.';
                $gambar   = 'default_room.jpg';
            }
        }
    }

    $galleryStr = null;
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
            $galleryStr = implode(',', $galleryImages);
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO rooms (nama_kamar, tipe, deskripsi, harga_per_malam, kapasitas, fasilitas, status, gambar, gallery) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssdissss', $namaKamar, $tipe, $deskripsi, $harga, $kapasitas, $fasilitas, $status, $gambar, $galleryStr);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Room \"$namaKamar\" successfully added!";
            $stmt->close();
            header('Location: ' . BASE_URL . 'pages/rooms.php');
            exit;
        } else {
            $errors[] = 'Failed to save room to database.';
            $stmt->close();
        }
    }
}

$pageTitle   = 'Add New Room';
$currentPage = 'rooms';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="section-gap-sm section-cream" style="min-height:calc(100vh - 80px);">
<div class="section-container" style="max-width:760px;">

    <nav class="elysian-breadcrumb mb-4">
        <a href="<?= BASE_URL ?>pages/rooms.php">Rooms &amp; Suites</a>
        <span class="sep material-symbols-outlined" style="font-size:18px;">chevron_right</span>
        <span class="current">Add New Room</span>
    </nav>

    <div class="login-card mx-0" style="max-width:100%;">
        <div class="gold-line"></div>
        <p class="elysian-label-sm text-gold mb-2">ADMIN</p>
        <h1 class="elysian-headline-md mb-5">Add New Room</h1>

        <?php if (!empty($errors)): ?>
        <div class="alert-elysian error mb-4">
            <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form id="roomForm" method="POST" action="" enctype="multipart/form-data" novalidate>

            <div class="row g-4">
                <div class="col-12 col-sm-8">
                    <div class="elysian-input-wrap">
                        <label for="nama_kamar">Room Name *</label>
                        <input type="text" id="nama_kamar" name="nama_kamar" required
                               value="<?= htmlspecialchars($_POST['nama_kamar'] ?? '') ?>"
                               placeholder="e.g., Azure Horizon Suite">
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="elysian-input-wrap">
                        <label for="tipe">Room Type *</label>
                        <select id="tipe" name="tipe" required>
                            <option value="">Select type...</option>
                            <?php foreach (['Suite','Villa','Penthouse','Pavilion','Loft'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($_POST['tipe'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="elysian-input-wrap">
                <label for="deskripsi">Description *</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" required
                          placeholder="A detailed description of this room's unique character..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="row g-4">
                <div class="col-12 col-sm-6">
                    <div class="elysian-input-wrap">
                        <label for="harga_per_malam">Price per Night (IDR) *</label>
                        <input type="number" id="harga_per_malam" name="harga_per_malam" required min="1"
                               value="<?= htmlspecialchars($_POST['harga_per_malam'] ?? '') ?>"
                               placeholder="1500000"
                               data-validate="positive-number">
                    </div>
                </div>
                <div class="col-12 col-sm-3">
                    <div class="elysian-input-wrap">
                        <label for="kapasitas">Max Guests *</label>
                        <input type="number" id="kapasitas" name="kapasitas" required min="1" max="20"
                               value="<?= htmlspecialchars($_POST['kapasitas'] ?? '2') ?>">
                    </div>
                </div>
                <div class="col-12 col-sm-3">
                    <div class="elysian-input-wrap">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="available" <?= ($_POST['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="booked" <?= ($_POST['status'] ?? '') === 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="maintenance" <?= ($_POST['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="elysian-input-wrap">
                <label for="fasilitas">Amenities (comma-separated)</label>
                <input type="text" id="fasilitas" name="fasilitas"
                       value="<?= htmlspecialchars($_POST['fasilitas'] ?? '') ?>"
                       placeholder="Private Pool, Butler Service, Ocean View, Spa Bathroom">
            </div>

            <div class="row g-4 mt-2">
                <div class="col-12 col-md-6">
                    <div class="elysian-input-wrap">
                        <label for="gambar">Room Main Image (JPG/PNG/WebP, max 5MB)</label>
                        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp"
                               style="border-bottom:1px solid var(--color-outline-var);padding:8px 0;background:transparent;width:100%;font-size:14px;">
                        <div id="mainImagePreviewContainer" class="mt-3" style="display:none;">
                            <p class="elysian-label-sm text-gold mb-1">Main Image Preview:</p>
                            <img id="mainImagePreview" src="" alt="Main Image Preview" 
                                 style="max-width:100%;height:180px;object-fit:cover;border:1px solid var(--color-gold);border-radius:4px;">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="elysian-input-wrap">
                        <label for="gallery">Room Gallery/Preview Images (multiple, max 5MB each)</label>
                        <input type="file" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple
                               style="border-bottom:1px solid var(--color-outline-var);padding:8px 0;background:transparent;width:100%;font-size:14px;">
                        <div id="galleryPreviewContainer" class="mt-3" style="display:none;">
                            <p class="elysian-label-sm text-gold mb-1">Gallery Previews:</p>
                            <div id="galleryPreviews" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn-elysian-primary flex-fill">Save Room</button>
                <a href="<?= BASE_URL ?>pages/rooms.php" class="btn-elysian-secondary flex-fill text-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainInput = document.getElementById('gambar');
    const mainPreviewContainer = document.getElementById('mainImagePreviewContainer');
    const mainPreview = document.getElementById('mainImagePreview');

    const galleryInput = document.getElementById('gallery');
    const galleryPreviewContainer = document.getElementById('galleryPreviewContainer');
    const galleryPreviews = document.getElementById('galleryPreviews');

    // Main image preview
    mainInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                mainPreview.src = e.target.result;
                mainPreviewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            mainPreviewContainer.style.display = 'none';
        }
    });

    // Gallery images preview
    galleryInput.addEventListener('change', function() {
        galleryPreviews.innerHTML = '';
        if (this.files && this.files.length > 0) {
            galleryPreviewContainer.style.display = 'block';
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
        } else {
            galleryPreviewContainer.style.display = 'none';
        }
    });
});
</script>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
