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
<style>
  /* Minor local tweaks to complement style.css */
  .edit-card { max-width:980px; margin:28px auto; background:var(--card,#fff); border-radius:12px; padding:18px; box-shadow:0 8px 30px rgba(2,6,23,0.06); }
  .edit-grid { display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap; }
  .edit-left { flex:1 1 360px; min-width:280px; }
  .edit-right { flex:1 1 520px; min-width:280px; }
  .field { margin-bottom:12px; }
  .field label { display:block; font-weight:600; margin-bottom:6px; color:var(--muted,#6b7280); }
  .img-preview { width:100%; max-width:420px; border-radius:10px; box-shadow:0 6px 18px rgba(2,6,23,0.06); display:block; }
  .actions { margin-top:14px; display:flex; gap:10px; align-items:center; }
  .note { font-size:13px; color:var(--muted,#6b7280); }
</style>
</head>
<body>
  <div class="container">
    <div class="edit-card">
      <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="margin:0">Edit Barang</h2>
        <a href="index.php" class="admin-btn" style="background:transparent;color:var(--primary,#4c6ef5);padding:6px 10px;border-radius:8px;">← Kembali</a>
      </header>

      <?php if (!empty($errors)): ?>
        <div class="errors" style="margin-bottom:12px;"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success" style="margin-bottom:12px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <div class="edit-grid">
        <div class="edit-left">
          <div class="field">
            <label>Gambar saat ini</label>
            <?php if ($image_db): ?>
              <img id="currentImage" src="../uploads/<?= rawurlencode($image_db) ?>" alt="" class="img-preview">
            <?php else: ?>
              <img id="currentImage" src="https://via.placeholder.com/420x300?text=No+Image" alt="" class="img-preview">
            <?php endif; ?>
            <div class="note" style="margin-top:8px;">Pratinjau gambar saat ini. Pilih file baru untuk mengganti.</div>
          </div>
        </div>

        <div class="edit-right">
          <form method="post" enctype="multipart/form-data" id="editForm">
            <div class="field">
              <label>Nama Barang</label>
              <input type="text" name="item_name" value="<?= htmlspecialchars($_POST['item_name'] ?? $item_name_db, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="field">
              <label>Deskripsi</label>
              <textarea name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? $description_db, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
              <div class="field" style="flex:1 1 180px;">
                <label>Harga Awal (Rp)</label>
                <input type="number" name="starting_price" step="0.01" value="<?= htmlspecialchars($_POST['starting_price'] ?? $starting_price_db, ENT_QUOTES, 'UTF-8') ?>" required>
              </div>

              <div class="field" style="flex:1 1 220px;">
                <label>Waktu Berakhir</label>
                <?php
                  $dtVal = '';
                  if (!empty($_POST['end_time'])) {
                    $dtVal = htmlspecialchars($_POST['end_time'], ENT_QUOTES, 'UTF-8');
                  } elseif (!empty($end_time_db)) {
                    $d = DateTime::createFromFormat('Y-m-d H:i:s', $end_time_db);
                    if ($d) $dtVal = $d->format('Y-m-d\TH:i');
                  }
                ?>
                <input type="datetime-local" name="end_time" value="<?= $dtVal ?>" required>
              </div>
            </div>

            <div class="field">
              <label>Ganti Gambar (opsional)</label>
              <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp">
              <div class="note">Format: JPG / PNG / WEBP. Max 5MB.</div>
            </div>

            <div class="actions">
              <button type="submit">Simpan Perubahan</button>
              <a href="index.php" class="back">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

<script>
(function(){
  const input = document.getElementById('imageInput');
  const img = document.getElementById('currentImage');
  if (!input || !img) return;
  input.addEventListener('change', function(e){
    const f = input.files && input.files[0];
    if (!f) return;
    if (!f.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = function(ev){ img.src = ev.target.result; };
    reader.readAsDataURL(f);
  });
})();
</script>
</body>
</html>