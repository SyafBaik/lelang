<?php
// ...existing code...
include 'db.php';

date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');

// Jangan tampilkan error di produksi
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// ensure using $koneksi (db.php defines $koneksi)
if (!isset($koneksi) || !$koneksi) {
    // fallback if another var name used
    if (isset($conn) && $conn) {
        $koneksi = $conn;
    }
}

if (!isset($koneksi) || !$koneksi) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Koneksi database tidak tersedia."]);
    exit;
}

session_start();

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$bid_amount = (float)($_POST['bid_amount'] ?? 0);

// Ambil nama penawar dari session jika ada
$bidder_name = '';
if (isset($_SESSION['user_name']) && $_SESSION['user_name']) {
    $bidder_name = trim($_SESSION['user_name']);
} else {
    // Jika tidak login, beri tahu frontend
    http_response_code(401);
    echo json_encode(["success" => false, "login_required" => true, "message" => "Login diperlukan."]);
    exit;
}

// Validasi input dasar
if ($item_id <= 0 || $bidder_name === '' || $bid_amount <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Data tidak lengkap atau tidak valid."]);
    exit;
}

// Pastikan nama tabel/kolom sesuai dengan skema Anda.
// Di file lain Anda memakai 'auctions' dan kolom mungkin 'id' & 'auction_id'.
// Sesuaikan jika DB Anda berbeda.
$selectSql = "SELECT end_time FROM auctions WHERE id = ?";
if ($stmt = mysqli_prepare($koneksi, $selectSql)) {
    mysqli_stmt_bind_param($stmt, 'i', $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $end_time);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Lelang tidak ditemukan."]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Query gagal disiapkan."]);
    exit;
}

// Cek waktu lelang dengan DateTime
try {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $end = new DateTime($end_time, new DateTimeZone('Asia/Jakarta'));
    if ($now > $end) {
        echo json_encode(["success" => false, "message" => "Waktu lelang sudah berakhir."]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Format waktu lelang tidak valid."]);
    exit;
}

// Masukkan tawaran dengan prepared statement
$insertSql = "INSERT INTO bids (auction_id, bidder_name, bid_amount, bid_time) VALUES (?, ?, ?, NOW())";
if ($stmt = mysqli_prepare($koneksi, $insertSql)) {
    mysqli_stmt_bind_param($stmt, 'isd', $item_id, $bidder_name, $bid_amount);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Tawaran berhasil dimasukkan!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Gagal menyimpan tawaran."]);
    }
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Persiapan query gagal."]);
}
exit;
?>