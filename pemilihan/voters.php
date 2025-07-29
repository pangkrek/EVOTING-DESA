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
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
    </style>
    <style>
        .delete-button {
            float: right; /* Mengapungkan tombol ke kanan */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Daftar Pemilih</h2>
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

        <a href="admin.php" class="btn btn-secondary mt-3">Back to Admin</a>
    </div>
    <?php include 'footer.php'; ?> <!-- Menyertakan footer.php -->
</body>
</html>
