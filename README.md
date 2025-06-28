# Nginx Cache MultiServer Purger

**Versi:** 1.4.0
**Pengarang:** Hadee Roslan
**URI Pengarang:** https://hadeeroslan.my
**Lesen:** MIT License

Sebuah plugin WordPress untuk membersihkan cache Nginx FastCGI secara serentak merentasi pelbagai server di belakang *load balancer*. Direka untuk persekitaran *multi-server* yang memerlukan penyegerakan *cache purge* yang pantas dan boleh diskala.

---

## Ciri-ciri Utama

* **Pembersihan Automatik:** Membersihkan cache secara automatik untuk *post* atau *page* yang baru dikemaskini.
* **Penyegerakan Multi-Server:** Menghantar arahan *purge* kepada semua server yang disenaraikan dalam tetapan, memastikan semua cache dibersihkan serentak.
* **Pembersihan Manual:** Menyediakan antaramuka (*interface*) dalam *dashboard* admin untuk:
    * Membersihkan cache untuk URL spesifik.
    * Membersihkan **keseluruhan** cache dengan satu klik (Butang Nuklear!).
* **API Selamat:** Menggunakan REST API endpoint yang dilindungi oleh *secret key* untuk semua operasi *purge*.
* **Kemaskini dari GitHub:** Boleh dikemaskini secara automatik terus dari repositori GitHub persendirian atau awam.
* **Konfigurasi Mudah:** Semua tetapan penting seperti senarai IP server, *secret key*, dan path cache boleh diuruskan melalui halaman tetapan yang mesra pengguna.

---

## Keperluan Server (PENTING!)

Plugin ini memerlukan konfigurasi server yang spesifik untuk berfungsi dengan betul.

1.  **Server Web:** Nginx dengan `ngx_http_fastcgi_module` (untuk `fastcgi_cache`).
2.  **WordPress:** Versi 5.0 atau lebih tinggi.
3.  **PHP:** Versi 7.4 atau lebih tinggi.
4.  **Kebenaran Fail (File Permissions):** Pengguna yang menjalankan proses PHP-FPM (cth: `nginx`, `apache`, atau `www-data`) **MESTI** mempunyai kebenaran untuk menulis dan memadam (`write/delete`) fail di dalam direktori cache Nginx yang ditetapkan.
    ```bash
    # Contoh untuk user 'nginx'
    sudo chown -R nginx:nginx /var/cache/nginx/fastcgi
    sudo chmod -R 775 /var/cache/nginx/fastcgi
    ```
5.  **SELinux (Untuk RHEL/CentOS):** Jika SELinux diaktifkan, konteks untuk direktori cache mesti ditetapkan untuk membenarkan akses tulis oleh proses web.
    ```bash
    # Benarkan akses tulis oleh proses httpd
    sudo semanage fcontext -a -t httpd_sys_rw_content_t "/path/to/your/cache(/.*)?"
    sudo restorecon -Rv /path/to/your/cache
    ```

---

## Pemasangan (Installation)

1.  Muat turun `plugin-update-checker.zip` dari [halaman rasminya](https://github.com/YahnisElsts/plugin-update-checker/releases).
2.  Cipta folder `lib` di dalam direktori plugin ini.
3.  *Unzip* dan letakkan kandungan `plugin-update-checker` ke dalam folder `lib/`.
4.  Muat naik keseluruhan folder plugin (`nginx-cache-multiserver-purger`) ke direktori `/wp-content/plugins/` anda.
5.  Aktifkan plugin melalui menu 'Plugins' di WordPress.

---

## Konfigurasi

Selepas pengaktifan, pergi ke **Dashboard > Settings > MultiServer Purger**.

1.  **Senarai IP Web Server:** Masukkan semua alamat IP *internal* untuk setiap *web server* anda, satu IP setiap baris. Ini adalah senarai 'armada' yang akan menerima arahan *purge*.
2.  **Secret Key:** Klik butang **"Generate"** untuk mencipta satu kunci rahsia yang unik, atau masukkan kunci anda sendiri. Kunci ini digunakan untuk melindungi API.
3.  **Path Direktori Cache Nginx:** Masukkan path penuh ke direktori cache FastCGI anda di server (cth: `/var/cache/nginx/fastcgi`).
4.  Klik **"Simpan Tetapan"**. Ulangi langkah ini pada setiap server dalam *cluster* anda, pastikan tetapannya sama.

---

## Kemaskini Automatik dari GitHub

Untuk mengaktifkan fungsi kemaskini automatik:

1.  Buka fail utama plugin `nginx-cache-multiserver-purger.php`.
2.  Ubah suai URL repositori GitHub kepada URL anda sendiri.
    ```php
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        '[https://github.com/YOUR_USERNAME/YOUR_REPO_NAME/](https://github.com/YOUR_USERNAME/YOUR_REPO_NAME/)', // <-- Tukar di sini
        __FILE__,
        'nginx-cache-multiserver-purger'
    );
    $myUpdateChecker->setBranch('main'); // Tetapkan branch yang betul
    ```
3.  Setiap kali anda mahu mengeluarkan versi baru, naikkan nombor `Version:` dalam *header* fail utama, *commit* & *push* kod anda, dan cipta satu **"Release"** baru di GitHub dengan nombor *tag* yang sepadan (cth: `v1.4.1`).
4.  Notifikasi kemaskini akan muncul dalam *dashboard* WordPress anda.

---

## Lesen

Plugin ini dikeluarkan di bawah MIT License. Sila rujuk fail `LICENSE` untuk butiran lanjut.

---

## Changelog

### 1.4.0 (29 Jun 2025)
* **Initial release.**
* Tambah fungsi pembersihan cache automatik dan manual.
* Tambah sokongan untuk *multi-server* dengan senarai IP.
* Tambah halaman tetapan dengan penjana *secret key*.
* Integrasi dengan Plugin Update Checker untuk kemaskini dari GitHub.
