# Kuliner - Sistem Manajemen Restoran

Aplikasi web berbasis **PHP + MySQL** untuk manajemen restoran kuliner. Dibangun sebagai proyek tugas Basis Data.

## 🏗️ Struktur Proyek

```
Project-Basdat-Kuliner/
├── config/
│   └── database.php         # Konfigurasi & koneksi MySQL
├── assets/
│   ├── css/style.css        # Main stylesheet (SB Admin 2 design)
│   └── js/main.js           # JavaScript utama (modal, toast, dll)
├── includes/
│   ├── header.php           # HTML <head> + buka <body>
│   ├── sidebar.php          # Navigasi sidebar
│   ├── topbar.php           # Topbar + search
│   └── footer.php           # Script closing + modal global
├── pages/
│   ├── dashboard.php        # Halaman utama dengan KPI cards
│   ├── kategori.php         # CRUD Kategori Menu
│   ├── menu.php             # CRUD Menu
│   ├── pegawai.php          # CRUD Pegawai
│   ├── pelanggan.php        # CRUD Pelanggan
│   ├── pesanan.php          # CRUD Pesanan + Detail Item
│   └── pembayaran.php       # Proses & riwayat pembayaran
├── index.php                # Front controller (router)
└── kuliner.sql              # Skema database MySQL
```

## 🗄️ Tabel Database

| Tabel | Deskripsi |
|-------|-----------|
| `kategori_menu` | Kategori (Makanan, Minuman, dll) |
| `menu` | Item menu dengan harga & ketersediaan |
| `pegawai` | Staf (Kasir, Admin, Dapur) |
| `pelanggan` | Data pelanggan |
| `pesanan` | Header pesanan |
| `detail_pesanan` | Item per pesanan (level pedas, catatan) |
| `pembayaran` | Transaksi pembayaran |

## 🚀 Cara Menjalankan

1. Import `kuliner.sql` ke MySQL
2. Edit `config/database.php` sesuai kredensial MySQL Anda
3. Taruh folder di htdocs XAMPP / laragon
4. Akses: `http://localhost/Project-Basdat-Kuliner/`

## 🌿 Git Branching Strategy

```
main
├── feature/setup-project      # Struktur dasar + config + assets
├── feature/dashboard          # Dashboard KPI
├── feature/kategori-menu      # CRUD Kategori & Menu
├── feature/pegawai-pelanggan  # CRUD Pegawai & Pelanggan
├── feature/pesanan            # CRUD Pesanan + detail item
└── feature/pembayaran         # Modul pembayaran
```

## ✨ Fitur

- ✅ Dashboard ringkasan (total pendapatan, pesanan, menu, pelanggan)
- ✅ CRUD Kategori Menu
- ✅ CRUD Menu (dengan status Tersedia/Habis)
- ✅ CRUD Pegawai (Kasir / Admin / Dapur)
- ✅ CRUD Pelanggan
- ✅ Buat & kelola pesanan (Dine-in / Takeaway)
- ✅ Tambah item ke pesanan (level pedas, catatan)
- ✅ Update status pesanan (Diproses → Selesai / Batal)
- ✅ Proses pembayaran dengan kalkulator kembalian
- ✅ Riwayat pembayaran (Tunai / QRIS / Transfer)
- ✅ Konfirmasi delete & notifikasi toast
- ✅ Responsive (mobile friendly)
