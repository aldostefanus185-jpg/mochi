<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit; }

$success = "";

// Update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $pid = $_POST['id_pesanan'];
    $status = $_POST['status'];
    try {
        $upd = $conn->prepare("UPDATE PESANAN SET STATUS_PESANAN = :s WHERE ID_PESANAN = :id");
        if ($upd->execute([':s' => $status, ':id' => $pid])) {
            $success = "Status pesanan #$pid berhasil diupdate!";
        }
    } catch (PDOException $e) {
        $success = "Gagal: " . $e->getMessage();
    }
}

try {
    $sql = $conn->query("SELECT P.ID_PESANAN, U.USERNAME, DATE_FORMAT(P.TANGGAL_PESANAN,'%Y-%m-%d') AS TANGGAL_PESANAN, P.TOTAL_HARGA, P.METODE_PEMBAYARAN, P.STATUS_PESANAN, P.ALAMAT, P.NO_HP FROM PESANAN P JOIN USERS U ON P.ID_USER=U.ID_USER ORDER BY P.ID_PESANAN DESC");
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Pesanan - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;background:#f0f2f5;min-height:100vh}
.sidebar{position:fixed;left:0;top:0;width:250px;height:100vh;background:linear-gradient(180deg,#0f0c29,#302b63);padding:30px 0;z-index:100;display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,.2)}
.sidebar-brand{text-align:center;padding:0 20px 25px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:10px}
.sidebar-brand .icon{font-size:36px;margin-bottom:6px}
.sidebar-brand h2{color:#fff;font-size:17px;font-weight:600}
.sidebar-brand p{color:rgba(255,255,255,.4);font-size:11px}
.sidebar-menu{flex:1;padding:10px 0}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:13px 24px;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:500;transition:.3s;margin:2px 10px;border-radius:10px}
.sidebar-menu a:hover,.sidebar-menu a.active{color:#fff;background:rgba(102,126,234,.2)}
.sidebar-menu a.active{border-left:3px solid #667eea}
.sidebar-footer{padding:20px 24px;border-top:1px solid rgba(255,255,255,.08)}
.sidebar-footer a{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.5);text-decoration:none;font-size:14px;transition:.3s}
.sidebar-footer a:hover{color:#f5576c}
.main{margin-left:250px;padding:30px 36px;min-height:100vh}
.top-bar{margin-bottom:28px}
.top-bar h1{font-size:26px;color:#1a1a2e;font-weight:700}
.alert-s{background:linear-gradient(135deg,#43e97b,#38f9d7);color:#1a1a2e;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500}
.o-table{width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.06);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.o-table table{width:100%;border-collapse:collapse}
.o-table th{background:linear-gradient(135deg,#0f0c29,#302b63);color:#fff;padding:14px 16px;text-align:left;font-size:12px;font-weight:600}
.o-table td{padding:12px 16px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#444;vertical-align:middle}
.o-table tr:hover td{background:#fafafa}
.status{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block}
.s-diproses{background:#fff3cd;color:#856404}
.s-dikirim{background:#cce5ff;color:#004085}
.s-selesai{background:#d4edda;color:#155724}
.status-form{display:flex;gap:6px;align-items:center}
.status-form select{padding:6px 10px;border:2px solid #eee;border-radius:8px;font-family:'Poppins',sans-serif;font-size:12px;outline:none;cursor:pointer;background:#fafafa}
.status-form select:focus{border-color:#667eea}
.btn-upd{padding:6px 12px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:8px;color:#fff;font-size:11px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;transition:.3s}
.btn-upd:hover{box-shadow:0 4px 12px rgba(102,126,234,.4)}
.empty{text-align:center;padding:60px;background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,.06)}
.empty .icon{font-size:64px;margin-bottom:16px}
.empty h2{color:#1a1a2e;margin-bottom:8px}
.empty p{color:#888;font-size:14px}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span:not(:first-child),.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}.o-table{overflow-x:auto}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><div class="icon"></div><h2>Daifuku Mochi</h2><p>Admin </p></div>
 <div class="sidebar-menu">
  <a href="dashboard.php"><span>Dashboard</span></a>
  <a href="produk.php"><span>Kelola Produk</span></a>
  <a href="users.php"><span>Kelola Pelanggan</span></a>
  <a href="pesanan.php" class="active"><span>Riwayat Pesanan</span></a>
  <a href="settings.php"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span></span><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar"><h1>Riwayat Pesanan</h1></div>
 <?php if($success):?><div class="alert-s"><?=htmlspecialchars($success)?></div><?php endif;?>
 <?php
  $rows = [];
  while ($r = $sql->fetch(PDO::FETCH_ASSOC)) { $rows[] = $r; }
 if (count($rows) == 0):
 ?>
 <div class="empty"><div class="icon"></div><h2>Belum Ada Pesanan</h2><p>Belum ada pesanan masuk.</p></div>
 <?php else:?>
 <div class="o-table">
  <table>
   <thead><tr><th>ID</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Metode</th><th>Alamat</th><th>No. HP</th><th>Status</th><th>Update</th></tr></thead>
   <tbody>
   <?php foreach($rows as $r):
    $sc='s-diproses';
    if($r['STATUS_PESANAN']=='Dikirim') $sc='s-dikirim';
    if($r['STATUS_PESANAN']=='Selesai') $sc='s-selesai';
   ?>
   <tr>
    <td>#<?=$r['ID_PESANAN']?></td>
    <td><strong><?=htmlspecialchars($r['USERNAME'])?></strong></td>
    <td><?= htmlspecialchars($r['TANGGAL_PESANAN']) ?></td>
    <td>Rp <?=number_format($r['TOTAL_HARGA'],0,',','.')?></td>
    <td><?=htmlspecialchars($r['METODE_PEMBAYARAN'])?></td>
    <td><?=htmlspecialchars($r['ALAMAT'])?></td>
    <td><?=htmlspecialchars($r['NO_HP'] ?? '-')?></td>
    <td><span class="status <?=$sc?>"><?=htmlspecialchars($r['STATUS_PESANAN'])?></span></td>
    <td>
     <form method="POST" class="status-form">
      <input type="hidden" name="id_pesanan" value="<?=$r['ID_PESANAN']?>">
      <select name="status">
       <option value="Diproses" <?=$r['STATUS_PESANAN']=='Diproses'?'selected':''?>>Diproses</option>
       <option value="Dikirim" <?=$r['STATUS_PESANAN']=='Dikirim'?'selected':''?>>Dikirim</option>
       <option value="Selesai" <?=$r['STATUS_PESANAN']=='Selesai'?'selected':''?>>Selesai</option>
      </select>
      <button type="submit" name="update_status" class="btn-upd">Update</button>
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
