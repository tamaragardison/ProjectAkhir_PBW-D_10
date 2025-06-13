<?php
session_start();

// Cek apakah ada data checkout di session
if (!isset($_SESSION['checkout_data'])) {
    // Jika tidak ada data checkout, redirect ke keranjang
    header("Location: keranjang.php?error=no_checkout_data");
    exit;
}

$checkout_data = $_SESSION['checkout_data'];
$tiket_list = $checkout_data['tiket'];
$is_pembelian_langsung = $checkout_data['is_pembelian_langsung'];

// Validasi apakah session checkout masih valid (tidak lebih dari 30 menit)
if (time() - $checkout_data['timestamp'] > 1800) {
    unset($_SESSION['checkout_data']);
    header("Location: keranjang.php?error=session_expired");
    exit;
}

// Hitung total bayar dari data tiket
$total_bayar = 0;
foreach ($tiket_list as $tiket) {
    $total_bayar += $tiket['total'];
}

// Validasi total bayar dari POST jika ada
if (isset($_POST['total_bayar'])) {
    $posted_total = intval($_POST['total_bayar']);
    if ($posted_total !== $total_bayar) {
        // Total tidak cocok, mungkin ada manipulasi
        unset($_SESSION['checkout_data']);
        header("Location: keranjang.php?error=invalid_total");
        exit;
    }
}

// Buat kode pembayaran unik
$kode_pembayaran = 'PAY-' . strtoupper(uniqid());

// Simulasi email admin tujuan
$email_admin = 'admin@bluutix.com';

