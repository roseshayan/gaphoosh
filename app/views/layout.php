<?php
$description = $description ?? 'گپ‌هوش؛ چت با هوش مصنوعی فارسی، سریع، ساده و راست‌چین.';
$title = $title ?? config('app_name');
$bodyClass = $bodyClass ?? '';
$canonical = config('app_url') . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$nonce = $nonce ?? '';
$ogImage = config('app_url') . public_url('assets/img/og-logo.png');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <title><?= h($title) ?></title>
    <meta name="description" content="<?= h($description) ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="گپ‌هوش">
    <meta property="og:title" content="<?= h($title) ?>">
    <meta property="og:description" content="<?= h($description) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/png" href="<?= h(public_url('assets/img/logo.png')) ?>">
    <link rel="preload" href="<?= h(public_url('assets/app.css')) ?>?v=4.0.0" as="style">
    <link rel="stylesheet" href="<?= h(public_url('assets/app.css')) ?>?v=4.0.0">
    <?php if (str_contains((string) ($bodyClass ?? ''), 'landing-body')): ?>
        <script type="application/ld+json" nonce="<?= h($nonce) ?>">
        {"@context":"https://schema.org","@type":"WebApplication","name":"گپ‌هوش","url":"https://gaphoosh.ir","applicationCategory":"AIApplication","operatingSystem":"Web","inLanguage":"fa-IR","description":"چت با هوش مصنوعی فارسی با رابط راست‌چین، استریم لحظه‌ای، مدیریت گفتگوها و انتخاب چند مدل هوش مصنوعی.","offers":{"@type":"Offer","price":"0","priceCurrency":"IRR"}}
        </script>
    <?php endif; ?>

    <?php if (str_contains((string) ($bodyClass ?? ''), 'contact-body')): ?>
        <script type="application/ld+json" nonce="<?= h($nonce) ?>">
        {"@context":"https://schema.org","@type":"ContactPage","name":"ارتباط با گپ‌هوش","url":"https://gaphoosh.ir/contact","inLanguage":"fa-IR","mainEntity":{"@type":"Organization","name":"گپ‌هوش","url":"https://gaphoosh.ir","email":"<?= h(config('contact_email')) ?>","sameAs":["<?= h(config('contact_telegram')) ?>","<?= h(config('contact_github')) ?>"]}}
        </script>
    <?php endif; ?>
</head>
<body class="<?= h($bodyClass) ?>">
<?php render_partial($viewFile, get_defined_vars()); ?>
<script src="<?= h(public_url('assets/app.js')) ?>?v=4.0.0" defer></script>
</body>
</html>
