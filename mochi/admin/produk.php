<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit; }

$success = ""; $error = "";

// Tambah produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $harga = intval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $foto = trim($_POST['foto']);
    $desk = trim($_POST['deskripsi']);
    try {
        $ins = $conn->prepare("INSERT INTO PRODUK (NAMA_PRODUK,HARGA,STOK,FOTO,DESKRIPSI) VALUES (:n,:h,:s,:f,:d)");
        if ($ins->execute([
            ':n' => $nama,
            ':h' => $harga,
            ':s' => $stok,
            ':f' => $foto,
            ':d' => $desk
        ])) {
            $success = "Produk berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        $error = "Gagal: " . $e->getMessage();
    }
}

// Edit produk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id_produk'];
    $nama = trim($_POST['nama']);
    $harga = intval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $foto = trim($_POST['foto']);
    $desk = trim($_POST['deskripsi']);
    try {
        $upd = $conn->prepare("UPDATE PRODUK SET NAMA_PRODUK=:n,HARGA=:h,STOK=:s,FOTO=:f,DESKRIPSI=:d WHERE ID_PRODUK=:id");
        if ($upd->execute([
            ':n' => $nama,
            ':h' => $harga,
            ':s' => $stok,
            ':f' => $foto,
            ':d' => $desk,
            ':id' => $id
        ])) {
            $success = "Produk berhasil diupdate!";
        }
    } catch (PDOException $e) {
        $error = "Gagal: " . $e->getMessage();
    }
}

// Hapus produk
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $del = $conn->prepare("DELETE FROM PRODUK WHERE ID_PRODUK=:id");
        if ($del->execute([':id' => $id])) {
            $success = "Produk dihapus!";
        }
    } catch (PDOException $e) {
        $error = "Gagal menghapus. Produk mungkin terkait pesanan.";
    }
}

