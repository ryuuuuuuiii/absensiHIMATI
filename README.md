# Sistem Absensi HIMA TI — Politeknik Hasnur (Laravel Edition)

Aplikasi manajemen rapat, kepanitiaan, presensi QR code, dan sinkronisasi Google Sheets & Drive untuk Himpunan Mahasiswa Teknik Informatika (HIMA TI) Politeknik Hasnur yang telah dimigrasikan sepenuhnya dari PHP Native ke **Laravel 11**.

---

## 🚀 Fitur Utama

1. **Dashboard & Monitoring**:
   - Statistik real-time (total pengurus, rapat bulan ini, rata-rata kehadiran).
   - Banner alert untuk rapat aktif dalam mode *scanning*.
   - Shortcut aksi cepat & daftar rapat terbaru.

2. **Manajemen Rapat**:
   - Pembuatan rapat publik (`/rapat`) & admin (`/meetings/create`).
   - Tipe rapat: Internal Departemen, Rapat Perdana, Kepanitiaan/Event, BPH, Lintas Departemen.
   - Mode rapat: Offline (QR Scanner Kamera) & Online (Self Check-in).
   - Form izin/sakit mandiri (`/absent/{absent_link}`).
   - Upload & simpan foto dokumentasi rapat.
   - Integrasi otomatis Google Sheets & Google Drive via Google Apps Script.

3. **QR Code Scanner**:
   - Scanner kamera HTML5 interaktif dengan audio feedback & visual status badge.
   - Deteksi ganda dan validasi peserta yang terdaftar.
   - Kompatibel dengan perangkat desktop, tablet, maupun smartphone.

4. **Manajemen Anggota (Pengurus)**:
   - CRUD data pengurus per departemen dan jabatan.
   - Generator QR Code individual & generate massal (Batch QR).
   - Download ZIP seluruh badge QR anggota.
   - Halaman pencarian & download QR publik (`/my-qr`).

5. **Manajemen Event & Kepanitiaan**:
   - Pembuatan event dan divisi kepanitiaan.
   - Penugasan pengurus ke divisi (Koordinator & Anggota).
   - Riwayat rapat terkait per event.

6. **Laporan & Rekapitulasi Presensi**:
   - Filter rekap bulanan, tahunan, dan per departemen.
   - Kartu persentase kehadiran per departemen.
   - Matriks kehadiran individual (Hadir, Izin, Sakit, Alpha).
   - Tombol cetak ramah printer / PDF.

7. **Pengaturan Sistem**:
   - Upload 3 logo instansi/organisasi secara terpisah.
   - Konfigurasi Web App URL Google Apps Script & Google Drive Folder ID.
   - Pengaturan masa kedaluwarsa link absensi online.

---

## 🛠️ Persyaratan Sistem

- PHP 8.2 atau 8.3 (dengan ekstensi: `pdo_mysql`, `gd`, `zip`, `fileinfo`, `mbstring`, `curl`)
- MySQL 8.x / MariaDB
- Composer 2.x
- Web Server: Apache/Nginx (atau Laragon)

---

## ⚙️ Cara Menjalankan Aplikasi

### 1. Menggunakan Laragon (Rekomendasi)
Jika folder berada di direktori Laragon (`D:\laragon_www\Absensi-HIMATI-new`):
- Buka Laragon -> Klik **Start All** (Apache & MySQL).
- Virtual Host otomatis: `http://absensi-himati-new.test` (pastikan Document Root mengarah ke folder `/public`).

### 2. Menggunakan Artisan Serve
Buka terminal di root folder project:
```bash
php artisan serve
```
Akses aplikasi di browser: `http://127.0.0.1:8000`

---

## 🔐 Kredensial Login Admin

- **URL Login**: `http://127.0.0.1:8000/login`
- **Email**: `admin@himati.id`
- **Password**: `admin123`

---

## 🔗 Rute Penting

| URL | Deskripsi | Akses |
|---|---|---|
| `/` | Beranda (Redirect ke Dashboard jika login, atau Form Rapat) | Publik / Admin |
| `/login` | Halaman login administrator | Publik |
| `/rapat` | Form pembuatan rapat umum/panitia | Publik |
| `/my-qr` | Pencarian & unduh kartu QR pengurus | Publik |
| `/scan/{unique_link}` | Scanner QR presensi rapat | Publik / Panitia |
| `/attend/{unique_link}` | Link presensi online | Publik / Peserta |
| `/absent/{absent_link}` | Form izin / sakit | Publik / Peserta |
| `/dashboard` | Dashboard analitik & ringkasan | Admin |
| `/meetings` | Kelola rapat & presensi | Admin |
| `/members` | Kelola data anggota & QR Code | Admin |
| `/events` | Kelola event & divisi panitia | Admin |
| `/reports` | Laporan & rekapitulasi kehadiran | Admin |
| `/settings` | Pengaturan logo & Google Integration | Admin |

---

## 🧪 Menjalankan Automated Test

Semua rute dan fungsionalitas telah divalidasi dengan PHPUnit / Pest:
```bash
php artisan test
```

---

## 📂 Struktur Migrasi

- `legacy_backup/`: Cadangan seluruh kode PHP native lawas yang aman tersimpan.
- `app/Models/`: Model Eloquent (`Admin`, `Member`, `Meeting`, `Attendance`, `Event`, `EventDivision`, `EventMember`, `Setting`).
- `app/Http/Controllers/`: Controller untuk Admin & Publik.
- `app/Services/`: Service QR Code Generator & Google Sync Integration.
- `resources/views/`: Seluruh template Blade dengan layout modular dan UI dark-glassmorphism.
- `public/assets/`: File CSS, JavaScript, logo, audio scanner chime, dan upload QR.
