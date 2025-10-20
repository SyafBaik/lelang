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
  <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center;">
    <?php if (!empty($_SESSION['user_id'])): ?>
      <span style="color:white;opacity:0.95;">Halo, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
      <a href="user_logout.php" class="admin-btn">Logout</a>
    <?php else: ?>
      <a href="user_login.php" class="admin-btn">Login</a>
      <a href="user_register.php" class="admin-btn">Daftar</a>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin'])): ?>
      <a href="admin/tambah_barang.php" class="admin-btn">Tambah Barang</a>
      <a href="admin/logout.php" class="admin-btn">Logout Admin</a>
    <?php else: ?>
      <a href="admin/login.php" class="admin-btn">Login Admin</a>
    <?php endif; ?>
  </div>

    <p>Daftar Barang Lelang</p>
</header>
  <main id="auction-list"></main>

  <script src="script.js"></script>
</body>
</html>
