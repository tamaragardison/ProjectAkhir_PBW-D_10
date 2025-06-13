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

$keranjang_items = [];
$total_harga = 0;
$message = "";

// Proses update jumlah atau hapus item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $id_tiket = intval($_POST['id_tiket']);
        
        switch ($action) {
            case 'update':
                $jumlah_baru = intval($_POST['jumlah']);
                if ($jumlah_baru > 0) {
                    $_SESSION['keranjang'][$id_tiket] = $jumlah_baru;
                    $message = "Jumlah tiket berhasil diupdate!";
                } else {
                    unset($_SESSION['keranjang'][$id_tiket]);
                    $message = "Tiket berhasil dihapus dari keranjang!";
                }
                break;
                
            case 'remove':
                unset($_SESSION['keranjang'][$id_tiket]);
                $message = "Tiket berhasil dihapus dari keranjang!";
                break;
                
            case 'clear':
                $_SESSION['keranjang'] = [];
                $message = "Keranjang berhasil dikosongkan!";
                break;
        }
    }
}

// Ambil data tiket dari keranjang
if (isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
    $keranjang = $_SESSION['keranjang'];
    
    // Sanitize IDs
    $validated_ids = [];
    foreach (array_keys($keranjang) as $id) {
        $id = intval($id);
        if ($id > 0) {
            $validated_ids[] = $id;
        }
    }
    
    if (!empty($validated_ids)) {
        $placeholders = str_repeat('?,', count($validated_ids) - 1) . '?';
        $sql = "SELECT * FROM tiket WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $types = str_repeat('i', count($validated_ids));
            $stmt->bind_param($types, ...$validated_ids);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $jumlah = intval($keranjang[$row['id']]);
                if ($jumlah > 0) {
                    $row['jumlah'] = $jumlah;
                    $row['subtotal'] = $row['harga'] * $jumlah;
                    $total_harga += $row['subtotal'];
                    $keranjang_items[] = $row;
                }
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - BluuTIX</title>
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
            line-height: 1.5;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #003366;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }
        h1 {
            color: #FFC107;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .message {
            background: #28a745;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: bold;
        }
        .cart-item {
            background: #0047AB;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 2px solid #FFC107;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .item-info {
            flex: 1;
            min-width: 200px;
        }
        .item-name {
            color: #FFC107;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .item-detail {
            font-size: 0.9rem;
            margin: 0.2rem 0;
        }
        .kategori-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 0.5rem;
        }
        .kategori-badge.vip {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            color: white;
        }
        .kategori-badge.reguler {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
        }
        .item-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 6px;
        }
        .quantity-btn {
            background: #FFC107;
            color: #002244;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .quantity-btn:hover {
            background: #e6b800;
            transform: scale(1.1);
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            background: transparent;
            border: 1px solid #FFC107;
            color: white;
            padding: 0.3rem;
            border-radius: 4px;
        }
        .item-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #FFC107;
            text-align: center;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .btn-remove:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .cart-summary {
            background: #0047AB;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: center;
            border: 3px solid #FFC107;
        }
        .total-price {
            color: #FFC107;
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
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
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .empty-cart {
            text-align: center;
            color: #FFC107;
            font-size: 1.3rem;
            margin: 3rem 0;
        }
        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            .container {
                padding: 1.5rem;
            }
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            .btn-group {
                flex-direction: column;
            }
            .btn {
                flex: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Keranjang Belanja</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message">
                ✅ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($keranjang_items)): ?>
            <?php foreach ($keranjang_items as $item): ?>
                <div class="cart-item">
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['nama_event']) ?></div>
                        <div class="item-detail">📍 <?= htmlspecialchars($item['lokasi']) ?></div>
                        <div class="item-detail">📅 <?= date('d M Y', strtotime($item['tanggal'])) ?></div>
                        <div class="item-detail">
                            🎫 Kategori: <?= htmlspecialchars($item['kategori']) ?>
                            <span class="kategori-badge <?= strtolower($item['kategori']) ?>"><?= htmlspecialchars($item['kategori']) ?></span>
                        </div>
                        <div class="item-detail">💰 Harga: Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                    </div>
                    
                    <div class="item-controls">
                        <form method="POST" style="display: inline;">
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?= $item['id'] ?>, -1)">-</button>
                                <input type="number" name="jumlah" value="<?= $item['jumlah'] ?>" min="1" max="10" class="quantity-input" id="qty-<?= $item['id'] ?>">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?= $item['id'] ?>, 1)">+</button>
                            </div>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_tiket" value="<?= $item['id'] ?>">
                        </form>
                        
                        <div class="item-price">
                            Subtotal:<br>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                        </div>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id_tiket" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn-remove" onclick="return confirm('Yakin ingin menghapus tiket ini?')">
                                🗑️ Hapus
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="cart-summary">
                <h3 class="total-price">Total: Rp <?= number_format($total_harga, 0, ',', '.') ?></h3>
            </div>
            
            <div class="btn-group">
                <form method="POST" action="checkout.php" style="flex: 2;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        💳 Checkout Sekarang
                    </button>
                </form>
                
                <a href="index.php" class="btn btn-secondary">
                    ← Lanjut Belanja
                </a>
                
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-danger" style="width: 100%;" onclick="return confirm('Yakin ingin mengosongkan keranjang?')">
                        🗑️ Kosongkan
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Keranjang belanja Anda kosong</p>
                <p style="font-size: 1rem; margin-top: 1rem; color: #ccc;">
                    Silakan pilih tiket yang ingin Anda beli di halaman utama
                </p>
            </div>
            
            <div class="btn-group">
                <a href="index.php" class="btn btn-primary">
                    🎫 Pilih Tiket
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function changeQuantity(ticketId, change) {
            const input = document.getElementById('qty-' + ticketId);
            let currentValue = parseInt(input.value);
            let newValue = currentValue + change;
            
            if (newValue >= 1 && newValue <= 10) {
                input.value = newValue;
                // Auto submit form
                input.closest('form').submit();
            }
        }
        
        // Auto submit on direct input change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value >= 1 && this.value <= 10) {
                    this.closest('form').submit();
                }
            });
        });
    </script>
</body>
</html>