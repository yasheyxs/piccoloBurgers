<?php
if (!isset($GLOBALS['floating_button_css_href'])) {
    $defaultHref = '/assets/css/floating-button.css';
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    $publicDir = '/public/';

    if ($scriptName !== '' && ($publicPos = strpos($scriptName, $publicDir)) !== false) {
        $baseSegment = substr($scriptName, 0, $publicPos + strlen($publicDir));
        $candidateHref = rtrim($baseSegment, '/') . $defaultHref;
        $GLOBALS['floating_button_css_href'] = $candidateHref !== '' ? $candidateHref : $defaultHref;
    } else {
        $GLOBALS['floating_button_css_href'] = $defaultHref;
    }
}

if (empty($GLOBALS['floating_button_css_loaded'])) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars((string) $GLOBALS['floating_button_css_href'], ENT_QUOTES, 'UTF-8') . '">';
    $GLOBALS['floating_button_css_loaded'] = true;
}

$whatsappNumero = "5493573438947";
$whatsappMensaje = urlencode("¡Hola! Me gustaría hacer un pedido 🍔✨");
?>

<a href="https://wa.me/<?php echo $whatsappNumero; ?>?text=<?php echo $whatsappMensaje; ?>"
   class="floating-btn floating-btn--green btn-whatsapp" target="_blank" aria-label="Contactanos por WhatsApp">
   <i class="fab fa-whatsapp"></i>
</a>
