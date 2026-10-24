# Panduan Setup Google Apps Script

## Absensi HIMA TI Politeknik Hasnur

### Langkah-langkah Setup:

#### 1. Buat Google Spreadsheet
1. Buka [Google Sheets](https://sheets.google.com)
2. Buat spreadsheet baru
3. Beri nama: **"Absensi HIMA TI Politeknik Hasnur"**
4. Sheet pertama sudah otomatis ada, biarkan saja

#### 2. Buka Apps Script Editor
1. Di spreadsheet, klik **Extensions → Apps Script**
2. Akan terbuka editor Apps Script di tab baru

#### 3. Paste Code
1. Hapus semua code default (`function myFunction()...`)
2. Buka file `Code.gs` di folder ini
3. Copy seluruh isi file
4. Paste ke editor Apps Script
5. Klik **Save** (Ctrl+S)

#### 4. Deploy sebagai Web App
1. Klik tombol **Deploy → New Deployment**
2. Klik ikon gear ⚙️ di samping "Select type"
3. Pilih **Web App**
4. Isi konfigurasi:
   - **Description**: Absensi HIMA TI API
   - **Execute as**: Me (akun Google kamu)
   - **Who has access**: Anyone
5. Klik **Deploy**
6. Akan muncul dialog permission → Klik **Authorize access**
7. Pilih akun Google kamu
8. Jika muncul warning "Google hasn't verified this app":
   - Klik **Advanced**
   - Klik **Go to Absensi HIMA TI (unsafe)**
   - Klik **Allow**
9. **COPY URL Web App yang muncul** (format: `https://script.google.com/macros/s/.../exec`)

#### 5. Masukkan URL ke Website
1. Login ke website absensi sebagai Admin
2. Buka **Admin → Pengaturan**
3. Paste URL Web App ke field "Google Apps Script Web App URL"
4. Klik **Simpan**

#### 6. Setup Google Drive (Untuk Foto)
1. Buka [Google Drive](https://drive.google.com)
2. Buat folder baru: **"Absensi HIMA TI - Dokumentasi"**
3. Buka folder tersebut
4. Copy **Folder ID** dari URL browser
   - URL: `https://drive.google.com/drive/folders/[FOLDER_ID_DISINI]`
   - Copy bagian `[FOLDER_ID_DISINI]`
5. Paste ke halaman Pengaturan → Google Drive Folder ID

### Testing
1. Di Apps Script editor, pilih function `testAppendAttendance`
2. Klik **Run**
3. Cek spreadsheet — harusnya ada data test yang muncul

### Troubleshooting
- **Error 403**: Pastikan "Who has access" diset ke "Anyone"
- **Data tidak masuk**: Cek URL di Pengaturan sudah benar
- **Foto tidak tersimpan**: Pastikan Folder ID Google Drive sudah benar
- **Perlu re-deploy**: Setiap ubah code, perlu deploy ulang:
  - Deploy → Manage Deployments → Edit → Version: New → Deploy
