<?php
session_start();
include 'db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Variabel untuk menyimpan pesan kesalahan
$errorMessage = "";
$successMessage = ""; // Untuk menyimpan pesan sukses

// Menghasilkan token jika belum ada
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32)); // Menghasilkan token
}

// Variabel untuk menyimpan data yang akan diedit
$editCandidate = null;

// Proses penghapusan kandidat
if (isset($_GET['delete_candidate'])) {
    $id = $_GET['delete_candidate'];
    try {
        // Hapus suara yang terkait dengan kandidat
        $stmt_votes = $pdo->prepare("DELETE FROM votes WHERE candidate_id = :candidate_id");
        $stmt_votes->execute(['candidate_id' => $id]);

        // Hapus kandidat
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $successMessage = "Kandidat berhasil dihapus.";
    } catch (PDOException $e) {
        $errorMessage = "Gagal menghapus kandidat: " . $e->getMessage();
    }
}

// Mengambil data kandidat
$stmt_kandidat = $pdo->query("SELECT * FROM candidates");
$kandidatList = $stmt_kandidat->fetchAll();

// Mengambil data suara untuk grafik
$voteCounts = [];
$totalVotes = 0; // Total suara

foreach ($kandidatList as $kandidat) {
    $stmt_votes = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = :candidate_id");
    $stmt_votes->execute(['candidate_id' => $kandidat['id']]);
    $voteCounts[$kandidat['id']] = $stmt_votes->fetchColumn();
    $totalVotes += $voteCounts[$kandidat['id']];
}

// Data untuk grafik
$labels = [];
$data = [];
foreach ($kandidatList as $kandidat) {
    $labels[] = $kandidat['name'];
    $data[] = $voteCounts[$kandidat['id']];
}

// Proses penambahan kandidat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_candidate'])) {
    if ($_POST['token'] !== $_SESSION['token']) {
        $errorMessage = "Token tidak valid atau sudah digunakan.";
    } else {
        $name = $_POST['name'];
        $vision = $_POST['vision'];
        $mission = $_POST['mission'];

        // Proses upload foto
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photo = 'uploads/' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        }

        // Cek apakah kandidat dengan nama yang sama sudah ada, hanya untuk penambahan
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE name = :name");
            $stmt_check->execute(['name' => $name]);
            $count = $stmt_check->fetchColumn();

            if ($count > 0) {
                $errorMessage = "Kandidat dengan nama tersebut sudah ada.";
            } else {
                // Proses insert kandidat
                $stmt = $pdo->prepare("INSERT INTO candidates (name, vision, mission, photo) VALUES (:name, :vision, :mission, :photo)");
                $stmt->execute(['name' => $name, 'vision' => $vision, 'mission' => $mission, 'photo' => $photo]);
                $successMessage = "Kandidat berhasil ditambahkan.";
            }
        } else {
            // Proses update kandidat
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE candidates SET name = :name, vision = :vision, mission = :mission, photo = :photo WHERE id = :id");
            $stmt->execute(['name' => $name, 'vision' => $vision, 'mission' => $mission, 'photo' => $photo, 'id' => $id]);
            $successMessage = "Kandidat berhasil diperbarui.";
        }
    }
}

// Proses pengeditan kandidat
if (isset($_GET['edit_candidate'])) {
    $id = $_GET['edit_candidate'];
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editCandidate = $stmt->fetch();
}

// Mengambil data kandidat untuk ditampilkan
$stmt_kandidat = $pdo->query("SELECT * FROM candidates");
$kandidatList = $stmt_kandidat->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Dashboard Admin</h2>
        
        <!-- Menampilkan Pesan Sukses dan Kesalahan -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <h3>Grafik Pemilihan</h3>
        <h4>Grafik Batang Suara</h4>
        <canvas id="candidateBarChart" width="400" height="200"></canvas>

        <h4 class="mt-5">Grafik Diagram Suara</h4>
        <canvas id="candidatePieChart" width="400" height="200"></canvas>

        <h3 class="mt-5">Daftar Kandidat</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
            <input type="hidden" name="id" value="<?php echo $editCandidate ? $editCandidate['id'] : ''; ?>">
            <div class="form-group">
                <label for="name">Nama:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $editCandidate ? $editCandidate['name'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="vision">Visi:</label>
                <textarea class="form-control" id="vision" name="vision" required><?php echo $editCandidate ? $editCandidate['vision'] : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="mission">Misi:</label>
                <textarea class="form-control" id="mission" name="mission" required><?php echo $editCandidate ? $editCandidate['mission'] : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="photo">Foto Kandidat:</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/*" <?php echo $editCandidate ? '' : 'required'; ?>>
                <?php if ($editCandidate && isset($editCandidate['photo']) && $editCandidate['photo']): ?>
                    <img src="<?php echo $editCandidate['photo']; ?>" alt="Foto Kandidat" class="img-thumbnail mt-2" style="max-width: 150px;">
                <?php endif; ?>
            </div>
            <button type="submit" name="add_candidate" class="btn btn-primary">
                <?php echo $editCandidate ? "Perbarui Data Kandidat" : "Simpan Kandidat Baru"; ?>
            </button>
            <?php if ($editCandidate): ?>
                <a href="admin.php" class="btn btn-secondary ml-2">Kandidat Baru</a>
            <?php endif; ?>
        </form>

        <table class="table table-bordered mt-4">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Visi</th>
                    <th>Misi</th>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kandidatList as $kandidat): ?>
                    <tr>
                        <td><?php echo $kandidat['id']; ?></td>
                        <td><?php echo $kandidat['name']; ?></td>
                        <td><?php echo $kandidat['vision']; ?></td>
                        <td><?php echo $kandidat['mission']; ?></td>
                        <td>
                            <?php if ($kandidat['photo']): ?>
                                <img src="<?php echo $kandidat['photo']; ?>" alt="Foto Kandidat" class="img-thumbnail" style="max-width: 100px;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="admin.php?edit_candidate=<?php echo $kandidat['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="admin.php?delete_candidate=<?php echo $kandidat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus?');">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="voters.php" class="btn btn-secondary mt-3">Kelola Pemilih</a>
        <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
    </div>
<?php include 'footer.php'; ?> <!-- Menyertakan footer.php -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Membuat grafik batang menggunakan Chart.js
        var ctxBar = document.getElementById('candidateBarChart').getContext('2d');
        var candidateBarChart = new Chart(ctxBar, {
            type: 'bar', // Jenis grafik
            data: {
                labels: <?php echo json_encode($labels); ?>, // Label kandidat
                datasets: [{
                    label: 'Jumlah Suara',
                    data: <?php echo json_encode($data); ?>, // Data suara
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

        // Membuat grafik diagram menggunakan Chart.js
        var ctxPie = document.getElementById('candidatePieChart').getContext('2d');
        var candidatePieChart = new Chart(ctxPie, {
            type: 'pie', // Jenis grafik
            data: {
                labels: <?php echo json_encode($labels); ?>, // Label kandidat
                datasets: [{
                    label: 'Jumlah Suara',
                    data: <?php echo json_encode($data); ?>, // Data suara
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Suara per Kandidat'
                    }
                }
            }
        });
    </script>
</body>
</html>
