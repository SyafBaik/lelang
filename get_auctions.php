<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($koneksi) || !$koneksi) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available']);
    exit;
}

mysqli_set_charset($koneksi, 'utf8mb4');

$result = mysqli_query($koneksi, "SELECT id, item_name, description, starting_price, end_time, image_url FROM auctions ORDER BY id DESC");
if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed', 'detail' => mysqli_error($koneksi)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = $row;
}
mysqli_free_result($result);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit;
?>