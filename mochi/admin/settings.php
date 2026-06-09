<?php
include '../koneksi.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit; }

$uid = $_SESSION['user_id'];
$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['ubah_username'])) {
        $new_user = trim($_POST['new_username']);
        if (strlen($new_user) < 3) { $error = "Username minimal 3 karakter!"; }
        else {
            try {
                $cek = $conn->prepare("SELECT COUNT(*) AS JML FROM USERS WHERE USERNAME = :u AND ID_USER != :u_id");
                $cek->execute([':u' => $new_user, ':u_id' => $uid]);
                $r = $cek->fetch(PDO::FETCH_ASSOC);
                if ($r['JML'] > 0) { $error = "Username sudah digunakan!"; }
                else {
                    $upd = $conn->prepare("UPDATE USERS SET USERNAME = :u WHERE ID_USER = :u_id");
                    $upd->execute([':u' => $new_user, ':u_id' => $uid]);
                    $_SESSION['username'] = $new_user;
                    $success = "Username berhasil diubah!";
                }
            } catch (PDOException $e) {
                $error = "Gagal mengubah username: " . $e->getMessage();
            }
        }
    }
    if (isset($_POST['ubah_password'])) {
        $old = trim($_POST['old_password']);
        $new = trim($_POST['new_password']);
        $confirm = trim($_POST['confirm_password']);
        try {
            $cek = $conn->prepare("SELECT PASSWORD FROM USERS WHERE ID_USER = :u_id");
            $cek->execute([':u_id' => $uid]);
            $r = $cek->fetch(PDO::FETCH_ASSOC);
            if ($r['PASSWORD'] != $old) { $error = "Password lama salah!"; }
            elseif (strlen($new) < 6) { $error = "Password baru minimal 6 karakter!"; }
            elseif ($new != $confirm) { $error = "Konfirmasi password tidak cocok!"; }
            else {
                $upd = $conn->prepare("UPDATE USERS SET PASSWORD = :p WHERE ID_USER = :u_id");
                $upd->execute([':p' => $new, ':u_id' => $uid]);
                $success = "Password berhasil diubah!";
            }
        } catch (PDOException $e) {
            $error = "Gagal mengubah password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Admin</title>
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
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.s-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 15px rgba(0,0,0,.06);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.s-card h2{font-size:18px;color:#1a1a2e;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #f0f0f0}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#1a1a2e;margin-bottom:8px}
.form-group input{width:100%;padding:12px 16px;border:2px solid #eee;border-radius:12px;font-family:'Poppins',sans-serif;font-size:14px;transition:.3s;outline:none;background:#fafafa}
.form-group input:focus{border-color:#667eea;background:#fff}
.btn-save{padding:12px 28px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:12px;color:#fff;font-size:14px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s}
.btn-save:hover{box-shadow:0 6px 20px rgba(102,126,234,.4);transform:translateY(-2px)}
.alert-s{background:linear-gradient(135deg,#43e97b,#38f9d7);color:#1a1a2e;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500}
.alert-e{background:rgba(245,87,108,.1);border:1px solid rgba(245,87,108,.3);color:#f5576c;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px}
@media(max-width:900px){.settings-grid{grid-template-columns:1fr}}
@media(max-width:768px){.sidebar{width:60px}.sidebar-brand h2,.sidebar-brand p,.sidebar-menu span:not(:first-child),.sidebar-footer span{display:none}.sidebar-menu a{padding:13px 0;justify-content:center;margin:2px 6px}.main{margin-left:60px;padding:20px}}
</style>
</head>
<body>
<div class="sidebar">
 <div class="sidebar-brand"><div class="icon"></div><h2>Daifuku Mochi</h2><p>Admin</p></div>
 <div class="sidebar-menu">
  <a href="dashboard.php"><span>Dashboard</span></a>
  <a href="produk.php"><span>Kelola Produk</span></a>
  <a href="users.php"><span>Kelola Pelanggan</span></a>
  <a href="pesanan.php"><span>Riwayat Pesanan</span></a>
  <a href="settings.php" class="active"><span>Settings</span></a>
 </div>
 <div class="sidebar-footer"><a href="../logout.php"><span></span><span>Logout</span></a></div>
</div>
<div class="main">
 <div class="top-bar"><h1>Settings Admin</h1></div>
 <?php if($success):?><div class="alert-s"><?=htmlspecialchars($success)?></div><?php endif;?>
 <?php if($error):?><div class="alert-e"><?=htmlspecialchars($error)?></div><?php endif;?>
 <div class="settings-grid">
  <div class="s-card">
   <h2>Ubah Username</h2>
   <form method="POST">
    <div class="form-group"><label>Username Saat Ini</label><input type="text" value="<?=htmlspecialchars($_SESSION['username'])?>" disabled></div>
    <div class="form-group"><label>Username Baru</label><input type="text" name="new_username" required placeholder="Masukkan username baru"></div>
    <button type="submit" name="ubah_username" class="btn-save">Simpan Username</button>
   </form>
  </div>
  <div class="s-card">
   <h2>Ubah Password</h2>
   <form method="POST">
    <div class="form-group"><label>Password Lama</label><input type="password" name="old_password" required placeholder="Masukkan password lama"></div>
    <div class="form-group"><label>Password Baru</label><input type="password" name="new_password" required placeholder="Minimal 6 karakter"></div>
    <div class="form-group"><label>Konfirmasi Password</label><input type="password" name="confirm_password" required placeholder="Ulangi password baru"></div>
    <button type="submit" name="ubah_password" class="btn-save">Simpan Password</button>
   </form>
  </div>
 </div>
</div>
</body>
</html>
