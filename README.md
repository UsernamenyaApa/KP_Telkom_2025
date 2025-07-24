KPTELKOM

Ini adalah proyek Laravel yang dikembangkan untuk mengelola laporan fallout dan pelurusan di lingkungan Telkom. Proyek ini menggunakan Livewire untuk antarmuka pengguna yang dinamis dan Spatie/Laravel-Permission untuk manajemen peran dan izin.

## Fitur

*   **Manajemen Laporan Fallout**: Melacak dan mengelola laporan fallout.
*   **Manajemen Laporan Pelurusan**: Melacak dan mengelola laporan pelurusan.
*   **Manajemen Pengguna HD Daman**: Pembuatan akun pengguna otomatis untuk setiap HD Daman, dengan email dan kata sandi yang dibuat secara otomatis berdasarkan nama dan tanggal lahir mereka.
*   **Manajemen Tipe Order**: Mengelola berbagai tipe order yang terkait dengan laporan.
*   **Manajemen Status Fallout**: Mengelola status yang berbeda untuk laporan fallout.
*   **Integrasi Telegram Bot**: Pengguna dapat berinteraksi dengan sistem melalui bot Telegram untuk membuat laporan dan menerima notifikasi.
*   **Manajemen Peran dan Izin**: Menggunakan Spatie/Laravel-Permission untuk mengelola peran pengguna (misalnya, `super-admin`, `hd-daman`).

## Instalasi

Ikuti langkah-langkah ini untuk menginstal dan menjalankan proyek secara lokal:

1.  **Kloning repositori:**
    ```bash
    git clone https://github.com/your-username/kptelkom.git
    cd kptelkom
    ```

2.  **Instal dependensi Composer:**
    ```bash
    composer install
    ```

3.  **Instal dependensi Node.js:**
    ```bash
    npm install
    ```

4.  **Buat file `.env`:**
    ```bash
    cp .env.example .env
    ```

5.  **Buat kunci aplikasi:**
    ```bash
    php artisan key:generate
    ```

6.  **Konfigurasi database:**
    Edit file `.env` dan atur kredensial database Anda:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=kptelkom
    DB_USERNAME=root
    DB_PASSWORD=
    ```

7.  **Jalankan migrasi database dan seeder:**
    ```bash
    php artisan migrate --seed
    ```

8.  **Jalankan server pengembangan:**
    ```bash
    php artisan serve
    ```

9.  **Jalankan Vite untuk aset:**
    ```bash
    npm run dev
    ```

    Proyek sekarang akan berjalan di `http://127.0.0.1:8000`.

## Penggunaan

### Peran Pengguna

Proyek ini mendefinisikan peran-peran berikut:

*   **`super-admin`**: Memiliki akses penuh ke semua fitur, termasuk manajemen pengguna, tipe order, dan status fallout.
*   **`hd-daman`**: Peran default untuk pengguna yang dibuat melalui bot Telegram atau panel admin. Memiliki akses terbatas untuk membuat dan melihat laporan mereka sendiri.

### Login Pengguna HD Daman

Ketika HD Daman baru dibuat melalui panel admin, akun pengguna yang sesuai akan dibuat secara otomatis:

*   **Email**: Berasal dari nama HD Daman (misalnya, "Daman A" menjadi `damana@tif.com`).
*   **Kata Sandi**: Berasal dari tanggal lahir HD Daman dalam format `YYMMDD` (misalnya, 30-07-2003 menjadi `030730`).

Pengguna HD Daman dapat login menggunakan email dan kata sandi yang dibuat secara otomatis ini.

### Integrasi Telegram Bot

Untuk mengaktifkan integrasi Telegram Bot, Anda perlu mengatur token bot Anda di file `.env`:

```
TELEGRAM_BOT_TOKEN=YOUR_BOT_TOKEN
```

Kemudian, atur webhook bot Anda:

```bash
php artisan telegram:set-webhook
```

Pengguna dapat memulai interaksi dengan bot dengan mengirim perintah `/start`.

## Struktur Proyek

*   `app/Models`: Model Eloquent untuk database.
*   `app/Livewire`: Komponen Livewire untuk antarmuka pengguna yang dinamis.
*   `app/Http/Controllers`: Pengontrol untuk menangani permintaan HTTP.
*   `app/Console/Commands`: Perintah Artisan kustom, termasuk perintah untuk mengatur webhook Telegram dan mengirim notifikasi.
*   `database/migrations`: Migrasi database.
*   `database/seeders`: Seeder database untuk data awal.
*   `resources/views`: File template Blade dan komponen Livewire.
*   `routes`: Definisi rute web dan API.

## Kontribusi

Silakan baca [CONTRIBUTING.md](CONTRIBUTING.md) untuk detail tentang proses kami untuk mengirimkan pull request kepada kami.

## Lisensi

Proyek ini dilisensikan di bawah Lisensi MIT - lihat file [LICENSE.md](LICENSE.md) untuk detailnya.
