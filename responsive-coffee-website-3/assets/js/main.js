/*=============== SHOW MENU ===============*/
const navMenu = document.getElementById('nav-menu'),
      navToggle = document.getElementById('nav-toggle'),
      navClose = document.getElementById('nav-close')

/* Menu show */
if(navToggle){
    navToggle.addEventListener('click', () =>{
        navMenu.classList.add('show-menu')
    })
}

/* Menu hidden */
if(navClose){
    navClose.addEventListener('click', () =>{
        navMenu.classList.remove('show-menu')
    })
}

/*=============== REMOVE MENU MOBILE ===============*/
const navLink = document.querySelectorAll('.nav__link')

const linkAction = () =>{
    const navMenu = document.getElementById('nav-menu')
    // When we click on each nav__link, we remove the show-menu class
    navMenu.classList.remove('show-menu')
}
navLink.forEach(n => n.addEventListener('click', linkAction))

/*=============== ADD SHADOW HEADER ===============*/
const shadowHeader = () =>{
    const header = document.getElementById('header')
    // Add a class if the bottom offset is greater than 50 of the viewport
    this.scrollY >= 50 ? header.classList.add('shadow-header') 
                       : header.classList.remove('shadow-header')
}
window.addEventListener('scroll', shadowHeader)


/*=============== SWIPER POPULAR ===============*/
const swiperPopular = new Swiper('.popular__swiper', {
  loop: true,
  grabCursor: true,
  spaceBetween: 32,
  slidesPerView: 'auto',
  centeredSlides: 'auto',

  breakpoints:{
    1150:{
        spaceBetween: 80,
    }
  }
})

/*=============== SHOW SCROLL UP ===============*/ 
const scrollUp = () =>{
	const scrollUp = document.getElementById('scroll-up')
     // When the scroll is higher than 350 viewport height, add the show-scroll class to the a tag with the scrollup class
	this.scrollY >= 350 ? scrollUp.classList.add('show-scroll')
						: scrollUp.classList.remove('show-scroll')
}
window.addEventListener('scroll', scrollUp)

/*=============== SCROLL SECTIONS ACTIVE LINK ===============*/
const sections = document.querySelectorAll('section[id]')
    
const scrollActive = () =>{
  	const scrollDown = window.scrollY

	sections.forEach(current =>{
		const sectionHeight = current.offsetHeight,
			  sectionTop = current.offsetTop - 58,
			  sectionId = current.getAttribute('id'),
			  sectionsClass = document.querySelector('.nav__menu a[href*=' + sectionId + ']')

		if(scrollDown > sectionTop && scrollDown <= sectionTop + sectionHeight){
			sectionsClass.classList.add('active-link')
		}else{
			sectionsClass.classList.remove('active-link')
		}                                                    
	})
}
window.addEventListener('scroll', scrollActive)

/*=============== CART FEATURE ===============*/
let cart = []

// ambil semua tombol tambah produk
const productButtons = document.querySelectorAll('.products__button')
const cartBtn       = document.getElementById('cart-btn')
const cartCount     = document.getElementById('cart-count')
const cartModal     = document.getElementById('cart-modal') // ini sidebar cart
const cartItems     = document.getElementById('cart-items')
const closeCart     = document.getElementById('close-cart')
const checkoutBtn   = document.getElementById('checkout-btn')
const resetBtn      = document.getElementById('reset-btn')

// update tampilan keranjang
function updateCartUI() {
  cartItems.innerHTML = ""
  let totalHarga = 0

  cart.forEach((item) => {
    const li = document.createElement('li')
    li.classList.add('cart-item')

    const hargaSatuan = parseInt(item.price.replace(/\D/g, ''))
    const subTotal = item.quantity * hargaSatuan
    totalHarga += subTotal

    li.innerHTML = `
      <div class="item-left">
        <img src="${item.image}" alt="${item.name}">
        <div class="item-info">
          <h4>${item.name}</h4>
          <p>Rp. ${hargaSatuan.toLocaleString("id-ID")},-</p>
          <div class="qty-control">
            <button class="qty-btn minus" data-id="${item.id}">-</button>
            <span>${item.quantity}</span>
            <button class="qty-btn plus" data-id="${item.id}">+</button>
          </div>
        </div>
      </div>
      <div class="item-right">
        <strong>Rp. ${subTotal.toLocaleString("id-ID")},-</strong>
      </div>
    `
    cartItems.appendChild(li)
    // simpan cart ke localStorage setiap kali diperbarui
localStorage.setItem("cart", JSON.stringify(cart))

  })

  // total qty
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0)
  cartCount.innerText = totalItems

  // total harga
  document.getElementById("cart-total-price").innerText =
    "Rp. " + totalHarga.toLocaleString("id-ID") + ",-"

  // event listener tombol +/-
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id')
      const item = cart.find(i => i.id === id)

      if (btn.classList.contains('plus')) {
        item.quantity++
      } else if (btn.classList.contains('minus')) {
        item.quantity--
        if (item.quantity <= 0) {
          cart = cart.filter(i => i.id !== id)
        }
      }
      updateCartUI()
    })
  })
}

