<?php
$menu = [
    ["nama" => "Chocolate", "harga" => 10000, "foto" => "image/coklat.jpg"],
    ["nama" => "Strawberry", "harga" => 10000, "foto" => "image/strowberry.jpg"],
    ["nama" => "Matcha", "harga" => 10000, "foto" => "image/matcha.jpg"],
    ["nama" => "Blueberry", "harga" => 10000, "foto" => "image/anggur.jpg"],
    ["nama" => "Manggo Creamy", "harga" => 10000, "foto" => "image/manggo.jpg"],
];
?>

<!DOCTYPE html>
<html>
<head>
<title>Daifuku Mochi - Menu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f8f9fa;}
.navbar{background:#f6c1cc;}
.navbar a{color:white !important; margin-right:20px; font-weight:500;}
.hero{background:#f6c1cc; padding:60px; text-align:center; color:white;}
.card{border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.card img{border-radius:10px; height:200px; object-fit:cover; width:100%; margin-bottom:10px;}
.btn-pink{background:#f6c1cc; color:white; border:none;}
.btn-pink:hover{background:#d98fa1; color:white;}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg px-4">
<a class="navbar-brand text-white" href="index.php">Daifuku Mochi</a>
<div class="navbar-nav">
    <a class="nav-link" href="index.php">Home</a>
    <a class="nav-link" href="Menu.php">Menu</a>
    <a class="nav-link" href="pesanan.php">Pesanan</a>
    <a class="nav-link" href="tentang.php">Tentang</a>
    <a class="nav-link" href="ulasan.php">Ulasan</a>
    <a class="nav-link" href="sosmed.php">Sosial Media</a>
</div>
</nav>

<div class="container py-5">
<h2 class="mb-4 text-center text-dark">Varian Rasa Daifuku Mochi</h2>

<div class="row">
    <?php foreach($menu as $m){ ?>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card p-3 text-center">
                <img src="<?= $m['foto']; ?>" alt="<?= $m['nama']; ?>">
                <h5><?= $m['nama']; ?></h5>
                <p class="text-primary fw-bold">Rp<?= number_format($m['harga']); ?></p>
                <a href="pesanan.php?nama=<?= urlencode($m['nama']); ?>&harga=<?= $m['harga']; ?>" class="btn btn-pink">Beli</a>
            </div>
        </div>
    <?php } ?>
</div>

</div>

</body>
</html>