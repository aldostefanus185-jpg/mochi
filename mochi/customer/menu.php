<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') { header("Location: ../index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_keranjang'])) {
    $id_produk = $_POST['id_produk'];
    $id_user = $_SESSION['user_id'];
    try {
        $cek = $conn->prepare("SELECT ID_KERANJANG, JUMLAH FROM KERANJANG WHERE ID_USER = :u_id AND ID_PRODUK = :pid");
        $cek->execute([':u_id' => $id_user, ':pid' => $id_produk]);
        $existing = $cek->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $nq = $existing['JUMLAH'] + 1;
            $upd = $conn->prepare("UPDATE KERANJANG SET JUMLAH = :jml WHERE ID_KERANJANG = :id");
            $upd->execute([':jml' => $nq, ':id' => $existing['ID_KERANJANG']]);
        } else {
            $ins = $conn->prepare("INSERT INTO KERANJANG (ID_USER, ID_PRODUK, JUMLAH) VALUES (:u_id, :pid, 1)");
            $ins->execute([':u_id' => $id_user, ':pid' => $id_produk]);
        }
        header("Location: menu.php?added=1"); exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

try {
    $sql = $conn->query("SELECT * FROM PRODUK ORDER BY ID_PRODUK");
    $cart_sql = $conn->prepare("SELECT COALESCE(SUM(JUMLAH),0) AS TOTAL FROM KERANJANG WHERE ID_USER = :u_id");
    $cart_sql->execute([':u_id' => $_SESSION['user_id']]);
    $cart_count = $cart_sql->fetch(PDO::FETCH_ASSOC)['TOTAL'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
$page = 'menu';
$foto_produk = [
    1 => 'image/coklat.jpg',
    2 => 'image/strowberry.jpg',
    3 => 'image/matcha.jpg',
    4 => 'image/anggur.jpg',
    5 => 'image/manggo.jpg'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu - Daifuku Mochi</title>
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
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.top-bar h1{font-size:26px;color:#1a1a2e;font-weight:700}
.user-info{display:flex;align-items:center;gap:10px;background:#fff;padding:10px 18px;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
.avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f093fb,#f5576c);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:14px}
.user-info span{font-size:13px;color:#555;font-weight:500}
.alert-toast{background:linear-gradient(135deg,#43e97b,#38f9d7);color:#1a1a2e;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500;animation:slideD .5s ease;box-shadow:0 4px 15px rgba(67,233,123,.3)}
@keyframes slideD{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:22px}
.card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.06);transition:.4s;animation:fadeUp .6s ease backwards}
.card:nth-child(1){animation-delay:.1s}.card:nth-child(2){animation-delay:.15s}.card:nth-child(3){animation-delay:.2s}.card:nth-child(4){animation-delay:.25s}.card:nth-child(5){animation-delay:.3s}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.card:hover{transform:translateY(-5px);box-shadow:0 12px 30px rgba(0,0,0,.12)}
.card-img-wrap{overflow:hidden;position:relative}
.card-img-wrap img{width:100%;height:190px;object-fit:cover;transition:transform .4s}
.card:hover .card-img-wrap img{transform:scale(1.05)}
.stock-badge{position:absolute;top:10px;right:10px;background:rgba(26,26,46,.7);color:#fff;padding:4px 10px;border-radius:20px;font-size:11px;backdrop-filter:blur(10px)}
.card-body{padding:18px}
.card-body h3{font-size:16px;font-weight:600;color:#1a1a2e;margin-bottom:4px}
.card-body .desc{font-size:12px;color:#888;margin-bottom:12px}
.card-body .price{font-size:19px;font-weight:700;background:linear-gradient(135deg,#f093fb,#f5576c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:12px}
.btn-cart{width:100%;padding:11px;background:linear-gradient(135deg,#f093fb,#f5576c);border:none;border-radius:10px;color:#fff;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s}
.btn-cart:hover{box-shadow:0 6px 20px rgba(245,87,108,.4);transform:translateY(-1px)}
.btn-cart:disabled{background:#ccc;cursor:not-allowed;box-shadow:none;transform:none}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span:not(:first-child),.sidebar-menu .badge,.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><div class="icon"></div><h2>Daifuku Mochi</h2><p>Pelanggan</p></div>
 <div class="sidebar-menu">
  <a href="menu.php" class="active"><span>Menu</span></a>
  <a href="keranjang.php"><span>Keranjang</span><?php if($cart_count>0):?><span class="badge"><?=$cart_count?></span><?php endif;?></a>
  <a href="riwayat.php"><span>Riwayat</span></a>
  <a href="settings.php"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span></span><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar">
  <h1>Menu Mochi</h1>
  <div class="user-info"><div class="avatar"><?=strtoupper(substr($_SESSION['username'],0,1))?></div><span>Halo, <?=htmlspecialchars($_SESSION['username'])?>!</span></div>
 </div>
 <?php if(isset($_GET['added'])):?><div class="alert-toast">Produk berhasil ditambahkan ke keranjang!</div><?php endif;?>
 <div class="grid">
  <?php while($p=$sql->fetch(PDO::FETCH_ASSOC)):?>
  <div class="card">
   <div class="card-img-wrap">
    <img src="../<?=htmlspecialchars($p['FOTO'])?>" alt="<?=htmlspecialchars($p['NAMA_PRODUK'])?>">
    <div class="stock-badge">Stok: <?=$p['STOK']?></div>
   </div>
   <div class="card-body">
    <h3><?=htmlspecialchars($p['NAMA_PRODUK'])?></h3>
    <p class="desc"><?=htmlspecialchars($p['DESKRIPSI'])?></p>
    <div class="price">Rp <?=number_format($p['HARGA'],0,',','.')?></div>
    <form method="POST"><input type="hidden" name="id_produk" value="<?=$p['ID_PRODUK']?>">
    <button type="submit" name="tambah_keranjang" class="btn-cart" <?=$p['STOK']<=0?'disabled':''?>><?=$p['STOK']<=0?'Stok Habis':'Tambah ke Keranjang'?></button></form>
   </div>
  </div>
  <?php endwhile;?>
 </div>
</div>
</body>
</html>
