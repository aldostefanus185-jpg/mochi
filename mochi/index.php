<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customer/menu.php");
    }
    

    exit;
}

include 'koneksi.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $sql = "SELECT * FROM USERS
                WHERE USERNAME = :username
                AND PASSWORD = :password";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':password' => $password
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            $_SESSION['user_id'] = $row['ID_USER'];
            $_SESSION['username'] = $row['USERNAME'];
            $_SESSION['role'] = $row['ROLE'];

            if ($row['ROLE'] == 'admin') {

                $success = "Login berhasil sebagai ADMIN";
                header("refresh:2;url=admin/dashboard.php");

            } else {

                $success = "Login berhasil sebagai CUSTOMER";
                header("refresh:2;url=customer/menu.php");
            }

        } else {

            $error = "Username atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Gagal login: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - Daifuku Mochi</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    scroll-behavior:smooth;
}

body{

    font-family:'Poppins',sans-serif;

    background:#ffeef5;

    padding-top:90px;
}

/* ================= NAVBAR ================= */

.navbar{

    position:fixed;

    top:0;
    left:0;

    width:100%;

    padding:18px 60px;

    display:flex;

    justify-content:space-between;
    align-items:center;

    background:rgba(255,255,255,0.9);

    backdrop-filter:blur(12px);

    z-index:999;
}

.nav-logo{

    font-size:24px;

    font-weight:700;

    color:#ff2f7d;
}

.nav-menu{

    display:flex;

    gap:30px;

    list-style:none;
}

.nav-menu a{

    text-decoration:none;

    color:#ff2f7d;

    font-weight:600;

    transition:0.3s;
}

.nav-menu a:hover{

    color:#ff69a6;
}

/* ================= CONTAINER ================= */

.main-container{

    width:1050px;
    max-width:95%;

    margin:auto;

    height:620px;

    background:white;

    border-radius:35px;

    overflow:hidden;

    display:flex;

    box-shadow:
    0 25px 60px rgba(255,105,180,0.18);

    animation:fadeIn 0.7s ease;
}

