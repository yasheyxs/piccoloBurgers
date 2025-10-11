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
?>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Botón de carrito -->
<a href="carrito.php" class="floating-btn floating-btn--gold btn-carrito" aria-label="Ir al carrito">
  <i class="fa-solid fa-cart-shopping"></i>
</a>
