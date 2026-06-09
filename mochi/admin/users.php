<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../index.php"); 
    exit; 
}

$success = ""; 
$error = "";

// Tambah User Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($password)) {
        $error = "Username dan Password wajib diisi!";
    } else {
        try {
            // Cek apakah username sudah digunakan
            $cek = $conn->prepare("SELECT COUNT(*) AS JML FROM USERS WHERE USERNAME = :u");
            $cek->execute([':u' => $username]);
            $exists = $cek->fetch(PDO::FETCH_ASSOC)['JML'];

            if ($exists > 0) {
                $error = "Username '$username' sudah digunakan!";
            } else {
                $ins = $conn->prepare("INSERT INTO USERS (USERNAME, PASSWORD, ROLE) VALUES (:u, :p, :r)");
                if ($ins->execute([
                    ':u' => $username,
                    ':p' => $password,
                    ':r' => $role
                ])) {
                    $success = "User baru berhasil ditambahkan!";
                }
            }
        } catch (PDOException $e) {
            $error = "Gagal menambahkan user: " . $e->getMessage();
        }
    }
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cegah admin menghapus dirinya sendiri
    if ($id == $_SESSION['user_id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!";
    } else {
        try {
            $del = $conn->prepare("DELETE FROM USERS WHERE ID_USER = :id");
            if ($del->execute([':id' => $id])) {
                $success = "User berhasil dihapus!";
            }
        } catch (PDOException $e) {
            $error = "Gagal menghapus user. User mungkin memiliki keterkaitan dengan data pesanan/keranjang.";
        }
    }
}

// Ambil Semua Data User
try {
    $sql = $conn->query("SELECT * FROM USERS ORDER BY ID_USER DESC");
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pelanggan - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;background:#f0f2f5;min-height:100vh}
.sidebar{position:fixed;left:0;top:0;width:250px;height:100vh;background:linear-gradient(180deg,#0f0c29,#302b63);padding:30px 0;z-index:100;display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,.2)}
.sidebar-brand{text-align:center;padding:0 20px 25px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:10px}
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
.alert-s{background:linear-gradient(135deg,#43e97b,#38f9d7);color:#1a1a2e;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500}
.alert-e{background:rgba(245,87,108,.1);border:1px solid rgba(245,87,108,.3);color:#f5576c;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px}
.form-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 15px rgba(0,0,0,.06);margin-bottom:24px;animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.form-card h2{font-size:18px;color:#1a1a2e;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f0f0}
.form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:600;color:#1a1a2e;margin-bottom:6px}
.form-group input, .form-group select{width:100%;padding:10px 14px;border:2px solid #eee;border-radius:10px;font-family:'Poppins',sans-serif;font-size:13px;transition:.3s;outline:none;background:#fafafa}
.form-group input:focus, .form-group select:focus{border-color:#667eea;background:#fff}
.form-actions{display:flex;gap:10px;margin-top:8px}
.btn-save{padding:10px 24px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:10px;color:#fff;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s}
.btn-save:hover{box-shadow:0 4px 15px rgba(102,126,234,.4)}
.u-table{width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.06);animation:fadeUp .5s ease}
.u-table table{width:100%;border-collapse:collapse}
.u-table th{background:linear-gradient(135deg,#0f0c29,#302b63);color:#fff;padding:14px 18px;text-align:left;font-size:12px;font-weight:600}
.u-table td{padding:12px 18px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#444;vertical-align:middle}
.u-table tr:hover td{background:#fafafa}
.badge-role{padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;display:inline-block}
.role-admin{background:rgba(102,126,234,0.15);color:#667eea}
.role-pelanggan{background:rgba(245,87,108,0.15);color:#f5576c}
.btn-del{padding:6px 14px;background:#f5576c;border:none;border-radius:8px;color:#fff;font-size:12px;cursor:pointer;text-decoration:none;transition:.3s;font-family:'Poppins',sans-serif}
.btn-del:hover{background:#d94456}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span,.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}.form-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><h2>Daifuku Mochi</h2><p>Admin </p></div>
 <div class="sidebar-menu">
  <a href="dashboard.php"><span>Dashboard</span></a>
  <a href="produk.php"><span>Kelola Produk</span></a>
  <a href="users.php" class="active"><span>Kelola Pelanggan</span></a>
  <a href="pesanan.php"><span>Riwayat Pesanan</span></a>
  <a href="settings.php"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar"><h1>Kelola Pelanggan</h1></div>
 <?php if($success):?><div class="alert-s"> <?=htmlspecialchars($success)?></div><?php endif;?>
 <?php if($error):?><div class="alert-e"><?=htmlspecialchars($error)?></div><?php endif;?>

 <div class="form-card">
  <h2>Tambah User Baru</h2>
  <form method="POST">
   <div class="form-grid">
    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username baru..." required>
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password..." required>
    </div>
    <div class="form-group">
        <label>Role</label>
        <select name="role">
            <option value="pelanggan">Pelanggan</option>
            <option value="admin">Admin</option>
        </select>
    </div>
   </div>
   <div class="form-actions">
    <button type="submit" name="tambah" class="btn-save">Tambah User</button>
   </div>
  </form>
 </div>

 <div class="u-table">
  <table>
   <thead><tr><th>ID User</th><th>Username</th><th>Password</th><th>Role</th><th>Aksi</th></tr></thead>
   <tbody>
    <?php while($u=$sql->fetch(PDO::FETCH_ASSOC)):?>
    <tr>
     <td>#<?=$u['ID_USER']?></td>
     <td><strong><?=htmlspecialchars($u['USERNAME'])?></strong></td>
     <td><code><?=htmlspecialchars($u['PASSWORD'])?></code></td>
     <td>
        <?php if($u['ROLE'] == 'admin'): ?>
            <span class="badge-role role-admin">Admin</span>
        <?php else: ?>
            <span class="badge-role role-pelanggan">Pelanggan</span>
        <?php endif; ?>
     </td>
     <td>
      <a href="users.php?hapus=<?=$u['ID_USER']?>" class="btn-del" onclick="return confirm('Hapus user ini?')">Hapus</a>
     </td>
    </tr>
    <?php endwhile;?>
   </tbody>
  </table>
 </div>
</div>
</body>
</html>
