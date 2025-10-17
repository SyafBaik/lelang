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
            <td><img src="../uploads/<?= $row['image_url'] ?>" width="100"></td>
            
            <td>
                <a href="edit_barang.php?id=<?= $row['id'] ?>" class="edit">Edit</a>
                <a href="hapus_barang.php?id=<?= $row['id'] ?>" class="hapus" onclick="return confirm('Yakin ingin hapus barang ini?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
