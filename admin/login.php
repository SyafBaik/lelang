<?php
session_start();
include '../db.php'; // naik satu folder, karena db.php ada di luar folder admin

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $_SESSION['admin'] = $data['username'];
        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('Username atau password salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
            width: 300px;
        }
        input {
            width: 100%;
            padding: 8px;
            margin: 6px 0;
        }
        button {
            background: #0066ff;
            color: white;
            border: none;
            padding: 8px;
            width: 100%;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Login Admin</h2>
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" name="login">Masuk</button>
    </form>
</body>
</html>
