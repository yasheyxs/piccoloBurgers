
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

<button id="btn-scroll-up" class="floating-btn floating-btn--gold btn-scroll-up" aria-label="Volver arriba">
  <i class="fa-solid fa-chevron-up"></i>
</button>

<script>
  const scrollUpBtn = document.getElementById("btn-scroll-up");

  window.addEventListener("scroll", () => {
    if (window.scrollY > 300) {
      scrollUpBtn.style.display = "flex";
    } else {
      scrollUpBtn.style.display = "none";
    }
  });

  scrollUpBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
</script>
