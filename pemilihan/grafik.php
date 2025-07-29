<?php
session_start();
include 'db.php';

// Ambil data suara dari database
$stmt = $pdo->query("SELECT candidates.name, COUNT(votes.candidate_id) AS vote_count 
                      FROM candidates 
                      LEFT JOIN votes ON candidates.id = votes.candidate_id 
                      GROUP BY candidates.id");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data untuk grafik
$labels = [];
$data = [];

foreach ($candidates as $candidate) {
    $labels[] = $candidate['name'];
    $data[] = $candidate['vote_count'] ? $candidate['vote_count'] : 0; // Jika tidak ada suara, set ke 0
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Grafik Pemilihan</title>
</head>
<body>
    <div class="container">
        <h2>Grafik Pemilihan Ketua RT</h2>
        <canvas id="voteChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('voteChart').getContext('2d');
        const voteChart = new Chart(ctx, {
            type: 'bar', // Tipe grafik (bar, line, pie, dll)
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Jumlah Suara',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
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
    </script>
</body>
</html>
