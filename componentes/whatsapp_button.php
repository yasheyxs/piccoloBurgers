<?php
$whatsappNumero = "5493573438947";
$whatsappMensaje = urlencode("¡Hola! Me gustaría hacer un pedido 🍔✨");
?>

<link rel="stylesheet" href="/assets/css/floating-button.css">

<a href="https://wa.me/<?php echo $whatsappNumero; ?>?text=<?php echo $whatsappMensaje; ?>"
   class="floating-btn floating-btn--green btn-whatsapp" target="_blank" aria-label="Contactanos por WhatsApp">
   <i class="fab fa-whatsapp"></i>
</a>
