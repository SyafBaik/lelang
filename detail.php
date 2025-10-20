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
// tentukan highest bid
$highest_bid = 0;
if (!empty($bids)) {
  $highest_bid = (float)$bids[0]['bid_amount'];
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
  <img class="media-img" src="<?= htmlspecialchars($item['image_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='https://via.placeholder.com/400'">
  </div>

    <div class="right">
      <h2><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></h2>
      <p><?= nl2br(htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8')) ?></p>
      <p class="price">Harga Awal: Rp <?= number_format($item['starting_price'] ?? 0, 0, ',', '.') ?></p>
      <div class="timer" id="countdown"></div>
      <div style="margin-top:8px;">
        <span class="highest">Tertinggi: Rp <?= number_format($highest_bid > 0 ? $highest_bid : ($item['starting_price'] ?? 0), 0, ',', '.') ?></span>
      </div>
      <p><strong>Berakhir:</strong> <?= htmlspecialchars($item['end_time'], ENT_QUOTES, 'UTF-8') ?></p>
      <div class="bid-form" style="margin-top:12px;">
        <form id="bidForm">
          <input type="hidden" name="item_id" id="item_id" value="<?= (int)$item['id']; ?>">
          <div class="row">
            <input type="text" id="bidder_name" name="bidder_name" placeholder="Nama Anda" required>
            <input type="number" id="bid_amount" name="bid_amount" placeholder="Tawaran (Rp)" required step="1">
            <button type="submit" id="bid-btn">Tawar</button>
          </div>
          <div class="bid-msg" id="bidMsg"></div>
        </form>
      </div>

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
      const bidInput = document.getElementById('bid_amount');
      const bidderInput = document.getElementById('bidder_name');
      const bidMsg = document.getElementById('bidMsg');
      const highestEl = document.querySelector('.highest');

      // set minimum bid: highest_bid + 1 or starting price
      const base = <?= json_encode((float)($highest_bid > 0 ? $highest_bid : ($item['starting_price'] ?? 0))); ?>;
      if (bidInput) {
        bidInput.min = Math.max(1, Math.ceil(base + 1));
        bidInput.value = bidInput.min;
      }

      bidForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!bidderInput.value.trim()) {
          if (bidMsg) { bidMsg.className = 'bid-msg error'; bidMsg.textContent = 'Nama wajib diisi.'; }
          return;
        }
        const fd = new FormData(bidForm);
        bidBtn.disabled = true;
        bidBtn.textContent = 'Mengirim...';
        if (bidMsg) { bidMsg.textContent = ''; bidMsg.className = 'bid-msg'; }
        try {
          const res = await fetch('place_bid.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.success) {
            if (bidMsg) { bidMsg.className = 'bid-msg success'; bidMsg.textContent = json.message || 'Tawaran berhasil.'; }
            // update highest display and bids list by reloading or optimistic update
            // simple approach: reload after a short delay
            setTimeout(() => window.location.reload(), 900);
          } else {
            if (bidMsg) { bidMsg.className = 'bid-msg error'; bidMsg.textContent = json.message || 'Gagal menawar.'; }
          }
        } catch (err) {
          console.error(err);
          if (bidMsg) { bidMsg.className = 'bid-msg error'; bidMsg.textContent = 'Terjadi kesalahan jaringan.'; }
        } finally {
          bidBtn.disabled = false;
          bidBtn.textContent = 'Tawar';
        }
      });
    }
  })();
  </script>
</body>
</html>