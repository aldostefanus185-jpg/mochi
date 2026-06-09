<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['user_id'];
$success = $error = "";

/* ================= DATA KERANJANG ================= */
try {
    $q = $conn->prepare("
        SELECT k.ID_PRODUK, k.JUMLAH, p.NAMA_PRODUK, p.HARGA, p.FOTO
        FROM KERANJANG k
        JOIN PRODUK p ON k.ID_PRODUK=p.ID_PRODUK
        WHERE k.ID_USER=:id
    ");

    $q->execute([':id' => $uid]);

    $items = []; 
    $total = 0;

    while($r = $q->fetch(PDO::FETCH_ASSOC)){
        $r['SUBTOTAL'] = $r['HARGA'] * $r['JUMLAH'];
        $total += $r['SUBTOTAL'];
        $items[] = $r;
    }
} catch (PDOException $e) {
    $error = "Gagal mengambil data keranjang: " . $e->getMessage();
}

/* ================= CHECKOUT ================= */
if(isset($_POST['bayar'])){
    if (empty($items)) {
        $error = "Keranjang Anda kosong!";
    } else {
        $metode = $_POST['metode'] ?? '';
        $nama   = trim($_POST['nama_penerima'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $nohp   = trim($_POST['nohp'] ?? '');

        if(!isset($_POST['konfirmasi']) && $metode != 'COD'){
            $error = "Konfirmasi pembayaran dulu!";
        }elseif(empty($nama) || empty($alamat) || empty($nohp)){
            $error = "Nama, Alamat dan Nomor HP wajib diisi!";
        }elseif(empty($metode)){
            $error = "Pilih metode pembayaran terlebih dahulu!";
        }else{
            $status = ($metode == 'COD') ? "Diproses" : "Menunggu Konfirmasi";
            
            $alamat_lengkap = "[Nama: " . $nama . "] " . $alamat;

            try {
                $conn->beginTransaction();

                $ins = $conn->prepare("
                INSERT INTO PESANAN (
                    ID_USER, TOTAL_HARGA, 
                    METODE_PEMBAYARAN, STATUS_PESANAN, ALAMAT, NO_HP
                ) VALUES (
                    :u, :t, :m, :s, :a, :nohp
                )");

                $ins->execute([
                    ':u' => $uid,
                    ':t' => $total,
                    ':m' => $metode,
                    ':s' => $status,
                    ':a' => $alamat_lengkap,
                    ':nohp' => $nohp
                ]);

                $pid = $conn->lastInsertId();

                foreach($items as $i){
                    $d = $conn->prepare("
                    INSERT INTO DETAIL_PESANAN (ID_PESANAN, ID_PRODUK, JUMLAH, HARGA_SATUAN)
                    VALUES (:pid, :pr, :j, :h)
                    ");

                    $d->execute([
                        ':pid' => $pid,
                        ':pr'  => $i['ID_PRODUK'],
                        ':j'   => $i['JUMLAH'],
                        ':h'   => $i['HARGA']
                    ]);
                }

                $del = $conn->prepare("DELETE FROM KERANJANG WHERE ID_USER=:u");
                $del->execute([':u' => $uid]);

                $conn->commit();
                $success = "Pesanan berhasil dibuat!";
                $items = [];
                $total = 0;
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = "Checkout gagal: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #121826; 
            color: #cbd5e1; 
        }
        
        .main { max-width: 1140px; margin: auto; padding: 30px 20px; }
        
        .header-area { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
        .btn-back { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            text-decoration: none; 
            color: #94a3b8; 
            font-size: 14px; 
            font-weight: 500; 
            transition: color 0.2s; 
        }
        .btn-back:hover { color: #f07187; }
        h1 { font-size: 26px; color: #ffffff; font-weight: 600; letter-spacing: -0.5px;}

        .grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        
        .card { 
            background: #1e2640; 
            padding: 24px; 
            border-radius: 16px; 
            border: 1px solid #2e3754; 
            margin-bottom: 24px; 
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .card h2 { 
            font-size: 14px; 
            font-weight: 600; 
            color: #94a3b8; 
            margin-bottom: 20px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        
        .item { display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #2e3754; }
        .item:last-child { border-bottom: none; }
        .item img { width: 56px; height: 56px; object-fit: cover; border-radius: 10px; border: 1px solid #3b4463; }
        .info { flex: 1; }
        .info b { font-size: 15px; color: #ffffff; font-weight: 500; }
        .info small { color: #94a3b8; font-size: 12px; display: block; margin-top: 2px;}
        .price { font-size: 15px; font-weight: 600; color: #ea6297; }

        /* Gaya baru untuk input Alamat & No HP */
        .input-pengiriman { display: flex; gap: 16px; }
        .input-pengiriman textarea { 
            flex: 2;
            padding: 14px; 
            background: #151b2d;
            border: 1px solid #3b4463; 
            color: #ffffff;
            border-radius: 10px; 
            resize: none; 
            min-height: 90px; 
            outline: none; 
            font-family: inherit; 
            font-size: 14px; 
            transition: border-color 0.2s;
        }
        .input-pengiriman input {
            flex: 1;
            padding: 14px;
            background: #151b2d;
            border: 1px solid #3b4463;
            color: #ffffff;
            border-radius: 10px;
            outline: none;
            font-family: inherit;
            font-size: 14px;
            height: fit-content;
            transition: border-color 0.2s;
        }
        .input-pengiriman textarea:focus, .input-pengiriman input:focus { border-color: #ea6297; }

        .opsi { display: flex; gap: 12px; margin-bottom: 12px; }
        .opsi div { flex: 1; }
        .opsi input { display: none; }
        .opsi label { 
            display: block; 
            padding: 14px; 
            background: #151b2d;
            border: 1px solid #3b4463; 
            color: #94a3b8;
            border-radius: 10px; 
            text-align: center; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 500; 
            transition: all 0.2s ease; 
        }
        .opsi input:checked + label { 
            background: #2e243a; 
            border-color: #ea6297; 
            color: #ffffff; 
            box-shadow: 0 0 12px rgba(234, 98, 151, 0.2);
        }

        .payment-detail { 
            display: none; 
            margin-top: 20px; 
            padding: 20px; 
            background: #151b2d; 
            border-radius: 12px; 
            border: 1px dashed #3b4463; 
            text-align: center; 
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        
        .qr img { width: 180px; height: 180px; margin-bottom: 12px; border-radius: 8px; border: 4px solid #fff; }
        .payment-detail h3 { font-size: 20px; color: #ffffff; margin: 6px 0; letter-spacing: 1px; }
        .payment-detail p { font-size: 13px; color: #94a3b8; }

        .checkbox-konfirmasi { 
            display: none; 
            margin-top: 18px; 
            align-items: center; 
            gap: 10px; 
            font-size: 13px; 
            color: #cbd5e1; 
            background: rgba(234, 98, 151, 0.08);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(234, 98, 151, 0.2);
        }

        .summary-card { position: sticky; top: 30px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; color: #94a3b8; }
        .total { 
            display: flex; 
            justify-content: space-between; 
            font-size: 18px; 
            font-weight: 600; 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 1px solid #2e3754; 
            color: #ffffff; 
        }
        
        button { 
            width: 100%; 
            padding: 15px; 
            margin-top: 24px; 
            border: none; 
            border-radius: 10px; 
            background: linear-gradient(135deg, #ea6297, #f07187); 
            color: #fff; 
            font-size: 15px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: opacity 0.2s, transform 0.1s; 
            box-shadow: 0 4px 15px rgba(234, 98, 151, 0.3);
        }
        button:hover { opacity: 0.95; }
        button:active { transform: scale(0.99); }

        .alert, .success { padding: 14px 18px; border-radius: 10px; font-size: 14px; margin-bottom: 25px; font-weight: 500; }
        .alert { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .success { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }

        @media(max-width: 850px) {
            .grid { grid-template-columns: 1fr; }
            .summary-card { position: relative; top: 0; }
            .input-pengiriman { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="main">
    <div class="header-area">
        <a href="menu.php" class="btn-back">← Kembali ke Menu</a>
        <h1>Checkout</h1>
    </div>

    <?php if($error): ?> <div class="alert"><?=$error?></div> <?php endif; ?>
    <?php if($success): ?> <div class="success"><?=$success?></div> <?php endif; ?>

    <form method="POST">
        <div class="grid">
            <div>
                <div class="card">
                    <h2>Daftar Pesanan</h2>
                    <?php if(empty($items)): ?>
                        <p style="font-size:14px; color:#94a3b8; text-align: center; padding: 10px 0;">Tidak ada item di keranjang.</p>
                    <?php else: ?>
                        <?php foreach($items as $i): ?>
                        <div class="item">
                            <img src="<?=$i['FOTO']?>" alt="<?=$i['NAMA_PRODUK']?>">
                            <div class="info">
                                <b><?=$i['NAMA_PRODUK']?></b>
                                <small>Jumlah: <?=$i['JUMLAH']?></small>
                            </div>
                            <div class="price">
                                Rp <?=number_format($i['SUBTOTAL'],0,',','.')?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Informasi Pengiriman</h2>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="display:block; font-size:13px; font-weight:600; color:#94a3b8; margin-bottom:6px;">Nama Penerima</label>
                        <input type="text" name="nama_penerima" placeholder="Tulis nama lengkap penerima..." required style="width:100%; padding:14px; background:#151b2d; border:1px solid #3b4463; color:#ffffff; border-radius:10px; outline:none; font-family:inherit; font-size:14px; transition:border-color 0.2s;">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="display:block; font-size:13px; font-weight:600; color:#94a3b8; margin-bottom:6px;">Nomor HP (WhatsApp)</label>
                        <input type="text" name="nohp" placeholder="Tulis nomor HP..." required style="width:100%; padding:14px; background:#151b2d; border:1px solid #3b4463; color:#ffffff; border-radius:10px; outline:none; font-family:inherit; font-size:14px; transition:border-color 0.2s;">
                    </div>
                    <div class="form-group">
                        <label style="display:block; font-size:13px; font-weight:600; color:#94a3b8; margin-bottom:6px;">Alamat Lengkap</label>
                        <textarea name="alamat" placeholder="Masukkan alamat lengkap rumah / lokasi pengantaran..." required style="width:100%; padding:14px; background:#151b2d; border:1px solid #3b4463; color:#ffffff; border-radius:10px; min-height:90px; outline:none; font-family:inherit; font-size:14px; transition:border-color 0.2s; resize:none;"></textarea>
                    </div>
                </div>

                <div class="card">
                    <h2>Metode Pembayaran</h2>
                    <div class="opsi">
                        <div>
                            <input type="radio" name="metode" id="m_qris" value="QRIS" onclick="togglePembayaran('qris')">
                            <label for="m_qris">QRIS</label>
                        </div>
                        <div>
                            <input type="radio" name="metode" id="m_dana" value="Dana" onclick="togglePembayaran('dana')">
                            <label for="m_dana">DANA</label>
                        </div>
                        <div>
                            <input type="radio" name="metode" id="m_cod" value="COD" onclick="togglePembayaran('cod')">
                            <label for="m_cod">COD</label>
                        </div>
                    </div>

                    <div id="boxQRIS" class="payment-detail qr">
                        <img src="image/kiris.jpeg" alt="QRIS Code">
                        <p>Scan barcode QRIS di atas menggunakan aplikasi e-wallet Anda.</p>
                    </div>

                    <div id="boxDANA" class="payment-detail">
                        <p style="margin-bottom: 5px;">Transfer langsung ke Akun DANA:</p>
                        <h3>081234567890</h3>
                        <p style="color: #ea6297; font-weight: 500;">a.n. Daifuku Mochi</p>
                    </div>

                    <div id="boxCOD" class="payment-detail">
                        <p style="color:#4ade80; font-weight:600; font-size:15px; margin-bottom: 4px;">Bayar di Tempat (COD)</p>
                        <p>Siapkan uang tunai pas saat kurir mengantarkan produk Anda.</p>
                    </div>

                    <div id="konfirmasiBox" class="checkbox-konfirmasi">
                        <label style="cursor:pointer; display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="konfirmasi" id="chkKonfirmasi"> 
                            <span>Saya sudah mengirim/menyelesaikan pembayaran secara penuh</span>
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <div class="card summary-card">
                    <h2>Ringkasan Pembayaran</h2>
                    <?php foreach($items as $i): ?>
                    <div class="summary-row">
                        <span><?=$i['NAMA_PRODUK']?> <small style="color:#64748b">(x<?=$i['JUMLAH']?>)</small></span>
                        <span style="color: #e2e8f0;">Rp <?=number_format($i['SUBTOTAL'],0,',','.')?></span>
                    </div>
                    <?php endforeach; ?>

                    <div class="total">
                        <span>Total Tagihan</span>
                        <span style="color: #ea6297;">Rp <?=number_format($total,0,',','.')?></span>
                    </div>

                    <button type="submit" name="bayar">Konfirmasi & Bayar</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function togglePembayaran(metode) {
    document.getElementById('boxQRIS').style.display = 'none';
    document.getElementById('boxDANA').style.display = 'none';
    document.getElementById('boxCOD').style.display = 'none';
    
    const konfirmasiBox = document.getElementById('konfirmasiBox');
    const chkKonfirmasi = document.getElementById('chkKonfirmasi');

    if(metode === 'qris') {
        document.getElementById('boxQRIS').style.display = 'block';
        konfirmasiBox.style.display = 'flex';
        chkKonfirmasi.required = true;
    } else if(metode === 'dana') {
        document.getElementById('boxDANA').style.display = 'block';
        konfirmasiBox.style.display = 'flex';
        chkKonfirmasi.required = true;
    } else if(metode === 'cod') {
        document.getElementById('boxCOD').style.display = 'block';
        konfirmasiBox.style.display = 'none'; 
        chkKonfirmasi.required = false;
        chkKonfirmasi.checked = false;
    }
}
</script>

</body>
</html>