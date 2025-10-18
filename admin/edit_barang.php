<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';

// Ambil data item saat ini
if ($stmt = mysqli_prepare($koneksi, "SELECT item_name, description, starting_price, end_time, image_url FROM auctions WHERE id = ?")) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $item_name_db, $description_db, $starting_price_db, $end_time_db, $image_db);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
} else {
    die('Gagal mengambil data item.');
}

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

    // handle upload gambar (opsional, mengganti gambar lama)
    $newImageName = $image_db; // default: tetap pakai nama lama
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error: ' . $file['error'];
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'File upload invalid (tmp file missing).';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $errors[] = 'Tipe file tidak didukung. Gunakan JPG/PNG/WEBP.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 5MB.';
            } else {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    $errors[] = 'Gagal membuat folder upload. Periksa permission.';
                } else {
                    $newImageName = uniqid('img_', true) . '.' . $ext;
                    $dest = $uploadDir . $newImageName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $errors[] = 'Gagal memindahkan file gambar. Periksa permission folder uploads.';
                        error_log('edit_barang: move_uploaded_file failed tmp=' . $file['tmp_name'] . ' dest=' . $dest);
                    } else {
                        @chmod($dest, 0644);
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE auctions SET item_name = ?, description = ?, starting_price = ?, end_time = ?, image_url = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ssdssi', $item_name, $description, $starting_price, $end_time, $newImageName, $id);
            $ok = mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                // jika gambar baru menggantikan yang lama, hapus file lama
                if (!empty($_FILES['image']['name']) && $image_db && $image_db !== $newImageName) {
                    $oldPath = __DIR__ . '/../uploads/' . basename($image_db);
                    if (is_file($oldPath)) @unlink($oldPath);
                }
                $success = 'Barang berhasil diperbarui.';
                header('Location: index.php?updated=1');
                exit;
            } else {
                $errors[] = 'Gagal memperbarui data.';
            }
        } else {
            $errors[] = 'Persiapan query gagal: ' . mysqli_error($koneksi);
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Edit Barang - Admin</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
  <div style="max-width:700px;margin:30px auto;padding:20px;background:#fff;border-radius:8px;">
    <h2>Edit Barang</h2>

    <?php if (!empty($errors)): ?>
      <div style="color:#b00;margin-bottom:12px;">
        <ul>
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label>Nama Barang</label>
      <input type="text" name="item_name" value="<?= htmlspecialchars($_POST['item_name'] ?? $item_name_db, ENT_QUOTES, 'UTF-8') ?>" required>

      <label>Deskripsi</label>
      <textarea name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? $description_db, ENT_QUOTES, 'UTF-8') ?></textarea>

      <label>Harga Awal (Rp)</label>
      <input type="number" name="starting_price" step="0.01" value="<?= htmlspecialchars($_POST['starting_price'] ?? $starting_price_db, ENT_QUOTES, 'UTF-8') ?>" required>

      <label>Waktu Berakhir</label>
      <?php
        // convert DB 'Y-m-d H:i:s' ke input datetime-local value 'Y-m-d\TH:i'
        $dtVal = '';
        if (!empty($_POST['end_time'])) {
          $dtVal = htmlspecialchars($_POST['end_time'], ENT_QUOTES, 'UTF-8');
        } elseif (!empty($end_time_db)) {
          $d = DateTime::createFromFormat('Y-m-d H:i:s', $end_time_db);
          if ($d) $dtVal = $d->format('Y-m-d\TH:i');
        }
      ?>
      <input type="datetime-local" name="end_time" value="<?= $dtVal ?>" required>

      <label>Gambar saat ini</label>
      <?php if ($image_db): ?>
        <div><img src="../uploads/<?= rawurlencode($image_db) ?>" alt="" style="max-width:200px;display:block;margin-bottom:8px;"></div>
      <?php else: ?>
        <div>Tidak ada gambar.</div>
      <?php endif; ?>

      <label>Ganti Gambar (opsional)</label>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp">

      <div style="margin-top:12px;">
        <button type="submit" style="padding:8px 12px;">Simpan Perubahan</button>
        <a href="index.php" style="margin-left:10px;">Batal</a>
      </div>
    </form>
  </div>
</body>
</html>