<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($koneksi) || !$koneksi) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available']);
    exit;
}

mysqli_set_charset($koneksi, 'utf8mb4');

// detect if table has a dedicated 'image' column (local filename)
$hasImageCol = false;
$colCheck = mysqli_query($koneksi, "SHOW COLUMNS FROM `auctions` LIKE 'image'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) $hasImageCol = true;

$selectCols = "id, item_name, description, starting_price, end_time";
if ($hasImageCol) {
  $selectCols .= ", image";
} else {
  $selectCols .= ", image_url"; // fallback if no image column
}

$result = mysqli_query($koneksi, "SELECT $selectCols FROM auctions ORDER BY id DESC");
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