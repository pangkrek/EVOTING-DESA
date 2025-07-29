<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = $_POST['candidate_id'];

    // Simpan suara ke database
    $stmt = $pdo->prepare("INSERT INTO votes (user_id, candidate_id) VALUES (:user_id, :candidate_id)");
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'candidate_id' => $candidate_id]);

    echo "Terima kasih telah memberikan suara!";
    session_destroy(); // Hapus session setelah voting
}
?>
