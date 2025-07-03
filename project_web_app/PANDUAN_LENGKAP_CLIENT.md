# 🚀 Panduan Lengkap Sistem Manajemen Web - UNTUK CLIENT

## 📋 DAFTAR ISI
1. [Cara Install Program](#-cara-install-program)
2. [Login Pertama Kali](#-login-pertama-kali)
3. [Penjelasan Dashboard](#-penjelasan-dashboard)
4. [Manajemen User](#-manajemen-user)
5. [Manajemen Customer](#-manajemen-customer)
6. [Manajemen Produk](#-manajemen-produk)
7. [Manajemen Order/Pesanan](#-manajemen-orderpesanan)
8. [Laporan & Statistik](#-laporan--statistik)
9. [Tips & Troubleshooting](#-tips--troubleshooting)
10. [Dukungan & Revisi](#-dukungan--revisi)

---

## 🛠️ CARA INSTALL PROGRAM

### Langkah 1: Persiapan XAMPP
1. **Download XAMPP** dari https://www.apachefriends.org/
2. **Install XAMPP** di komputer Anda
3. **Buka XAMPP Control Panel**
4. **Start Apache dan MySQL** (tombol Start pada kedua service)

### Langkah 2: Copy File Program
1. **Copy folder `project_web_app`** ke dalam folder `htdocs` XAMPP
   - Lokasi htdocs biasanya: `C:\xampp\htdocs\` (Windows) atau `/Applications/XAMPP/htdocs/` (Mac)
2. **Pastikan struktur folder seperti ini:**
   ```
   C:\xampp\htdocs\project_web_app\
   ├── admin/
   ├── assets/
   ├── config/
   └── index.php
   ```

### Langkah 3: Setup Database
1. **Buka browser** dan ketik: `http://localhost/phpmyadmin`
2. **Klik "New"** untuk membuat database baru
3. **Ketik nama database:** `web_app_db`
4. **Klik "Create"**
5. **Pilih database yang baru dibuat**
6. **Klik tab "Import"**
7. **Pilih file:** `project_web_app/database/create_database.sql`
8. **Klik "Go"** untuk import struktur database
9. **Ulangi langkah 6-8** dengan file: `project_web_app/database/sample_data.sql`

### Langkah 4: Konfigurasi Program
1. **Buka file:** `project_web_app/config/database.php`
2. **Sesuaikan pengaturan database** (biasanya sudah benar):
   ```php
   private $host = 'localhost';
   private $dbname = 'web_app_db';
   private $username = 'root';
   private $password = '';  // Kosong untuk XAMPP default
   ```

### Langkah 5: Test Program
1. **Buka browser** dan ketik: `http://localhost/project_web_app`
2. **Jika muncul halaman login**, berarti instalasi BERHASIL!

---

## 🔐 LOGIN PERTAMA KALI

### Akun Administrator
- **Username:** `admin`
- **Password:** `password`

### Akun User Biasa
- **Username:** `user1`
- **Password:** `password`

**⚠️ PENTING:** Segera ganti password setelah login pertama!

### Cara Ganti Password:
1. Login ke sistem
2. Klik nama Anda di pojok kanan atas
3. Pilih "Edit Profile"
4. Ganti password lama dengan yang baru
5. Klik "Update"

---

## 🏠 PENJELASAN DASHBOARD

### Dashboard Admin (Setelah Login sebagai Admin)
Dashboard adalah halaman utama yang menampilkan:

#### 📊 Kartu Statistik (4 kotak berwarna)
- **Total Users:** Jumlah pengguna sistem
- **Total Products:** Jumlah produk dalam sistem
- **Total Orders:** Jumlah pesanan yang masuk
- **Total Revenue:** Total pendapatan

#### 🔗 Quick Actions (Tombol Cepat)
- **Add User:** Tambah pengguna baru
- **Add Product:** Tambah produk baru
- **View Reports:** Lihat laporan

#### 📋 Recent Activity
- Menampilkan aktivitas terbaru dalam sistem
- Siapa yang login, kapan, melakukan apa

---

## 👥 MANAJEMEN USER

### Cara Masuk ke Manajemen User:
1. **Klik menu "Users"** di sidebar kiri
2. **Pilih "All Users"**

### Fitur yang Tersedia:

#### ✅ Lihat Daftar User
- Tabel menampilkan semua pengguna
- Informasi: Username, Email, Nama Lengkap, Telepon, Role, Status
- **Search Box:** Ketik nama untuk mencari user tertentu
- **Pagination:** Navigasi halaman jika user banyak

#### ✅ Tambah User Baru
1. **Klik tombol "Add User"** (warna biru)
2. **Isi form lengkap:**
   - Username (unik, tidak boleh sama)
   - Email (format email yang benar)
   - Nama Lengkap
   - Nomor Telepon
   - Password
   - Role (Admin atau User)
3. **Klik "Save"**

#### ✅ Edit User
1. **Klik tombol "Edit"** (warna kuning) pada user yang ingin diubah
2. **Ubah data yang diperlukan**
3. **Klik "Update"**

#### ✅ Hapus User
1. **Klik tombol "Delete"** (warna merah)
2. **Konfirmasi penghapusan**
3. User akan dinonaktifkan (tidak benar-benar dihapus)

#### ✅ Lihat Detail User
1. **Klik tombol "View"** (warna biru)
2. Muncul popup dengan detail lengkap user

---

## 👤 MANAJEMEN CUSTOMER

### Cara Masuk ke Manajemen Customer:
1. **Klik menu "Customers"** di sidebar kiri
2. **Pilih "All Customers"**

### Fitur yang Tersedia:

#### ✅ Lihat Daftar Customer
- Tabel semua customer/pelanggan
- Info: Nama, Email, Telepon, Alamat, Status
- **Search:** Cari customer berdasarkan nama

#### ✅ Tambah Customer Baru
1. **Klik "Add Customer"**
2. **Isi data customer:**
   - Nama lengkap
   - Email
   - Nomor telepon
   - Alamat lengkap
   - Kota
   - Kode Pos
3. **Klik "Save"**

#### ✅ Edit Customer
1. **Klik "Edit"** pada customer yang ingin diubah
2. **Update informasi**
3. **Klik "Update"**

#### ✅ Lihat Detail Customer
1. **Klik "View"** untuk melihat profil lengkap
2. **Melihat riwayat pesanan** customer tersebut

---

## 📦 MANAJEMEN PRODUK

### Cara Masuk ke Manajemen Produk:
1. **Klik menu "Products"** di sidebar kiri
2. **Pilih "All Products"**

### Fitur yang Tersedia:

#### ✅ Lihat Daftar Produk
- Tabel semua produk
- Info: Nama, SKU, Kategori, Harga, Stok
- **Indikator Stok:**
  - 🟢 Hijau: Stok aman
  - 🟡 Kuning: Stok menipis
  - 🔴 Merah: Stok habis/kritis

#### ✅ Tambah Produk Baru
1. **Klik "Add Product"**
2. **Isi informasi produk:**
   - Nama produk
   - SKU (kode produk) - bisa auto-generate
   - Deskripsi
   - Kategori
   - Harga jual
   - Harga beli (cost)
   - Stok awal
   - Minimum stok (untuk alert)
   - Upload gambar produk
3. **Klik "Save"**

#### ✅ Edit Produk
1. **Klik "Edit"** pada produk
2. **Update informasi** yang diperlukan
3. **Ganti gambar** jika perlu
4. **Klik "Update"**

#### ✅ Manajemen Kategori
1. **Klik "Categories"** di menu Products
2. **Tambah kategori baru** atau edit yang existing
3. **Nonaktifkan kategori** yang tidak dipakai

---

## 🛒 MANAJEMEN ORDER/PESANAN

### Cara Masuk ke Manajemen Order:
1. **Klik menu "Orders"** di sidebar kiri
2. **Pilih "All Orders"**

### Fitur yang Tersedia:

#### ✅ Lihat Daftar Order
- Tabel semua pesanan
- Info: Nomor Order, Customer, Tanggal, Total, Status
- **Status Order:**
  - 🟡 Pending: Menunggu konfirmasi
  - 🔵 Processing: Sedang diproses
  - 🟢 Completed: Selesai
  - 🔴 Cancelled: Dibatalkan

#### ✅ Tambah Order Baru
1. **Klik "Add Order"**
2. **Pilih Customer** dari dropdown
3. **Pilih produk** dan tentukan jumlah
4. **Review total** harga
5. **Tambah catatan** jika perlu
6. **Klik "Save Order"**

#### ✅ Lihat Detail Order
1. **Klik "View"** pada order
2. **Lihat detail lengkap:**
   - Info customer
   - Daftar produk yang dipesan
   - Total pembayaran
   - Status order
3. **Update status** order jika diperlukan

#### ✅ Print Invoice
1. **Buka detail order**
2. **Klik "Print Invoice"**
3. **Invoice siap untuk dicetak**

---

## 📊 LAPORAN & STATISTIK

### Cara Masuk ke Laporan:
1. **Klik menu "Reports"** di sidebar kiri

### Jenis Laporan:

#### ✅ Dashboard Reports
- **Statistik umum** sistem
- **Grafik penjualan** per bulan
- **Top produk** terlaris
- **Analisis customer**

#### ✅ Sales Report
1. **Klik "Sales Report"**
2. **Pilih periode** (tanggal mulai - tanggal akhir)
3. **Pilih jenis periode:**
   - Daily: Harian
   - Weekly: Mingguan  
   - Monthly: Bulanan
4. **Klik "Generate"**
5. **Lihat hasil:**
   - Total penjualan
   - Jumlah order
   - Customer unik
   - Rata-rata order

#### 📈 Cara Membaca Laporan:
- **Revenue:** Total pendapatan dalam periode
- **Orders Count:** Jumlah pesanan
- **Unique Customers:** Jumlah customer berbeda
- **Average Order Value:** Rata-rata nilai per pesanan

---

## 🔧 TIPS & TROUBLESHOOTING

### ✅ Tips Penggunaan Sehari-hari:

#### 1. **Backup Data Rutin**
- Export database lewat phpMyAdmin setiap minggu
- Copy folder project_web_app sebagai backup

#### 2. **Manajemen Stok**
- Set minimum stok untuk setiap produk
- Check laporan stok rutin
- Update stok setelah penjualan/pembelian

#### 3. **User Management**
- Jangan buat terlalu banyak admin
- Gunakan role "User" untuk karyawan biasa
- Ganti password secara berkala

### ❗ Troubleshooting Masalah Umum:

#### Problem: "Database connection failed"
**Solusi:**
1. Pastikan MySQL jalan di XAMPP
2. Check pengaturan di config/database.php
3. Pastikan database 'web_app_db' sudah dibuat

#### Problem: "Access denied" saat login
**Solusi:**
1. Check username dan password
2. Pastikan data user ada di database
3. Clear browser cache dan coba lagi

#### Problem: Gambar produk tidak muncul
**Solusi:**
1. Pastikan folder assets/uploads/ ada
2. Check permissions folder (chmod 755)
3. Upload ulang gambar dengan format JPG/PNG

#### Problem: Laporan tidak muncul data
**Solusi:**
1. Pastikan ada data order di periode tersebut
2. Check tanggal filter
3. Pastikan database ter-update

#### Problem: Halaman lambat loading
**Solusi:**
1. Restart Apache di XAMPP
2. Clear browser cache
3. Check apakah database terlalu besar

---

## 🎯 WORKFLOW OPERASIONAL HARIAN

### Untuk Admin/Pemilik:
1. **Login** pagi hari
2. **Check dashboard** untuk overview
3. **Review order baru** yang masuk
4. **Update status order** yang sudah diproses
5. **Check stok produk** yang menipis
6. **Review laporan** mingguan/bulanan

### Untuk Staff/User:
1. **Login** dengan akun user
2. **Input order baru** dari customer
3. **Update status order** yang sudah diproses
4. **Check stok** sebelum terima order
5. **Print invoice** untuk customer

---

## 📞 DUKUNGAN & REVISI

### ✅ **GRATIS REVISI MINOR:**
Kami menyediakan revisi gratis untuk:
- **Perubahan warna** tema/button
- **Penyesuaian layout** tabel/form
- **Penambahan field** sederhana di form
- **Perubahan text** atau label
- **Penyesuaian report** format
- **Bug fixing** dan error handling
- **Tutorial tambahan** jika ada fitur yang belum jelas

### ✅ **Yang Termasuk Support Gratis:**
- Panduan instalasi ulang jika diperlukan
- Penjelasan fitur yang belum dipahami
- Troubleshooting masalah teknis
- Update minor untuk keamanan
- Backup dan restore database
- Training penggunaan untuk tim

### 📞 **Hubungi Developer:**
- **WhatsApp/Telegram:** [Nomor Developer]
- **Email:** [Email Developer] 
- **Response time:** Maksimal 24 jam
- **Remote support:** Via TeamViewer jika diperlukan

### 🕐 **Jam Support:**
- **Senin - Jumat:** 09:00 - 17:00 WIB
- **Weekend:** Emergency only
- **Response emergency:** Maksimal 4 jam

---

## 🎉 SELAMAT MENGGUNAKAN SISTEM!

Sistem ini sudah **LENGKAP** dan **SIAP PAKAI** untuk operasional bisnis Anda. Semua fitur telah ditest dan berfungsi dengan baik:

✅ **User Management** - Kelola tim dengan mudah  
✅ **Customer Database** - Data pelanggan terorganisir  
✅ **Product Catalog** - Inventory yang rapi  
✅ **Order Processing** - Proses pesanan lancar  
✅ **Reports & Analytics** - Insight bisnis yang berguna  
✅ **Responsive Design** - Bisa diakses dari HP/tablet  
✅ **Security** - Sistem login yang aman  

**Jika ada pertanyaan atau butuh bantuan, jangan ragu untuk menghubungi kami. Kami siap membantu hingga sistem berjalan sempurna untuk bisnis Anda!**

---

*Dokumen ini dibuat pada: 23 Juni 2025*  
*Versi Sistem: 1.0 - Production Ready*  
*© 2025 - Web Development Team*