@keyframes fadeIn{

    from{
        opacity:0;
        transform:translateY(20px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* ================= LEFT ================= */

.left-side{

    width:48%;

    background:
    linear-gradient(
        rgba(255,182,193,0.82),
        rgba(255,192,203,0.88)
    ),
    url('images/mochi-kamu.jpg');

    background-size:cover;
    background-position:center;

    color:white;

    padding:50px;

    display:flex;
    flex-direction:column;
    justify-content:center;

    position:relative;
}

.logo{

    position:absolute;

    top:35px;
    left:45px;

    font-size:24px;
    font-weight:700;

    color:white;
}

.small-text{

    font-size:13px;

    letter-spacing:1px;

    margin-bottom:14px;

    font-weight:500;
}

.big-title{

    font-size:45px;

    line-height:1.2;

    font-weight:700;

    margin-bottom:18px;
}

.desc{

    font-size:14px;

    line-height:1.8;

    margin-bottom:35px;
}

.btn-explore{

    width:165px;

    padding:13px;

    border:none;

    border-radius:40px;

    background:white;

    color:#ff69a6;

    font-weight:600;

    cursor:pointer;

    transition:0.3s;
}

.btn-explore:hover{

    transform:translateY(-3px);

    box-shadow:0 10px 20px rgba(0,0,0,0.15);
}

/* ================= RIGHT ================= */

.right-side{

    width:52%;

    position:relative;

    overflow:hidden;

    display:flex;
    justify-content:center;
    align-items:center;
}

.right-side::before{

    content:"";

    position:absolute;

    inset:0;

    background:
    linear-gradient(
        rgba(255,182,193,0.58),
        rgba(255,105,180,0.58)
    ),
    url('images/mochi-login.jpg');

    background-size:cover;
    background-position:center;

    z-index:1;
}

/* ================= LOGIN BOX ================= */

.login-box{

    position:relative;

    z-index:2;

    width:350px;
    max-width:88%;

    background:rgba(255,255,255,0.18);

    backdrop-filter:blur(16px);

    border:1px solid rgba(255,255,255,0.25);

    border-radius:24px;

    padding:30px 28px;

    color:white;

    box-shadow:0 10px 30px rgba(0,0,0,0.15);
}

.login-title{

    font-size:30px;

    font-weight:700;

    margin-bottom:8px;

    text-align:center;

    color:#ff2f7d;
}

.login-subtitle{

    text-align:center;

    font-size:13px;

    color:#ff5c97;

    margin-bottom:24px;

    font-weight:500;
}

/* ================= ALERT ================= */

.alert{

    padding:12px;

    border-radius:12px;

    background:rgba(255,80,120,0.22);

    margin-bottom:18px;

    font-size:13px;

    text-align:center;

    color:white;
}

.success{

    padding:12px;

    border-radius:12px;

    background:rgba(120,255,180,0.18);

    margin-bottom:18px;

    font-size:13px;

    text-align:center;

    color:white;
}

/* ================= FORM ================= */

.form-group{

    margin-bottom:16px;
}

.form-group label{

    display:block;

    margin-bottom:8px;

    font-size:13px;

    font-weight:600;

    color:#ff2f7d;
}

.form-group input{

    width:100%;

    padding:13px 15px;

    border:none;
    outline:none;

    border-radius:14px;

    background:rgba(255,255,255,0.22);

    color:#ff2f7d;

    font-size:14px;
}

.form-group input::placeholder{

    color:#ff8bb3;
}

/* ================= OPTIONS ================= */

.form-options{

    display:flex;

    justify-content:space-between;

    margin-bottom:20px;

    font-size:12px;
}

.form-options a{

    color:#ff2f7d;

    text-decoration:none;

    font-weight:600;
}

/* ================= BUTTON ================= */

.btn-login{

    width:100%;

    padding:13px;

    border:none;

    border-radius:14px;

    background:#ff5c97;

    color:white;

    font-size:14px;

    font-weight:700;

    cursor:pointer;
}

/* ================= SECTION ================= */

.section-box{

    padding:90px 10%;

    text-align:center;

    background:#fff5f8;
}

.section-box h2{

    font-size:36px;

    color:#ff2f7d;

    margin-bottom:20px;
}

.section-box p{

    font-size:15px;

    color:#555;

    line-height:1.8;
}

/* ================= CARD ================= */

.card-container{

    display:flex;

    justify-content:center;

    gap:25px;

    flex-wrap:wrap;

    margin-top:35px;
}

.card{

    width:250px;

    background:white;

    border-radius:25px;

    overflow:hidden;

    box-shadow:0 10px 30px rgba(0,0,0,0.08);

    transition:0.3s;
}

.card:hover{

    transform:translateY(-8px);
}

.card img{

    width:100%;

    height:220px;

    object-fit:cover;
}

.card h3{

    margin-top:15px;

    color:#ff2f7d;
}

.card p{

    padding:15px;
}

/* ================= PROMO ================= */

.promo-box{

    background:#ff5c97;

    color:white;

    padding:35px;

    border-radius:25px;

    font-size:28px;

    font-weight:700;

    margin-top:25px;
}

/* ================= SOCIAL ================= */

.social-links{

    display:flex;

    justify-content:center;

    gap:20px;

    flex-wrap:wrap;

    margin-top:25px;
}

.social-links a{

    padding:14px 28px;

    background:white;

    border-radius:40px;

    text-decoration:none;

    color:#ff2f7d;

    font-weight:600;
}

/* ================= RESPONSIVE ================= */

@media(max-width:950px){

    .main-container{

        flex-direction:column;

        height:auto;
    }

    .left-side,
    .right-side{

        width:100%;
    }

    .left-side{

        min-height:300px;
    }

    .right-side{

        min-height:650px;
    }

    .navbar{

        padding:18px 25px;
    }

    .nav-menu{

        gap:15px;
    }
}

</style>

</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav class="navbar">

    <div class="nav-logo">
        Daifuku Mochi
    </div>

    <ul class="nav-menu">

        <li><a href="#varian">Best Seller</a></li>

        <li><a href="#promo">Promo</a></li>

        <li><a href="#sosmed">Sosial Media</a></li>

    </ul>

</nav>

<div class="main-container">

    <!-- LEFT -->

    <div class="left-side">

        <div class="logo">
            Daifuku
        </div>

        <div class="small-text">
            SWEET JAPANESE MOCHI
        </div>

        <h1 class="big-title">
            Fresh Mochi,<br>
            Soft & Sweet<br>
            Everyday
        </h1>

        <p class="desc">
            Ayooo Nikmati mochi dengan rasa manis,
            lembut, dan tampilan lucu yang bikin mood kamu makin happy.
        </p>

        <button class="btn-explore">
            Explore Menu
        </button>

    </div>

    <!-- RIGHT -->

    <div class="right-side">

        <div class="login-box">

            <h1 class="login-title">
                Login
            </h1>

            <p class="login-subtitle">
                Silahkan login untuk melanjutkan
            </p>

            <?php if($error): ?>
                <div class="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label>Username</label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >

                </div>

                <div class="form-options">

                    <a href="register.php">
                        Registrasi
                    </a>

                    <a href="forgot_password.php">
                        Lupa Password?
                    </a>

                </div>

                <button type="submit" class="btn-login">
                    Masuk
                </button>

            </form>

        </div>

    </div>

</div>

<!-- ================= BEST SELLER ================= -->

<section id="varian" class="section-box">

    <h2>Best Seller</h2>

    <div class="card-container">

        <div class="card">

            <img src="image/coklat.jpg">

            <h3>Mochi Coklat</h3>

            <p>Rasa coklat lumer favorit pelanggan.</p>

        </div>

        <div class="card">

            <img src="image/strOwberry.jpg">

            <h3>Mochi Strawberry</h3>

            <p>Manis segar dengan isian strawberry creamy.</p>

        </div>

        <div class="card">

            <img src="image/matcha.jpg">

            <h3>Mochi Matcha</h3>

            <p>Rasa matcha premium khas Jepang.</p>

        </div>

    </div>

</section>

<!-- ================= PROMO ================= -->

<section id="promo" class="section-box">

    <h2>Promo Spesial</h2>

    <div class="promo-box">

        BURUANNN BELI 10 GRATIS 1!

    </div>

</section>

<!-- ================= SOSMED ================= -->

<section id="sosmed" class="section-box">

    <h2>Sosial Media</h2>

    <div class="social-links">

        <a href="#">Instagram</a>

        <a href="#">TikTok</a>

        <a href="#">WhatsApp</a>

        <a href="#">Facebook</a>

    </div>

</section>

</body>
</html>
