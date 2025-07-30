<?php
session_start();
include 'db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Mengambil data kandidat
$stmt_kandidat = $pdo->query("SELECT * FROM candidates");
$kandidatList = $stmt_kandidat->fetchAll(PDO::FETCH_ASSOC);

// Mengambil data suara
$voteCounts = [];
foreach ($kandidatList as $kandidat) {
    $stmt_votes = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = :candidate_id");
    $stmt_votes->execute(['candidate_id' => $kandidat['id']]);
    $voteCounts[$kandidat['id']] = (int)$stmt_votes->fetchColumn();
}

// Mengembalikan data dalam format JSON
echo json_encode($voteCounts);
?>
