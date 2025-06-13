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

// Ambil data tiket terbaru (group by nama_event untuk mendapatkan event unik)
$sql_terbaru = "SELECT DISTINCT nama_event, lokasi, tanggal, gambar FROM tiket ORDER BY id DESC LIMIT 6";
$result_terbaru = $conn->query($sql_terbaru);

$events_terbaru = [];
if ($result_terbaru && $result_terbaru->num_rows > 0) {
    while ($row = $result_terbaru->fetch_assoc()) {
        // Ambil harga untuk Regular dan VIP dari event yang sama
        $event_name = $row['nama_event'];
        $sql_prices = "SELECT kategori, harga, id FROM tiket WHERE nama_event = '$event_name' ORDER BY kategori";
        $price_result = $conn->query($sql_prices);
        
        $prices = [];
        while ($price_row = $price_result->fetch_assoc()) {
            $prices[$price_row['kategori']] = [
                'harga' => $price_row['harga'],
                'id' => $price_row['id']
            ];
        }
        
        $row['prices'] = $prices;
        $events_terbaru[] = $row;
    }
}

// Ambil data tiket terlaris (simulasi random)
$sql_terlaris = "SELECT DISTINCT nama_event, lokasi, tanggal, gambar FROM tiket ORDER BY RAND() LIMIT 8";
$result_terlaris = $conn->query($sql_terlaris);

