<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IndoLang</title>
  <!-- Add font and improved stylesheet -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <h1> IndoLang</h1>
  <?php if (isset($_SESSION['admin'])): ?>
  <a href="admin/login.php" class="admin-btn">Login <?= htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8') ?></a>
  <a href="admin/tambah_barang.php" class="admin-btn">Tambah Barang</a>
<?php else: ?>
  <a href="admin/logout.php" class="admin-btn">Logout Admin</a>
<?php endif; ?>

    <p>Daftar Barang Lelang</p>
</header>
  <main id="auction-list"></main>

  <script src="script.js"></script>
</body>
</html>
