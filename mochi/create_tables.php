<?php
include 'koneksi.php';

$log = [];

function run($conn, $sql, $label) {
    global $log;
    try {
        $conn->exec($sql);
        $log[] = "<span style='color:green'>Success: $label</span>";
    } catch (PDOException $e) {
        $log[] = "<span style='color:orange'>Warning: $label — " . htmlspecialchars($e->getMessage()) . "</span>";
    }
}

// Drop (abaikan error jika tabel belum ada)
run($conn, "DROP TABLE IF EXISTS DETAIL_PESANAN", "Drop DETAIL_PESANAN");
run($conn, "DROP TABLE IF EXISTS PESANAN", "Drop PESANAN");
run($conn, "DROP TABLE IF EXISTS KERANJANG", "Drop KERANJANG");
run($conn, "DROP TABLE IF EXISTS PRODUK", "Drop PRODUK");
run($conn, "DROP TABLE IF EXISTS USERS", "Drop USERS");

// Buat Tabel
run($conn, "CREATE TABLE USERS (
    ID_USER INT AUTO_INCREMENT PRIMARY KEY,
    USERNAME VARCHAR(50) UNIQUE NOT NULL,
    PASSWORD VARCHAR(255) NOT NULL,
    ROLE VARCHAR(20) DEFAULT 'pelanggan'
)", "Create USERS");

run($conn, "CREATE TABLE PRODUK (
    ID_PRODUK INT AUTO_INCREMENT PRIMARY KEY,
    NAMA_PRODUK VARCHAR(100) NOT NULL,
    HARGA INT NOT NULL,
    STOK INT DEFAULT 0,
    FOTO VARCHAR(255),
    DESKRIPSI VARCHAR(500)
)", "Create PRODUK");

run($conn, "CREATE TABLE KERANJANG (
    ID_KERANJANG INT AUTO_INCREMENT PRIMARY KEY,
    ID_USER INT NOT NULL,
    ID_PRODUK INT NOT NULL,
    JUMLAH INT DEFAULT 1,
    FOREIGN KEY (ID_USER) REFERENCES USERS(ID_USER) ON DELETE CASCADE,
    FOREIGN KEY (ID_PRODUK) REFERENCES PRODUK(ID_PRODUK) ON DELETE CASCADE
)", "Create KERANJANG");

run($conn, "CREATE TABLE PESANAN (
    ID_PESANAN INT AUTO_INCREMENT PRIMARY KEY,
    ID_USER INT NOT NULL,
    TANGGAL_PESANAN TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TOTAL_HARGA INT NOT NULL,
    METODE_PEMBAYARAN VARCHAR(50),
    STATUS_PESANAN VARCHAR(30) DEFAULT 'Diproses',
    ALAMAT VARCHAR(500),
    NO_HP VARCHAR(15),
    FOREIGN KEY (ID_USER) REFERENCES USERS(ID_USER) ON DELETE CASCADE
)", "Create PESANAN");

run($conn, "CREATE TABLE DETAIL_PESANAN (
    ID_DETAIL INT AUTO_INCREMENT PRIMARY KEY,
    ID_PESANAN INT NOT NULL,
    ID_PRODUK INT NOT NULL,
    JUMLAH INT NOT NULL,
    HARGA_SATUAN INT NOT NULL,
    FOREIGN KEY (ID_PESANAN) REFERENCES PESANAN(ID_PESANAN) ON DELETE CASCADE,
    FOREIGN KEY (ID_PRODUK) REFERENCES PRODUK(ID_PRODUK) ON DELETE CASCADE
)", "Create DETAIL_PESANAN");

// Insert data awal
run($conn, "INSERT INTO USERS (USERNAME, PASSWORD, ROLE) VALUES ('admin', 'admin123', 'admin')", "Insert admin");
run($conn, "INSERT INTO USERS (USERNAME, PASSWORD, ROLE) VALUES ('customer', 'customer123', 'pelanggan')", "Insert customer default");

run($conn, "INSERT INTO PRODUK (NAMA_PRODUK, HARGA, STOK, FOTO, DESKRIPSI)
VALUES ('Chocolate', 10000, 50, 'image/coklat.jpg', 'Mochi lembut isian coklat premium')", "Insert Chocolate");

run($conn, "INSERT INTO PRODUK (NAMA_PRODUK, HARGA, STOK, FOTO, DESKRIPSI)
VALUES ('Strawberry', 10000, 50, 'image/strowberry.jpg', 'Mochi segar rasa strawberry')", "Insert Strawberry");

run($conn, "INSERT INTO PRODUK (NAMA_PRODUK, HARGA, STOK, FOTO, DESKRIPSI)
VALUES ('Matcha', 10000, 50, 'image/matcha.jpg', 'Mochi matcha Jepang asli')", "Insert Matcha");

run($conn, "INSERT INTO PRODUK (NAMA_PRODUK, HARGA, STOK, FOTO, DESKRIPSI)
VALUES ('Blueberry', 10000, 50, 'image/anggur.jpg', 'Mochi rasa blueberry segar')", "Insert Blueberry");

run($conn, "INSERT INTO PRODUK (NAMA_PRODUK, HARGA, STOK, FOTO, DESKRIPSI)
VALUES ('Manggo Creamy', 10000, 50, 'image/manggo.jpg', 'Mochi mangga creamy')", "Insert Manggo");

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Setup Database</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.card { background: #fff; border-radius: 20px; padding: 40px; width: 540px; max-width: 95%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.card h1 { font-size: 22px; color: #1a1a2e; margin-bottom: 24px; text-align: center; }
.log { list-style: none; margin-bottom: 28px; }
.log li { padding: 8px 12px; border-radius: 8px; margin-bottom: 6px; font-size: 13px; background: #f8f9fa; }
.btn { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #f093fb, #f5576c); border: none; border-radius: 12px; color: #fff; font-size: 15px; font-weight: 600; font-family: 'Poppins', sans-serif; cursor: pointer; text-align: center; text-decoration: none; }
.btn:hover { opacity: 0.9; }
.note { text-align: center; margin-top: 14px; font-size: 12px; color: #999; }
</style>
</head>
<body>
<div class="card">
 <h1>Setup Database Daifuku Mochi</h1>
 <ul class="log">
  <?php foreach ($log as $l): ?>
  <li><?= $l ?></li>
  <?php endforeach; ?>
 </ul>
  <a href="index.php" class="btn">Lanjut ke Halaman Login</a>
 <p class="note">
   Login Admin: <strong>admin</strong> / <strong>admin123</strong><br>
   Login Customer: <strong>customer</strong> / <strong>customer123</strong>
 </p>
</div>
</body>
</html>
