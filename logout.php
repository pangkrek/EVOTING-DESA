<?php
session_start();
session_destroy(); // Menghancurkan sesi
header("Location: admin_login.php"); // Redirect ke halaman login
exit();
?>
