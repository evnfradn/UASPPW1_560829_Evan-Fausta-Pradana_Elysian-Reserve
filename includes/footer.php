<?php

?>

<!-- ===== FOOTER ===== -->
<footer class="elysian-footer">
    <div class="container-fluid px-nav">
        <div class="row align-items-start py-5 border-bottom border-white border-opacity-10">
            <!-- Brand Column -->
            <div class="col-12 col-md-4 mb-5 mb-md-0">
                <div class="elysian-brand text-white mb-3">ELYSIAN RESERVE</div>
                <p class="elysian-body-sm opacity-60 mb-4" style="max-width:260px;">
                    Redefining the architecture of silence and the luxury of unhurried time.
                </p>
                <p class="elysian-label-sm opacity-40">&copy; <?= date('Y') ?> Elysian Reserve. All rights reserved.</p>
            </div>

            <!-- Links -->
            <div class="col-6 col-md-2 mb-4 mb-md-0">
                <p class="elysian-label-sm text-gold mb-4">EXPLORE</p>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= BASE_URL ?>pages/rooms.php">Rooms &amp; Suites</a></li>
                    <li><a href="<?= BASE_URL ?>index.php#services">Services</a></li>
                    <li><a href="<?= BASE_URL ?>index.php#amenities">Amenities</a></li>
                    <li><a href="<?= BASE_URL ?>index.php#about">About</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2 mb-4 mb-md-0">
                <p class="elysian-label-sm text-gold mb-4">GUEST</p>
                <ul class="list-unstyled footer-links">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= BASE_URL ?>pages/my_bookings.php">My Bookings</a></li>
                        <li><a href="<?= BASE_URL ?>logout.php">Sign Out</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>login.php">Sign In</a></li>
                        <li><a href="<?= BASE_URL ?>pages/rooms.php">Book Now</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-6 col-md-2 mb-4 mb-md-0">
                <p class="elysian-label-sm text-gold mb-4">CONNECT</p>
                <ul class="list-unstyled footer-links">
                    <li><a href="#">Instagram</a></li>
                    <li><a href="#">LinkedIn</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <p class="elysian-label-sm text-gold mb-4">LEGAL</p>
                <ul class="list-unstyled footer-links">
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>

        <div class="py-4 text-center">
            <p class="elysian-label-sm opacity-30 mb-0">Understated Opulence. Effortless Serenity.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
