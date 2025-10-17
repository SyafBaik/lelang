<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

function redirect_index($q = '') {
    header('Location: index.php' . ($q ? "?$q" : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) redirect_index();

    // Ambil nama file gambar dulu
    $img = null;
    if ($stmt = mysqli_prepare($koneksi, "SELECT image_url FROM auctions WHERE id = ?")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $img);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    // Hapus bids terkait
    if ($stmt = mysqli_prepare($koneksi, "DELETE FROM bids WHERE auction_id = ?")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Hapus auction
    $deleted = false;
    if ($stmt = mysqli_prepare($koneksi, "DELETE FROM auctions WHERE id = ?")) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $deleted = ($affected > 0);
    } else {
        error_log('hapus_barang: prepare failed: ' . mysqli_error($koneksi));
    }

    // Hapus file gambar fisik jika ada
    if ($img) {
        $path = __DIR__ . '/../uploads/' . basename($img);
        if (is_file($path)) {
            if (!@unlink($path)) {
                error_log("hapus_barang: gagal menghapus file $path");
            }
        }
    }

    redirect_index('deleted=' . ($deleted ? '1' : '0'));
}

// Jika GET tampilkan konfirmasi sederhana
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) redirect_index();

// ambil nama item untuk tampil di konfirmasi
$item_name = '';
if ($stmt = mysqli_prepare($koneksi, "SELECT item_name FROM auctions WHERE id = ?")) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $item_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Hapus Barang</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
  <div style="max-width:600px;margin:40px auto;padding:20px;background:#fff;border-radius:8px;">
    <h2>Hapus Barang</h2>
    <p>Yakin ingin menghapus barang: <strong><?= htmlspecialchars($item_name ?: 'ID ' . $id, ENT_QUOTES, 'UTF-8') ?></strong> ?</p>
    <form method="post" action="hapus_barang.php">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <button type="submit" style="background:#c82333;color:#fff;padding:8px 12px;border:0;border-radius:4px;">Ya, Hapus</button>
      <a href="index.php" style="margin-left:10px;">Batal</a>
    </form>
  </div>
</body>
</html>