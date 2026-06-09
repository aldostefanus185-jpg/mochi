<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') { header("Location: ../index.php"); exit; }

$uid = $_SESSION['user_id'];

// Hapus item
if (isset($_GET['hapus'])) {
    $hid = $_GET['hapus'];
    try {
        $del = $conn->prepare("DELETE FROM KERANJANG WHERE ID_KERANJANG = :id AND ID_USER = :u_id");
        $del->execute([':id' => $hid, ':u_id' => $uid]);
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
    header("Location: keranjang.php"); exit;
}

// Update jumlah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_qty'])) {
    $kid = $_POST['id_keranjang'];
    $qty = max(1, intval($_POST['jumlah']));
    try {
        $upd = $conn->prepare("UPDATE KERANJANG SET JUMLAH = :jml WHERE ID_KERANJANG = :id AND ID_USER = :u_id");
        $upd->execute([':jml' => $qty, ':id' => $kid, ':u_id' => $uid]);
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
    header("Location: keranjang.php"); exit;
}

// Ambil data keranjang
try {
    $sql = $conn->prepare("SELECT k.ID_KERANJANG, k.JUMLAH, p.NAMA_PRODUK, p.HARGA, p.FOTO FROM KERANJANG k JOIN PRODUK p ON k.ID_PRODUK = p.ID_PRODUK WHERE k.ID_USER = :u_id ORDER BY k.ID_KERANJANG");
    $sql->execute([':u_id' => $uid]);

    $items = [];
    $total = 0;
    while ($r = $sql->fetch(PDO::FETCH_ASSOC)) {
        $r['SUBTOTAL'] = $r['HARGA'] * $r['JUMLAH'];
        $total += $r['SUBTOTAL'];
        $items[] = $r;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$cart_count = count($items);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang - Daifuku Mochi</title>
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
.cart-table{width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.06);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.cart-table table{width:100%;border-collapse:collapse}
.cart-table th{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;padding:16px 20px;text-align:left;font-size:13px;font-weight:600;letter-spacing:.5px}
.cart-table td{padding:16px 20px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#444;vertical-align:middle}
.cart-table tr:last-child td{border-bottom:none}
.cart-table tr:hover td{background:#fafafa}
.item-info{display:flex;align-items:center;gap:14px}
.item-info img{width:55px;height:55px;border-radius:10px;object-fit:cover}
.item-info .name{font-weight:600;color:#1a1a2e}
.qty-control{display:flex;align-items:center;gap:6px}
.qty-control input{width:50px;padding:6px;text-align:center;border:1px solid #ddd;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px}
.qty-control button{padding:6px 10px;border:none;border-radius:8px;cursor:pointer;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;background:#eee;color:#555;transition:.3s}
.qty-control button:hover{background:#f093fb;color:#fff}
.btn-hapus{background:none;border:none;color:#f5576c;cursor:pointer;font-size:18px;transition:.3s}
.btn-hapus:hover{transform:scale(1.2)}
.cart-summary{background:#fff;border-radius:16px;padding:28px;margin-top:20px;box-shadow:0 4px 15px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center;animation:fadeUp .6s ease}
.cart-summary .total-text{font-size:14px;color:#888}
.cart-summary .total-price{font-size:28px;font-weight:700;background:linear-gradient(135deg,#f093fb,#f5576c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.btn-checkout{padding:14px 36px;background:linear-gradient(135deg,#f093fb,#f5576c);border:none;border-radius:12px;color:#fff;font-size:15px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s;text-decoration:none;display:inline-block}
.btn-checkout:hover{box-shadow:0 6px 20px rgba(245,87,108,.4);transform:translateY(-2px)}
.empty-cart{text-align:center;padding:60px 20px;background:#fff;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,.06)}
.empty-cart .icon{font-size:64px;margin-bottom:16px}
.empty-cart h2{color:#1a1a2e;margin-bottom:8px}
.empty-cart p{color:#888;margin-bottom:20px}
.empty-cart a{color:#f093fb;text-decoration:none;font-weight:600}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span:not(:first-child),.sidebar-menu .badge,.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><div class="icon"></div><h2>Daifuku Mochi</h2><p> Pelanggan</p></div>
 <div class="sidebar-menu">
  <a href="menu.php"><span>Menu</span></a>
  <a href="keranjang.php" class="active"><span>Keranjang</span><?php if($cart_count>0):?><span class="badge"><?=$cart_count?></span><?php endif;?></a>
  <a href="riwayat.php"><span>Riwayat</span></a>
  <a href="settings.php"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span></span><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar"><h1>Keranjang Belanja</h1></div>
 <?php if(count($items)==0):?>
 <div class="empty-cart">
  <div class="icon"></div>
  <h2>Keranjang Kosong</h2>
  <p>Belum ada produk di keranjang. <a href="menu.php">Lihat Menu →</a></p>
 </div>
 <?php else:?>
 <div class="cart-table">
  <table>
   <thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
   <tbody>
   <?php foreach($items as $i):?>
   <tr>
    <td><div class="item-info"><img src="../<?=htmlspecialchars($i['FOTO'])?>"><div class="name"><?=htmlspecialchars($i['NAMA_PRODUK'])?></div></div></td>
    <td>Rp <?=number_format($i['HARGA'],0,',','.')?></td>
    <td>
     <form method="POST" class="qty-control">
      <input type="hidden" name="id_keranjang" value="<?=$i['ID_KERANJANG']?>">
      <input type="number" name="jumlah" value="<?=$i['JUMLAH']?>" min="1">
      <button type="submit" name="update_qty">Ubah</button>
     </form>
    </td>
    <td><strong>Rp <?=number_format($i['SUBTOTAL'],0,',','.')?></strong></td>
    <td><a href="keranjang.php?hapus=<?=$i['ID_KERANJANG']?>" class="btn-hapus" onclick="return confirm('Hapus item ini?')">Hapus</a></td>
   </tr>
   <?php endforeach;?>
   </tbody>
  </table>
 </div>
 <div class="cart-summary">
  <div><div class="total-text">Total Pembayaran</div><div class="total-price">Rp <?=number_format($total,0,',','.')?></div></div>
  <a href="checkout.php" class="btn-checkout">Checkout →</a>
 </div>
 <?php endif;?>
</div>
</body>
</html>