try {
    // Ambil data produk
    $sql = $conn->query("SELECT * FROM PRODUK ORDER BY ID_PRODUK");

    // Ambil data edit
    $edit_data = null;
    if (isset($_GET['edit_id'])) {
        $eid = $_GET['edit_id'];
        $eq = $conn->prepare("SELECT * FROM PRODUK WHERE ID_PRODUK=:id");
        $eq->execute([':id' => $eid]);
        $edit_data = $eq->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Produk - Admin</title>
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
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.top-bar h1{font-size:26px;color:#1a1a2e;font-weight:700}
.btn-add{padding:10px 22px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:12px;color:#fff;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s;text-decoration:none}
.btn-add:hover{box-shadow:0 6px 20px rgba(102,126,234,.4);transform:translateY(-2px)}
.alert-s{background:linear-gradient(135deg,#43e97b,#38f9d7);color:#1a1a2e;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500}
.alert-e{background:rgba(245,87,108,.1);border:1px solid rgba(245,87,108,.3);color:#f5576c;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px}
.form-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 15px rgba(0,0,0,.06);margin-bottom:24px;animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.form-card h2{font-size:18px;color:#1a1a2e;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f0f0}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:600;color:#1a1a2e;margin-bottom:6px}
.form-group input,.form-group textarea{width:100%;padding:10px 14px;border:2px solid #eee;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;transition:.3s;outline:none;background:#fafafa}
.form-group input:focus,.form-group textarea:focus{border-color:#667eea;background:#fff}
.form-group textarea{resize:vertical;min-height:60px}
.form-actions{display:flex;gap:10px;margin-top:8px}
.btn-save{padding:10px 24px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:10px;color:#fff;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s}
.btn-save:hover{box-shadow:0 4px 15px rgba(102,126,234,.4)}
.btn-cancel{padding:10px 24px;background:#eee;border:none;border-radius:10px;color:#555;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;text-decoration:none;display:inline-block;transition:.3s}
.btn-cancel:hover{background:#ddd}
.p-table{width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.06);animation:fadeUp .5s ease}
.p-table table{width:100%;border-collapse:collapse}
.p-table th{background:linear-gradient(135deg,#0f0c29,#302b63);color:#fff;padding:14px 18px;text-align:left;font-size:12px;font-weight:600}
.p-table td{padding:12px 18px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#444;vertical-align:middle}
.p-table tr:hover td{background:#fafafa}
.p-table .prod-info{display:flex;align-items:center;gap:12px}
.p-table .prod-info img{width:45px;height:45px;border-radius:8px;object-fit:cover}
.p-table .prod-info .name{font-weight:600;color:#1a1a2e}
.action-btns{display:flex;gap:8px}
.btn-edit{padding:6px 14px;background:#667eea;border:none;border-radius:8px;color:#fff;font-size:12px;cursor:pointer;text-decoration:none;transition:.3s;font-family:'Poppins',sans-serif}
.btn-edit:hover{background:#5a6fd6}
.btn-del{padding:6px 14px;background:#f5576c;border:none;border-radius:8px;color:#fff;font-size:12px;cursor:pointer;text-decoration:none;transition:.3s;font-family:'Poppins',sans-serif}
.btn-del:hover{background:#d94456}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span:not(:first-child),.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}.form-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><div class="icon"></div><h2>Daifuku Mochi</h2><p>Admin </p></div>
 <div class="sidebar-menu">
  <a href="dashboard.php"><span>Dashboard</span></a>
  <a href="produk.php" class="active"><span>Kelola Produk</span></a>
  <a href="users.php"><span>Kelola Pelanggan</span></a>
  <a href="pesanan.php"><span>Riwayat Pesanan</span></a>
  <a href="settings.php"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span></span><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar"><h1>Kelola Produk</h1></div>
 <?php if($success):?><div class="alert-s"> <?=htmlspecialchars($success)?></div><?php endif;?>
 <?php if($error):?><div class="alert-e"><?=htmlspecialchars($error)?></div><?php endif;?>

 <div class="form-card">
  <h2><?=$edit_data ? 'Edit Produk' : 'Tambah Produk Baru'?></h2>
  <form method="POST">
   <?php if($edit_data):?><input type="hidden" name="id_produk" value="<?=$edit_data['ID_PRODUK']?>"><?php endif;?>
   <div class="form-grid">
    <div class="form-group"><label>Nama Produk</label><input type="text" name="nama" required value="<?=$edit_data?htmlspecialchars($edit_data['NAMA_PRODUK']):''?>"></div>
    <div class="form-group"><label>Harga (Rp)</label><input type="number" name="harga" required value="<?=$edit_data?$edit_data['HARGA']:''?>"></div>
    <div class="form-group"><label>Stok</label><input type="number" name="stok" required value="<?=$edit_data?$edit_data['STOK']:''?>"></div>
    <div class="form-group"><label>Path Foto</label><input type="text" name="foto" placeholder="image/nama.jpg" value="<?=$edit_data?htmlspecialchars($edit_data['FOTO']):''?>"></div>
   </div>
   <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi"><?=$edit_data?htmlspecialchars($edit_data['DESKRIPSI']):''?></textarea></div>
   <div class="form-actions">
    <button type="submit" name="<?=$edit_data?'edit':'tambah'?>" class="btn-save"><?=$edit_data?'Update':'Tambah'?></button>
    <?php if($edit_data):?><a href="produk.php" class="btn-cancel">Batal</a><?php endif;?>
   </div>
  </form>
 </div>

 <div class="p-table">
  <table>
   <thead><tr><th>Produk</th><th>Harga</th><th>Stok</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
   <tbody>
    <?php while($p=$sql->fetch(PDO::FETCH_ASSOC)):?>
   <tr>
    <td><div class="prod-info"><img src="../<?=htmlspecialchars($p['FOTO'])?>" alt=""><div class="name"><?=htmlspecialchars($p['NAMA_PRODUK'])?></div></div></td>
    <td>Rp <?=number_format($p['HARGA'],0,',','.')?></td>
    <td><?=$p['STOK']?></td>
    <td><?=htmlspecialchars(substr($p['DESKRIPSI'],0,40))?><?=strlen($p['DESKRIPSI'])>40?'...':''?></td>
    <td><div class="action-btns">
     <a href="produk.php?edit_id=<?=$p['ID_PRODUK']?>" class="btn-edit">Edit</a>
     <a href="produk.php?hapus=<?=$p['ID_PRODUK']?>" class="btn-del" onclick="return confirm('Hapus produk ini?')">Hapus</a>
    </div></td>
   </tr>
   <?php endwhile;?>
   </tbody>
  </table>
 </div>
</div>
</body>
</html>
