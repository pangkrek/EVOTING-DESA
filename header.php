<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .header-title {
            text-align: center;
            margin: 20px 0;
        }
        .nav-link {
            font-size: 1.1rem;
            margin-left: 10px; /* Menambahkan jarak antar tombol */
            color: white; /* Warna teks putih */
        }
        .nav-link.kandidat {
            background-color: #007bff; /* Warna biru */
        }
        .nav-link.pemilih {
            background-color: #28a745; /* Warna hijau */
        }
        .nav-link.logout {
            background-color: #dc3545; /* Warna merah */
        }
        .nav-link:hover {
            opacity: 0.8; /* Efek hover */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand navbar-light bg-light">
        <a class="navbar-brand" href="admin.php">
            <i class="fas fa-user-shield"></i> Admin Dashboard
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link kandidat" href="kandidat.php"><i class="fas fa-users"></i> Kandidat</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link pemilih" href="voters.php"><i class="fas fa-user-friends"></i> Pemilih</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    
