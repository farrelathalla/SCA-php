<?php
$siteName = (string) site('site.name', 'Saiga Conservation Alliance');
$pageTitle = trim((string) ($meta['title'] ?? ''));
$title = $pageTitle !== '' ? $pageTitle . ' — ' . $siteName : $siteName;
$description = plain_text($meta['description'] ?? '') ?: plain_text(site('site.description'));
// Search engines and link previews: the page's own address and a picture.
$canonical = site_origin() . (request_path() === '/' ? '/' : request_path());
$shareImage = trim((string) ($meta['image'] ?? '')) ?: (string) site('site.logoFull');
$shareImage = preg_match('~^https?://~', $shareImage) ? $shareImage : site_origin() . $shareImage;
$analytics = trim((string) site('site.analyticsId'));
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="<?= e($meta['type'] ?? 'website') ?>">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($shareImage) ?>">
  <meta property="og:locale" content="en_GB">
  <meta name="twitter:card" content="summary_large_image">
  <?php if (config('noindex')): ?><meta name="robots" content="noindex, nofollow"><?php elseif (!empty($meta['noindex'])): ?><meta name="robots" content="noindex"><?php endif; ?>
  <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">
  <link rel="icon" href="<?= asset('logo/icon-256.png') ?>" type="image/png" sizes="256x256">
  <link rel="apple-touch-icon" href="<?= asset('logo/apple-icon.png') ?>" sizes="180x180">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=Playfair+Display:wght@400..700&display=swap">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <noscript><style>.reveal{opacity:1;transform:none}</style></noscript>
  <?php if (request_path() === '/'): ?>
  <script type="application/ld+json"><?= json_encode(array_filter([
      '@context' => 'https://schema.org',
      '@type' => 'NGO',
      'name' => $siteName,
      'alternateName' => (string) site('site.shortName'),
      'url' => site_origin() . '/',
      'logo' => site_origin() . (string) site('site.logoFull'),
      'description' => plain_text(site('site.description')),
      'sameAs' => array_values(array_filter(array_map(fn ($s) => (string) ($s['href'] ?? ''), (array) site('footer.socials', [])), fn ($u) => preg_match('~^https?://~', $u))),
  ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <?php endif; ?>
  <?php if ($analytics !== '' && !config('noindex') && ($_COOKIE['sca_analytics'] ?? '') !== 'off'): ?>
  <!--
    Google Analytics, for anonymous, aggregate visitor statistics only (the UK
    "statistics" exemption): Consent Mode keeps advertising storage and ad
    personalisation denied, Google signals are off, and nothing is loaded for
    a visitor who used the footer's "Analytics opt-out" link or whose browser
    sends Global Privacy Control.
  -->
  <script>
  (function (id) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { dataLayer.push(arguments); };
    if (navigator.globalPrivacyControl === true || /(?:^|; )sca_analytics=off/.test(document.cookie)) {
      window["ga-disable-" + id] = true;
      return;
    }
    gtag("consent", "default", { ad_storage: "denied", ad_user_data: "denied", ad_personalization: "denied", analytics_storage: "granted", functionality_storage: "denied", personalization_storage: "denied", security_storage: "granted" });
    gtag("set", "ads_data_redaction", true);
    gtag("js", new Date());
    gtag("config", id, { allow_google_signals: false, allow_ad_personalization_signals: false });
    var s = document.createElement("script");
    s.async = true;
    s.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(id);
    document.head.appendChild(s);
  })(<?= json_encode($analytics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
  </script>
  <?php endif; ?>
</head>
<body class="min-h-screen antialiased">
  <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[60] focus:rounded-full focus:bg-accent focus:px-5 focus:py-2.5 focus:text-cream"><?= e(site('labels.skipToContent', 'Skip to content')) ?></a>
  <?= view('partials/header') ?>
  <main id="main"><?= $content ?></main>
  <?= view('partials/footer') ?>
  <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
