<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Kasih</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Terima Kasih!</h2>
        <p>Anda telah berhasil memilih kandidat. Suara Anda sangat berarti!</p>
        <a href="index.php" class="btn btn-primary">Kembali ke Halaman Utama</a>
    </div>
    <?php include 'footer.php'; ?> <!-- Menyertakan footer.php -->
</body>
</html>
