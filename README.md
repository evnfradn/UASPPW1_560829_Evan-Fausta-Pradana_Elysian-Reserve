# Elysian Reserve 

> FIlosofi: *Kemewahan yang bersahaja. Ketenangan yang tanpa batas.*

Project ini adalah aplikasi web booking hotel mewah berbasis **PHP & MySQL** yang dibuat untuk tugas **UAS Praktikum Pemrograman Web 1**. Di sini, tamu bisa cari kamar, hitung harga langsung, dan pesan kamar. Sementara admin bisa kelola semua data kamar lewat panel dashboard. Semua proses input divalidasi pake JavaScript, tampilannya responsif berkat Bootstrap 5, dan keamanannya dijaga lewat session login.

---

## Fitur Utama

| Fitur | Deskripsi |
|---|---|
| **Beranda** | Halaman utama dengan hero banner, pencarian kamar, dan info fasilitas hotel. |
| **Daftar Kamar** | Menampilkan semua pilihan kamar dengan fitur pencarian, filter kapasitas/tipe, serta paginasi. |
| **Detail Kamar** | Galeri foto kamar, daftar amenitas, serta kalkulator harga otomatis (real-time). |
| **Sistem Booking** | Form reservasi lengkap dengan validasi JavaScript sebelum disimpan ke database. |
| **Dashboard Tamu** | Halaman khusus tamu untuk melihat riwayat pemesanan aktif maupun yang sudah lalu. |
| **Panel Admin** | Manajemen kamar (Tambah, Edit, dan Hapus data kamar) khusus untuk akun Admin. |
| **Autentikasi** | Sistem Login dan Logout aman menggunakan enkripsi password (`password_hash()`). |
| **Responsif** | Desain fleksibel yang rapi di layar mobile (375px) hingga desktop (1440px+). |

---

## Screenshot Aplikasi


* **Halaman Beranda**
  [Halaman Beranda](assets/img/screenshot/dashboard.png)

* **Daftar Data**
  [Daftar Data Kamar](assets/img/screenshot/view-data.png)

* **Form Tambah Data (Admin)**
  [Form Tambah Data](assets/img/screenshot/form-tambah.png)

* **Form Edit Data (Admin / Guest)**
  [Form Edit Data](assets/img/screenshot/form-edit.png)

* **Tampilan Mobile**
  [Tampilan Mobile](assets/img/screenshot/tampilan-mobile.png)

---

## Teknologi yang Digunakan

- **Backend:** PHP 8.x
- **Database:** MySQL 8.x (melalui XAMPP)
- **Frontend:** Bootstrap 5.3 (CDN), Vanilla CSS, Vanilla JavaScript
- **Fonts:** Playfair Display & Manrope (Google Fonts)
- **Icons:** Material Symbols (Google)

---

## Panduan Instalasi

### Persyaratan Sistem
- Instal **XAMPP** (dengan modul Apache dan MySQL aktif)
- Git (opsional, untuk clone)

### Langkah Langkah

1. **Clone atau Unduh Project**
   Masukkan project ke folder `htdocs` di direktori XAMPP Anda (`C:\xampp\htdocs\`).
   ```bash
   git clone https://github.com/username-kamu/luxury-grand-hotel.git
   ```
   Atau ekstrak file zip ke:
   ```text
   C:\xampp\htdocs\luxury-grand-hotel\
   ```

2. **Siapkan Database**
   - Jalankan Apache dan MySQL melalui **XAMPP Control Panel**.
   - Buka phpMyAdmin di browser: `http://localhost/phpmyadmin/`.
   - Buat database baru dengan nama `elysian_reserve`.
   - Pilih database tersebut, klik tab **Import**, pilih file [database.sql](database.sql) yang ada di root project, lalu klik **Import / Go**.

3. **Konfigurasi Koneksi Database**
   - Salin file `includes/config.example.php` dan ubah namanya menjadi `includes/config.php`.
   - Buka file `includes/config.php` dan sesuaikan kredensial database jika perlu:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Kosongkan secara default untuk XAMPP
     define('DB_NAME', 'elysian_reserve');
     ```

4. **Jalankan Aplikasi**
   Buka browser dan akses URL berikut:
   ```text
   http://localhost/luxury-grand-hotel/
   ```

---

## Akun Demo untuk Uji Coba

Untuk memudahkan pengujian, Anda bisa menggunakan akun demo berikut:

| Peran (Role) | Username | Password |
|---|---|---|
| **Admin** | `admin` | `Password123!` |
| **Tamu / Guest** | `evan` | `Password123!` |

---

## Struktur Folder Project

```text
luxury-grand-hotel/
├── assets/
│   ├── css/style.css       → Styling custom 
│   ├── js/main.js          → Validasi form, kalkulator harga, & efek DOM
│   └── img/                → File gambar kamar hotel
├── includes/
│   ├── config.php          → Koneksi database (diabaikan dari git)
│   ├── header.php          → Komponen navbar & tag <head>
│   └── footer.php          → Komponen footer & script Bootstrap JS
├── pages/
│   ├── rooms.php           → Menampilkan daftar kamar (Search & Paginasi)
│   ├── booking.php         → Detail kamar & form booking (Create data)
│   ├── my_bookings.php     → Dashboard tamu (Read data pemesanan)
│   ├── edit_booking.php    → Edit pemesanan aktif (Update data)
│   ├── hapus_booking.php   → Membatalkan pemesanan (Delete data)
│   ├── tambah_kamar.php    → Tambah kamar baru oleh Admin (Create data)
│   ├── edit_kamar.php      → Edit info kamar oleh Admin (Update data)
│   └── hapus_kamar.php     → Hapus data kamar oleh Admin (Delete data)
├── index.php               → Halaman Beranda utama
├── login.php               → Autentikasi Login masuk
├── logout.php              → Mengakhiri sesi user (Logout)
├── database.sql            → Struktur database & contoh data bawaan
├── .gitignore
└── README.md
```

---


