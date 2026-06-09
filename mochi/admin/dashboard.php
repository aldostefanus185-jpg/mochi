<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

/* ================= PROSES UPDATE STATUS PESANAN ================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id_pesanan = $_POST['id_pesanan'];
    $status_baru = $_POST['status_pesanan'];

    try {
        $p_update = $conn->prepare("UPDATE PESANAN SET STATUS_PESANAN = :status WHERE ID_PESANAN = :id");
        if ($p_update->execute([':status' => $status_baru, ':id' => $id_pesanan])) {
            echo "<script>alert('Status pesanan #$id_pesanan berhasil diperbarui!'); window.location='dashboard.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal memperbarui status');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Gagal memperbarui status: " . htmlentities($e->getMessage()) . "');</script>";
    }
}

try {
    /* ================= TOTAL PRODUK ================= */
    $q1 = $conn->query("SELECT COUNT(*) AS JML FROM PRODUK");
    $d1 = $q1->fetch(PDO::FETCH_ASSOC);
    $total_produk = $d1 ? $d1['JML'] : 0;

    /* ================= TOTAL PESANAN ================= */
    $q2 = $conn->query("SELECT COUNT(*) AS JML FROM PESANAN");
    $d2 = $q2->fetch(PDO::FETCH_ASSOC);
    $total_pesanan = $d2 ? $d2['JML'] : 0;

    /* ================= TOTAL USER ================= */
    $q3 = $conn->query("SELECT COUNT(*) AS JML FROM USERS WHERE ROLE='pelanggan'");
    $d3 = $q3->fetch(PDO::FETCH_ASSOC);
    $total_user = $d3 ? $d3['JML'] : 0;

    /* ================= TOTAL PENDAPATAN ================= */
    $q4 = $conn->query("SELECT COALESCE(SUM(TOTAL_HARGA),0) AS JML FROM PESANAN");
    $d4 = $q4->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $d4 ? $d4['JML'] : 0;

    /* ================= PESANAN TERBARU ================= */
    $sqlPesanan = "
    SELECT 
        P.ID_PESANAN,
        U.USERNAME,
        DATE_FORMAT(P.TANGGAL_PESANAN,'%Y-%m-%d') AS TANGGAL_PESANAN,
        P.TOTAL_HARGA,
        P.STATUS_PESANAN
    FROM PESANAN P
    JOIN USERS U ON P.ID_USER = U.ID_USER
    ORDER BY P.ID_PESANAN DESC
    LIMIT 5
    ";

    $q5 = $conn->query($sqlPesanan);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'Poppins',sans-serif; background:#f4f5f7; min-height:100vh; }

