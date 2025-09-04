<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Botón de carrito -->
<a href="carrito.php" class="btn-carrito" aria-label="Ir al carrito">
  <i class="fa-solid fa-cart-shopping"></i>
</a>

<style>
.btn-carrito {
  position: fixed;
  bottom: 170px;
  right: 30px;
  background-color: var(--main-gold, #fac30c);
  color: #000;
  font-size: 2rem;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  z-index: 1100;
  opacity: 0.6; 
  transition: transform 0.3s, box-shadow 0.3s, opacity 0.3s;
  text-decoration: none;
}

.btn-carrito:hover {
  background-color: var(--gold-hover, #e0ae00);
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0,0,0,0.4);
  color: #000;
  opacity: 1; 
}
</style>
