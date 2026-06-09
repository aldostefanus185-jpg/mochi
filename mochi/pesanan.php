
<?php
$nama = isset($_GET['nama']) ? $_GET['nama'] : '';
$harga = isset($_GET['harga']) ? $_GET['harga'] : '';
?>

<!DOCTYPE html>
<html>
<head>
<title>Daifuku Mochi - Pesanan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background: linear-gradient(135deg, #ffe4ec, #f8f9fa);
}
.navbar{
    background:#f06292;
}
.navbar a{
    color:white !important;
    margin-right:20px;
    font-weight:500;
}
.container{
    padding:40px;
    background:white;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}
.btn-pink{
    background:#f06292;
    color:white;
    border:none;
}
.btn-pink:hover{
    background:#d94b7d;
}
.btn-wa{
    background:#25D366;
    color:white;
    border:none;
}
.btn-wa:hover{
    background:#1ebe5d;
}
</style>

</head>
<body>

<nav class="navbar navbar-expand-lg px-4">
<a class="navbar-brand text-white" href="index.php">Daifuku Mochi</a>
<div class="navbar-nav">
    <a class="nav-link" href="index.php">Home</a>
    <a class="nav-link" href="menu.php">Menu</a>
    <a class="nav-link" href="pesanan.php">Pesanan</a>
    <a class="nav-link" href="tentang.php">Tentang</a>
    <a class="nav-link" href="ulasan.php">Ulasan</a>
    <a class="nav-link" href="sosmed.php">Sosial Media</a>
</div>
</nav>

<div class="container">
<h2>Pesanan Produk</h2>

<?php if($nama && $harga){ ?>

<p>Produk: <strong><?= htmlspecialchars($nama); ?></strong></p>
<p>Harga: <strong>Rp<?= number_format($harga); ?></strong></p>

<form action="checkout.php" method="post">
    <div class="mb-3">
        <label>Nama Pemesan</label>
        <input type="text" name="nama_pemesan" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Alamat</label>
        <textarea name="alamat" class="form-control" required></textarea>
    </div>

    <input type="hidden" name="id_pelanggan" value="1">
    <input type="hidden" name="harga" value="<?= $harga; ?>">

    <!-- Tombol Submit -->
    <button type="submit" class="btn btn-pink w-100 mb-2">
        Submit Pesanan
    </button>

    <!-- Tombol WhatsApp -->
    <button type="button" onclick="kirimWA()" class="btn btn-wa w-100">
        <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" width="20">
        Pesan via WhatsApp
    </button>
</form>

<?php } else { ?>
<p>Pilih produk dulu dari menu.</p>
<?php } ?>

</div>

<script>
function kirimWA(){
    let namaPemesan = document.querySelector('[name="nama_pemesan"]').value;
    let alamat = document.querySelector('[name="alamat"]').value;

    if(namaPemesan === "" || alamat === ""){
        alert("Isi dulu nama dan alamat ya!");
        return;
    }

    let pesan = `Halo, saya ingin memesan:
Produk: <?= $nama ?>
Harga: Rp<?= number_format($harga) ?>
Nama: ${namaPemesan}
Alamat: ${alamat}`;

    let no = "+6287817618168"; // GANTI NOMOR WA KAMU

    let url = "https://wa.me/" + no + "?text=" + encodeURIComponent(pesan);

    window.open(url, '_blank');
}
</script>

</body>
</html>
```
