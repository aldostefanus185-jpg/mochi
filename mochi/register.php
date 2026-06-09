<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: customer/menu.php");
    exit;
}

include 'koneksi.php'; // Disarankan menggunakan file koneksi terpusat seperti halaman riwayat
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    if (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek username sudah ada atau belum
        try {
            $check = $conn->prepare("SELECT COUNT(*) AS JML FROM USERS WHERE USERNAME = :username");
            $check->execute([':username' => $username]);
            $row = $check->fetch(PDO::FETCH_ASSOC);

            if ($row['JML'] > 0) {
                $error = "Username sudah digunakan!";
            } else {
                $sql = "INSERT INTO USERS (USERNAME, PASSWORD, ROLE) VALUES (:username, :password, 'pelanggan')";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([
                    ':username' => $username,
                    ':password' => $password
                ])) {
                    $success = "Registrasi berhasil! Silakan login.";
                } else {
                    $error = "Gagal registrasi!";
                }
            }
        } catch (PDOException $e) {
            $error = "Gagal registrasi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daifuku Mochi - Daftar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    /* DISESUAIKAN: Menggunakan gradasi warna navy khas sidebar riwayat */
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    overflow: hidden;
}
.bg-circles {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    pointer-events: none; z-index: 0;
}
.bg-circles div {
    position: absolute; border-radius: 50%;
    /* DISESUAIKAN: Menggunakan pendaran warna pink/purple lembut dari badge keranjang */
    background: rgba(240, 147, 251, 0.05);
    animation: floatUp 15s infinite ease-in-out;
}
.bg-circles div:nth-child(1) { width: 200px; height: 200px; left: 10%; top: 80%; animation-delay: 0s; }
.bg-circles div:nth-child(2) { width: 120px; height: 120px; left: 70%; top: 60%; animation-delay: 2s; }
.bg-circles div:nth-child(3) { width: 300px; height: 300px; left: 50%; top: 90%; animation-delay: 4s; }
.bg-circles div:nth-child(4) { width: 80px; height: 80px; left: 30%; top: 70%; animation-delay: 6s; }
.bg-circles div:nth-child(5) { width: 160px; height: 160px; left: 80%; top: 85%; animation-delay: 8s; }
@keyframes floatUp {
    0% { transform: translateY(0) rotate(0deg); opacity: 0.5; }
    50% { transform: translateY(-400px) rotate(180deg); opacity: 0.2; }
    100% { transform: translateY(0) rotate(360deg); opacity: 0.5; }
}
.register-container {
    position: relative; z-index: 1;
    width: 420px; max-width: 95%;
    animation: slideUp 0.8s ease-out;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}
.register-card {
    /* DISESUAIKAN: Membuat card sedikit lebih gelap & solid agar kontras di latar navy */
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 44px 40px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
}
.logo-section {
    text-align: center; margin-bottom: 28px;
}
.logo-section h1 {
    color: #fff; font-size: 24px; font-weight: 700;
    letter-spacing: 1px; margin-bottom: 4px;
}
.logo-section p {
    color: rgba(255,255,255,0.5); font-size: 14px; font-weight: 300;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block; color: rgba(255,255,255,0.8);
    font-size: 13px; font-weight: 500;
    margin-bottom: 8px; letter-spacing: 0.5px;
}
.form-group input {
    width: 100%; padding: 14px 18px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px; color: #fff;
    font-size: 15px; font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease; outline: none;
}
.form-group input::placeholder { color: rgba(255,255,255,0.3); }
.form-group input:focus {
    /* DISESUAIKAN: Focus border beralih ke warna tema pink pastel */
    border-color: #f093fb;
    background: rgba(255,255,255,0.12);
    box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.2);
}
.btn-register {
    width: 100%; padding: 14px;
    /* DISESUAIKAN: Menggunakan gradasi gradien ikonik dari tombol badge/aksesoris riwayat */
    background: linear-gradient(135deg, #f093fb, #f5576c);
    border: none; border-radius: 12px;
    color: #fff; font-size: 16px;
    font-weight: 600; font-family: 'Poppins', sans-serif;
    cursor: pointer; transition: all 0.3s ease;
    letter-spacing: 0.5px; margin-top: 8px;
}
.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245, 87, 108, 0.4);
}
.login-link {
    text-align: center; margin-top: 24px;
    color: rgba(255,255,255,0.5); font-size: 14px;
}
.login-link a {
    /* DISESUAIKAN: Menggunakan warna pink pastel link aksen */
    color: #f093fb; text-decoration: none;
    font-weight: 500; transition: color 0.3s;
}
.login-link a:hover { color: #fff; }
.alert-error {
    /* DISESUAIKAN: Menyelaraskan skema warna merah gagal hapus riwayat */
    background: rgba(245, 87, 108, 0.15);
    border: 1px solid rgba(245, 87, 108, 0.3);
    border-radius: 12px; padding: 12px 16px;
    color: #ffb3bc; font-size: 13px;
    margin-bottom: 18px; text-align: center;
}
.alert-success {
    /* DISESUAIKAN: Menyelaraskan status-selesai riwayat */
    background: rgba(212, 237, 218, 0.15);
    border: 1px solid rgba(21, 87, 36, 0.3);
    border-radius: 12px; padding: 12px 16px;
    color: #d4edda; font-size: 13px;
    margin-bottom: 18px; text-align: center;
}
</style>
</head>
<body>

<div class="bg-circles">
    <div></div><div></div><div></div><div></div><div></div>
</div>

<div class="register-container">
    <div class="register-card">
        <div class="logo-section">
            <h1>Buat Akun Baru</h1>
            <p>Daftar untuk mulai memesan</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Minimal 3 karakter" required value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn-register">Daftar</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="index.php">Masuk</a>
        </div>
    </div>
</div>

</body>
</html>