// ambil cart dari localStorage saat halaman dimuat
document.addEventListener("DOMContentLoaded", () => {
  const savedCart = localStorage.getItem("cart")
  if (savedCart) {
    cart = JSON.parse(savedCart)
    updateCartUI()
  }
});


// tambah produk ke keranjang
productButtons.forEach((btn) => {
  btn.addEventListener('click', () => {
    const card  = btn.closest('.products__card')
    const id    = card.getAttribute("data-id")
    const name  = card.querySelector('.products__name').innerText
    const price = card.querySelector('.products__price').innerText
    const image = card.getAttribute('data-img')   // ambil gambar produk

    // cek apakah produk sudah ada
    const existingItem = cart.find(item => item.id === id)

    
    if (existingItem) {
      existingItem.quantity++
    } else {
      cart.push({ id, name, price, image, quantity: 1 })
    }

    updateCartUI()
  })
})

//Tambahan untuk Checkout
// Di assets/js/main.js

document.querySelectorAll('.products__card').forEach(productCard => {
    productCard.querySelector('.products__button').addEventListener('click', (e) => {
        
        // PASTIKAN BARIS INI ADA DI LOGIKA ANDA:
        const productImage = productCard.dataset.img; 

        // PASTIKAN OBJEK ITEM BARU MENYIMPANNYA:
        const newItem = {
            id: productId,
            name: productName,
            price: productPrice,
            img: productImage, // <--- Data gambar kini disimpan!
            quantity: 1
        };

        // ... simpan newItem ke array cart dan set ke LocalStorage
        
    });
});
/*=============== OPEN / CLOSE CART SIDEBAR ===============*/
// buka keranjang (slide-in)
cartBtn.addEventListener('click', () => {
  cartModal.classList.add('show')
})

// tutup keranjang (slide-out)
closeCart.addEventListener('click', () => {
  cartModal.classList.remove('show')
})

/*=============== RESET CART ===============*/
resetBtn.addEventListener('click', () => {
  cart = []
  updateCartUI()
})

/*=============== CHECKOUT ===============*/
checkoutBtn.addEventListener('click', () => {
  if(cart.length === 0){
    alert("Keranjang kosong!")
    return
  }

  // Simpan cart ke localStorage sementara
  localStorage.setItem("cart", JSON.stringify(cart))

  // Redirect ke halaman checkout
  window.location.href = "assets/php/checkout.php"
})





/*=============== SCROLL REVEAL ANIMATION ===============*/
const sr = ScrollReveal({
    origin: 'top',
    distance: '60px',
    duration: 2000,
    delay: 300,
    //reset: true, //Animations repeat
})

sr.reveal(`.popular__swiper, .footer__container, .footer__copy`)
sr.reveal(`.home__shape`, {origin: 'bottom'})
sr.reveal(`.home__coffee`, {delay: 1000, distance: '200px', duration: 1500})
sr.reveal(`.home__splash`, {delay: 1600, scale: 0, duration: 1500})
sr.reveal(`.home__bean-1, .home__bean-2`, {delay: 2200, scale: 0, duration: 1500, rotate: {z: 180}})
sr.reveal(`.home__ice-1, .home__ice-2`, {delay: 2600, scale: 0, duration: 1500, rotate: {z: 180}})
sr.reveal(`.home__leaf`, {delay: 2800, scale: 0, duration: 1500, rotate: {z: 90}})
sr.reveal(`.home__title`, {delay: 3500})
sr.reveal(`.home__data, .home__sticker`, {delay: 4000})
sr.reveal(`.about__data`, {origin: 'left'})
sr.reveal(`.about__images`, {origin: 'right'})
sr.reveal(`.about__coffee`, {delay: 1000})
sr.reveal(`.about__leaf-1, .about__leaf-2`, {delay: 1400, rotate: {z: 90}})
sr.reveal(`.products__card, .contact__info`, {interval: 100})
sr.reveal(`.contact__shape`, {delay: 600, scale: 0})
sr.reveal(`.contact__delivery`, {delay: 1200})