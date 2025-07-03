# 👥 User Manual - Web Application

## 🎯 Overview

Aplikasi web ini adalah sistem manajemen yang menyediakan fitur-fitur untuk mengelola produk, pelanggan, pesanan, dan inventori. Aplikasi memiliki dua level akses: **Admin** dan **User** dengan hak akses yang berbeda.

## 🔐 Getting Started

### Login ke Sistem
1. Buka browser dan akses aplikasi (contoh: `http://localhost:8000`)
2. Masukkan **Username** dan **Password**
3. Klik tombol **Login**
4. Sistem akan mengarahkan ke dashboard sesuai role

### Default Accounts
- **Admin:** username `admin`, password `password`
- **User:** username `user1`, password `password`

## 👤 User Roles & Permissions

### Administrator
- ✅ Akses penuh ke semua fitur
- ✅ Manajemen user (CRUD)
- ✅ Manajemen produk dan kategori
- ✅ Manajemen supplier
- ✅ View dan generate reports
- ✅ Manajemen sistem

### Regular User
- ✅ View dashboard
- ✅ Manajemen pesanan
- ✅ View produk dan inventory
- ✅ Update profil sendiri
- ❌ Tidak bisa manage user lain
- ❌ Tidak bisa akses system settings

## 🏠 Dashboard

### Admin Dashboard
Dashboard admin menampilkan:
- **Statistics Cards:** Total users, products, orders, revenue
- **Recent Activities:** Log aktivitas terbaru
- **Quick Actions:** Shortcut ke fitur utama
- **Charts:** Grafik penjualan dan inventory

### User Dashboard
Dashboard user menampilkan:
- **My Statistics:** Pesanan saya, aktivitas
- **Quick Access:** Fitur yang sering digunakan
- **Notifications:** Pemberitahuan penting

## 👥 User Management (Admin Only)

### View Users
1. Navigate ke **Admin → Users → User List**
2. Tabel menampilkan semua user dengan info: username, email, role, status
3. Gunakan **Search** untuk mencari user tertentu
4. **Filter** berdasarkan role atau status

### Add New User
1. Klik **Add New User**
2. Isi form dengan data:
   - Username (unique)
   - Email (unique)
   - Password
   - Full Name
   - Phone
   - Role (Admin/User)
3. Klik **Save** untuk menyimpan

### Edit User
1. Klik **Edit** pada user yang ingin diubah
2. Modify data yang diperlukan
3. Klik **Update** untuk menyimpan perubahan

### Delete User
1. Klik **Delete** pada user yang ingin dihapus
2. Konfirmasi penghapusan
3. User akan dinonaktifkan (soft delete)

## 📦 Product Management

### View Products
1. Navigate ke **Products → Product List**
2. Tabel menampilkan: nama, kategori, supplier, harga, stock
3. **Color coding:** 
   - 🔴 Stock rendah (di bawah minimum)
   - 🟡 Stock normal
   - 🟢 Stock tinggi

### Add Product
1. Klik **Add New Product**
2. Isi form lengkap:
   - Product Name
   - Category (dropdown)
   - Supplier (dropdown)
   - Description
   - Price
   - Initial Stock
   - Minimum Stock Level
   - SKU (auto-generate available)
3. Upload gambar produk (optional)
4. **Save** produk

### Manage Categories
1. Navigate ke **Products → Categories**
2. **Add Category:** nama dan deskripsi
3. **Edit/Delete:** manage kategori existing

### Manage Suppliers
1. Navigate ke **Products → Suppliers**
2. **Add Supplier:** info lengkap supplier
3. **Contact Management:** info kontak supplier

## 🛒 Order Management

### View Orders
1. Navigate ke **Orders → Order List**
2. Filter berdasarkan:
   - Status (Pending, Processing, Completed, Cancelled)
   - Payment Status (Paid, Unpaid, Partial)
   - Date Range
   - Customer

