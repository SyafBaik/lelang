// ...existing code...
async function loadAuctions() {
  try {
    const res = await fetch("get_auctions.php");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) throw new Error('Response bukan JSON');

    const data = await res.json();
    const container = document.getElementById("auction-list");
    if (!container) {
      console.error('Elemen #auction-list tidak ditemukan di DOM');
      return;
    }
    container.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
      container.innerHTML = "<p>Tidak ada barang lelang.</p>";
      return;
    }

    // ...existing code...
    data.forEach(item => {
      const card = document.createElement("div");
      card.className = "card";

      const endTime = item.end_time ? Date.parse(item.end_time) : NaN;

    // prefer local file column `image` (or `image_file`) stored in uploads/; fallback to image_url or placeholder
    const localFile = item.image || item.image_file || null;
    const imageSrc = localFile
      ? (`uploads/${encodeURIComponent(localFile)}`)
      : (item.image_url
        ? (item.image_url.startsWith('http') || item.image_url.startsWith('/') ? item.image_url : `uploads/${encodeURIComponent(item.image_url)}`)
        : 'https://via.placeholder.com/200');

      card.innerHTML = `
        <img class="media-img" src="${imageSrc}" alt="${item.item_name || ''}" onerror="this.src='https://via.placeholder.com/200'">
        <h3>${item.item_name || 'Tanpa nama'}</h3>
        <p>${item.description || ''}</p>
        <p class="price">Rp ${parseInt(item.starting_price || 0).toLocaleString()}</p>
        <div class="timer" id="timer-${item.id}"></div>
        <a href="detail.php?id=${item.id}" class="btn">Lihat Detail</a>
      `;
// ...existing code...

      container.appendChild(card);

      const timerEl = document.getElementById(`timer-${item.id}`);
      if (isNaN(endTime)) {
        timerEl.innerText = "Waktu tidak tersedia";
        return;
      }

      const timer = setInterval(() => {
        const now = Date.now();
        const diff = endTime - now;
        if (diff <= 0) {
          timerEl.innerText = "Lelang Berakhir";
          clearInterval(timer);
          return;
        }
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        timerEl.innerText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
      }, 1000);
    });
  } catch (err) {
    console.error("Gagal mengambil data:", err);
    const container = document.getElementById("auction-list");
    if (container) container.innerHTML = "<p>Gagal memuat daftar barang.</p>";
  }
}

loadAuctions();
// ...existing code...