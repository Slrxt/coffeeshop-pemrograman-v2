<?php
session_start();
// Pastikan config.php sudah ada di assets/php/
include "config.php"; 

// Tentukan status login
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = $isLoggedIn ? htmlspecialchars($_SESSION['nama']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title>
  <link rel="stylesheet" href="../css/styles.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""/>
   <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>
   <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
  <style>
    /* CSS KUSTOM UNTUK HALAMAN CHECKOUT */
    .checkout-wrapper {
        display: flex; gap: 2rem; padding: 4rem 1rem; max-width: 1200px; margin: 0 auto;
        min-height: calc(100vh - 150px);
    }
    .checkout__form-section { flex: 1; min-width: 350px; }
    .checkout__summary-section { flex: 1; min-width: 350px; }
    
    .checkout__box {
        background-color: var(--body-white-color); border-radius: 12px;
        padding: 2rem; box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    .checkout__title { font-size: var(--h2-font-size); margin-bottom: 1.5rem; color: var(--title-color); }
    
    /* Form */
    .checkout__form .form-input {
        width: 100%; padding: 0.75rem; margin-bottom: 1rem;
        border-radius: 8px; border: 1px solid #ddd;
        resize: vertical;
    }
    .checkout__form .button { width: 100%; border-radius: 8px; }
    
    /* Daftar Item Keranjang */
    .checkout__product-list { margin-bottom: 1.5rem; }
    .checkout__product-item {
        display: flex; align-items: center; gap: 1rem;
        padding: 1rem 0; border-bottom: 1px dashed #eee;
    }
    .checkout__product-item:last-child { border-bottom: none; }
    .checkout__product-img { width: 80px; height: 80px; object-fit: contain; padding: 4px; }
    .checkout__product-details { flex-grow: 1; text-align: left; }
    .checkout__product-name { font-size: var(--small-font-size); font-weight: var(--font-semi-bold); color: var(--title-color); margin-bottom: 0.25rem; }
    .checkout__product-info { font-size: var(--smaller-font-size); color: var(--text-color); }

    /* Total Harga */
    .checkout__total-info { margin-top: 1.5rem; border-top: 1px solid #ddd; padding-top: 1rem; }
    .checkout__total-info p { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
    .checkout__total-info strong { font-size: var(--h3-font-size); }

    /* Responsif */
    @media screen and (max-width: 768px) {
        .checkout-wrapper { flex-direction: column; padding: 2rem 1rem; }
    }
  </style>
  <style>
        /* CSS KUSTOM UNTUK MODAL */
        .custom-modal {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }

        .custom-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: var(--body-white-color);
            padding: 3rem 2rem;
            border-radius: 12px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .custom-modal.active .modal-content {
            transform: scale(1);
        }

        .modal-icon {
            font-size: 3rem;
            color: var(--first-color); /* Warna hijau/utama Anda */
            display: block;
            margin-bottom: 1rem;
        }

        .modal-content h2 {
            font-size: var(--h2-font-size);
            color: var(--title-color);
            margin-bottom: 0.5rem;
        }

        .modal-info-transfer {
            font-size: var(--small-font-size);
            color: var(--text-color);
            margin: 1rem 0 2rem;
            padding: 0.5rem;
            border: 1px dashed var(--first-color-alt);
            border-radius: 6px;
        }

        #modalCloseBtn {
            width: 100%;
        }
    </style>
</head>
<body>
  <header class="header" id="header">
    <nav class="nav container">
        <a href="../../index.php" class="nav__logo">STARCOFFEE</a>
        <div class="nav__menu" id="nav-menu">
            <ul class="nav__list">
                <li><a href="../../index.php" class="nav__link">Beranda</a></li>
                <?php if(!$isLoggedIn): ?>
                    <li><a href="login.php" class="nav__link">Login</a></li>
                <?php else: ?>
                    <li><a href="logout.php" class="nav__link">Logout</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav__close" id="nav-close"><i class="ri-close-line"></i></div>
        </div>
        <div class="nav__toggle" id="nav-toggle"><i class="ri-menu-line"></i></div>
    </nav>
  </header>

  <div class="checkout-wrapper">
     <div class="checkout__form-section">
        <div class="checkout__box">
            <h2 class="checkout__title">Informasi Pengiriman</h2>
            <form id="checkoutForm" class="checkout__form">
                  <p style="margin-bottom: 0.5rem; font-weight: var(--font-semi-bold);">Nama Penerima</p>
                  
                  <input 
                    type="text" 
                    name="nama_penerima" 
                    class="form-input" 
                    placeholder="Nama Penerima (Wajib)" 
                    value="<?php echo $userName; ?>" 
                    required
                >
                <p style="margin-bottom: 0.5rem; font-weight: var(--font-semi-bold);">Pilih Lokasi di Peta:</p>
                <div id="map" style="height: 300px; margin-bottom: 1rem; border-radius: 8px; border: 1px solid #ddd;"></div>
                
                <input type="hidden" name="alamat_lengkap" id="alamat_lengkap" required>
                
                <textarea 
                name="catatan" 
                    class="form-input" 
                    id="alamat_display"
                    placeholder="Alamat Lengkap (Otomatis terisi dari peta dan bisa ditambahkan catatan)" 
                    rows="4" 
                    required
                ></textarea>
                  <h2 class="checkout__title" style="margin-top: 2rem;">2. Metode Pembayaran</h2>
            
            <?php if (!$isLoggedIn): ?>
                <p style="font-size: var(--small-font-size); margin-bottom: 1rem;">
                    <a href="login.php" style="color: var(--first-color); font-weight: var(--font-semi-bold);">Login</a> untuk bayar dengan Transfer. Pembayaran saat ini terbatas pada **COD** saja.
                </p>
            <?php endif; ?>

            <div class="checkout__payment-options" style="display: flex; gap: 2rem; margin-bottom: 1.5rem;">
                
                <?php 
                // Tentukan status checked untuk COD.
                $cod_checked = $isLoggedIn ? '' : 'checked';
                
                if ($isLoggedIn): 
                ?>
                    <label style="display: flex; align-items: center; cursor: pointer; font-size: 1rem;">
                        <input type="radio" name="metode_pembayaran" value="transfer" required checked style="margin-right: 0.5rem;">
                        Transfer Bank
                    </label>
                <?php endif; ?>
                
                <label style="display: flex; align-items: center; cursor: pointer; font-size: 1rem;">
                    <input 
                        type="radio" 
                        name="metode_pembayaran" 
                        value="cod" 
                        required 
                        <?php echo $cod_checked; ?> 
                        style="margin-right: 0.5rem;"
                    >
                    COD (Cash On Delivery)
                </label>
            </div>
                <button type="submit" class="button">Proses Pesanan</button>
            </form>
        </div>
    </div>

    <div class="checkout__summary-section">
        <div class="checkout__box">
            <h2 class="checkout__title">Ringkasan Pesanan</h2>
            
            <div id="checkoutList" class="checkout__product-list">
                <p style="text-align: center; color: var(--text-color);">Memuat Keranjang...</p>
            </div>
            
            <div id="checkoutTotal" class="checkout__total-info">
                </div>
            
        </div>
    </div>
  </div>

<div id="successModal" class="custom-modal">
    <div class="modal-content">
        <i class="ri-check-circle-line modal-icon"></i> 
        <h2 id="modalTitle">Pesanan Berhasil Dibuat!</h2>
        
        <p id="modalMessage">
            Kode Pesanan: <strong><span id="orderIdDisplay" style="color: var(--first-color);"></span></strong>
        </p>
        
        <p class="modal-info-transfer">
            Untuk pembayaran selain COD, cek halaman profil Anda!
        </p>

        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 1.5rem;">
            
            <a id="printStrukBtn" href="#" class="button button--small" 
               style="background-color: darkgreen; border: none; flex-grow: 1;">
               <i class="ri-printer-line"></i> Struk
            </a>
            
            <a id="viewDetailBtn" href="#" class="button button--small" 
               style="background-color: var(--first-color); border: none; flex-grow: 1;">
               <i class="ri-search-line"></i> Cek Detail
            </a>
        </div>
        
        <button id="modalCloseBtn" class="button" style="background-color: var(--text-color);">
            Kembali ke Beranda
        </button>
    </div>
</div>  

  <script>
    const checkoutList = document.getElementById("checkoutList");
    const checkoutTotal = document.getElementById("checkoutTotal");
    const form = document.getElementById("checkoutForm");
    const successModal = document.getElementById("successModal");
    const orderIdDisplay = document.getElementById("orderIdDisplay");
    const modalCloseBtn = document.getElementById("modalCloseBtn");
    const alamatDisplay = document.getElementById("alamat_display");
    const alamatLengkapInput = document.getElementById("alamat_lengkap"); // Input tersembunyi
    
    let cart;
    try {
        cart = JSON.parse(localStorage.getItem("cart") || "[]");
    } catch (e) {
        console.error("Gagal parsing LocalStorage cart:", e);
        cart = []; 
    }

    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    let totalKeseluruhan = 0;
    
    // ===================================
    // 1. INISIALISASI PETA LEAFLET DENGAN PENCARIAN
    // ===================================
    
    const defaultCoords = [-6.200000, 106.816666]; 
    let marker;

    const map = L.map('map').setView(defaultCoords, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // BARIS BARU: Menambahkan Control Geocoder (Pencarian Lokasi)
    const geocoder = L.Control.Geocoder.nominatim();

    L.Control.geocoder({
        geocoder: geocoder,
        placeholder: 'Cari Alamat...', // Teks di kolom pencarian
        errorMessage: 'Alamat tidak ditemukan.',
        defaultMarkGeocode: false // Jangan beri marker otomatis
    }).on('markgeocode', function(e) {
        // Hapus marker lama jika ada
        if (marker) {
            map.removeLayer(marker);
        }
        
        const bbox = e.geocode.bbox;
        const poly = L.polygon([
            [bbox.getSouthEast().lat, bbox.getSouthEast().lng],
            [bbox.getNorthWest().lat, bbox.getNorthWest().lng]
        ]).addTo(map);
        
        // Pindahkan peta ke hasil pencarian
        map.fitBounds(poly.getBounds());
        map.removeLayer(poly); // Hapus poligon setelah peta dipindah
        
        const latlng = e.geocode.center;
        const address = e.geocode.name;
        
        // Tambahkan marker baru di lokasi hasil pencarian
        marker = L.marker(latlng).addTo(map);
        marker.bindPopup(address).openPopup();

        // Isi field input tersembunyi dan textarea
        alamatLengkapInput.value = address;
        alamatDisplay.value = address;

    }).addTo(map);

    // Handler saat peta diklik (Tetap ada, untuk pemilihan manual)
    map.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        
        // Hapus marker lama jika ada
        if (marker) {
            map.removeLayer(marker);
        }

        // Tambahkan marker baru
        marker = L.marker([lat, lng]).addTo(map);

        // Ambil alamat dari koordinat (Reverse Geocoding)
        // Fungsi getAddress() yang lama harus tetap didefinisikan di luar map.on('click')
        const fullAddress = await getAddress(lat, lng);

        // Isi field input tersembunyi dan textarea
        alamatLengkapInput.value = fullAddress;
        alamatDisplay.value = fullAddress;

        map.panTo(e.latlng);
    });

    // Catatan: Pastikan fungsi getAddress (Reverse Geocoding) yang lama tetap ada
    const getAddress = async (lat, lng) => {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
        try {
            const response = await fetch(url);
            const data = await response.json();
            return data.display_name || `Lat: ${lat}, Lng: ${lng}`;
        } catch (error) {
            console.error("Error geocoding:", error);
            return `Lokasi Pilihan (Koordinat: ${lat}, ${lng})`;
        }
    };
    
    // ===================================
    // 2. FUNGSI RENDER KERANJANG (Sama seperti sebelumnya, tapi pakai key 'image'/'img')
    // ===================================

    const renderCheckoutList = () => {
        if (!Array.isArray(cart) || cart.length === 0) {
            checkoutList.innerHTML = "<p style='text-align: center; color: var(--text-color);'>Keranjang Anda Kosong.</p>";
            checkoutTotal.innerHTML = "<p>Total: <strong>Rp 0</strong></p>";
            form.querySelector('button').disabled = true;
            return;
        }

        totalKeseluruhan = 0;
        
        const itemsHTML = cart.map(item => {
            const hargaString = String(item.price).replace('Rp', '').replace(/\./g, '').replace(',', '.').trim();
            const harga = parseFloat(hargaString) || 0;
            const qty = item.quantity || 1;
            const subtotal = harga * qty;

            totalKeseluruhan += subtotal;

            const imgKey = item.image || item.img;
            // Path disesuaikan: ../../assets/img/
            const fixedImgPath = imgKey ? `../../${imgKey}` : '../../assets/img/default.png'; 
            
            return `
                <div class="checkout__product-item">
                    <img src="${fixedImgPath}" alt="${item.name}" class="checkout__product-img">
                    <div class="checkout__product-details">
                        <p class="checkout__product-name">${item.name}</p>
                        <p class="checkout__product-info">
                            ${item.price} x ${qty} = <strong>Rp ${(subtotal).toLocaleString('id-ID')}</strong>
                        </p>
                    </div>
                </div>
            `;
        }).join('');

        checkoutList.innerHTML = itemsHTML;

        // Hitung dan render Total (Diskon 10%, TANPA PPN)
        let totalDisplay = totalKeseluruhan;
        let diskonHTML = ''; 
        let subtotalDiskon = 0; // Variabel untuk menyimpan jumlah diskon

        if(isLoggedIn){
            subtotalDiskon = totalKeseluruhan * 0.10; // Diskon 10%
            totalDisplay = totalKeseluruhan - subtotalDiskon;
            
            // Tampilkan jumlah Diskon yang diterapkan
            diskonHTML = `<p><span>Diskon Member (10%)</span> <span>- Rp ${subtotalDiskon.toLocaleString('id-ID')}</span></p>`;
        } else {
            // Jika tidak login, totalDisplay tetap subtotal awal
            totalDisplay = totalKeseluruhan;
        }


        checkoutTotal.innerHTML = `
            <p><span>Subtotal Produk</span> <span>Rp ${totalKeseluruhan.toLocaleString('id-ID')}</span></p>
            ${diskonHTML}
            
            <p><strong><span>Total Bayar</span> <span>Rp ${totalDisplay.toLocaleString('id-ID')}</span></strong></p>
        `;
    };

    renderCheckoutList();

    // ===================================
    // 3. EVENT LISTENER FORM SUBMIT
    // ===================================
    form.addEventListener("submit", e => {
    e.preventDefault();
    
    // Periksa Keranjang
    if (!Array.isArray(cart) || cart.length === 0) {
        alert("Keranjang kosong, tidak bisa melanjutkan checkout.");
        return;
    }

    // Ambil Data Form
    const namaPenerima = form.nama_penerima.value.trim();
    const alamatLengkap = form.alamat_lengkap.value.trim(); 
    const catatanTambahan = form.catatan.value.trim(); 
    const metodePembayaran = form.metode_pembayaran.value; 

    // Validasi Dasar
    if (namaPenerima === "" || alamatLengkap === "") {
        alert("Nama Penerima dan Alamat wajib diisi.");
        return;
    }

    // Gabungkan alamat peta dengan catatan
    const finalAlamat = `${alamatLengkap}\n\nCatatan Tambahan: ${catatanTambahan}`;


    fetch("proses_checkout.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        // KIRIM DATA LENGKAP
        body: JSON.stringify({ 
            cart: cart, 
            alamat: finalAlamat, 
            nama_penerima: namaPenerima,
            metode: metodePembayaran 
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success"){
            // A. KOSONGKAN KERANJANG
            localStorage.removeItem("cart"); 
            
            const orderId = data.order_id;
            
            // B. TAMPILKAN ID PESANAN
            orderIdDisplay.textContent = orderId; 

            // C. TENTUKAN URL & SET LINK TOMBOL
            const orderDetailUrl = `order_detail.php?id=${orderId}`;
            
            // Tombol 1: Cetak Struk (dengan parameter &print=true)
            printStrukBtn.href = orderDetailUrl + "&print=true"; 
            
            // Tombol 2: Detail Pesanan (view normal)
            viewDetailBtn.href = orderDetailUrl; 

            // D. TAMPILKAN MODAL
            successModal.classList.add('active'); 

            // E. SET EVENT LISTENER TOMBOL 'KEMBALI KE BERANDA'
            modalCloseBtn.addEventListener('click', () => {
                // Redirect ke Beranda setelah tombol di klik
                window.location.replace("../../index.php"); 
            }, { once: true }); 

        } else {
            // Error dari server
            alert("Gagal memproses pesanan: " + data.message);
            console.error("Server Error:", data.message);
        }
    })
    .catch(error => {
        // Error jaringan/parsing JSON
        alert("Terjadi kesalahan jaringan atau server tidak valid JSON. Cek console untuk detail.");
        console.error("Fetch Error:", error);
    });
});
</script>
   
</body>
</html>