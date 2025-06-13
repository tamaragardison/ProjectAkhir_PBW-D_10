<?php
session_start();

// Konfigurasi Database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tiket_konser";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// Cek apakah ada ID tiket yang dikirim
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_tiket = intval($_GET['id']);
    
    // Ambil data tiket dari database
    $sql = "SELECT * FROM tiket WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_tiket);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Inisialisasi keranjang jika belum ada
        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }
        
        // Cek apakah tiket sudah ada di keranjang
        if (isset($_SESSION['keranjang'][$id_tiket])) {
            // Jika sudah ada, tambah jumlahnya
            $_SESSION['keranjang'][$id_tiket]++;
            $message = "Jumlah tiket '{$row['nama_event']}' di keranjang berhasil ditambah!";
        } else {
            // Jika belum ada, tambahkan dengan jumlah 1
            $_SESSION['keranjang'][$id_tiket] = 1;
            $message = "Tiket '{$row['nama_event']}' berhasil ditambahkan ke keranjang!";
        }
        $message_type = "success";
    } else {
        $message = "Tiket tidak ditemukan!";
        $message_type = "error";
    }
    $stmt->close();
} else {
    $message = "ID tiket tidak valid!";
    $message_type = "error";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah ke Keranjang - BluuTIX</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background: #001f4d;
            color: white;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 500px;
            background: #003366;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            text-align: center;
        }
        .message {
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .message.success {
            background: #28a745;
            color: white;
            border: 2px solid #1e7e34;
        }
        .message.error {
            background: #dc3545;
            color: white;
            border: 2px solid #c82333;
        }
        .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .success .icon {
            color: #28a745;
        }
        .error .icon {
            color: #dc3545;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        .btn-primary {
            background: #FFC107;
            color: #002244;
        }
        .btn-primary:hover {
            background: #e6b800;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        h2 {
            color: #FFC107;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            .container {
                padding: 1.5rem;
            }
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>BluuTIX - Keranjang Belanja</h2>
        
        <div class="message <?= $message_type ?>">
            <div class="icon">
                <?= $message_type === 'success' ? '✅' : '❌' ?>
            </div>
            <?= htmlspecialchars($message) ?>
        </div>
        
        <div class="btn-group">
            <a href="keranjang.php" class="btn btn-primary">
                🛒 Lihat Keranjang
            </a>
            <a href="index.php" class="btn btn-secondary">
                ← Lanjut Belanja
            </a>
        </div>
    </div>
    
    <script>
        // Auto redirect setelah 3 detik jika berhasil
        <?php if ($message_type === 'success'): ?>
        setTimeout(() => {
            window.location.href = 'keranjang.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>