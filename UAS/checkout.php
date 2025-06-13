<?php
session_start();

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "tiket_konser");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$tiket_dalam_keranjang = [];
$error_message = "";

// Cek apakah ini pembelian langsung dari tombol "Beli Sekarang"
if (isset($_POST['beli_sekarang']) && $_POST['beli_sekarang'] == '1') {
    // Validasi input untuk pembelian langsung
    if (!isset($_POST['id_tiket']) || !isset($_POST['jumlah'])) {
        $error_message = "Data tiket tidak lengkap.";
    } else {
        $id_tiket = intval($_POST['id_tiket']);
        $jumlah = intval($_POST['jumlah']);
        
        if ($id_tiket <= 0 || $jumlah <= 0) {
            $error_message = "ID tiket atau jumlah tidak valid.";
        } else {
            // Ambil data tiket untuk pembelian langsung
            $sql = "SELECT * FROM tiket WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("i", $id_tiket);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Cek stok tiket
                    if (isset($row['stok']) && $row['stok'] < $jumlah) {
                        $error_message = "Stok tiket tidak mencukupi. Stok tersisa: " . $row['stok'];
                    } else {
                        $row['jumlah'] = $jumlah;
                        $row['total'] = $row['harga'] * $row['jumlah'];
                        $tiket_dalam_keranjang[] = $row;
                    }
                } else {
                    $error_message = "Tiket tidak ditemukan.";
                }
                $stmt->close();
            } else {
                $error_message = "Error dalam query database.";
            }
        }
    }
    
    // Set flag untuk pembelian langsung
    $is_pembelian_langsung = true;
} else {
    // Pembelian dari keranjang (kode asli)
    $keranjang = isset($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];
    
    if (!empty($keranjang)) {
        // Sanitize dan validasi IDs dari keranjang
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
                        // Cek stok tiket jika ada kolom stok
                        if (isset($row['stok']) && $row['stok'] < $jumlah) {
                            $error_message .= "Stok tiket '{$row['nama_event']}' tidak mencukupi. ";
                            continue;
                        }
                        $row['jumlah'] = $jumlah;
                        $row['total'] = $row['harga'] * $row['jumlah'];
                        $tiket_dalam_keranjang[] = $row;
                    }
                }
                $stmt->close();
            } else {
                $error_message = "Error dalam query database.";
            }
        }
    }
    
    $is_pembelian_langsung = false;
}

$conn->close();

