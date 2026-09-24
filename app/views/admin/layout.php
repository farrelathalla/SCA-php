<?php
$path = request_path();
$groups = [];
foreach (ADMIN_PAGES as $slug => [$label, $url, $group]) {
    $groups[$group][] = ['href' => '/admin/pages/' . $slug, 'label' => $label];
}
$sections = [
    ['title' => null, 'links' => [['href' => '/admin', 'label' => 'Dashboard']]],
    ['title' => 'News & collections', 'links' => [
        ['href' => '/admin/news', 'label' => 'News & Updates'],
        ['href' => '/admin/projects', 'label' => 'Projects'],
        ['href' => '/admin/themes', 'label' => 'Our Work themes'],
        ['href' => '/admin/programmes', 'label' => 'Grants programmes'],
    ]],
    ['title' => 'Build a page', 'links' => [
        ['href' => '/admin/built-pages', 'label' => 'Pages you build'],
    ]],
];
foreach (['Pages', 'About Us', 'About Saigas', 'Our Work', 'Support Us', 'Site'] as $g) {
    $sections[] = ['title' => $g, 'links' => $groups[$g] ?? []];
}
$sections[] = ['title' => 'Other', 'links' => [
    ['href' => '/admin/media', 'label' => 'Media library'],
    ['href' => '/admin/messages', 'label' => 'Messages'],
    ['href' => '/admin/account', 'label' => 'Account & users'],
]];
$flash = flash();
$isActive = fn ($href) => $href === '/admin' ? $path === '/admin' : ($path === $href || strpos($path, $href . '/') === 0);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($title) ?> — SCA Admin</title>
  <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=Playfair+Display:wght@400..700&display=swap">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
</head>
<body class="min-h-screen bg-cream-deep text-body">
  <div class="lg:flex">
    <aside class="border-b border-hairline bg-cream lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-b-0">
      <div class="flex items-center justify-between px-5 py-4">
        <a href="/admin" class="flex items-center gap-2.5">
          <img src="<?= e(local_url((string) site('site.logoMark'))) ?>" alt="" class="h-8 w-auto">
          <span class="leading-tight"><span class="block font-display text-lg text-ink">SCA</span><span class="block text-[10px] tracking-[0.14em] text-muted uppercase">Content admin</span></span>
        </a>
        <button type="button" class="rounded-lg border border-hairline px-3 py-1.5 text-[0.8rem] text-ink lg:hidden" data-admin-menu-toggle>Menu</button>
      </div>
      <nav class="hidden px-3 pb-6 lg:block" data-admin-menu>
        <?php foreach ($sections as $section): ?>
        <div class="mt-3">
          <?php if ($section['title']): ?><p class="px-3 pb-1 text-[10px] font-medium tracking-[0.14em] text-muted uppercase"><?= e($section['title']) ?></p><?php endif; ?>
          <?php foreach ($section['links'] as $link): ?>
          <a href="<?= e($link['href']) ?>" class="block rounded-lg px-3 py-1.5 text-[0.875rem] transition-colors <?= $isActive($link['href']) ? 'bg-accent-soft text-accent-dark' : 'text-ink hover:bg-buff' ?>"><?= e($link['label']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <div class="mt-6 border-t border-hairline px-3 pt-4 text-[0.8rem]">
          <p class="text-muted">Signed in as <span class="text-ink"><?= e(current_user()['username'] ?? '') ?></span></p>
          <div class="mt-2 flex gap-4">
            <a href="/" target="_blank" class="text-accent-dark hover:underline">View site ↗</a>
            <a href="/admin/logout" class="text-muted hover:text-ink">Sign out</a>
          </div>
        </div>
      </nav>
    </aside>

    <main class="min-w-0 flex-1 px-4 py-6 md:px-10 md:py-10">
      <?php if ($flash): ?>
      <div class="mb-6 rounded-xl px-4 py-3 text-[0.9rem] <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-800' : 'bg-accent-soft text-ink' ?>" role="status"><?= e($flash['message']) ?></div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>

  <div class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/40 p-4" data-library-modal>
    <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-cream shadow-2xl">
      <div class="flex items-center justify-between border-b border-hairline px-5 py-3">
        <h2 class="font-display text-xl text-ink">Choose an image</h2>
        <button type="button" class="rounded-lg px-3 py-1.5 text-[0.9rem] text-muted hover:bg-buff" data-library-close>Close</button>
      </div>
      <div class="grid min-h-0 auto-rows-max grid-cols-2 gap-3 overflow-y-auto p-5 sm:grid-cols-3 md:grid-cols-4" data-library-grid>
        <p class="col-span-full text-muted">Loading…</p>
      </div>
    </div>
  </div>

  <div class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/50 p-4" data-crop-modal>
    <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-cream shadow-2xl">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline px-5 py-3">
        <div>
          <h2 class="font-display text-xl text-ink">Crop &amp; zoom</h2>
          <p class="text-[0.8rem] text-muted">Drag the frame, or scroll / use + − to zoom. Saving makes a new copy — the original stays as it is.</p>
        </div>
        <button type="button" class="rounded-lg px-3 py-1.5 text-[0.9rem] text-muted hover:bg-buff" data-crop-close>Cancel</button>
      </div>
      <div class="min-h-0 flex-1 bg-ink/90 p-3"><div class="h-[58vh]"><img data-crop-image alt="" class="block max-w-full"></div></div>
      <div class="flex flex-wrap items-center gap-2 border-t border-hairline px-5 py-3 text-[0.8rem]">
        <span class="text-muted">Shape:</span>
        <button type="button" data-crop-ratio="NaN" class="rounded-full border border-hairline px-3 py-1 text-ink hover:border-ink/40">Free</button>
        <button type="button" data-crop-ratio="1.3333" class="rounded-full border border-hairline px-3 py-1 text-ink hover:border-ink/40">Landscape 4:3</button>
        <button type="button" data-crop-ratio="1.7778" class="rounded-full border border-hairline px-3 py-1 text-ink hover:border-ink/40">Wide 16:9</button>
        <button type="button" data-crop-ratio="0.8" class="rounded-full border border-hairline px-3 py-1 text-ink hover:border-ink/40">Portrait 4:5</button>
        <button type="button" data-crop-ratio="1" class="rounded-full border border-hairline px-3 py-1 text-ink hover:border-ink/40">Square</button>
        <span class="ml-2 text-muted">Zoom:</span>
        <button type="button" data-crop-zoom="0.1" class="h-7 w-7 rounded-full border border-hairline text-ink hover:border-ink/40" aria-label="Zoom in">+</button>
        <button type="button" data-crop-zoom="-0.1" class="h-7 w-7 rounded-full border border-hairline text-ink hover:border-ink/40" aria-label="Zoom out">−</button>
        <button type="button" data-crop-save class="ml-auto rounded-full bg-accent px-5 py-2 text-[0.85rem] font-medium text-cream hover:bg-accent-dark">Save cropped copy</button>
      </div>
    </div>
  </div>

  <datalist id="project-types">
    <?php foreach (site('projectTypes', []) as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?>
  </datalist>

  <script type="application/json" id="icon-paths"><?= json_encode(ICON_PATHS, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?></script>
  <script type="application/json" id="block-labels"><?= json_encode(array_map(fn ($b) => $b['label'], BLOCK_TYPES), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" defer></script>
  <script src="<?= asset('assets/admin/admin.js') ?>" defer></script>
</body>
</html>
