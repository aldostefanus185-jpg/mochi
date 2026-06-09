<?php
include 'koneksi.php';

echo "<h2>Debug Koneksi MySQL</h2>";

try {
    // 1. Cek User yang sedang login
    $stmt = $conn->query("SELECT USER()");
    $user = $stmt->fetchColumn();
    echo "PHP Login sebagai User: <b>" . htmlspecialchars($user) . "</b><br>";

    // 2. Cek database yang digunakan
    $stmt2 = $conn->query("SELECT DATABASE()");
    $db = $stmt2->fetchColumn();
    echo "PHP Terhubung ke Database: <b>" . htmlspecialchars($db) . "</b><br><br>";

    // 3. Cari Tabel USERS
    echo "Mencari tabel USERS...<br>";
    $stmt3 = $conn->query("SHOW TABLES LIKE 'USERS'");
    $table = $stmt3->fetchColumn();

    if (!$table) {
        echo "<b style='color:red'>Tabel USERS tidak ditemukan sama sekali oleh koneksi PHP ini!</b><br>";
    } else {
        echo "<b style='color:green'>Tabel USERS ditemukan! Koneksi MySQL berhasil dan berjalan lancar.</b>";
    }
} catch (PDOException $e) {
    echo "<b style='color:red'>Koneksi atau query error: " . htmlspecialchars($e->getMessage()) . "</b>";
}
?>