### Create New Order
1. Klik **New Order**
2. **Step 1:** Select Customer (atau add new)
3. **Step 2:** Add Products
   - Search product
   - Set quantity
   - Review price
4. **Step 3:** Review & Confirm
   - Check total amount
   - Add notes (optional)
5. **Submit Order**

### Process Order
1. Open order detail
2. **Update Status:**
   - Pending → Processing
   - Processing → Completed
   - Any → Cancelled
3. **Add Payment:** record payment received
4. **Print Invoice/Receipt** (if available)

## 💰 Payment Management

### Record Payment
1. Dari order detail, klik **Add Payment**
2. Select payment method:
   - Cash
   - Bank Transfer
   - Credit/Debit Card
   - E-Wallet
3. Enter amount dan reference number
4. **Save Payment**

### Payment History
1. Navigate ke **Payments → Payment History**
2. View semua transaksi pembayaran
3. Filter berdasarkan method, date, status

## 📊 Inventory Management

### Stock Overview
1. Navigate ke **Inventory → Stock Overview**
2. View current stock semua produk
3. **Alerts:** produk dengan stock rendah
4. **Search & Filter** produk

### Stock Adjustment
1. Navigate ke **Inventory → Stock Adjustment**
2. Select produk yang akan di-adjust
3. Choose adjustment type:
   - **Stock In:** penambahan stock
   - **Stock Out:** pengurangan stock
   - **Adjustment:** koreksi stock
4. Enter quantity dan alasan
5. **Save Adjustment**

### Inventory History
1. View semua transaksi inventory
2. Track stock movement
3. Filter berdasarkan produk, date, type

## 📈 Reports (Admin Only)

### User Report
- Total users berdasarkan role
- User activity summary
- Registration trends

### Sales Report
- Revenue per periode
- Top selling products
- Sales by category

### Inventory Report
- Stock levels overview
- Low stock alerts
- Inventory value

### Generate Reports
1. Select report type
2. Choose date range
3. Apply filters (optional)
4. **Generate Report**
5. **Export** ke PDF/Excel (if available)

## ⚙️ System Settings (Admin Only)

### Application Settings
- App name dan logo
- Default currency
- Time zone settings
- System notifications

### User Settings
- Password requirements
- Session timeout
- Default user role

## 🔒 Security Features

### Password Management
1. Navigate ke **Profile → Change Password**
2. Enter current password
3. Enter new password (min 8 characters)
4. Confirm new password
5. **Update Password**

### Session Management
- Auto logout setelah inaktif (default: 30 menit)
- Secure session handling
- Activity logging

### Activity Logs
- Semua aktivitas user tercatat
- Admin dapat view activity logs semua user
- User hanya bisa view activity log sendiri

## 🆘 Troubleshooting

### Common Issues

**Cannot Login**
- Check username/password
- Ensure account is active
- Clear browser cache

**Permission Denied**
- Check user role
- Contact admin for access

**Data Not Saving**
- Check required fields
- Verify data format
- Check internet connection

**Stock Discrepancy**
- Check inventory history
- Verify recent transactions
- Contact admin for adjustment

### Getting Help
1. Check system notifications
2. Review user manual
3. Contact system administrator
4. Check activity logs for errors

## 📱 Mobile Usage

### Responsive Design
- Aplikasi support mobile browser
- Touch-friendly interface
- Optimized untuk tablet dan smartphone

### Mobile Best Practices
- Use portrait mode untuk forms
- Landscape mode untuk tables
- Touch gestures support

---

## 💡 Tips & Best Practices

1. **Regular Backup:** Admin should backup data regularly
2. **Strong Passwords:** Use complex passwords
3. **Stock Monitoring:** Check low stock alerts daily
4. **Data Accuracy:** Double-check data entry
5. **Regular Updates:** Keep user info updated

---
**User Manual v1.0** | Last Updated: June 2025