<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

$errorMessage = ""; // Untuk menyimpan pesan kesalahan

// Pastikan token CSRF diatur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Menghasilkan token CSRF
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $csrf_token = $_POST['csrf_token']; // Ambil token CSRF

    // Validasi token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $errorMessage = "Token CSRF tidak valid.";
    } else {
        // Validasi token
        $stmt = $pdo->prepare("SELECT * FROM voters WHERE token = :token AND used = 0");
        $stmt->execute(['token' => $token]);
        $voter = $stmt->fetch();

        if ($voter) {
            // Tandai token sebagai digunakan
            $update = $pdo->prepare("UPDATE voters SET used = 1 WHERE token = :token");
            $update->execute(['token' => $token]);

            // Simpan informasi pemilih di session
            $_SESSION['voter_id'] = $voter['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            // Jika token tidak valid, tampilkan pesan kesalahan
            $errorMessage = "Token tidak valid atau sudah digunakan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Login Pemilihan Ketua RT</title>
    <style>
        body {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .alert {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #6a11cb;
            border: none;
        }
        .btn-primary:hover {
            background-color: #2575fc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-header">Login Pemilihan Ketua RT</h2>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="token">Token:</label>
                <input type="text" id="token" name="token" class="form-control" required aria-label="Token">
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- Token CSRF -->
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>
</body>
</html>