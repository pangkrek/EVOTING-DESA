<?php
session_start();
include 'db.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Variabel untuk menyimpan pesan kesalahan dan sukses
$errorMessage = "";
$successMessage = "";

// Proses penambahan atau pembaruan kandidat
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $vision = $_POST['vision'];
    $mission = $_POST['mission'];
    $photo = $_FILES['photo']['name'];
    $id = isset($_POST['id']) ? $_POST['id'] : null; // Ambil ID jika ada

    // Cek apakah nama kandidat sudah ada
    if ($id) {
        // Jika ID ada, berarti ini adalah pembaruan
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE name = ? AND id != ?");
        $stmt_check->execute([$name, $id]);
    } else {
        // Jika ID tidak ada, berarti ini adalah penambahan
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE name = ?");
        $stmt_check->execute([$name]);
    }

    if ($stmt_check->fetchColumn() > 0) {
        $errorMessage = "Nama kandidat sudah digunakan. Silakan gunakan nama lain.";
    } else {
        // Upload foto jika ada
        $target_file = $photo ? "uploads/" . basename($photo) : null;
        if ($photo) {
            move_uploaded_file($_FILES['photo']['tmp_name'], $target_file);
        }

        if ($id) {
            // Memperbarui kandidat di database
            $stmt_update = $pdo->prepare("UPDATE candidates SET name = ?, vision = ?, mission = ?, photo = ? WHERE id = ?");
            if ($stmt_update->execute([$name, $vision, $mission, $target_file, $id])) {
                $successMessage = "Kandidat berhasil diperbarui!";
            } else {
                $errorMessage = "Gagal memperbarui kandidat.";
            }
        } else {
            // Menyimpan kandidat baru ke database
            $stmt = $pdo->prepare("INSERT INTO candidates (name, vision, mission, photo) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $vision, $mission, $target_file])) {
                $successMessage = "Kandidat berhasil ditambahkan!";
            } else {
                $errorMessage = "Gagal menambahkan kandidat.";
            }
        }
    }
}

// Proses pengeditan kandidat
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $kandidat = $stmt->fetch();
}

// Proses penghapusan kandidat
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: kandidat.php"); // Kembali ke halaman kandidat setelah berhasil
        exit();
    } else {
        $errorMessage = "Gagal menghapus kandidat.";
    }
}

// Mengambil data kandidat
$stmt_kandidat = $pdo->query("SELECT * FROM candidates");
$kandidatList = $stmt_kandidat->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kandidat</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #e9ecef; /* Warna latar belakang yang lebih cerah */
        }
        .header-title {
            text-align: center;
            margin: 20px 0;
            color: #343a40; /* Warna teks judul */
        }
        .card {
            margin-bottom: 20px;
            border: none; /* Menghilangkan border default */
            border-radius: 15px; /* Membuat sudut kartu lebih bulat */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Menambahkan bayangan */
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
            border-radius: 20px; /* Membuat tombol lebih bulat */
        }
        .btn-custom:hover {
            background-color: #0056b3;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Menambahkan bayangan saat hover */
        }
        .img-thumbnail {
            max-width: 100px;
            border-radius: 10px; /* Membuat sudut gambar lebih bulat */
        }
        .alert {
            border-radius: 10px; /* Membuat sudut alert lebih bulat */
        }
        .form-control, .form-control-file {
            border-radius: 10px; /* Membuat sudut input lebih bulat */
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="admin.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>
        <h3 class="header-title">Form Penambahan / Pembaruan Kandidat</h3>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo isset($kandidat) ? $kandidat['id'] : ''; ?>">
                    <div class="form-group">
                        <label for="name">Nama Kandidat</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($kandidat) ? $kandidat['name'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="vision">Visi</label>
                        <textarea class="form-control" id="vision" name="vision" required><?php echo isset($kandidat) ? $kandidat['vision'] : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="mission">Misi</label>
                        <textarea class="form-control" id="mission" name="mission" required><?php echo isset($kandidat) ? $kandidat['mission'] : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="photo">Foto</label>
                        <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                        <?php if (isset($kandidat) && $kandidat['photo']): ?>
                            <small class="form-text text-muted">Foto saat ini:</small>
                            <img src="<?php echo $kandidat['photo']; ?>" alt="Foto Kandidat" class="img-thumbnail">
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="<?php echo isset($kandidat) ? 'update_candidate' : 'add_candidate'; ?>" class="btn btn-custom">
                        <?php echo isset($kandidat) ? 'Perbarui Data' : 'Tambah Kandidat'; ?>
                    </button>
                    <?php if (isset($kandidat)): ?>
                        <a href="kandidat.php" class="btn btn-secondary">Tambah Kandidat Baru</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <h3 class="header-title">Daftar Kandidat</h3>
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-striped">
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
                                        <img src="<?php echo $kandidat['photo']; ?>" alt="Foto Kandidat" class="img-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit_id=<?php echo $kandidat['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="?delete_id=<?php echo $kandidat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus kandidat ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php include 'footer.php'; // Menyertakan footer.php ?>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

