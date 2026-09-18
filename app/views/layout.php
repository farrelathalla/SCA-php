<?php
$siteName = (string) site('site.name', 'Saiga Conservation Alliance');
$pageTitle = trim((string) ($meta['title'] ?? ''));
$title = $pageTitle !== '' ? $pageTitle . ' — ' . $siteName : $siteName;
$description = trim((string) ($meta['description'] ?? '')) ?: (string) site('site.description');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <?php if (config('noindex')): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/logo/icon-256.png" type="image/png" sizes="256x256">
  <link rel="apple-touch-icon" href="/logo/apple-icon.png" sizes="180x180">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=Playfair+Display:wght@400..700&display=swap">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <noscript><style>.reveal{opacity:1;transform:none}</style></noscript>
</head>
<body class="min-h-screen antialiased">
  <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[60] focus:rounded-full focus:bg-accent focus:px-5 focus:py-2.5 focus:text-cream"><?= e(site('labels.skipToContent', 'Skip to content')) ?></a>
  <?= view('partials/header') ?>
  <main id="main"><?= $content ?></main>
  <?= view('partials/footer') ?>
  <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
