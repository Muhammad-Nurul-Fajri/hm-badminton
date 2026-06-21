# HM Badminton Court
Sistem Manajemen Lapangan Badminton — Reservasi Online, Pembayaran, dan Laporan

## 🏸 Tentang Proyek
Website manajemen reservasi lapangan badminton dengan 3 role user:
- **Pelanggan** — Booking lapangan, upload bukti pembayaran, lihat tiket
- **Admin Cabang** — Verifikasi pembayaran, kelola jadwal, kelola harga, laporan cabang
- **Owner** — Dashboard semua cabang, kelola admin, laporan konsolidasi

## 📂 Struktur Folder
```
hm-badminton/
├── index.php                  → Entry point (redirect)
├── config/                    → Konfigurasi & fungsi
│   ├── config.php             → Database, session, helper
│   ├── functions.php          → Business logic (60+ fungsi)
│   └── middleware.php         → Auth guards (role-based)
├── includes/                  → Template reusable
│   ├── header.php / footer.php
│   └── sidebar_pelanggan/admin/owner.php
├── auth/                      → Login, register, logout
├── pelanggan/                 → Halaman pelanggan (7 file)
│   ├── dashboard.php          → Dashboard + pilih cabang
│   ├── reservasi.php          → Booking lapangan
│   ├── pembayaran.php         → Upload bukti transfer
│   ├── riwayat.php            → Riwayat booking
│   ├── tiket.php              → Tiket/invoice (printable)
│   ├── profil.php             → Edit profil & password
│   └── batal.php              → Batalkan booking
├── admin/                     → Halaman admin cabang (9 file)
│   ├── dashboard.php          → Stats hari ini + Chart.js 7 hari
│   ├── jadwal.php             → Date nav, grid jadwal, blokir tanggal
│   ├── harga.php              → Edit harga, simulasi, riwayat perubahan
│   ├── reservasi.php          → Filter, detail, cancel, walk-in, CSV
│   ├── pembayaran.php         → Verifikasi + modal approve/reject
│   ├── konfirmasi.php         → Handler konfirmasi/batal
│   ├── pengguna.php           → Cari, detail, aktifkan/nonaktifkan
│   ├── profil.php             → Edit profil, foto, password
│   └── laporan.php            → Chart bulanan, per-lapangan, top 10, CSV
├── owner/                     → Halaman owner (4 file)
│   ├── dashboard.php          → Overview semua cabang
│   ├── cabang.php             → Kelola cabang & lapangan
│   ├── admin_manage.php       → CRUD admin cabang
│   └── laporan.php            → Laporan konsolidasi
├── assets/
│   ├── css/style.css          → Unified stylesheet (1600+ lines)
│   └── js/main.js             → Flash dismiss, file preview
├── uploads/
│   ├── bukti_pembayaran/      → Upload bukti transfer
│   └── foto_profil/           → Upload foto profil admin
└── database/hm_badminton.sql  → Schema + seed data (11 tables)
```

## ⚙️ Instalasi
1. Copy folder ke `htdocs` (XAMPP) atau `www` (Laragon)
2. Import database:
   ```sql
   mysql -u root < database/hm_badminton.sql
   ```
3. Edit `config/config.php` jika perlu (BASE_URL, DB credentials)
4. Akses: `http://localhost/hm-badminton/`

## 🔑 Akun Test (Password: `password`)
| Role | Email | Cabang |
|------|-------|--------|
| Pelanggan | ahmad@email.com | - |
| Pelanggan | budi@email.com | - |
| Pelanggan | citra@email.com | - |
| Admin | admin.katamso@hm.com | HM1 Katamso |
| Admin | admin.garu@hm.com | HM2 Garu |
| Owner | owner@hm.com | Semua |

## 🛡️ Fitur Admin Cabang
| Fitur | Deskripsi |
|-------|-----------|
| 📊 Dashboard | Statistik hari ini, Chart.js 7 hari (booking + pendapatan), notifikasi pending |
| 📅 Kelola Jadwal | Navigasi tanggal, grid per lapangan, status court, blokir tanggal (CRUD) |
| 💰 Kelola Harga | Edit harga via modal, simulasi harga, riwayat perubahan (log) |
| 📋 Data Reservasi | Filter (status/tanggal/lapangan), detail modal, cancel + alasan, walk-in booking, export CSV |
| 💳 Verifikasi Bayar | Preview bukti, approve/reject modal, alasan wajib saat reject, pagination |
| 👥 Pengguna | Search nama/email/HP, detail modal, total booking & spending, aktifkan/nonaktifkan |
| 📈 Laporan | Date range, Chart.js bulanan, statistik per lapangan, top 10 pelanggan, export CSV |
| 👤 Profil | Edit data diri, upload foto, ganti password |

## 💰 Pricelist
| Hari | Waktu | Harga |
|------|-------|-------|
| Senin - Kamis | 08:00 - 16:00 | Rp 50.000/jam |
| Senin - Kamis | 16:00 - 23:00 | Rp 80.000/jam |
| Jumat - Minggu | 08:00 - 23:00 | Rp 80.000/jam |
| ⭐ Special (Cab Garu) | Sen-Jum 08:00-16:00 | Rp 150.000/4 jam |

## 🛠️ Tech Stack
- PHP 7.4+ (Native, tanpa framework)
- MySQL / MariaDB (11 tables, 2 views)
- HTML5, CSS3, JavaScript (Vanilla)
- Chart.js 4.x (Dashboard & Report charts)
- PDO untuk database (prepared statements)
- Pagination (10 items per halaman)
- Modal confirmation (approve, reject, cancel, delete)
- CSV Export (reservasi & laporan)
