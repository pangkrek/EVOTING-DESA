<?php
session_start();
include 'db.php';
include 'header.php'; // Menyertakan header.php

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Mengambil data kandidat
$stmt_kandidat = $pdo->query("SELECT * FROM candidates");
$kandidatList = $stmt_kandidat->fetchAll(PDO::FETCH_ASSOC);

// Mengambil data suara untuk grafik
$voteCounts = [];
$totalVotes = 0; // Total suara

foreach ($kandidatList as $kandidat) {
    $stmt_votes = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = :candidate_id");
    $stmt_votes->execute(['candidate_id' => $kandidat['id']]);
    $voteCounts[$kandidat['id']] = (int)$stmt_votes->fetchColumn(); // Pastikan ini adalah integer
    $totalVotes += $voteCounts[$kandidat['id']];
}

// Data untuk grafik
$labels = [];
$data = [];
foreach ($kandidatList as $kandidat) {
    $labels[] = htmlspecialchars($kandidat['name']); // Menghindari XSS
    $data[] = $voteCounts[$kandidat['id']];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Menyertakan Chart.js -->
    <style>
        body {
            background-color: #f8f9fa; /* Warna latar belakang */
        }
        .container {
            background-color: #ffffff; /* Warna latar belakang kontainer */
            border-radius: 10px; /* Sudut melengkung */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Bayangan */
            padding: 20px; /* Padding di dalam kontainer */
            margin-top: 30px; /* Margin atas */
        }
        h3 {
            color: #343a40; /* Warna judul */
        }
        canvas {
            border: 1px solid #dee2e6; /* Border untuk grafik */
            border-radius: 10px; /* Sudut melengkung untuk grafik */
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h3>Grafik Pemilihan</h3>
    <canvas id="candidateBarChart" width="400" height="200"></canvas>

    <script>
    // Membuat grafik batang menggunakan Chart.js
    var ctxBar = document.getElementById('candidateBarChart').getContext('2d');
    var candidateBarChart = new Chart(ctxBar, {
        type: 'bar', // Jenis grafik
        data: {
            labels: <?php echo json_encode($labels); ?>, // Label kandidat
            datasets: [{
                label: 'Jumlah Suara',
                data: <?php echo json_encode($data); ?>, // Data suara awal
                backgroundColor: 'rgba(75, 192, 192, 0.5)', // Warna latar belakang batang
                borderColor: 'rgba(75, 192, 192, 1)', // Warna border batang
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Fungsi untuk memperbarui grafik
    function updateChart() {
        fetch('get_votes.php')
            .then(response => response.json())
            .then(data => {
                // Memperbarui data grafik
                candidateBarChart.data.datasets[0].data = Object.values(data);
                candidateBarChart.update(); // Memperbarui grafik
            })
            .catch(error => console.error('Error fetching vote data:', error));
    }

    // Memperbarui grafik setiap 5 detik
    setInterval(updateChart, 5000);
</script>

</div>

<?php include 'footer.php'; // Menyertakan footer.php ?>
</body>
</html>
