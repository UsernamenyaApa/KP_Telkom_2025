# Dokumentasi Proyek Infranexia

Dokumen ini menyediakan panduan teknis untuk pemeliharaan (maintenance), pengembangan, dan penanganan masalah (troubleshooting) pada aplikasi Infranexia.

## 1. Ringkasan Proyek

Aplikasi ini adalah sistem internal yang dirancang untuk mengelola dan melacak dua jenis laporan utama: **Laporan Fallout** dan **Laporan Pelurusan**. Sistem ini memfasilitasi alur kerja mulai dari pembuatan laporan, penugasan ke teknisi, pembaruan status, hingga penyelesaian. Aplikasi ini juga terintegrasi dengan Telegram untuk notifikasi real-time.

## 2. Teknologi yang Digunakan

Berikut adalah daftar teknologi utama yang digunakan dalam proyek ini:

- **Backend:** PHP 8.2+, [Laravel 11](https://laravel.com/)
- **Frontend:** [Livewire 3](https://livewire.laravel.com/), [Alpine.js](https://alpinejs.dev/)
- **UI & Styling:** [Tailwind CSS 4](https://tailwindcss.com/), [Flux (Komponen dari Livewire)](https://livewire.laravel.com/docs/flux)
- **Database:** Menggunakan driver database Laravel.
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
    git clone https://github.com/UsernamenyaApa/KP_Telkom_2025.git
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

    Tambahkan token Bot Telegram ke dalam file `.env`. ID Grup untuk notifikasi tidak diatur di sini, melainkan dikelola di dalam aplikasi.

    ```env
    TELEGRAM_BOT_TOKEN=token_bot_telegram_anda
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
  - Berisi perintah-perintah custom yang bisa dijalankan melalui `php artisan`. Contohnya adalah `SetTelegramWebhookCommand.php` yang digunakan untuk mendaftarkan webhook bot Telegram saat setup awal.

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

Alur kerja laporan fallout mengandalkan intervensi manual untuk setiap tahapannya.

- **Pembuatan Laporan:** Laporan dapat dibuat oleh pengguna melalui antarmuka web atau dikirim melalui bot Telegram.
- **Penugasan (Assignment):** Teknisi dapat "mengambil" laporan yang belum ditugaskan dari dashboard. Setelah diambil, status berubah menjadi `OnProgress`.
- **Pembaruan Status & Eskalasi:** Teknisi yang ditugaskan dapat mengubah status laporan (misalnya ke `FA`, `PI`, `Eskalasi`). Semua perubahan status, termasuk eskalasi, harus dilakukan secara **manual** melalui antarmuka pengguna. Tidak ada sistem eskalasi otomatis yang berjalan.

### 5.2. Manajemen Laporan Pelurusan

Alur kerja pelurusan mirip dengan fallout dan juga mengandalkan intervensi manual.

- **Pembuatan & Penugasan:** Mirip dengan Fallout, laporan dibuat dan diambil oleh teknisi.
- **Pembaruan Status & Eskalasi:** Perubahan status ke `Eskalasi` dilakukan secara manual oleh teknisi melalui halaman detail laporan (`PelurusanReportDetail.php`).
- **Fleksibilitas Status:** Tidak ada penguncian status. Teknisi dapat mengubah status dari `Eskalasi` kembali ke status lain jika diperlukan.

### 5.3. Notifikasi Telegram

Sistem secara aktif mengirim notifikasi ke grup Telegram utama dan pengguna perorangan untuk berbagai kejadian. ID grup target diambil dari tabel `telegram_groups` di database, bukan dari file `.env`.

- Laporan baru dibuat.
- Laporan diambil oleh teknisi.
- Status laporan diperbarui.

Logika pengiriman notifikasi ditangani oleh `App\Jobs\SendTelegramNotificationJob.php` untuk memastikan tidak memperlambat aplikasi.

### 5.4. Sistem Antrian (Queue)

Sistem ini menggunakan antrian untuk menangani tugas-tugas yang memakan waktu, seperti mengirim notifikasi Telegram, agar tidak memperlambat interaksi pengguna.

- **Driver Aktif:** Secara default, antrian menggunakan **database**. Pekerjaan yang akan dieksekusi disimpan dalam tabel `jobs`.
- **Konfigurasi:** Driver antrian diatur dalam file `.env` melalui variabel `QUEUE_CONNECTION`. Untuk mengubahnya ke Redis, ganti nilainya menjadi `redis` dan pastikan Redis server sudah terkonfigurasi.

## 5. Panduan Troubleshooting

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
        - Token bot (`TELEGRAM_BOT_TOKEN`) di file `.env` salah.
        - ID grup tidak ada atau salah di tabel `telegram_groups` di database.
        - Bot belum ditambahkan sebagai admin di grup Telegram.
        - Pengguna yang dituju belum pernah berinteraksi dengan bot (bot tidak bisa memulai percakapan).
        - Ada error pada saat proses pengiriman (periksa `laravel.log`).
    - **Solusi:**
        - Pastikan `TELEGRAM_BOT_TOKEN` di `.env` sudah benar.
        - Pastikan ada entri yang benar di tabel `telegram_groups`.
        - Pastikan bot memiliki izin yang cukup di grup.
        - Minta pengguna untuk memulai chat dengan bot terlebih dahulu.

2.  **Proses Antrian (Queue) Tidak Berjalan**
    - **Penyebab Umum:**
        - Perintah `php artisan queue:listen` atau `queue:work` tidak berjalan atau macet. Ini akan menyebabkan notifikasi tidak terkirim.
    - **Solusi:**
        - Jalankan ulang proses antrian. Untuk produksi, sangat disarankan menggunakan Supervisor untuk menjaga agar proses antrian selalu berjalan.

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
