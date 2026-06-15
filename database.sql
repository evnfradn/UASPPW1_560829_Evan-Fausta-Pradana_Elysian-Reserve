
CREATE DATABASE IF NOT EXISTS `elysian_reserve`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `elysian_reserve`;

-- ======================================================
-- TABLES 
-- ======================================================

-- ----------------------------
-- 1. Tabel: users
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(80)   NOT NULL UNIQUE,
  `password`   VARCHAR(255)  NOT NULL,
  `full_name`  VARCHAR(120)  NOT NULL,
  `email`      VARCHAR(120)  NOT NULL,
  `phone`      VARCHAR(30)   DEFAULT NULL,
  `role`       ENUM('admin','guest') NOT NULL DEFAULT 'guest',
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 2. Tabel: rooms
-- ----------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nama_kamar`       VARCHAR(120)    NOT NULL,
  `tipe`             ENUM('Suite','Villa','Penthouse','Pavilion','Loft') NOT NULL DEFAULT 'Suite',
  `deskripsi`        TEXT            NOT NULL,
  `harga_per_malam`  DECIMAL(12,2)   NOT NULL,
  `kapasitas`        INT             NOT NULL DEFAULT 2,
  `fasilitas`        TEXT            DEFAULT NULL COMMENT 'comma-separated list',
  `status`           ENUM('available','booked','maintenance') NOT NULL DEFAULT 'available',
  `gambar`           VARCHAR(255)    DEFAULT 'default_room.jpg',
  `gallery`          TEXT            DEFAULT NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 3. Tabel: bookings
-- ----------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED   NOT NULL,
  `room_id`          INT UNSIGNED   NOT NULL,
  `nama_pemesan`     VARCHAR(120)   NOT NULL,
  `tanggal_checkin`  DATE           NOT NULL,
  `tanggal_checkout` DATE           NOT NULL,
  `jumlah_tamu`      INT            NOT NULL DEFAULT 1,
  `total_harga`      DECIMAL(14,2)  NOT NULL DEFAULT 0,
  `status`           ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `catatan`          TEXT           DEFAULT NULL,
  `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_booking_user` (`user_id`),
  KEY `fk_booking_room` (`room_id`),
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 4. Tabel: reviews
-- ----------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED  NOT NULL,
  `user_id`    INT UNSIGNED  NOT NULL,
  `room_id`    INT UNSIGNED  NOT NULL,
  `rating`     TINYINT       NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `komentar`   TEXT          DEFAULT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_review` (`booking_id`),
  KEY `fk_review_user` (`user_id`),
  KEY `fk_review_room` (`room_id`),
  CONSTRAINT `fk_review_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_room`    FOREIGN KEY (`room_id`)    REFERENCES `rooms`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 5. Tabel: payments
