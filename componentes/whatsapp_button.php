<?php
$whatsappNumero = "5493573438947"; 
$whatsappMensaje = urlencode("¡Hola! Me gustaría hacer un pedido 🍔✨");
?>

<a href="https://wa.me/<?php echo $whatsappNumero; ?>?text=<?php echo $whatsappMensaje; ?>" 
   class="btn-whatsapp" target="_blank" aria-label="Contactanos por WhatsApp">
   <i class="fab fa-whatsapp"></i>
</a>

<style>
.btn-whatsapp {
  position: fixed;
  bottom: 100px;
  right: 30px;
  background-color: #25D366;
  color: white;
  font-size: 2rem;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  z-index: 1000;
  transition: transform 0.3s, box-shadow 0.3s;
  text-decoration: none;
}

.btn-whatsapp:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0,0,0,0.4);
  color: white;
  text-decoration: none;
}
</style>