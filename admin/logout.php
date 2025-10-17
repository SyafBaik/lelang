<?php
// ...existing code...
 if (isset($_SESSION['admin'])): ?>
    <form method="post" action="admin/logout.php" style="display:inline">
      <button type="submit" class="admin-btn">Logout (<?= htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8') ?>)</button>
    </form>
    <a href="tambah_barang.php" class="admin-btn">Tambah Barang</a>
  <?php else: ?>
    <a href="admin/login.php" class="admin-btn">Login Admin</a>
  <?php endif; ?>
// ...existing code...