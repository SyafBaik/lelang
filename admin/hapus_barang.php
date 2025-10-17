<?php
// ...existing code...
    <table>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Harga Awal</th>
            <th>Berakhir</th>
            <th>Aksi</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($query)) : ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>Rp <?= number_format($row['starting_price'] ?? 0, 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($row['end_time'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <a href="edit_barang.php?id=<?= (int)$row['id'] ?>" class="edit">Edit</a>
                <!-- Hapus via POST untuk keamanan -->
                <form method="post" action="hapus_barang.php" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus barang ini?');">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" class="hapus">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
// ...existing code...