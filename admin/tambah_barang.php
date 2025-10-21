<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $starting_price = floatval($_POST['starting_price'] ?? 0);
    $end_time_raw = $_POST['end_time'] ?? '';

    if ($item_name === '') $errors[] = 'Nama barang wajib diisi.';
    if ($starting_price <= 0) $errors[] = 'Harga awal harus lebih dari 0.';

    // parse end_time dari input datetime-local (format: YYYY-MM-DDTHH:MM)
    $end_time = null;
    if (!empty($end_time_raw)) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $end_time_raw);
        if ($dt) $end_time = $dt->format('Y-m-d H:i:s');
        else $errors[] = 'Format waktu akhir tidak valid.';
    } else {
        $errors[] = 'Waktu akhir lelang wajib diisi.';
    }

    // handle upload gambar (optional)
    $uploadedName = null;
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowed[$mime])) {
                $errors[] = 'Tipe file tidak didukung. Gunakan JPG/PNG/WEBP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 2MB.';
            } else {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $uploadedName = uniqid('img_', true) . '.' . $ext;
                $dest = $uploadDir . $uploadedName;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $errors[] = 'Gagal memindahkan file gambar.';
                }
            }
        } else {
            $errors[] = 'Terjadi kesalahan saat upload gambar.';
        }
    }

  if (empty($errors)) {
    $sql = "INSERT INTO auctions (item_name, description, starting_price, end_time, image) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($koneksi, $sql)) {
      mysqli_stmt_bind_param($stmt, 'ssdss', $item_name, $description, $starting_price, $end_time, $uploadedName);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($ok) {
                $success = 'Barang berhasil ditambahkan.';
                // redirect ke dashboard admin
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Gagal menyimpan ke database.';
            }
        } else {
            $errors[] = 'Persiapan query gagal: ' . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Tambah Barang - Admin</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .box { max-width:600px;margin:30px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08); }
    .errors { color:#b00;margin-bottom:12px; }
    label { display:block;margin-top:8px; }
    input, textarea { width:100%; padding:8px; box-sizing:border-box; }
    .actions { margin-top:12px; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Tambah Barang</h2>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label>Nama Barang</label>
      <input type="text" name="item_name" value="<?= htmlspecialchars($_POST['item_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

      <label>Deskripsi</label>
      <textarea name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

      <label>Harga Awal (Rp)</label>
      <input type="number" name="starting_price" step="0.01" value="<?= htmlspecialchars($_POST['starting_price'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

      <label>Waktu Berakhir</label>
      <input type="datetime-local" name="end_time" value="<?= htmlspecialchars($_POST['end_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

      <label>Gambar (opsional, JPG/PNG/WEBP, max 2MB)</label>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp">

      <div class="actions">
        <button type="submit">Simpan</button>
        <a href="index.php" style="margin-left:8px;">Batal</a>
      </div>
    </form>
  </div>
</body>
</html>