-- ----------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`                 INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `booking_id`         INT UNSIGNED   NOT NULL,
  `jumlah_bayar`       DECIMAL(14,2)  NOT NULL,
  `metode_pembayaran`  ENUM('transfer','kartu_kredit','tunai','e-wallet') NOT NULL DEFAULT 'transfer',
  `status_pembayaran`  ENUM('pending','paid','refunded','failed')         NOT NULL DEFAULT 'pending',
  `tanggal_bayar`      DATETIME       DEFAULT NULL,
  `keterangan`         VARCHAR(255)   DEFAULT NULL,
  `created_at`         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payment_booking` (`booking_id`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ======================================================
-- FUNCTIONS (2 fungsi)
-- ======================================================

DELIMITER $$


DROP FUNCTION IF EXISTS `fn_durasi_menginap`$$
CREATE FUNCTION `fn_durasi_menginap`(
    p_checkin  DATE,
    p_checkout DATE
) RETURNS INT
DETERMINISTIC
BEGIN
    RETURN DATEDIFF(p_checkout, p_checkin);
END$$


DROP FUNCTION IF EXISTS `fn_hitung_total_harga`$$
CREATE FUNCTION `fn_hitung_total_harga`(
    p_room_id  INT UNSIGNED,
    p_checkin  DATE,
    p_checkout DATE
) RETURNS DECIMAL(14,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_harga   DECIMAL(12,2) DEFAULT 0;
    DECLARE v_durasi  INT           DEFAULT 0;

    SELECT harga_per_malam INTO v_harga
    FROM   rooms
    WHERE  id = p_room_id
    LIMIT  1;

    SET v_durasi = DATEDIFF(p_checkout, p_checkin);

    RETURN v_harga * v_durasi;
END$$

DELIMITER ;


-- ======================================================
-- TRIGGERS (2 trigger)
-- ======================================================

DELIMITER $$



DROP TRIGGER IF EXISTS `trg_before_booking_insert`$$
CREATE TRIGGER `trg_before_booking_insert`
BEFORE INSERT ON `bookings`
FOR EACH ROW
BEGIN
    IF NEW.total_harga = 0 THEN
        SET NEW.total_harga = fn_hitung_total_harga(
            NEW.room_id,
            NEW.tanggal_checkin,
            NEW.tanggal_checkout
        );
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_after_booking_update`$$
CREATE TRIGGER `trg_after_booking_update`
AFTER UPDATE ON `bookings`
FOR EACH ROW
BEGIN
    IF NEW.status = 'confirmed' AND OLD.status != 'confirmed' THEN
        UPDATE rooms SET status = 'booked'
        WHERE  id = NEW.room_id;

    ELSEIF NEW.status IN ('cancelled', 'completed')
       AND OLD.status NOT IN ('cancelled', 'completed') THEN
        UPDATE rooms SET status = 'available'
        WHERE  id = NEW.room_id;
    END IF;
END$$

DELIMITER ;


-- ======================================================
-- VIEWS (2 view)
-- ======================================================

CREATE OR REPLACE VIEW `vw_booking_detail` AS
SELECT
    b.id                                                      AS booking_id,
    b.nama_pemesan,
    b.tanggal_checkin,
    b.tanggal_checkout,
    DATEDIFF(b.tanggal_checkout, b.tanggal_checkin)           AS durasi_malam,
    b.jumlah_tamu,
    b.total_harga,
    b.status                                                  AS status_booking,
    b.catatan,
    b.created_at                                              AS tanggal_pesan,
    u.username,
    u.full_name,
    u.email,
    u.phone,
    r.nama_kamar,
    r.tipe                                                    AS tipe_kamar,
    r.harga_per_malam,
    r.status                                                  AS status_kamar,
    p.status_pembayaran,
    p.metode_pembayaran
FROM       bookings b
JOIN       users    u ON b.user_id   = u.id
JOIN       rooms    r ON b.room_id   = r.id
LEFT JOIN  payments p ON b.id        = p.booking_id;

CREATE OR REPLACE VIEW `vw_room_stats` AS
SELECT
    r.id                                             AS room_id,
    r.nama_kamar,
    r.tipe,
    r.harga_per_malam,
    r.status,
    COUNT(DISTINCT b.id)                             AS total_booking,
    COALESCE(SUM(b.total_harga), 0)                  AS total_pendapatan,
    COALESCE(ROUND(AVG(rv.rating), 1), 0)            AS rata_rating,
    COUNT(DISTINCT rv.id)                            AS jumlah_ulasan
FROM       rooms    r
LEFT JOIN  bookings b  ON r.id = b.room_id AND b.status IN ('confirmed', 'completed')
LEFT JOIN  reviews  rv ON r.id = rv.room_id
GROUP BY   r.id, r.nama_kamar, r.tipe, r.harga_per_malam, r.status;


-- ======================================================
-- STORED PROCEDURES — COMPLEX QUERIES (min. 3)
-- ======================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_laporan_booking_bulanan`$$
CREATE PROCEDURE `sp_laporan_booking_bulanan`(
    IN p_tahun INT,
    IN p_bulan INT
)
BEGIN
    SELECT
        b.id                                                    AS booking_id,
        u.full_name                                             AS nama_tamu,
        u.email,
        r.nama_kamar,
        r.tipe,
        b.tanggal_checkin,
        b.tanggal_checkout,
        DATEDIFF(b.tanggal_checkout, b.tanggal_checkin)        AS durasi_malam,
        b.jumlah_tamu,
        b.total_harga,
        b.status                                               AS status_booking,
        p.metode_pembayaran,
        p.status_pembayaran
    FROM       bookings b
    JOIN       users    u ON b.user_id  = u.id
    JOIN       rooms    r ON b.room_id  = r.id
    LEFT JOIN  payments p ON b.id       = p.booking_id
    WHERE  YEAR(b.created_at)  = p_tahun
      AND  MONTH(b.created_at) = p_bulan
    ORDER BY b.created_at DESC;
END$$


DROP PROCEDURE IF EXISTS `sp_kamar_diatas_rata`$$
CREATE PROCEDURE `sp_kamar_diatas_rata`()
BEGIN
    SELECT
        r.id,
        r.nama_kamar,
        r.tipe,
        r.harga_per_malam,
        COALESCE(SUM(b.total_harga), 0)  AS total_pendapatan
    FROM       rooms    r
    LEFT JOIN  bookings b ON r.id = b.room_id AND b.status IN ('confirmed', 'completed')
    GROUP BY   r.id, r.nama_kamar, r.tipe, r.harga_per_malam
    HAVING total_pendapatan > (

        SELECT AVG(sub.total)
        FROM (
            SELECT COALESCE(SUM(b2.total_harga), 0) AS total
            FROM       rooms    r2
            LEFT JOIN  bookings b2 ON r2.id = b2.room_id AND b2.status IN ('confirmed', 'completed')
            GROUP BY   r2.id
        ) AS sub
    )
    ORDER BY total_pendapatan DESC;
END$$


DROP PROCEDURE IF EXISTS `sp_top_tamu`$$
CREATE PROCEDURE `sp_top_tamu`(IN p_limit INT)
BEGIN
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        COUNT(b.id)                  AS jumlah_booking,
        SUM(b.total_harga)           AS total_pengeluaran,
        MAX(b.tanggal_checkin)       AS kunjungan_terakhir,

        (
            SELECT r.nama_kamar
            FROM   bookings b2
            JOIN   rooms    r  ON b2.room_id = r.id
            WHERE  b2.user_id  = u.id
            ORDER BY b2.created_at DESC
            LIMIT  1
        )                            AS kamar_terakhir
    FROM   users    u
    JOIN   bookings b ON u.id = b.user_id
    WHERE  u.role = 'guest'
    GROUP BY u.id, u.full_name, u.email, u.phone
    ORDER BY jumlah_booking DESC, total_pengeluaran DESC
    LIMIT p_limit;
END$$

DELIMITER ;


-- ======================================================
-- SEED DATA
-- ======================================================

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `payments`;
DELETE FROM `reviews`;
DELETE FROM `bookings`;
DELETE FROM `rooms`;
DELETE FROM `users`;
ALTER TABLE `payments`  AUTO_INCREMENT = 1;
ALTER TABLE `reviews`   AUTO_INCREMENT = 1;
ALTER TABLE `bookings`  AUTO_INCREMENT = 1;
ALTER TABLE `rooms`     AUTO_INCREMENT = 1;
ALTER TABLE `users`     AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO `users` (`username`, `password`, `full_name`, `email`, `phone`, `role`) VALUES
('admin',    '$2y$10$55AHLH34e0yYGoSupINmduwB7QgO1kb./CkTgLRh4G2ylwjj6M8/K', 'Elysian Administrator', 'admin@elysian.com',            '+62 21 123 4567',    'admin'),
('evan',     '$2y$10$55AHLH34e0yYGoSupINmduwB7QgO1kb./CkTgLRh4G2ylwjj6M8/K', 'Evan Fausta',           'evanfausta07@gmail.com',       '+62 812 3456 7890',  'guest'),
('matthew',  '$2y$10$55AHLH34e0yYGoSupINmduwB7QgO1kb./CkTgLRh4G2ylwjj6M8/K', 'Matthew Christian',     'matthewchristian23@yahoo.co.id','+62 878 5678 1234', 'guest'),
('carthetya',    '$2y$10$55AHLH34e0yYGoSupINmduwB7QgO1kb./CkTgLRh4G2ylwjj6M8/K', 'Carthetya Fleurdelys',  'carthetya.f@outlook.com',      '+62 857 9012 3456',  'guest');

-- ----------------------------
-- Rooms
-- ----------------------------
INSERT IGNORE INTO `rooms` (`nama_kamar`, `tipe`, `deskripsi`, `harga_per_malam`, `kapasitas`, `fasilitas`, `status`, `gambar`) VALUES
('Celestial Master Suite', 'Suite',
 'A masterclass in understated opulence. Floating above the sapphire tides of our private lagoon, this 1,200 sq. ft. retreat offers a seamless dialogue between interior precision and raw natural beauty. Every element — from the hand-loomed linens to the custom-milled white oak cabinetry — has been curated to facilitate a state of effortless serenity.',
 1450000.00, 2,
 'Private Heated Infinity Pool,In-Suite Personal Chef,Starlink Global Connectivity,Aromatherapy Bath Rituals,24h Butler Service,Private Terrace',
 'available', 'room_suite.jpg'),

('Oceanic Penthouse', 'Penthouse',
 'Perched at the highest point of the reserve, this penthouse offers 270-degree views of the azure horizon. Double-height ceilings frame the boundless sky, while bespoke stone floors and handcrafted joinery ground the space in tactile luxury.',
 2200000.00, 2,
 'Panoramic Ocean View,Private Rooftop Terrace,Jacuzzi,Personal Butler,Wine Cellar Access,Helicopter Pad Access',
 'available', 'room_penthouse.jpg'),

('Presidential Sanctuary', 'Villa',
 'A secluded 450m² private estate featuring a personal infinity pool, dedicated butler kitchen, and direct beach access. Designed for those who demand absolute privacy without compromise. Host private dinners under the stars with our resident Michelin-starred chef.',
 4800000.00, 6,
 'Private Infinity Pool,Dedicated Butler Kitchen,Beach Access,Up to 6 Guests,Chef Available,Golf Cart,Private Cinema',
 'available', 'room_villa.jpg'),

('Garden Pavilion', 'Pavilion',
 'An intimate hideaway nestled within our botanical gardens, emphasizing organic materials and contemplative silence. Japanese-inspired sliding doors open to a private bamboo forest. Perfect for those seeking stillness and connection with nature.',
 975000.00, 2,
 'Garden View,Outdoor Soaking Tub,Meditation Space,Tea Ceremony Set,Forest Walks,Yoga Mat',
 'available', 'room_pavilion.jpg'),

('Sky Loft', 'Loft',
 'Double-height ceilings and a dramatic open layout designed for the modern connoisseur of space and light. A sculptural staircase leads to a private mezzanine lounge. City views by night become a masterwork of luminous geometry.',
 1425000.00, 2,
 'City Skyline View,Mezzanine Lounge,Espresso Bar,Smart Home Controls,Rainfall Shower,Designer Amenities',
 'maintenance', 'room_loft.jpg');

INSERT IGNORE INTO `bookings` (`user_id`, `room_id`, `nama_pemesan`, `tanggal_checkin`, `tanggal_checkout`, `jumlah_tamu`, `total_harga`, `status`, `catatan`) VALUES
(2, 3, 'Evan Fausta',           '2026-07-12', '2026-07-18', 4, 28800000.00, 'confirmed', 'Please arrange private chef dinner on arrival night.'),
(3, 1, 'Matthew Christian',    '2026-06-20', '2026-06-24', 2,  5800000.00, 'pending',   'Early check-in requested if possible.'),
(4, 2, 'Carthetya Fleurdelys', '2026-08-01', '2026-08-05', 2,  8800000.00, 'confirmed', 'Anniversary celebration. Please prepare champagne and roses.'),
(2, 4, 'Evan Fausta',           '2026-02-14', '2026-02-17', 2,  2925000.00, 'completed', NULL),
(3, 5, 'Matthew Christian',    '2026-03-10', '2026-03-13', 2,  4275000.00, 'cancelled', 'Change of travel plans.');

-- ----------------------------
-- Reviews 
-- ----------------------------
INSERT IGNORE INTO `reviews` (`booking_id`, `user_id`, `room_id`, `rating`, `komentar`) VALUES
(4, 2, 4, 5, 'An absolutely magical experience. The bamboo garden view was meditative and the outdoor soaking tub was perfect. We will return.'),
(5, 3, 5, 4, 'Stunning loft space with incredible city views. The mezzanine lounge was a highlight. Would definitely rebook in the future.');

-- ----------------------------
-- Payments
-- ----------------------------
INSERT IGNORE INTO `payments` (`booking_id`, `jumlah_bayar`, `metode_pembayaran`, `status_pembayaran`, `tanggal_bayar`, `keterangan`) VALUES
(1, 28800000.00, 'transfer',     'paid',     '2026-06-10 14:22:00', 'Full payment via BCA transfer'),
(2,  2900000.00, 'kartu_kredit', 'pending',   NULL,                 'Deposit 50% pending approval'),
(3,  8800000.00, 'e-wallet',     'paid',     '2026-07-25 09:15:00', 'GoPay full payment'),
(4,  2925000.00, 'tunai',        'paid',     '2026-02-14 12:00:00', 'Cash payment at front desk'),
(5,  4275000.00, 'transfer',     'refunded', '2026-03-05 16:45:00', 'Full refund processed due to cancellation');