/* ================= SIDEBAR ================= */
.sidebar{
    position:fixed; top:0; left:0; width:250px; height:100vh;
    background:linear-gradient(180deg,#0f0c29,#302b63); padding:25px 0;
    display:flex; flex-direction:column; box-shadow:4px 0 20px rgba(0,0,0,0.15);
}
.sidebar-brand{ text-align:center; padding-bottom:25px; border-bottom:1px solid rgba(255,255,255,0.08); }
.sidebar-brand h2{ color:#fff; font-size:20px; font-weight:600; }
.sidebar-brand p{ color:rgba(255,255,255,0.5); font-size:12px; margin-top:5px; }
.sidebar-menu{ flex:1; margin-top:15px; }
.sidebar-menu a{
    display:flex; align-items:center; gap:12px; padding:14px 24px; margin:6px 12px;
    border-radius:12px; text-decoration:none; color:rgba(255,255,255,0.7); transition:0.3s; font-size:14px; font-weight:500;
}
.sidebar-menu a:hover, .sidebar-menu a.active{ background:rgba(255,255,255,0.12); color:#fff; }
.sidebar-footer{ padding:20px; border-top:1px solid rgba(255,255,255,0.08); }
.sidebar-footer a{ text-decoration:none; color:#ffb3b3; font-size:14px; font-weight:500; }

/* ================= MAIN ================= */
.main{ margin-left:250px; padding:30px; }

/* ================= TOP BAR ================= */
.top-bar{ display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.top-bar h1{ color:#1a1a2e; font-size:28px; font-weight:700; }
.user-info{
    display:flex; align-items:center; gap:12px; background:#fff;
    padding:10px 16px; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,0.06);
}
.avatar{
    width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg,#667eea,#764ba2);
    display:flex; align-items:center; justify-content:center; color:#fff; font-weight:600;
}

/* ================= CARD ================= */
.stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:30px; }
.stat-card{ background:#fff; padding:24px; border-radius:18px; box-shadow:0 4px 15px rgba(0,0,0,0.05); transition:0.3s; }
.stat-card:hover{ transform:translateY(-5px); }
.stat-value{ font-size:28px; font-weight:700; color:#1a1a2e; margin-bottom:6px; }
.stat-label{ color:#777; font-size:14px; }

/* ================= TABLE ================= */
.recent{ background:#fff; border-radius:18px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
.recent h2{ padding:20px 24px; border-bottom:1px solid #eee; font-size:18px; color:#1a1a2e; }
table{ width:100%; border-collapse:collapse; }
th{ background:#fafafa; color:#777; font-size:12px; text-transform:uppercase; padding:14px; text-align:left; }
td{ padding:14px; border-bottom:1px solid #f2f2f2; font-size:14px; color:#444; vertical-align:middle; }
tr:hover{ background:#fafafa; }

/* ================= STATUS STYLE & SELECTOR ================= */
.status{ padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
.s-diproses { background:#fff3cd; color:#856404; }
.s-dikirim { background:#cce5ff; color:#004085; }
.s-selesai { background:#d4edda; color:#155724; }

/* Style komponen form ubah status baru */
.select-status {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    color: #444;
    outline: none;
    background-color: #fff;
}
.btn-update {
    padding: 6px 12px;
    background: #302b63;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    margin-left: 4px;
}
.btn-update:hover { background: #0f0c29; }

/* ================= RESPONSIVE ================= */
@media(max-width:1000px){ .stats{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:768px){
    .sidebar{ width:70px; }
    .sidebar-brand h2, .sidebar-brand p, .sidebar-menu span:last-child, .sidebar-footer span{ display:none; }
    .sidebar-menu a{ justify-content:center; padding:14px; }
    .main{ margin-left:70px; padding:20px; }
    .stats{ grid-template-columns:1fr; }
}
</style>
</head>

<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <h2>Daifuku Mochi</h2>
        <p>Admin Panel</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active"><span>Dashboard</span></a>
        <a href="produk.php"><span>Kelola Produk</span></a>
        <a href="users.php"><span>Kelola Pelanggan</span></a>
        <a href="pesanan.php"><span>Riwayat Pesanan</span></a>
        <a href="settings.php"><span>Settings</span></a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="top-bar">
        <h1>Admin</h1>
        <div class="user-info">
            <div class="avatar">
                <?= strtoupper(substr($_SESSION['username'],0,1)); ?>
            </div>
            <span><?= htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-value"><?= $total_produk; ?></div>
            <div class="stat-label">Total Produk</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $total_pesanan; ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $total_user; ?></div>
            <div class="stat-label">Total Pelanggan</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">Rp <?= number_format($total_revenue,0,',','.'); ?></div>
            <div class="stat-label">Total Pendapatan</div>
        </div>
    </div>

    <div class="recent">
        <h2>Pesanan Terbaru</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status Saat Ini</th>
                    <th>Aksi Edit Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            while($r = $q5->fetch(PDO::FETCH_ASSOC)):
                $sc = 's-diproses';
                if($r['STATUS_PESANAN'] == 'Dikirim') $sc = 's-dikirim';
                if($r['STATUS_PESANAN'] == 'Selesai') $sc = 's-selesai';
            ?>
                <tr>
                    <td>#<?= htmlspecialchars($r['ID_PESANAN']); ?></td>
                    <td><?= htmlspecialchars($r['USERNAME']); ?></td>
                    <td><?= htmlspecialchars($r['TANGGAL_PESANAN']); ?></td>
                    <td><strong>Rp <?= number_format($r['TOTAL_HARGA'],0,',','.'); ?></strong></td>
                    <td>
                        <span class="status <?= $sc; ?>">
                            <?= htmlspecialchars($r['STATUS_PESANAN']); ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="" style="display: flex; align-items: center;">
                            <input type="hidden" name="id_pesanan" value="<?= $r['ID_PESANAN']; ?>">
                            
                            <select name="status_pesanan" class="select-status">
                                <option value="Diproses" <?= $r['STATUS_PESANAN'] == 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="Dikirim" <?= $r['STATUS_PESANAN'] == 'Dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="Selesai" <?= $r['STATUS_PESANAN'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                            
                            <button type="submit" name="update_status" class="btn-update">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>