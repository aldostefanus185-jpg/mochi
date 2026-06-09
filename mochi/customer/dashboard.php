<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../index.php");
    exit;
}
header("Location: menu.php");
exit;
?>
