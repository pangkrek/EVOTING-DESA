<?php
session_start();
include 'db.php';

// Mengambil data kandidat untuk ditampilkan
$stmt_kandidat = $pdo->query("SELECT * FROM candidates");
$kandidatList = $stmt_kandidat->fetchAll();

// Proses pemungutan suara
if (isset($_POST['vote'])) {
    $candidateId = $_POST['candidate_id'];
    
    // Logika untuk menyimpan suara kandidat
    $stmt = $pdo->prepare("INSERT INTO votes (candidate_id) VALUES (:candidate_id)");
    $stmt->execute(['candidate_id' => $candidateId]);

    // Menghentikan pemilih dari memilih lagi
    $_SESSION['has_voted'] = true;

    // Redirect ke halaman terima kasih
    header("Location: thank_you.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilih</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Arial', sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #007bff;
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 15px;
            text-align: center;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .candidate-photo {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .btn-success {
            background-color: #28a745;
            border: none;
            transition: background-color 0.3s;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #007bff;
            color: white;
            border-radius: 10px;
        }
        @media (max-width: 768px) {
            .candidate-card {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="header">
            <h3>Daftar Kandidat</h3>
            <p>Pilih kandidat terbaik untuk masa depan yang lebih baik!</p>
        </div>
        <div class="row">
            <?php foreach ($kandidatList as $kandidat): ?>
                <div class="col-md-4">
                    <div class="candidate-card">
                        <?php if ($kandidat['photo']): ?>
                            <img src="<?php echo htmlspecialchars($kandidat['photo']); ?>" alt="Foto Kandidat" class="candidate-photo">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($kandidat['name']); ?></h4>
                        <p><strong>Visi:</strong> <?php echo htmlspecialchars($kandidat['vision']); ?></p>
                        <p><strong>Misi:</strong> <?php echo htmlspecialchars($kandidat['mission']); ?></p>
                        <form action="" method="POST">
                            <input type="hidden" name="candidate_id" value="<?php echo $kandidat['id']; ?>">
                            <button type="submit" name="vote" class="btn btn-success">Pilih Kandidat</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?> <!-- Menyertakan footer.php -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