// Simpan data checkout ke session untuk digunakan di pembayaran.php
if (!empty($tiket_dalam_keranjang) && empty($error_message)) {
    $_SESSION['checkout_data'] = [
        'tiket' => $tiket_dalam_keranjang,
        'is_pembelian_langsung' => $is_pembelian_langsung,
        'timestamp' => time()
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout Tiket - BluuTIX</title>
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
      margin: 0;
      min-height: 100vh;
      line-height: 1.5;
    }
    .container { 
      max-width: 600px; 
      margin: auto; 
      background: #003366; 
      padding: 2rem; 
      border-radius: 10px; 
      box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }
    h2 { 
      color: #FFC107; 
      text-align: center;
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .checkout-type {
      background: #0047AB;
      padding: 0.8rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      text-align: center;
      color: #FFC107;
      font-weight: bold;
    }
    .error-message {
      background: #ff4444;
      color: white;
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;
      text-align: center;
      font-weight: bold;
    }
    .tiket-box { 
      background: #0047AB; 
      padding: 1.5rem; 
      border-radius: 8px; 
      margin-bottom: 1rem; 
      border: 2px solid #FFC107;
    }
    .tiket-box strong {
      color: #FFC107;
      font-size: 1.2rem;
      display: block;
      margin-bottom: 0.5rem;
    }
    .tiket-info {
      margin: 0.3rem 0;
      font-size: 0.95rem;
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
    .total-section {
      background: #0047AB;
      padding: 1.5rem;
      border-radius: 8px;
      margin: 1.5rem 0;
      text-align: center;
      border: 3px solid #FFC107;
    }
    .total-section h3 {
      color: #FFC107;
      font-size: 1.5rem;
      margin: 0;
    }
    .btn { 
      padding: 1rem 2rem; 
      background: #FFC107; 
      border: none; 
      text-align: center; 
      color: #002244; 
      font-weight: bold; 
      border-radius: 6px; 
      text-decoration: none; 
      display: inline-block; 
      font-size: 1.1rem;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      margin-bottom: 1rem;
    }
    .btn:hover { 
      background: #e6b800; 
      transform: translateY(-2px);
    }
    .back { 
      margin-top: 1rem; 
      display: block; 
      text-align: center; 
      color: #FFC107; 
      text-decoration: underline; 
      font-weight: 500;
      transition: color 0.3s ease;
    }
    .back:hover {
      color: white;
    }
    .empty-message {
      text-align: center;
      color: #FFC107;
      font-size: 1.2rem;
      margin: 2rem 0;
    }
    .action-buttons {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }
    .btn-secondary {
      background: #666;
      color: white;
      flex: 1;
    }
    .btn-secondary:hover {
      background: #555;
    }
    .btn-primary {
      flex: 2;
    }
    
    @media (max-width: 768px) {
      body {
        padding: 1rem;
      }
      .container {
        padding: 1.5rem;
      }
      h2 {
        font-size: 1.5rem;
      }
      .action-buttons {
        flex-direction: column;
      }
      .btn-secondary,
      .btn-primary {
        flex: none;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <h2>Checkout Tiket Kamu 🎟️</h2>

  <?php if (!empty($error_message)): ?>
    <div class="error-message">
      ⚠️ <?= htmlspecialchars($error_message) ?>
    </div>
  <?php endif; ?>

  <?php if ($is_pembelian_langsung): ?>
    <div class="checkout-type">
      ⚡ Pembelian Langsung
    </div>
  <?php else: ?>
    <div class="checkout-type">
      🛒 Dari Keranjang Belanja
    </div>
  <?php endif; ?>

  <?php if (!empty($tiket_dalam_keranjang) && empty($error_message)): ?>
    <?php $total_bayar = 0; ?>
    <?php foreach ($tiket_dalam_keranjang as $tiket): ?>
      <div class="tiket-box">
        <strong><?= htmlspecialchars($tiket['nama_event']) ?></strong>
        <div class="tiket-info">📍 Lokasi: <?= htmlspecialchars($tiket['lokasi']) ?></div>
        <div class="tiket-info">📅 Tanggal: <?= date('d M Y', strtotime($tiket['tanggal'])) ?></div>
        <div class="tiket-info">
          🎫 Kategori: <?= htmlspecialchars($tiket['kategori']) ?>
          <span class="kategori-badge <?= strtolower($tiket['kategori']) ?>"><?= htmlspecialchars($tiket['kategori']) ?></span>
        </div>
        <div class="tiket-info">🔢 Jumlah: <?= $tiket['jumlah'] ?> tiket</div>
        <div class="tiket-info">💰 Harga Satuan: Rp <?= number_format($tiket['harga'], 0, ',', '.') ?></div>
        <div class="tiket-info"><strong>💵 Subtotal: Rp <?= number_format($tiket['total'], 0, ',', '.') ?></strong></div>
      </div>
      <?php $total_bayar += $tiket['total']; ?>
    <?php endforeach; ?>

    <div class="total-section">
      <h3>Total Bayar: Rp <?= number_format($total_bayar, 0, ',', '.') ?></h3>
    </div>

    <!-- Form untuk lanjut ke pembayaran -->
    <form action="pembayaran.php" method="post">
      <input type="hidden" name="total_bayar" value="<?= $total_bayar ?>">
      <input type="hidden" name="from_checkout" value="1">
      <button type="submit" class="btn btn-primary">Lanjutkan Pembayaran 💳</button>
    </form>
    
    <?php if ($is_pembelian_langsung): ?>
      <a href="index.php" class="back">← Kembali ke Beranda</a>
    <?php else: ?>
      <a href="keranjang.php" class="back">← Kembali ke Keranjang</a>
    <?php endif; ?>

  <?php else: ?>
    <div class="empty-message">
      <p>🛒 Tidak ada tiket untuk di-checkout.</p>
      <?php if (!empty($error_message)): ?>
        <p style="font-size: 1rem; margin-top: 1rem;">Silakan coba lagi atau hubungi admin jika masalah berlanjut.</p>
      <?php endif; ?>
    </div>
    <?php if ($is_pembelian_langsung): ?>
      <a href="index.php" class="back">← Kembali ke Beranda</a>
    <?php else: ?>
      <a href="keranjang.php" class="back">← Kembali ke Keranjang</a>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>