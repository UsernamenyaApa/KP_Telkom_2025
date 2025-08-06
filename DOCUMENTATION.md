# Dokumentasi Proyek Infranexia

Dokumen ini menyediakan panduan teknis untuk pemeliharaan (maintenance), pengembangan, dan penanganan masalah (troubleshooting) pada aplikasi Infranexia.

## 1. Ringkasan Proyek

Aplikasi ini adalah sistem internal yang dirancang untuk mengelola dan melacak dua jenis laporan utama: **Laporan Fallout** dan **Laporan Pelurusan**. Sistem ini memfasilitasi alur kerja mulai dari pembuatan laporan, penugasan ke teknisi, pembaruan status, hingga penyelesaian. Aplikasi ini juga terintegrasi dengan Telegram untuk notifikasi real-time.

## 2. Teknologi yang Digunakan

Berikut adalah daftar teknologi utama yang digunakan dalam proyek ini:

- **Backend:** PHP 8.2+, [Laravel 11](https://laravel.com/)
- **Frontend:** [Livewire 3](https://livewire.laravel.com/), [Alpine.js](https://alpinejs.dev/)
- **UI & Styling:** [Tailwind CSS 4](https://tailwindcss.com/), [Flux (Komponen dari Livewire)](https://livewire.laravel.com/docs/flux)
- **Database:** Menggunakan driver database Laravel. Umumnya **MySQL** atau **MariaDB** untuk produksi, dan **SQLite** untuk development atau testing.
- **Antrian (Queue):** Menggunakan driver default Laravel (kemungkinan database atau Redis) untuk menangani proses latar belakang seperti pengiriman notifikasi.
- **Notifikasi:** [Telegram Bot SDK](https://telegram-bot-sdk.readme.io/) untuk mengirim pesan ke pengguna dan grup.
- **Development Server:** [Vite](https://vitejs.dev/) untuk kompilasi aset frontend.
- **Dependency Management:** [Composer](https://getcomposer.org/) (PHP), [NPM](https://www.npmjs.com/) (JavaScript).
- **Hak Akses:** [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction) untuk mengelola peran (roles) dan izin (permissions).

## 3. Instalasi & Setup Lokal

Berikut adalah langkah-langkah untuk menginstal dan menjalankan proyek ini di lingkungan pengembangan lokal.

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- Node.js & NPM
- Database (misalnya MySQL, MariaDB, atau SQLite)

### Langkah-langkah Instalasi

1.  **Clone Repository**

    ```bash
    git clone <URL_REPOSITORY_ANDA>
    cd kp
    ```

2.  **Install Dependensi PHP**

    ```bash
    composer install
    ```

3.  **Install Dependensi JavaScript**

    ```bash
    npm install
    ```

4.  **Buat File Environment**

    Salin file `.env.example` menjadi `.env`. File ini berisi semua konfigurasi untuk lingkungan Anda.

    ```bash
    cp .env.example .env
    ```

5.  **Generate Kunci Aplikasi**

    ```bash
    php artisan key:generate
    ```

6.  **Konfigurasi Database**

    Buka file `.env` dan sesuaikan variabel berikut sesuai dengan pengaturan database lokal Anda:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nama_database_anda
    DB_USERNAME=user_database_anda
    DB_PASSWORD=password_database_anda
    ```

7.  **Konfigurasi Telegram**

    Tambahkan token Bot Telegram dan ID Grup utama ke dalam file `.env`:

    ```env
    TELEGRAM_BOT_TOKEN=token_bot_telegram_anda
    TELEGRAM_CHAT_ID=id_grup_telegram_anda
    ```

8.  **Jalankan Migrasi dan Seeder Database**

    Perintah ini akan membuat semua tabel yang diperlukan dan mengisi data awal (seperti status, role, dan user admin).

    ```bash
    php artisan migrate --seed
    ```

9.  **Jalankan Development Server**

    Proyek ini menggunakan `concurrently` untuk menjalankan server PHP, antrian (queue), dan Vite secara bersamaan.

    ```bash
    npm run dev
    ```

10. **Akses Aplikasi**

    Setelah server berjalan, aplikasi dapat diakses di `http://127.0.0.1:8000` atau alamat lain yang ditampilkan di terminal.

## 4. Struktur Direktori Penting

Memahami struktur direktori akan mempercepat proses pengembangan dan perbaikan.

- `app/Http/Controllers/`
  - Berisi controller standar, salah satunya `TelegramController.php` yang menangani webhook dari Telegram.

- `app/Livewire/`
  - **Direktori paling penting.** Berisi semua komponen interaktif yang menjadi inti dari aplikasi ini. Setiap komponen merepresentasikan sebuah halaman atau bagian dari halaman (misalnya, `FalloutReportDashboard.php`, `PelurusanReportDetail.php`). Logika bisnis sisi pengguna (seperti mengubah status, mengambil order) sebagian besar ada di sini.

- `app/Jobs/`
  - Berisi pekerjaan (jobs) yang dijalankan di latar belakang (antrian/queue). Sebagian besar digunakan untuk memproses update dari Telegram dan mengirim notifikasi tanpa membuat pengguna menunggu.

- `app/Console/Commands/`
  - Berisi perintah-perintah custom yang bisa dijalankan melalui `php artisan`. Perintah-perintah ini digunakan untuk **logika eskalasi otomatis Fallout** dan tugas-tugas terjadwal lainnya.

- `app/Models/`
  - Berisi definisi model Eloquent yang merepresentasikan tabel-tabel di database, seperti `FalloutReport.php`, `PelurusanReport.php`, dan `User.php`.

- `resources/views/`
  - Berisi semua file tampilan (views). Terutama di dalam `resources/views/livewire/` yang berpasangan dengan komponen di `app/Livewire/`.

- `routes/`
  - `web.php` mendefinisikan rute-rute utama aplikasi yang dapat diakses melalui browser.
  - `console.php` mendefinisikan tugas-tugas terjadwal (scheduled tasks).

- `database/migrations/`
  - Berisi file-file migrasi yang mendefinisikan skema database. Sangat berguna untuk memahami struktur tabel dan relasinya.

## 5. Fitur Utama & Logika Bisnis

### 5.1. Manajemen Laporan Fallout

Alur kerja laporan fallout dirancang agar proaktif dengan eskalasi otomatis untuk memastikan setiap laporan ditangani tepat waktu.

- **Pembuatan Laporan:** Laporan dapat dibuat oleh pengguna melalui antarmuka web atau dikirim melalui bot Telegram.
- **Penugasan (Assignment):** Teknisi dapat "mengambil" laporan yang belum ditugaskan dari dashboard. Setelah diambil, status berubah menjadi `OnProgress`.
- **Pembaruan Status:** Teknisi yang ditugaskan dapat mengubah status laporan (misalnya ke `FA`, `PI`, `Eskalasi`).
- **Logika Eskalasi Otomatis:** Ini adalah fitur kunci. Sistem memiliki tugas terjadwal (cron jobs) yang berjalan secara periodik untuk memantau laporan fallout:
    - `CheckUnassignedFalloutReports`: Mencari laporan yang berstatus `Open` terlalu lama dan mengirim notifikasi.
    - `CheckUncompletedFalloutReports`: Mencari laporan yang berstatus `OnProgress` terlalu lama tanpa pembaruan dan mengirim notifikasi.
    - Logika ini diatur di `app/Console/Commands/` dan dijadwalkan di `routes/console.php`.

### 5.2. Manajemen Laporan Pelurusan

Alur kerja pelurusan lebih sederhana dan mengandalkan intervensi manual.

- **Pembuatan & Penugasan:** Mirip dengan Fallout, laporan dibuat dan diambil oleh teknisi.
- **Pembaruan Status:** Teknisi dapat mengubah status laporan.
- **Logika Eskalasi Manual:** Tidak ada eskalasi otomatis. Perubahan status ke `Eskalasi` dilakukan secara manual oleh teknisi melalui tombol di halaman detail laporan (`PelurusanReportDetail.php`).
- **Fleksibilitas Status:** Tidak ada penguncian status. Teknisi dapat mengubah status dari `Eskalasi` kembali ke status lain jika diperlukan (sesuai perubahan yang telah diimplementasikan).

### 5.3. Notifikasi Telegram

Sistem secara aktif mengirim notifikasi ke grup Telegram utama dan pengguna perorangan untuk berbagai kejadian:

- Laporan baru dibuat.
- Laporan diambil oleh teknisi.
- Status laporan diperbarui.
- Notifikasi eskalasi otomatis.

Logika pengiriman notifikasi ditangani oleh `App\Jobs\SendTelegramNotificationJob.php` untuk memastikan tidak memperlambat aplikasi.

## 6. Tugas Terjadwal (Scheduled Tasks)

Aplikasi ini menggunakan Penjadwal (Scheduler) Laravel untuk menjalankan tugas-tugas secara otomatis di latar belakang. Konfigurasi jadwal ini terdapat di `app/Console/Kernel.php`.

**Penting:** Agar penjadwal ini berjalan, Anda perlu menambahkan satu baris konfigurasi Cron di server produksi Anda:

```bash
* * * * * cd /path-ke-proyek-anda && php artisan schedule:run >> /dev/null 2>&1
```

Berikut adalah tugas-tugas yang dijadwalkan:

1.  **`CheckUnassignedFalloutReports`**
    - **Perintah:** `app/Console/Commands/CheckUnassignedFalloutReports.php`
    - **Jadwal:** Setiap jam (`hourly`), pada hari kerja (`weekdays`), antara pukul 08:00 dan 18:00 (WIB).
    - **Tujuan:** Memeriksa laporan fallout yang belum ditugaskan (status `Open`) dan mengirimkan notifikasi untuk eskalasi.

2.  **`CheckUncompletedFalloutReports`**
    - **Perintah:** `app/Console/Commands/CheckUncompletedFalloutReports.php`
    - **Jadwal:** Setiap jam (`hourly`), pada hari kerja (`weekdays`), antara pukul 08:00 dan 18:00 (WIB).
    - **Tujuan:** Memeriksa laporan fallout yang sudah ditugaskan tapi tidak selesai (status `OnProgress`) dalam waktu yang lama dan mengirimkan notifikasi untuk eskalasi.

## 7. Panduan Troubleshooting

Berikut adalah panduan untuk mendiagnosis dan menyelesaikan masalah umum.

### Lokasi Log Error

Semua error yang terjadi di aplikasi (baik dari backend maupun proses antrian) akan dicatat di file log utama Laravel:

- **Lokasi:** `storage/logs/laravel.log`

Selalu periksa file ini terlebih dahulu saat terjadi masalah yang tidak terduga. Anda bisa memantaunya secara real-time dengan perintah:

```bash
tail -f storage/logs/laravel.log
```

### Masalah Umum dan Solusinya

1.  **Notifikasi Telegram Tidak Terkirim**
    - **Penyebab Umum:**
        - Token bot (`TELEGRAM_BOT_TOKEN`) atau ID grup (`TELEGRAM_CHAT_ID`) di file `.env` salah.
        - Bot belum ditambahkan sebagai admin di grup Telegram.
        - Pengguna yang dituju belum pernah berinteraksi dengan bot (bot tidak bisa memulai percakapan).
        - Ada error pada saat proses pengiriman (periksa `laravel.log`).
    - **Solusi:**
        - Pastikan konfigurasi di `.env` sudah benar.
        - Pastikan bot memiliki izin yang cukup di grup.
        - Minta pengguna untuk memulai chat dengan bot terlebih dahulu.

2.  **Tugas Terjadwal (Eskalasi Otomatis) Tidak Berjalan**
    - **Penyebab Umum:**
        - Cron job di server belum di-setup dengan benar (lihat bagian 6).
        - Proses antrian (`php artisan queue:listen`) tidak berjalan atau macet.
    - **Solusi:**
        - Pastikan cron job sudah terpasang dan menunjuk ke direktori proyek yang benar.
        - Restart proses antrian. Untuk produksi, disarankan menggunakan Supervisor untuk menjaga agar proses antrian selalu berjalan.

3.  **Perubahan pada File CSS/JS Tidak Muncul**
    - **Penyebab Umum:**
        - Proses Vite (`npm run dev`) tidak berjalan.
        - Cache browser masih menyimpan versi lama.
    - **Solusi:**
        - Pastikan `npm run dev` aktif saat development.
        - Lakukan *hard refresh* di browser (Ctrl + Shift + R).
        - Untuk produksi, jalankan `npm run build` untuk membuat versi file aset yang sudah terkompilasi.

4.  **Error "419 Page Expired"**
    - **Penyebab Umum:**
        - Halaman form dibiarkan terbuka terlalu lama, menyebabkan CSRF token menjadi tidak valid.
    - **Solusi:**
        - Refresh halaman dan coba lagi. Ini adalah perilaku keamanan standar di Laravel.