$events_terlaris = [];
if ($result_terlaris && $result_terlaris->num_rows > 0) {
    while ($row = $result_terlaris->fetch_assoc()) {
        // Ambil harga untuk Regular dan VIP dari event yang sama
        $event_name = $row['nama_event'];
        $sql_prices = "SELECT kategori, harga, id FROM tiket WHERE nama_event = '$event_name' ORDER BY kategori";
        $price_result = $conn->query($sql_prices);
        
        $prices = [];
        while ($price_row = $price_result->fetch_assoc()) {
            $prices[$price_row['kategori']] = [
                'harga' => $price_row['harga'],
                'id' => $price_row['id']
            ];
        }
        
        $row['prices'] = $prices;
        $events_terlaris[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BluuTIX - Penjualan Tiket Konser</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
  <style>
    * {
      margin: 0; padding: 0; box-sizing: border-box;
    }
    body {
      font-family: 'Roboto', sans-serif;
      background: #001f4d;
      color: #fff;
      animation: fadeInBody 0.8s ease;
      min-height: 100vh;
      line-height: 1.5;
    }
    @keyframes fadeInBody {
      from { opacity: 0; transform: translateY(10px);}
      to { opacity: 1; transform: translateY(0);}
    }
    header {
      background: #0047AB;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 2px 5px rgba(0,0,0,0.5);
    }
    .logo {
      font-size: 1.7rem;
      font-weight: bold;
      color: #FFC107;
      letter-spacing: 2px;
    }
    nav a {
      margin-left: 2rem;
      text-decoration: none;
      color: #FFC107;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      transition: color 0.3s ease;
    }
    nav a:hover {
      color: #ffffff;
    }
    .promo-banner {
      background: #003366;
      padding: 3rem 5%;
      text-align: center;
      color: #FFC107;
      font-size: 2rem;
      letter-spacing: 2px;
      user-select: none;
    }
    .section-title {
      text-align: center;
      font-weight: 700;
      padding: 2rem 1rem 1rem;
      border-bottom: 3px solid #FFC107;
      max-width: 800px;
      margin: 0 auto 1rem;
      font-size: 2.2rem;
      color: #FFC107;
      letter-spacing: 2px;
    }
    h2.sub-title {
      font-size: 1.6rem;
      margin: 2rem 1rem 1rem 1rem;
      color: #FFC107;
      text-transform: uppercase;
      text-align: center;
      letter-spacing: 1.4px;
    }
    .products {
      display: flex;
      overflow-x: auto;
      gap: 1rem;
      padding: 1rem 5%;
      scroll-snap-type: x mandatory;
    }
    .product {
      min-width: 300px;
      flex: 0 0 auto;
      background: #0047AB;
      border-radius: 10px;
      padding: 1rem;
      box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
      scroll-snap-align: start;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .product:hover {
      transform: translateY(-10px);
      box-shadow: 0 8px 20px #FFC107cc;
    }
    .product img {
      width: 100%;
      height: 350px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 0.8rem;
      border: 2px solid #FFC107;
    }
    .product-name {
      font-size: 1.2rem;
      margin-bottom: 0.3rem;
      text-transform: uppercase;
      color: #FFC107;
      font-weight: 700;
    }
    .product-info {
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
    }
    
    /* Styling untuk bagian harga dan kategori */
    .price-section {
      margin: 1rem 0;
      padding: 0.8rem;
      background: rgba(255, 193, 7, 0.1);
      border-radius: 8px;
      border: 1px solid rgba(255, 193, 7, 0.3);
    }
    
    .price-options {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .price-option {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    
    .price-option:hover {
      background: rgba(255, 193, 7, 0.1);
      border-color: rgba(255, 193, 7, 0.5);
    }
    
    .price-option.selected {
      background: rgba(255, 193, 7, 0.2);
      border-color: #FFC107;
    }
    
    .ticket-type {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .ticket-badge {
      padding: 0.2rem 0.6rem;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .ticket-badge.vip {
      background: linear-gradient(45deg, #ff6b6b, #ff8e53);
      color: white;
    }
    
    .ticket-badge.reguler {
      background: linear-gradient(45deg, #4ecdc4, #44a08d);
      color: white;
    }
    
    .ticket-price {
      font-weight: 700;
      color: #FFC107;
      font-size: 1rem;
    }
    
    .current-price {
      font-size: 1.3rem;
      font-weight: 700;
      color: #FFC107;
      text-align: center;
      margin: 0.8rem 0;
    }
    
    .btn-beli {
      background: #FFC107;
      color: #0047AB;
      border: none;
      padding: 0.8rem 1rem;
      font-weight: 700;
      text-transform: uppercase;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      opacity: 0.7;
    }
    
    .btn-beli:hover {
      background: #e6b800;
      color: #002d66;
    }
    
    .btn-beli.active {
      opacity: 1;
    }
    
    .btn-beli:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .products-vertical {
      max-width: 1200px;
      margin: 2rem auto 4rem;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      padding: 0 1rem;
    }
 .about-section {
  max-width: 100%;
  background: linear-gradient(135deg, #002244, #001f3f);
  color: #ffc107;
  text-align: center;
  padding: 4rem 1.5rem;
  margin-top: 3rem;
  border-top: 3px solid #FFC107;
  border-bottom: 3px solid #FFC107;
  box-shadow: inset 0 0 20px #00000066;
}

.about-section h2 {
  font-size: 2.5rem;
  margin-bottom: 1.5rem;
  color: #FFC107;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  position: relative;
}


.about-section p {
  font-size: 1.15rem;
  color: #e0e0e0;
  max-width: 700px;
  margin: 0 auto;
  line-height: 1.8;
  letter-spacing: 0.5px;
}

    .products::-webkit-scrollbar {
      height: 8px;
    }
    .products::-webkit-scrollbar-thumb {
      background: #FFC107aa;
      border-radius: 4px;
    }
    
    .button-group {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-top: 1rem;
    }
    
    .selection-info {
      text-align: center;
      font-size: 0.8rem;
      color: #ccc;
      margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
      .products {
        padding: 1rem;
      }
      .product {
        min-width: 280px;
      }
      .product img {
        height: 150px;
      }
      .price-option {
        font-size: 0.9rem;
      }
    }
    footer {
  background: #001a33;
  color: #ccc;
  text-align: center;
  padding: 1.5rem 1rem;
  font-size: 0.95rem;
  border-top: 2px solid #0047AB;
  margin-top: 3rem;
}

footer a {
  color: #FFC107;
  text-decoration: none;
  font-weight: 500;
}

footer a:hover {
  color: #ffffff;
  text-decoration: underline;
}
  </style>
</head>
<body>

<header>
  <div class="logo">BluuTIX</div>
  <nav>
    <a href="#home">Home</a>
    <a href="#tentang">Tentang</a>
    <a href="keranjang.php">Keranjang 🛒</a>
  </nav>
</header>

<div class="promo-banner">
  JUAL KONSER TERBAIK & TERPERCAYA
</div>

<section id="home">
  <h2 class="section-title">TIKET KONSER TERPOPULER</h2>

  <h2 class="sub-title">Tiket Terbaru</h2>
  <div class="products">
    <?php if (!empty($events_terbaru)): ?>
        <?php foreach ($events_terbaru as $event): ?>
          <div class="product fade-in">
            <img src="img/<?= htmlspecialchars($event['gambar']) ?>" alt="<?= htmlspecialchars($event['nama_event']) ?>" />
            <div class="product-name"><?= htmlspecialchars($event['nama_event']) ?></div>
            <div class="product-info">📍 <?= htmlspecialchars($event['lokasi']) ?></div>
            <div class="product-info">📅 <?= date('d M Y', strtotime($event['tanggal'])) ?></div>
            
            <div class="price-section">
              <div class="selection-info">Pilih Kategori Tiket:</div>
              <div class="price-options" data-event="<?= htmlspecialchars($event['nama_event']) ?>">
                <?php foreach ($event['prices'] as $kategori => $data): ?>
                  <div class="price-option" data-price="<?= $data['harga'] ?>" data-id="<?= $data['id'] ?>" data-kategori="<?= $kategori ?>">
                    <div class="ticket-type">
                      <span class="ticket-badge <?= strtolower($kategori) ?>"><?= $kategori ?></span>
                    </div>
                    <div class="ticket-price">Rp <?= number_format($data['harga'], 0, ',', '.') ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            
            <div class="current-price" id="current-price-<?= htmlspecialchars($event['nama_event']) ?>">
              Pilih kategori tiket
            </div>
            
            <div class="button-group">
              <button class="btn-beli keranjang-btn" data-event="<?= htmlspecialchars($event['nama_event']) ?>" disabled>
                + Keranjang
              </button>
              <button class="btn-beli beli-sekarang-btn" data-event="<?= htmlspecialchars($event['nama_event']) ?>" disabled>
                Beli Sekarang
              </button>
            </div>
          </div>
        <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center; color:#FFC107; width: 100%;">Belum ada tiket terbaru yang tersedia.</p>
    <?php endif; ?>
  </div>

  <h2 class="sub-title">Tiket Tersedia</h2>
  <div class="products-vertical">
    <?php if (!empty($events_terlaris)): ?>
        <?php foreach ($events_terlaris as $event): ?>
          <div class="product fade-in">
            <img src="img/<?= htmlspecialchars($event['gambar']) ?>" alt="<?= htmlspecialchars($event['nama_event']) ?>" />
            <div class="product-name"><?= htmlspecialchars($event['nama_event']) ?></div>
            <div class="product-info">📍 <?= htmlspecialchars($event['lokasi']) ?></div>
            <div class="product-info">📅 <?= date('d M Y', strtotime($event['tanggal'])) ?></div>
            
            <div class="price-section">
              <div class="selection-info">Pilih Kategori Tiket:</div>
              <div class="price-options" data-event="<?= htmlspecialchars($event['nama_event']) ?>">
                <?php foreach ($event['prices'] as $kategori => $data): ?>
                  <div class="price-option" data-price="<?= $data['harga'] ?>" data-id="<?= $data['id'] ?>" data-kategori="<?= $kategori ?>">
                    <div class="ticket-type">
                      <span class="ticket-badge <?= strtolower($kategori) ?>"><?= $kategori ?></span>
                    </div>
                    <div class="ticket-price">Rp <?= number_format($data['harga'], 0, ',', '.') ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            
            <div class="current-price" id="current-price-<?= htmlspecialchars($event['nama_event']) ?>">
              Pilih kategori tiket
            </div>
            
            <div class="button-group">
              <button class="btn-beli keranjang-btn" data-event="<?= htmlspecialchars($event['nama_event']) ?>" disabled>
                + Keranjang
              </button>
              <button class="btn-beli beli-sekarang-btn" data-event="<?= htmlspecialchars($event['nama_event']) ?>" disabled>
                Beli Sekarang
              </button>
            </div>
          </div>
        <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center; color:#FFC107; grid-column: 1 / -1;">Belum ada tiket yang tersedia.</p>
    <?php endif; ?>
  </div>
</section>

<section id="tentang" class="about-section">
  <h2>Tentang BluuTIX</h2>
  <p>
    BluuTIX adalah platform penjualan tiket konser terpercaya dengan pilihan event terkini dan terbaik.
    Kami menyediakan tiket dengan harga kompetitif dan proses pembelian mudah, aman, serta cepat.
    Nikmati kemudahan akses ke berbagai konser favorit Anda bersama BluuTIX.
  </p>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Fade in animation
  const fadeElems = document.querySelectorAll('.fade-in');
  function checkFade() {
    fadeElems.forEach(elem => {
      const rect = elem.getBoundingClientRect();
      if (rect.top < window.innerHeight - 100) {
        elem.classList.add('show');
      }
    });
  }
  window.addEventListener('scroll', checkFade);
  checkFade();

  // Ticket selection functionality
  const priceOptions = document.querySelectorAll('.price-option');
  const selectedTickets = {}; // Store selected tickets for each event

  priceOptions.forEach(option => {
    option.addEventListener('click', function() {
      const eventName = this.closest('.price-options').dataset.event;
      const price = this.dataset.price;
      const ticketId = this.dataset.id;
      const kategori = this.dataset.kategori;
      
      // Remove selected class from other options in the same event
      const eventOptions = this.closest('.price-options').querySelectorAll('.price-option');
      eventOptions.forEach(opt => opt.classList.remove('selected'));
      
      // Add selected class to clicked option
      this.classList.add('selected');
      
      // Update current price display
      const currentPriceElement = document.getElementById('current-price-' + eventName);
      currentPriceElement.textContent = `${kategori} - Rp ${parseInt(price).toLocaleString('id-ID')}`;
      
      // Store selected ticket info
      selectedTickets[eventName] = {
        id: ticketId,
        price: price,
        kategori: kategori
      };
      
      // Enable buttons for this event
      const product = this.closest('.product');
      const buttons = product.querySelectorAll('.btn-beli');
      buttons.forEach(btn => {
        btn.disabled = false;
        btn.classList.add('active');
      });
    });
  });

  // Handle add to cart button
  document.querySelectorAll('.keranjang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const eventName = this.dataset.event;
      const selectedTicket = selectedTickets[eventName];
      
      if (selectedTicket) {
        // Redirect to add to cart with ticket ID
        window.location.href = `tambah-keranjang.php?id=${selectedTicket.id}`;
      }
    });
  });

  // Handle buy now button
  document.querySelectorAll('.beli-sekarang-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const eventName = this.dataset.event;
      const selectedTicket = selectedTickets[eventName];
      
      if (selectedTicket) {
        // Create form and submit for immediate checkout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'checkout.php';
        
        const inputs = [
          { name: 'beli_sekarang', value: '1' },
          { name: 'id_tiket', value: selectedTicket.id },
          { name: 'jumlah', value: '1' }
        ];
        
        inputs.forEach(input => {
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = input.name;
          hiddenInput.value = input.value;
          form.appendChild(hiddenInput);
        });
        
        document.body.appendChild(form);
        form.submit();
      }
    });
  });
});
</script>

</body>
<footer>
  &copy; <?= date("Y") ?> BluuTIX. All Rights Reserved. | <a href="#home">Kembali ke Atas</a>
</footer>

</html>