$nama = '';
$email = '';
$metode = '';
$pesan_sukses = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = htmlspecialchars(trim($_POST['nama'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $metode = htmlspecialchars(trim($_POST['metode'] ?? ''));

    // Validasi input
    if (empty($nama) || empty($email) || empty($metode)) {
        $error_message = "Semua data wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid!";
    } else {
        // Proses pembayaran berhasil
        $pesan_sukses = "Terima kasih, $nama! Pembayaran kamu dengan kode <strong>$kode_pembayaran</strong> akan segera diproses.";
        
        // TODO: Di sini bisa ditambahkan:
        // 1. Simpan data pembayaran ke database
        // 2. Kirim email konfirmasi
        // 3. Update stok tiket
        
        // Simpan data pembayaran ke database (contoh)
        /*
        $conn = new mysqli("localhost", "root", "", "tiket_konser");
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO pembayaran (kode_pembayaran, nama, email, metode, total, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("ssssi", $kode_pembayaran, $nama, $email, $metode, $total_bayar);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
        */
        
        // Reset keranjang dan data checkout
        unset($_SESSION['keranjang']);
        unset($_SESSION['checkout_data']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran - BluuTIX</title>
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
      max-width: 600px; 
      margin: auto; 
      background: #003366; 
      padding: 2rem; 
      border-radius: 10px; 
      box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }
    h2 { 
      color: #FFC107; 
      margin-bottom: 1.5rem; 
      text-align: center;
      font-size: 1.8rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .checkout-summary {
      background: #0047AB;
      padding: 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      border: 2px solid #FFC107;
    }
    .checkout-summary h3 {
      color: #FFC107;
      margin-bottom: 1rem;
      font-size: 1.2rem;
    }
    .tiket-item {
      background: rgba(255, 255, 255, 0.1);
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 0.5rem;
      border-left: 4px solid #FFC107;
    }
    .tiket-item strong {
      color: #FFC107;
      display: block;
      margin-bottom: 0.3rem;
    }
    .tiket-detail {
      font-size: 0.9rem;
      margin: 0.2rem 0;
      color: #e0e0e0;
    }
    .total-bayar {
      text-align: center;
      font-size: 1.5rem;
      color: #FFC107;
      font-weight: bold;
      margin: 1rem 0;
      padding: 1rem;
      background: rgba(255, 193, 7, 0.1);
      border-radius: 6px;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #FFC107;
      font-weight: 500;
    }
    input, select { 
      width: 100%; 
      padding: 0.8rem; 
      border-radius: 5px; 
      border: none; 
      font-size: 1rem;
      background: #f8f9fa;
      color: #333;
    }
    input:focus, select:focus {
      outline: 2px solid #FFC107;
      box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.2);
    }
    .btn { 
      padding: 1rem 2rem; 
      background: #FFC107; 
      border: none; 
      color: #002244; 
      font-weight: bold; 
      border-radius: 6px; 
      cursor: pointer; 
      font-size: 1.1rem;
      text-transform: uppercase;
      width: 100%;
      transition: all 0.3s ease;
    }
    .btn:hover { 
      background: #e6b800; 
      transform: translateY(-2px);
    }
    .btn-back { 
      display: inline-block; 
      margin-bottom: 1.5rem; 
      text-decoration: none; 
      background: #666; 
      color: white; 
      padding: 0.8rem 1.5rem; 
      border-radius: 5px; 
      font-weight: bold; 
      transition: background 0.3s ease;
    }
    .btn-back:hover { 
      background: #555; 
    }
    .success { 
      background: #28a745; 
      padding: 1.5rem; 
      border-radius: 8px; 
      color: white; 
      margin-bottom: 1.5rem; 
      text-align: center;
      font-size: 1.1rem;
    }
    .error { 
      background: #dc3545; 
      padding: 1.5rem; 
      border-radius: 8px; 
      color: white; 
      margin-bottom: 1.5rem; 
      text-align: center;
      font-weight: bold;
    }
    .payment-methods {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .payment-method {
      background: rgba(255, 255, 255, 0.1);
      padding: 1rem;
      border-radius: 6px;
      text-align: center;
      border: 2px solid transparent;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .payment-method:hover {
      border-color: #FFC107;
      background: rgba(255, 193, 7, 0.1);
    }
    .payment-method.selected {
      border-color: #FFC107;
      background: rgba(255, 193, 7, 0.2);
    }
    .success-actions {
      text-align: center;
      margin-top: 2rem;
    }
    .success-actions a {
      display: inline-block;
      margin: 0.5rem;
      padding: 0.8rem 1.5rem;
      background: #FFC107;
      color: #002244;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
      transition: background 0.3s ease;
    }
    .success-actions a:hover {
      background: #e6b800;
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
      .payment-methods {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <h2>💳 Konfirmasi Pembayaran</h2>

  <?php if (!empty($pesan_sukses)): ?>
    <div class="success">
      ✅ <?= $pesan_sukses ?>
      <div style="margin-top: 1rem; font-size: 0.9rem;">
        Detailnya telah dikirim ke email admin (<strong><?= $email_admin ?></strong>)
      </div>
    </div>
    <div class="success-actions">
      <a href="index.php">🏠 Kembali ke Beranda</a>
      <a href="keranjang.php">🛒 Belanja Lagi</a>
    </div>
  <?php else: ?>
    
    <?php if (!empty($error_message)): ?>
      <div class="error">⚠️ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <a href="checkout.php" class="btn-back">← Kembali ke Checkout</a>

    <!-- Ringkasan Pembelian -->
    <div class="checkout-summary">
      <h3>📋 Ringkasan Pembelian</h3>
      <?php foreach ($tiket_list as $tiket): ?>
        <div class="tiket-item">
          <strong><?= htmlspecialchars($tiket['nama_event']) ?></strong>
          <div class="tiket-detail">📍 <?= htmlspecialchars($tiket['lokasi']) ?></div>
          <div class="tiket-detail">📅 <?= date('d M Y', strtotime($tiket['tanggal'])) ?></div>
          <div class="tiket-detail">🎫 <?= htmlspecialchars($tiket['kategori']) ?> • <?= $tiket['jumlah'] ?> tiket</div>
          <div class="tiket-detail">💰 Rp <?= number_format($tiket['harga'], 0, ',', '.') ?> x <?= $tiket['jumlah'] ?> = <strong>Rp <?= number_format($tiket['total'], 0, ',', '.') ?></strong></div>
        </div>
      <?php endforeach; ?>
      
      <div class="total-bayar">
        Total Pembayaran: Rp <?= number_format($total_bayar, 0, ',', '.') ?>
      </div>
    </div>

    <form method="POST" action="">
      <div class="form-group">
        <label for="nama">👤 Nama Lengkap</label>
        <input type="text" name="nama" id="nama" required value="<?= htmlspecialchars($nama) ?>" placeholder="Masukkan nama lengkap">
      </div>

      <div class="form-group">
        <label for="email">📧 Email Kamu</label>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($email) ?>" placeholder="example@email.com">
      </div>

      <div class="form-group">
        <label for="metode">💳 Metode Pembayaran</label>
        <select name="metode" id="metode" required>
          <option value="">-- Pilih Metode Pembayaran --</option>
          <option value="Transfer Bank" <?= $metode == 'Transfer Bank' ? 'selected' : '' ?>>🏦 Transfer Bank</option>
          <option value="QRIS" <?= $metode == 'QRIS' ? 'selected' : '' ?>>📱 QRIS</option>
          <option value="E-Wallet" <?= $metode == 'E-Wallet' ? 'selected' : '' ?>>💰 E-Wallet (OVO, DANA, dll)</option>
          <option value="Kartu Kredit" <?= $metode == 'Kartu Kredit' ? 'selected' : '' ?>>💳 Kartu Kredit</option>
        </select>
      </div>

      <button type="submit" class="btn">🚀 Kirim Konfirmasi Pembayaran</button>
    </form>
  <?php endif; ?>
</div>

<script>
// Auto-fill form jika ada data di localStorage (opsional)
document.addEventListener('DOMContentLoaded', function() {
    const namaInput = document.getElementById('nama');
    const emailInput = document.getElementById('email');
    
    // Simpan data ke localStorage saat user mengetik
    namaInput.addEventListener('input', function() {
        localStorage.setItem('bluutix_nama', this.value);
    });
    
    emailInput.addEventListener('input', function() {
        localStorage.setItem('bluutix_email', this.value);
    });
    
    // Load data dari localStorage jika form kosong
    if (!namaInput.value) {
        namaInput.value = localStorage.getItem('bluutix_nama') || '';
    }
    if (!emailInput.value) {
        emailInput.value = localStorage.getItem('bluutix_email') || '';
    }
});
</script>
</body>
</html>