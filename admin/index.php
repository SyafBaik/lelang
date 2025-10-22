<?php
session_start();
include '../db.php';

// Jika belum login, alihkan ke halaman login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Ambil data barang dari database
$query = mysqli_query($koneksi, "SELECT * FROM auctions ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - IndoLang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        header {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background: #007bff;
            color: white;
        }
        a {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .edit {
            background: orange;
            color: white;
        }
        .hapus {
            background: red;
            color: white;
        }
        .tambah {
            display: inline-block;
            background: green;
            color: white;
            padding: 8px 14px;
            margin: 20px 5%;
            border-radius: 6px;
        }
        .logout {
            background: crimson;
            color: white;
            padding: 6px 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Dashboard Admin - IndoLang</h1>
        <div>
            <a href="../index.php" class="logout">Kembali ke Beranda</a>
            <a href="login.php" class="logout">Logout</a>
        </div>
    </header>

    <a href="tambah_barang.php" class="tambah">+ Tambah Barang</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Nama Barang</th>
            <th>Harga Awal</th>
            <th>Deskripsi</th>
            <th>Gambar</th>
            <th>Aksi</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($query)) : ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['item_name'] ?></td>
            <td>Rp<?= number_format($row['starting_price'], 0, ',', '.') ?></td>
            <td><?= $row['description'] ?></td>
                        <td>
                                <?php
                                    $img = '';
                                    if (!empty($row['image'])) {
                                        $img = '../uploads/' . rawurlencode($row['image']);
                                    } elseif (!empty($row['image_url'])) {
                                        $img = '../uploads/' . rawurlencode($row['image_url']);
                                    }
                                ?>
                                <img src="<?= $img ?: 'https://via.placeholder.com/100' ?>" width="100" onerror="this.src='https://via.placeholder.com/100'">
                        </td>
            
            <td>
                <a href="edit_barang.php?id=<?= $row['id'] ?>" class="edit">Edit</a>
                                <a href="hapus_barang.php?id=<?= $row['id'] ?>" class="hapus delete-link" data-id="<?= $row['id'] ?>">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    
        <!-- Modal konfirmasi custom -->
        <div id="confirmModal" style="display:none; position:fixed; left:0; right:0; top:0; bottom:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
            <div style="background:#fff; padding:20px; border-radius:8px; max-width:480px; margin:0 auto;">
                <h3>Konfirmasi Hapus</h3>
                <p id="confirmText">Yakin ingin menghapus barang ini?</p>
                <label style="display:block; margin:8px 0;"><input type="checkbox" id="dontAskAgain"> Jangan ingatkan lagi</label>
                <div style="text-align:right; margin-top:12px;">
                    <button id="confirmCancel" style="margin-right:8px;">Batal</button>
                    <button id="confirmOk" style="background:#c82333;color:#fff;padding:8px 12px;border:0;border-radius:4px;">Ya, Hapus</button>
                </div>
            </div>
        </div>

        <script>
            (function(){
                const modal = document.getElementById('confirmModal');
                const confirmText = document.getElementById('confirmText');
                const dontAsk = document.getElementById('dontAskAgain');
                const confirmOk = document.getElementById('confirmOk');
                const confirmCancel = document.getElementById('confirmCancel');
                let currentHref = null;

                function shouldSkip() {
                    try { return localStorage.getItem('skipDeleteConfirm') === '1'; } catch (e) { return false; }
                }

                function showModalFor(href, text) {
                    currentHref = href;
                    confirmText.textContent = text || 'Yakin ingin menghapus barang ini?';
                    dontAsk.checked = false;
                    modal.style.display = 'flex';
                }

                document.querySelectorAll('.delete-link').forEach(a => {
                    a.addEventListener('click', function(e){
                        const href = this.getAttribute('href');
                        const id = this.dataset.id || '';
                        if (shouldSkip()) {
                            // langsung lanjutkan
                            window.location.href = href;
                            return;
                        }
                        e.preventDefault();
                        showModalFor(href, 'Yakin ingin menghapus barang ID ' + id + '?');
                    });
                });

                confirmCancel.addEventListener('click', function(){ modal.style.display = 'none'; currentHref = null; });
                confirmOk.addEventListener('click', function(){
                    try { if (dontAsk.checked) localStorage.setItem('skipDeleteConfirm','1'); } catch(e){}
                    if (currentHref) window.location.href = currentHref;
                });
            })();
        </script>
</body>
</html>
