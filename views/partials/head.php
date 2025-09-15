<?php
$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Piccolo Burgers';
$extraCss = $extraCss ?? [];

if (!is_array($extraCss)) {
    $extraCss = [$extraCss];
}
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link rel="icon" href="./img/favicon.png" type="image/x-icon" />
<?php foreach ($extraCss as $cssFile): ?>
  <?php if (is_string($cssFile) && $cssFile !== ''): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>
<?php endforeach; ?>
