<?php
session_start();
// clear user session
unset($_SESSION['user_id'], $_SESSION['user_name']);
// optionally destroy
// session_destroy();
header('Location: index.php');
exit;
?>