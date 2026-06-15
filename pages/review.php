<?php

require_once __DIR__ . '/../includes/config.php';
requireLogin();

$userId = $_SESSION['user']['id'];
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: ' . BASE_URL . 'pages/my_bookings.php');
    exit;
}

// ── VERIFY BOOKING OWNERSHIP ─────────────────────────
$stmt = $conn->prepare("
    SELECT b.*, r.nama_kamar, r.id AS room_id, r.gambar
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param('ii', $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    $_SESSION['flash_error'] = 'Booking not found or access denied.';
    header('Location: ' . BASE_URL . 'pages/my_bookings.php');
    exit;
}

// ── FETCH EXISTING REVIEW ─────────────────────────────
$reviewStmt = $conn->prepare("SELECT * FROM reviews WHERE booking_id = ?");
$reviewStmt->bind_param('i', $bookingId);
$reviewStmt->execute();
$review = $reviewStmt->get_result()->fetch_assoc();
$reviewStmt->close();

$errors = [];
$success = '';

// ── HANDLE ACTIONS (POST) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        if ($review) {
            $deleteStmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $deleteStmt->bind_param('i', $review['id']);
            if ($deleteStmt->execute()) {
                $_SESSION['flash_success'] = 'Your review has been successfully deleted.';
            } else {
                $_SESSION['flash_error'] = 'Failed to delete your review. Please try again.';
            }
            $deleteStmt->close();
        } else {
            $_SESSION['flash_error'] = 'Review not found.';
        }
        header('Location: ' . BASE_URL . 'pages/my_bookings.php');
        exit;
    }

    // CREATE OR UPDATE
    $rating = (int)($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Please select a rating (1 to 5 stars).';
    }
    if (strlen($komentar) < 5) {
        $errors[] = 'Your comment must be at least 5 characters long.';
    }

    if (empty($errors)) {
        if ($review) {
            // Update
            $updateStmt = $conn->prepare("UPDATE reviews SET rating = ?, komentar = ? WHERE id = ?");
            $updateStmt->bind_param('isi', $rating, $komentar, $review['id']);
            if ($updateStmt->execute()) {
                $_SESSION['flash_success'] = 'Your review has been successfully updated.';
            } else {
                $_SESSION['flash_error'] = 'Failed to update review.';
            }
            $updateStmt->close();
        } else {
            // Insert
            $roomId = $booking['room_id'];
            $insertStmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, room_id, rating, komentar) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param('iiiis', $bookingId, $userId, $roomId, $rating, $komentar);
            if ($insertStmt->execute()) {
                $_SESSION['flash_success'] = 'Thank you for your feedback! Your review has been saved.';
            } else {
                $_SESSION['flash_error'] = 'Failed to submit review.';
            }
            $insertStmt->close();
        }
        header('Location: ' . BASE_URL . 'pages/my_bookings.php');
        exit;
    }
}

$pageTitle = $review ? 'Edit Review' : 'Write a Review';
$currentPage = 'bookings';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Star Rating styling using Row-Reverse for pure CSS hover support */
.rating-stars-container {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 8px;
}
.rating-stars-container input:checked ~ label .star-icon {
    font-variation-settings: 'FILL' 1;
    color: var(--color-gold);
}
.rating-stars-container label {
    color: var(--color-outline-var);
    cursor: pointer;
    transition: color 0.2s ease;
}
.rating-stars-container label:hover,
.rating-stars-container label:hover ~ label {
    color: var(--color-gold);
}
.rating-stars-container label:hover .star-icon,
.rating-stars-container label:hover ~ label .star-icon {
    font-variation-settings: 'FILL' 1;
    color: var(--color-gold) !important;
}
.rating-stars-container .star-icon {
    font-size: 36px;
    user-select: none;
}
</style>

