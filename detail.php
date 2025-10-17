<?php
// ...existing code...
include 'db.php';


if (!isset($_GET['id'])) {
  die("Barang tidak ditemukan.");
}

$id = intval($_GET['id']);

$query = "SELECT * FROM auctions WHERE id = $id";
$result = mysqli_query($koneksi, $query);
if ($result === false) {
  die("Terjadi kesalahan pada server.");
}
$item = mysqli_fetch_assoc($result);

if (!$item) {
  die("Barang tidak ditemukan.");
}

// Ambil daftar penawar
$bids_res = mysqli_query($koneksi, "SELECT * FROM bids WHERE auction_id = $id ORDER BY bid_amount DESC");
$bids = [];
if ($bids_res && mysqli_num_rows($bids_res) > 0) {
  while ($b = mysqli_fetch_assoc($bids_res)) $bids[] = $b;
  mysqli_free_result($bids_res);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?> - IndoLang</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* ...existing styles... */
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
    <img src="<?= htmlspecialchars($item['image_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='https://via.placeholder.com/400'">
    </div>

    <div class="right">
      <h2><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></h2>
      <p><?= nl2br(htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8')) ?></p>
      <p class="price">Harga Awal: Rp <?= number_format($item['starting_price'] ?? 0, 0, ',', '.') ?></p>
      <div class="timer" id="countdown"></div>
      <p><strong>Berakhir:</strong> <?= htmlspecialchars($item['end_time'], ENT_QUOTES, 'UTF-8') ?></p>

      <form id="bidForm">
        <input type="hidden" name="item_id" id="item_id" value="<?= (int)$item['id']; ?>">
        <label for="bidder_name">Nama Anda:</label>
        <input type="text" id="bidder_name" name="bidder_name" required>

        <label for="bid_amount">Tawaran:</label>
        <input type="number" id="bid_amount" name="bid_amount" required>

        <button type="submit" id="bid-btn">Ikut Lelang</button>
      </form>

      <a href="index.php" class="back">← Kembali</a>
    </div>
  </div>

  <div class="bids" style="max-width: 900px; margin: 20px auto;">
    <h3>Penawar Tertinggi</h3>
    <?php if (!empty($bids)): ?>
      <?php foreach ($bids as $bid): ?>
        <div class="bid-item">
          <span><?= htmlspecialchars($bid['bidder_name'], ENT_QUOTES, 'UTF-8') ?></span>
          <span>Rp <?= number_format($bid['bid_amount'], 0, ',', '.') ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Belum ada penawar.</p>
    <?php endif; ?>
  </div>

  <script>
  // gunakan satu blok JS, referensi elemen yang benar
  (function() {
    const bidForm = document.getElementById('bidForm');
    const bidBtn = document.getElementById('bid-btn');
    const countdownEl = document.getElementById('countdown');

    // gunakan $item['end_time'] dari PHP (escape)
    const endTime = new Date("<?= htmlspecialchars($item['end_time'], ENT_QUOTES, 'UTF-8') ?>").getTime();

    let timer = null;
    function updateCountdown() {
      const now = Date.now();
      const distance = endTime - now;

      if (distance <= 0) {
        if (timer) clearInterval(timer);
        countdownEl.innerHTML = "<span class='ended'>Lelang Berakhir</span>";
        if (bidBtn) {
          bidBtn.disabled = true;
          bidBtn.textContent = "Lelang Selesai";
        }
        // disable inputs
        if (bidForm) {
          Array.from(bidForm.querySelectorAll("input, button")).forEach(el => el.disabled = true);
        }
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      countdownEl.textContent = `Sisa waktu: ${days}d ${hours}j ${minutes}m ${seconds}s`;
    }

    timer = setInterval(updateCountdown, 1000);
    updateCountdown();

    if (bidForm) {
      bidForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(bidForm);
        try {
          const res = await fetch('place_bid.php', { method: 'POST', body: fd });
          if (!res.ok) throw new Error('Network response not ok');
          const json = await res.json();
          alert(json.message || 'Respon tidak diketahui');
          // konsisten cek properti yang dikembalikan, misal json.success === true
          if (json.success || json.status === 'success') {
            window.location.reload();
          }
        } catch (err) {
          console.error(err);
          alert('Gagal mengirim tawaran.');
        }
      });
    }
  })();
  </script>
</body>
</html>