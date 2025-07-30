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
$editVoter = null;

// Fungsi untuk menghasilkan token 6 angka
function generateToken() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // Menghasilkan 6 angka
}

// Proses penambahan pemilih
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_voter'])) {
    if ($_POST['token'] !== $_SESSION['token']) {
        $errorMessage = "Token tidak valid atau sudah digunakan.";
    } else {
        $voterName = $_POST['voter_name'];
        $voterAddress = $_POST['voter_address'];
        $voterToken = generateToken(); // Menghasilkan token baru
        $tokenCreatedAt = date('Y-m-d H:i:s'); // Waktu pembuatan token

        // Cek apakah pemilih sudah ada hanya saat menambah pemilih
        if (!isset($_POST['voter_id']) || empty($_POST['voter_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM voters WHERE name = :name");
            $stmt->execute(['name' => $voterName]);
            $existingVoter = $stmt->fetch();

            if ($existingVoter) {
                $errorMessage = "Peringatan: Pemilih dengan nama '$voterName' sudah ada.";
            } else {
                // Proses insert pemilih
                $stmt = $pdo->prepare("INSERT INTO voters (name, address, token, token_created_at, token_used) VALUES (:name, :address, :token, :token_created_at, 0)");
                $stmt->execute(['name' => $voterName, 'address' => $voterAddress, 'token' => $voterToken, 'token_created_at' => $tokenCreatedAt]);
                $successMessage = "Pemilih berhasil ditambahkan.";
            }
        } else {
            // Proses update pemilih
            $id = $_POST['voter_id'];
            $stmt = $pdo->prepare("UPDATE voters SET name = :name, address = :address, token = :token, token_created_at = :token_created_at WHERE id = :id");
            $stmt->execute(['name' => $voterName, 'address' => $voterAddress, 'token' => $voterToken, 'token_created_at' => $tokenCreatedAt, 'id' => $id]);
            $successMessage = "Pemilih berhasil diperbarui.";
        }
    }
}

// Proses pengeditan pemilih
if (isset($_GET['edit_voter'])) {
    $id = $_GET['edit_voter'];
    $stmt = $pdo->prepare("SELECT * FROM voters WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editVoter = $stmt->fetch();
}

// Proses menghasilkan token baru untuk pemilih
if (isset($_GET['generate_token'])) {
    $id = $_GET['generate_token'];
    $newToken = generateToken(); // Menghasilkan token baru
    $tokenCreatedAt = date('Y-m-d H:i:s'); // Waktu pembuatan token
    // Update token dan set token_used ke 0
    $stmt = $pdo->prepare("UPDATE voters SET token = :token, token_created_at = :token_created_at, token_used = 0 WHERE id = :id");
    $stmt->execute(['token' => $newToken, 'token_created_at' => $tokenCreatedAt, 'id' => $id]);
    $successMessage = "Token baru berhasil dibuat.";
}

// Proses penghapusan pemilih
if (isset($_GET['delete_voter'])) {
    $id = $_GET['delete_voter'];
    $stmt = $pdo->prepare("DELETE FROM voters WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: voters.php"); // Redirect ke halaman pemilih setelah penghapusan
    exit();
}

// Proses penghapusan semua pemilih
if (isset($_POST['delete_all_voters'])) {
    // Hapus semua pemilih dari database
    $stmt = $pdo->prepare("DELETE FROM voters");
    $stmt->execute();
    
    // Redirect ke halaman pemilih setelah penghapusan
    header("Location: voters.php?success=1");
    exit();
}

// Proses penggunaan token
if (isset($_GET['use_token'])) {
    $id = $_GET['use_token'];
    
    // Update status pemilih menjadi sudah memilih
    $stmt = $pdo->prepare("UPDATE voters SET has_voted = 1 WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    // Redirect ke halaman pemilih setelah memperbarui status
    header("Location: voters.php?token_used=1");
    exit();
}

// Proses import data pemilih dari file CSV
if (isset($_POST['import_voters'])) {
    if ($_FILES['voter_file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['voter_file']['tmp_name'];
        $fileName = $_FILES['voter_file']['name'];
        $fileSize = $_FILES['voter_file']['size'];
        $fileType = $_FILES['voter_file']['type'];
        
        // Membaca file CSV
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $voterName = $data[0];
                $voterAddress = $data[1];
                $voterToken = generateToken(); // Menghasilkan token baru
                $tokenCreatedAt = date('Y-m-d H:i:s'); // Waktu pembuatan token

                // Proses insert pemilih
                $stmt = $pdo->prepare("INSERT INTO voters (name, address, token, token_created_at, token_used) VALUES (:name, :address, :token, :token_created_at, 0)");
                $stmt->execute(['name' => $voterName, 'address' => $voterAddress, 'token' => $voterToken, 'token_created_at' => $tokenCreatedAt]);
            }
            fclose($handle);
            $successMessage = "Data pemilih berhasil diimpor.";
        } else {
            $errorMessage = "Gagal membuka file.";
        }
    } else {
        $errorMessage = "Terjadi kesalahan saat mengunggah file.";
    }
}

// Proses ekspor data pemilih ke CSV
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="voters.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nama Pemilih', 'Alamat Pemilih', 'No Token', 'Status Memilih']);

    $stmt = $pdo->query("SELECT * FROM voters");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Proses ekspor data pemilih ke PDF
if (isset($_GET['export_pdf'])) {
    require('fpdf/fpdf.php'); // Pastikan Anda sudah mengunduh dan menyertakan FPDF

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Daftar Pemilih', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(10, 10, 'ID', 1);
    $pdf->Cell(60, 10, 'Nama Pemilih', 1);
    $pdf->Cell(60, 10, 'Alamat Pemilih', 1);
    $pdf->Cell(30, 10, 'No Token', 1);
    $pdf->Cell(30, 10, 'Status Memilih', 1);
    $pdf->Ln();
    $stmt = $pdo->query("SELECT * FROM voters");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf->Cell(10, 10, $row['id'], 1);
        $pdf->Cell(60, 10, $row['name'], 1);
        $pdf->Cell(60, 10, $row['address'], 1);
        $pdf->Cell(30, 10, $row['token'], 1);
        $pdf->Cell(30, 10, $row['has_voted'] ? 'Sudah Memilih' : 'Belum Memilih', 1);
        $pdf->Ln();
    }
    $pdf->Output('D', 'data pemilih.pdf');
    exit();
}

// Mengambil data pemilih untuk ditampilkan
$stmt_voters = $pdo->query("SELECT * FROM voters");
$voterList = $stmt_voters->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pemilih</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- Menambahkan Font Awesome -->
    <style>
        body {
            background-color: #f0f4f8; /* Warna latar belakang */
        }
        .container {
            background-color: #ffffff; /* Warna latar belakang kontainer */
            border-radius: 10px; /* Sudut melengkung */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Bayangan */
            padding: 20px; /* Padding di dalam kontainer */
            margin-top: 30px; /* Margin atas */
        }
        h2 {
            color: #343a40; /* Warna judul */
            margin-bottom: 20px; /* Jarak bawah judul */
        }
        .alert {
            border-radius: 5px; /* Sudut melengkung untuk alert */
        }
        .table {
            margin-top: 20px; /* Jarak atas tabel */
        }
        .btn {
            border-radius: 5px; /* Sudut melengkung untuk tombol */
        }
        .table-responsive {
            max-height: 400px; /* Tinggi maksimum untuk scroll */
            overflow-y: auto; /* Scroll vertikal */
            border: 1px solid #dee2e6; /* Border untuk area tabel */
            border-radius: 5px; /* Sudut melengkung */
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>
        <h2>Daftar Pemilih</h2>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="voter_file">Impor Data Pemilih (CSV):</label>
                <input type="file" class="form-control" id="voter_file" name="voter_file" accept=".csv" required>
            </div>
            <button type="submit" name="import_voters" class="btn btn-primary">Impor Pemilih</button>
        </form>

        <div class="mt-3 mb-3">
            <a href="?export_csv=1" class="btn btn-info">Ekspor ke CSV</a>
            <a href="?export_pdf=1" class="btn btn-danger">Ekspor ke PDF</a>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
            <input type="hidden" name="voter_id" value="<?php echo $editVoter ? $editVoter['id'] : ''; ?>">
            <div class="form-group">
                <label for="voter_name">Nama Pemilih:</label>
                <input type="text" class="form-control" id="voter_name" name="voter_name" value="<?php echo $editVoter ? $editVoter['name'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="voter_address">Alamat Pemilih:</label>
                <input type="text" class="form-control" id="voter_address" name="voter_address" value="<?php echo $editVoter ? $editVoter['address'] : ''; ?>" required>
            </div>
            <button type="submit" name="add_voter" class="btn btn-success"><?php echo $editVoter ? "Perbarui Pemilih" : "Tambah Pemilih"; ?></button>
            <a href="voters.php" class="btn btn-secondary ml-2">Tambah Pemilih Baru</a>
        </form>

        <!-- Menampilkan Pesan Kesalahan -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger mt-3"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <!-- Menampilkan Pesan Sukses -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success mt-3"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <!-- Tombol Hapus Semua Pemilih -->
        <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua pemilih?');">
            <button type="submit" name="delete_all_voters" class="btn btn-danger mb-3">Hapus Semua Pemilih</button>
        </form>
        
        <!-- Menampilkan Pesan Sukses -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success mt-3">Semua pemilih berhasil dihapus.</div>
        <?php endif; ?>

        <!-- Tabel Pemilih dengan Scroll -->
        <div class="table-responsive">
            <table class="table table-bordered mt-4">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Nama Pemilih</th>
                        <th>Alamat Pemilih</th>
                        <th>No Token</th>
                        <th>Status Memilih</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($voterList as $voter): ?>
                        <tr>
                            <td><?php echo $voter['id']; ?></td>
                            <td><?php echo $voter['name']; ?></td>
                            <td><?php echo $voter['address']; ?></td>
                            <td><?php echo $voter['token']; ?></td>
                            <td><?php echo $voter['has_voted'] ? 'Sudah Memilih' : 'Belum Memilih'; ?></td>
                            <td>
                                <a href="voters.php?edit_voter=<?php echo $voter['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="voters.php?delete_voter=<?php echo $voter['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus?');">Hapus</a>
                                <a href="voters.php?generate_token=<?php echo $voter['id']; ?>" class="btn btn-info btn-sm">Token Baru</a>
                                <a href="voters.php?use_token=<?php echo $voter['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Apakah Anda yakin ingin menggunakan token ini?');">Gunakan Token</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'footer.php'; ?> <!-- Menyertakan footer.php -->
</body>
</html>
