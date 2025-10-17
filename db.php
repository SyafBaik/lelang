<?php
$koneksi = mysqli_connect("localhost", "root", "", "smartbid_db");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