<main class="section-gap-sm section-cream" style="min-height:calc(100vh - 80px);">
<div class="section-container" style="max-width:700px;">

    <!-- Breadcrumb -->
    <nav class="elysian-breadcrumb mb-4">
        <a href="<?= BASE_URL ?>pages/my_bookings.php">My Bookings</a>
        <span class="sep material-symbols-outlined" style="font-size:18px;">chevron_right</span>
        <span class="current"><?= $review ? 'Edit Review' : 'Write Review' ?></span>
    </nav>

    <div class="login-card mx-0" style="max-width:100%;">
        <div class="gold-line"></div>
        <p class="elysian-label-sm text-gold mb-2">EXPERIENCE ASSESSMENT</p>
        <h1 class="elysian-headline-md mb-1"><?= htmlspecialchars($booking['nama_kamar']) ?></h1>
        <p class="elysian-body-sm text-muted-soft mb-5">Booking #<?= $bookingId ?> &bull; Stayed <?= date('M d', strtotime($booking['tanggal_checkin'])) ?> &ndash; <?= date('M d, Y', strtotime($booking['tanggal_checkout'])) ?></p>

        <?php if (!empty($errors)): ?>
        <div class="alert-elysian error mb-4">
            <?php foreach ($errors as $e): ?><div>&bull; <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form for Write/Edit Review -->
        <form id="reviewForm" method="POST" action="" novalidate>
            <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
            <input type="hidden" name="action" value="save">

            <!-- Star Rating Input (5 to 1 order for row-reverse CSS hover logic) -->
            <div class="elysian-input-wrap text-center mb-4">
                <label class="d-block mb-2">Your Rating</label>
                <div class="rating-stars-container" id="ratingStars">
                    <input type="radio" id="star5" name="rating" value="5" class="d-none" <?= (isset($review['rating']) && $review['rating'] == 5) ? 'checked' : '' ?>>
                    <label for="star5" title="Excellent"><span class="material-symbols-outlined star-icon">star</span></label>
                    
                    <input type="radio" id="star4" name="rating" value="4" class="d-none" <?= (isset($review['rating']) && $review['rating'] == 4) ? 'checked' : '' ?>>
                    <label for="star4" title="Very Good"><span class="material-symbols-outlined star-icon">star</span></label>

                    <input type="radio" id="star3" name="rating" value="3" class="d-none" <?= (isset($review['rating']) && $review['rating'] == 3) ? 'checked' : '' ?>>
                    <label for="star3" title="Good"><span class="material-symbols-outlined star-icon">star</span></label>

                    <input type="radio" id="star2" name="rating" value="2" class="d-none" <?= (isset($review['rating']) && $review['rating'] == 2) ? 'checked' : '' ?>>
                    <label for="star2" title="Fair"><span class="material-symbols-outlined star-icon">star</span></label>

                    <input type="radio" id="star1" name="rating" value="1" class="d-none" <?= (isset($review['rating']) && $review['rating'] == 1) ? 'checked' : '' ?>>
                    <label for="star1" title="Poor"><span class="material-symbols-outlined star-icon">star</span></label>
                </div>
                <!-- Inline Error Placeholder for Rating -->
                <p class="form-error-msg text-center" id="ratingError"></p>
            </div>

            <!-- Review Textarea -->
            <div class="elysian-input-wrap">
                <label for="komentar">Your Review &amp; Comments</label>
                <textarea id="komentar" name="komentar" rows="5" required placeholder="Tell us about your experience at Elysian Reserve..." class="p-2 w-100" style="border: 1px solid var(--color-outline-var); outline: none; background: transparent;"><?= htmlspecialchars($review['komentar'] ?? '') ?></textarea>
                <!-- Character counter and Inline Error -->
                <div class="d-flex justify-content-between mt-1">
                    <p class="form-error-msg" id="commentError" style="margin:0;"></p>
                    <small class="text-muted-soft" id="charCounter" style="font-size:11px;">0 / 1000 characters</small>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 mt-4">
                <button type="submit" class="btn-elysian-primary flex-fill"><?= $review ? 'Update Review' : 'Submit Review' ?></button>
                
                <?php if ($review): ?>
                    <!-- Separated form for Deletion to prevent trigger issues -->
                    <button type="button" class="btn-elysian-danger flex-fill" id="btnDeleteReview">Delete Review</button>
                <?php endif; ?>
                
                <a href="<?= BASE_URL ?>pages/my_bookings.php" class="btn-elysian-secondary flex-fill text-center">Cancel</a>
            </div>
        </form>

        <?php if ($review): ?>
            <!-- Hidden form for deletion -->
            <form id="deleteReviewForm" method="POST" action="" class="d-none">
                <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                <input type="hidden" name="action" value="delete">
            </form>
        <?php endif; ?>

    </div>
</div>
</main>

<!-- Inline script specifically for this page's DOM manipulations and form validation -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const reviewForm = document.getElementById('reviewForm');
    const deleteBtn = document.getElementById('btnDeleteReview');
    const deleteForm = document.getElementById('deleteReviewForm');
    
    // Character Counter
    const commentInput = document.getElementById('komentar');
    const charCounter = document.getElementById('charCounter');
    
    if (commentInput && charCounter) {
        const updateCounter = () => {
            const len = commentInput.value.length;
            charCounter.textContent = `${len} / 1000 characters`;
            if (len > 1000) {
                charCounter.style.color = 'var(--color-error)';
            } else {
                charCounter.style.color = '';
            }
        };
        commentInput.addEventListener('input', updateCounter);
        updateCounter(); // Initial call
    }


    // Delete Confirmation
    if (deleteBtn && deleteForm) {
        deleteBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                deleteForm.submit();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
