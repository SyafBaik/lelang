<?php
include 'db.php';

// Jika form dikirim
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $auction_id = $_POST['auction_id'];
  $bidder_name = $_POST['bidder_name'];
  $bid_amount = $_POST['bid_amount'];

  if (!empty($auction_id) && !empty($bidder_name) && !empty($bid_amount)) {
    $sql = "INSERT INTO bids (auction_id, bidder_name, bid_amount) VALUES (?, ?, ?)";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("isi", $auction_id, $bidder_name, $bid_amount);

    if ($stmt->execute()) {
      echo "<p style='color:green;'>Bid berhasil ditambahkan!</p>";
    } else {
      echo "<p style='color:red;'>Gagal menambahkan bid!</p>";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Bid</title>
  <style>
    body { font-family: Arial; margin: 20px; }
    table { border-collapse: collapse; width: 80%; margin-top: 20px; }
    th, td { border: 1px solid #999; padding: 8px; text-align: center; }
    th { background: #eee; }
  </style>
</head>
<body>
  <h2>Daftar Bid</h2>

  <form method="POST">
    <input type="number" name="auction_id" placeholder="ID Lelang" required>
    <input type="text" name="bidder_name" placeholder="Nama Penawar" required>
    <input type="number" name="bid_amount" placeholder="Jumlah Bid" required>
    <button type="submit">Tambah Bid</button>
  </form>

  <hr>

  <table>
    <tr>
      <th>ID</th>
      <th>ID Lelang</th>
      <th>Nama Penawar</th>
      <th>Jumlah Bid</th>
      <th>Waktu</th>
    </tr>
    <?php
    $result = $koneksi->query("SELECT * FROM bids ORDER BY bid_time DESC");
    while ($row = $result->fetch_assoc()) {
      echo "<tr>
              <td>{$row['id']}</td>
              <td>{$row['auction_id']}</td>
              <td>{$row['bidder_name']}</td>
              <td>Rp " . number_format($row['bid_amount'], 0, ',', '.') . "</td>
              <td>{$row['bid_time']}</td>
            </tr>";
    }
    ?>
  </table>
</body>
</html>
