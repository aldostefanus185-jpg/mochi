<?php
// 1. Pastikan session dimulai paling atas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') { 
    header("Location: ../index.php"); 
    exit; 
}

$uid = $_SESSION['user_id'];

// --- PROSES HAPUS RIWAYAT PESANAN ---
if (isset($_POST['hapus_pesanan'])) {
    $id_pesanan = $_POST['id_pesanan'];
    
    try {
        $conn->beginTransaction();

        // Langkah A: Hapus dulu data di DETAIL_PESANAN (jika ada) agar tidak bentrok Foreign Key
        $detail_sql = $conn->prepare("DELETE FROM DETAIL_PESANAN WHERE ID_PESANAN = :id_pesanan");
        $detail_sql->execute([':id_pesanan' => $id_pesanan]);
        
        // Langkah B: Baru hapus data utama di tabel PESANAN
        $delete_sql = $conn->prepare("DELETE FROM PESANAN WHERE ID_PESANAN = :id_pesanan AND ID_USER = :u_id");
        $delete_sql->execute([':id_pesanan' => $id_pesanan, ':u_id' => $uid]);
        
        $conn->commit();
        header("Location: riwayat.php");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "<script>alert('Gagal menghapus: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
    }
}
// ------------------------------------

try {
    // Ambil data riwayat pesanan
    $sql = $conn->prepare("SELECT P.ID_PESANAN, DATE_FORMAT(P.TANGGAL_PESANAN,'%Y-%m-%d') AS TANGGAL_PESANAN, P.TOTAL_HARGA, P.METODE_PEMBAYARAN, P.STATUS_PESANAN, P.ALAMAT FROM PESANAN P WHERE P.ID_USER = :u_id ORDER BY P.ID_PESANAN DESC");
    $sql->execute([':u_id' => $uid]);

    // Hitung jumlah isi keranjang
    $cart_sql = $conn->prepare("SELECT COALESCE(SUM(JUMLAH),0) AS TOTAL FROM KERANJANG WHERE ID_USER = :u_id");
    $cart_sql->execute([':u_id' => $uid]);
    $cart_count = $cart_sql->fetch(PDO::FETCH_ASSOC)['TOTAL'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat - Daifuku Mochi</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;background:#f0f2f5;min-height:100vh}
.sidebar{position:fixed;left:0;top:0;width:250px;height:100vh;background:linear-gradient(180deg,#1a1a2e,#16213e);padding:30px 0;z-index:100;display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,.15)}
.sidebar-brand{text-align:center;padding:0 20px 25px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:10px}
.sidebar-brand .icon{font-size:36px;margin-bottom:6px}
.sidebar-brand h2{color:#fff;font-size:17px;font-weight:600}
.sidebar-brand p{color:rgba(255,255,255,.4);font-size:11px}
.sidebar-menu{flex:1;padding:10px 0}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:13px 24px;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:500;transition:.3s;margin:2px 10px;border-radius:10px}
.sidebar-menu a:hover,.sidebar-menu a.active{color:#fff;background:rgba(240,147,251,.15)}
.sidebar-menu a.active{border-left:3px solid #f093fb}
.sidebar-menu .badge{margin-left:auto;background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;font-size:11px;padding:2px 8px;border-radius:10px}
.sidebar-footer{padding:20px 24px;border-top:1px solid rgba(255,255,255,.08)}
.sidebar-footer a{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.5);text-decoration:none;font-size:14px;transition:.3s}
.sidebar-footer a:hover{color:#f5576c}
.main{margin-left:250px;padding:30px 36px;min-height:100vh}
.top-bar{margin-bottom:28px}
.top-bar h1{font-size:26px;color:#1a1a2e;font-weight:700}
.r-table{width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.06);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.r-table table{width:100%;border-collapse:collapse}
.r-table th{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;padding:16px 20px;text-align:left;font-size:13px;font-weight:600}
.r-table td{padding:14px 20px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#444;vertical-align:middle}
.r-table tr:hover td{background:#fafafa}
.status{padding:5px 14px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block}
.status-diproses{background:#fff3cd;color:#856404}
.status-dikirim{background:#cce5ff;color:#004085}
.status-selesai{background:#d4edda;color:#155724}

/* Style tombol hapus */
.btn-hapus {
    background: #f5576c;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 11px;
    font-weight: 600;
    transition: background 0.2s;
}
.btn-hapus:hover {
    background: #d43f52;
}

.empty{text-align:center;padding:60px;background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,.06)}
.empty .icon{font-size:64px;margin-bottom:16px}
.empty h2{color:#1a1a2e;margin-bottom:8px}
.empty p{color:#888;font-size:14px}
.empty a{color:#f093fb;font-weight:600;text-decoration:none}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span:not(:first-child),.sidebar-menu .badge,.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}.r-table{overflow-x:auto}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><div class="icon"></div><h2>Daifuku Mochi</h2><p>Pelanggan</p></div>
 <div class="sidebar-menu">
  <a href="menu.php"><span>Menu</span></a>
  <a href="keranjang.php"><span>Keranjang</span><?php if($cart_count>0):?><span class="badge"><?=$cart_count?></span><?php endif;?></a>
  <a href="riwayat.php" class="active"><span>Riwayat</span></a>
  <a href="settings.php"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span></span><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar"><h1>Riwayat Pesanan</h1></div>
 <?php
 $rows = [];
 while ($r = $sql->fetch(PDO::FETCH_ASSOC)) { $rows[] = $r; }
 if (count($rows) == 0):
 ?>
 <div class="empty">
  <div class="icon"></div>
  <h2>Belum Ada Pesanan</h2>
  <p>Kamu belum pernah checkout. <a href="menu.php">Mulai belanja →</a></p>
 </div>
 <?php else:?>
 <div class="r-table">
  <table>
   <thead><tr><th>No</th><th>Tanggal</th><th>Total</th><th>Metode Bayar</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr></thead>
   <tbody>
   <?php $no=1; foreach($rows as $r):
    $status_class = 'status-diproses';
    if ($r['STATUS_PESANAN'] == 'Dikirim') $status_class = 'status-dikirim';
    if ($r['STATUS_PESANAN'] == 'Selesai') $status_class = 'status-selesai';
   ?>
   <tr>
    <td><?=$no++?></td>
    <td><?= htmlspecialchars($r['TANGGAL_PESANAN']) ?></td>
    <td><strong>Rp <?=number_format($r['TOTAL_HARGA'],0,',','.')?></strong></td>
    <td><?=htmlspecialchars($r['METODE_PEMBAYARAN'])?></td>
    <td><?=htmlspecialchars($r['ALAMAT'])?></td>
    <td><span class="status <?=$status_class?>"><?=htmlspecialchars($r['STATUS_PESANAN'])?></span></td>
    <td>
     <form method="POST" action="" onsubmit="return confirm('Hapus riwayat pesanan ini?');" style="display:inline;">
      <input type="hidden" name="id_pesanan" value="<?=$r['ID_PESANAN']?>">
      <button type="submit" name="hapus_pesanan" class="btn-hapus">Hapus</button>
     </form>
    </td>
   </tr>
   <?php endforeach;?>
   </tbody>
  </table>
 </div>
 <?php endif;?>
</div>
</body>
</html>