<?php
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../index.php");
    exit;
}

$uid = $_SESSION['user_id'];
$success = "";
$error = "";

/* ================= AMBIL KERANJANG ================= */

try {
    $sql = $conn->prepare("
        SELECT 
            k.ID_KERANJANG,
            k.JUMLAH,
            k.ID_PRODUK,
            p.NAMA_PRODUK,
            p.HARGA,
            p.FOTO
        FROM KERANJANG k
        JOIN PRODUK p
        ON k.ID_PRODUK = p.ID_PRODUK
        WHERE k.ID_USER = :u_id
        ORDER BY k.ID_KERANJANG
    ");

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

if (count($items) == 0 && $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: keranjang.php");
    exit;
}

/* ================= PROSES CHECKOUT ================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bayar'])) {
    $nama = trim($_POST['nama_penerima']);
    $nohp = trim($_POST['nohp']);
    $alamat = trim($_POST['alamat']);
    $metode = $_POST['metode_bayar'];

    if (empty($nama) || empty($nohp) || empty($alamat)) {
        $error = "Nama, Nomor HP, dan Alamat wajib diisi!";
    } else {
        try {
            $conn->beginTransaction();

            $alamat_lengkap = "[Nama: " . $nama . "] " . $alamat;

            $ins = $conn->prepare("
                INSERT INTO PESANAN
                (
                    ID_USER,
                    TOTAL_HARGA,
                    METODE_PEMBAYARAN,
                    STATUS_PESANAN,
                    ALAMAT,
                    NO_HP
                )
                VALUES
                (
                    :u_id,
                    :total,
                    :metode,
                    'Diproses',
                    :alamat,
                    :nohp
                )
            ");

            $ins->execute([
                ':u_id' => $uid,
                ':total' => $total,
                ':metode' => $metode,
                ':alamat' => $alamat_lengkap,
                ':nohp' => $nohp
            ]);

            $pesanan_id = $conn->lastInsertId();

            foreach ($items as $it) {
                $det = $conn->prepare("
                    INSERT INTO DETAIL_PESANAN
                    (
                        ID_PESANAN,
                        ID_PRODUK,
                        JUMLAH,
                        HARGA_SATUAN
                    )
                    VALUES
                    (
                        :pid,
                        :prid,
                        :jml,
                        :hrg
                    )
                ");

                $det->execute([
                    ':pid' => $pesanan_id,
                    ':prid' => $it['ID_PRODUK'],
                    ':jml' => $it['JUMLAH'],
                    ':hrg' => $it['HARGA']
                ]);

                $stk = $conn->prepare("
                    UPDATE PRODUK
                    SET STOK = STOK - :jml
                    WHERE ID_PRODUK = :pid
                ");

                $stk->execute([
                    ':jml' => $it['JUMLAH'],
                    ':pid' => $it['ID_PRODUK']
                ]);
            }

            $clr = $conn->prepare("
                DELETE FROM KERANJANG
                WHERE ID_USER = :u_id
            ");

            $clr->execute([':u_id' => $uid]);

            $conn->commit();

            $success = "Pesanan berhasil dibuat! No. Pesanan: #" . $pesanan_id;
            $items = [];
            $total = 0;
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Gagal memproses pesanan: " . $e->getMessage();
        }
    }
}

/* ================= JUMLAH CART ================= */

try {
    $cart_sql = $conn->prepare("
        SELECT COALESCE(SUM(JUMLAH),0) AS TOTAL
        FROM KERANJANG
        WHERE ID_USER = :u_id
    ");

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
    <title>Checkout - Daifuku Mochi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #131a2e; /* Menyesuaikan background utama dark theme */
            min-height: 100vh;
            color: #ffffff;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #131a2e; /* Sidebar menyatu dengan background */
            padding: 30px 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand h2 {
            color: white;
            font-size: 22px;
            font-weight: 600;
        }

        .sidebar-brand p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 12px;
        }

        .sidebar-menu {
            flex: 1;
            padding-top: 15px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            margin: 5px 12px;
            border-radius: 14px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: .3s;
            font-size: 15px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        /* Border gradient effect untuk menu aktif seperti di gambar */
        .sidebar-menu a.active {
            border: 1px solid #ef5081;
            background: linear-gradient(90deg, rgba(239, 80, 129, 0.15), transparent);
        }

        .badge {
            margin-left: auto;
            background: #ef5081;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-footer {
            padding: 20px;
        }

        .sidebar-footer a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            padding: 14px 24px;
            display: block;
        }

        .sidebar-footer a:hover {
            color: white;
        }

        /* ================= MAIN ================= */
        .main {
            margin-left: 250px;
            padding: 40px;
            background: #131a2e;
        }

        .top-bar h1 {
            font-size: 28px;
            color: white;
            margin-bottom: 30px;
            font-weight: 700;
        }

        /* ================= GRID ================= */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }

        .section-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            color: #333333;
        }

        .section-card h2 {
            font-size: 18px;
            color: #131a2e;
            margin-bottom: 20px;
            font-weight: 700;
        }

        /* ================= ORDER ================= */
        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid #f2f2f2;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 12px;
        }

        .order-item .info {
            flex: 1;
        }

        .order-item .name {
            font-weight: 600;
            color: #131a2e;
        }

        .order-item .qty {
            font-size: 13px;
            color: #888;
            margin-top: 2px;
        }

        .order-item .price {
            font-weight: 700;
            color: #131a2e;
        }

        /* ================= FORM ================= */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group textarea, .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #e0e0e0;
            border-radius: 14px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            background: #f9f9f9;
            transition: 0.3s;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: none;
        }

        .form-group textarea:focus, .form-group input:focus {
            border-color: #ef5081;
            background: #fff;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #131a2e;
            margin-bottom: 6px;
        }

        /* ================= PAYMENT ================= */
        .payment-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .payment-opt input {
            display: none;
        }

        .payment-opt label {
            display: block;
            padding: 13px;
            border: 1px solid #e0e0e0;
            border-radius: 14px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            transition: .3s;
            background: #f9f9f9;
            color: #555;
            font-size: 14px;
        }

        .payment-opt input:checked + label {
            background: #131a2e;
            border-color: #131a2e;
            color: white;
        }

        .payment-detail {
            margin-top: 22px;
            background: #f9fbfd;
            border-radius: 18px;
            padding: 20px;
            border: 1px dashed #ced4da;
            text-align: center;
        }

        .qr-img {
            width: 200px;
            margin: 15px auto;
            display: block;
            border-radius: 16px;
            background: white;
            padding: 10px;
            border: 1px solid #eee;
        }

        .pay-title {
            font-size: 16px;
            font-weight: 700;
            color: #131a2e;
        }

        .pay-note {
            font-size: 12px;
            color: #777;
        }

        .rekening-box h3 {
            color: #ef5081;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .rekening-box p {
            font-size: 22px;
            font-weight: 700;
            color: #131a2e;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .rekening-box span {
            font-size: 13px;
            color: #666;
        }

        /* ================= SUMMARY ================= */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 14px;
            color: #666;
        }

        .summary-row span:last-child {
            color: #131a2e;
            font-weight: 500;
        }

        .summary-row.total {
            border-top: 1px solid #eee;
            margin-top: 12px;
            padding-top: 18px;
            font-size: 20px;
            font-weight: 700;
        }

        .summary-row.total span:first-child {
            color: #131a2e;
        }

        .summary-row.total span:last-child {
            color: #ef5081; /* Total pembayaran menggunakan warna pink sesuai gambar */
            font-weight: 700;
        }

        .btn-pay {
            width: 100%;
            margin-top: 20px;
            padding: 16px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(to right, #f770a1, #ef5081); /* Mengikuti warna tombol checkout gambar */
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
        }

        .btn-pay:hover {
            opacity: 0.9;
            box-shadow: 0 8px 24px rgba(239, 80, 129, 0.3);
        }

        /* ================= ALERT ================= */
        .alert-e {
            background: #fff0f4;
            color: #ef5081;
            padding: 15px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid #ef5081;
        }

        .success-box {
            text-align: center;
            padding: 40px 20px;
        }

        .success-box .icon {
            font-size: 60px;
            margin-bottom: 16px;
        }

        .success-box h2 {
            color: #131a2e;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .success-box p {
            color: #666;
        }

        .success-box a {
            color: #ef5081;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 15px;
        }

        /* ================= RESPONSIVE ================= */
        @media(max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .main {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <h2>Daifuku Mochi</h2>
        <p>Pelanggan</p>
    </div>
    <div class="sidebar-menu">
        <a href="menu.php"> Menu</a>
        <a href="keranjang.php" class="active">
            Keranjang
            <?php if($cart_count > 0): ?>
                <span class="badge"><?=$cart_count?></span>
            <?php endif; ?>
        </a>
        <a href="riwayat.php"> Riwayat</a>
        <a href="settings.php"> Settings</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"> Logout</a>
    </div>
</div>

<div class="main">
    <div class="top-bar">
        <h1>Checkout</h1>
    </div>

    <?php if($success): ?>
    <div class="section-card">
        <div class="success-box">
            <h2>Pesanan Berhasil!</h2>
            <p><?=htmlspecialchars($success)?></p>
            <a href="riwayat.php">Lihat Riwayat Pesanan →</a>
        </div>
    </div>

    <?php elseif(count($items) > 0): ?>
        <?php if($error): ?>
            <div class="alert-e">
                <?=htmlspecialchars($error)?>
            </div>
        <?php endif; ?>

    <form method="POST">
        <div class="checkout-grid">
            <div>
                <div class="section-card">
                    <h2>Detail Pesanan</h2>
                    <?php foreach($items as $it): ?>
                    <div class="order-item">
                        <img src="../<?=htmlspecialchars($it['FOTO'])?>" alt="Produk">
                        <div class="info">
                            <div class="name"><?=htmlspecialchars($it['NAMA_PRODUK'])?></div>
                            <div class="qty">Jumlah: <?=$it['JUMLAH']?> pcs</div>
                        </div>
                        <div class="price">Rp <?=number_format($it['SUBTOTAL'],0,',','.')?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                 <div class="section-card" style="margin-top:20px">
                    <h2>Informasi Pengiriman</h2>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Nama Penerima</label>
                        <input type="text" name="nama_penerima" placeholder="Tulis nama lengkap penerima..." required>
                    </div>
                    <div class="form-group">
                        <label>Nomor HP (WhatsApp)</label>
                        <input type="text" name="nohp" placeholder="Tulis nomor HP yang bisa dihubungi..." required>
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" placeholder="Tuliskan alamat pengiriman lengkap Anda di sini..." required></textarea>
                    </div>
                </div>

                <div class="section-card" style="margin-top:20px">
                    <h2>Metode Pembayaran</h2>
                    <div class="payment-options">
                        <div class="payment-opt">
                            <input type="radio" name="metode_bayar" id="qris" value="QRIS" checked onclick="showPayment('qris')">
                            <label for="qris"> QRIS</label>
                        </div>
                        <div class="payment-opt">
                            <input type="radio" name="metode_bayar" id="dana" value="Dana" onclick="showPayment('dana')">
                            <label for="dana"> DANA</label>
                        </div>
                        <div class="payment-opt">
                            <input type="radio" name="metode_bayar" id="bri" value="BRI" onclick="showPayment('bri')">
                            <label for="bri"> BRI</label>
                        </div>
                    </div>

                    <div class="payment-detail">
                        <div id="qrisBox">
                            <div class="pay-title">Scan QRIS Resmi Daifuku</div>
                            <img src="../image/kiris.jpeg" class="qr-img" alt="QRIS Code">
                            <div class="pay-note">Dapat di-scan menggunakan E-Wallet atau Mobile Banking apa saja.</div>
                        </div>

                        <div id="danaBox" style="display:none">
                            <div class="rekening-box">
                                <h3>No. DANA Virtual Account</h3>
                                <p>087817618168</p>
                                <span>a.n Daifuku Mochi Store</span>
                            </div>
                        </div>

                        <div id="briBox" style="display:none">
                            <div class="rekening-box">
                                <h3>No. Rekening Bank BRI</h3>
                                <p>1234-5678-9012</p>
                                <span>a.n Daifuku Mochi Store</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="section-card" style="position:sticky; top:20px">
                    <h2>Ringkasan Belanja</h2>
                    <?php foreach($items as $it): ?>
                    <div class="summary-row">
                        <span><?=htmlspecialchars($it['NAMA_PRODUK'])?> (x<?=$it['JUMLAH']?>)</span>
                        <span>Rp <?=number_format($it['SUBTOTAL'],0,',','.')?></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="summary-row total">
                        <span>Total Pembayaran</span>
                        <span>Rp <?=number_format($total,0,',','.')?></span>
                    </div>

                    <button type="submit" name="bayar" class="btn-pay">
                        Selesaikan Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function showPayment(type){
    document.getElementById('qrisBox').style.display = 'none';
    document.getElementById('danaBox').style.display = 'none';
    document.getElementById('briBox').style.display = 'none';

    if(type == 'qris') document.getElementById('qrisBox').style.display = 'block';
    if(type == 'dana') document.getElementById('danaBox').style.display = 'block';
    if(type == 'bri')  document.getElementById('briBox').style.display = 'block';
}
</script>

</body>
</html>