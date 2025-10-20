<?php
session_start();
include 'db.php';

$errors = [];
$success = '';

// create users table if not exists
$createSql = "CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($koneksi, $createSql);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['full_name'] ?? '');

    if ($username === '' || $password === '' || $name === '') {
        $errors[] = 'Semua field wajib diisi.';
    }

    if (empty($errors)) {
        // check if username exists
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($koneksi, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = 'Username sudah digunakan.';
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = "INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($koneksi, $ins)) {
            mysqli_stmt_bind_param($stmt, 'sss', $username, $hash, $name);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($ok) {
                $success = 'Registrasi berhasil. Silakan login.';
            } else {
                $errors[] = 'Gagal menyimpan user.';
            }
        } else {
            $errors[] = 'Query gagal disiapkan.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar User</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div style="max-width:480px;margin:40px auto;background:#fff;padding:18px;border-radius:10px;">
    <h2>Daftar Akun</h2>
    <?php if (!empty($errors)): ?>
      <div class="errors"><ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Nama Lengkap</label>
      <input type="text" name="full_name" required>
      <label>Username</label>
      <input type="text" name="username" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <div style="margin-top:12px;"><button type="submit">Daftar</button> <a href="user_login.php" style="margin-left:8px;">Login</a></div>
    </form>
  </div>
</body>
</html>