<?php
/*
 * Always-visible navigation on its own solid surface, minimising slightly on
 * scroll. Interactive states are class swaps driven by assets/js/app.js:
 * each element lists its classes for either state in data-on / data-off.
 */
$path = request_path();
$nav = site('header.nav', []);
$searchLabel = site('labels.search') ?: 'Search';
$programmes = entries('programme');

$childrenOf = function (array $child) use ($programmes): array {
    if (empty($child['listProgrammes'])) {
        return [];
    }
    return array_map(fn ($p) => ['label' => $p['title'] ?? '', 'href' => $p['url']], $programmes);
};

$isActive = function (array $item) use ($path): bool {
    $href = rtrim((string) $item['href'], '/') ?: '/';
    if ($href === '/') {
        return $path === '/';
    }
    if ($path === $href || strpos($path, $href . '/') === 0) {
        return true;
    }
    foreach ($item['children'] ?? [] as $child) {
        if ($path === ($child['href'] ?? null)) {
            return true;
        }
    }
    return false;
};
?>
<header class="sticky top-0 z-50" data-header>
  <div class="border-b border-hairline bg-cream/95 backdrop-blur-sm transition-[padding,box-shadow] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] py-4" data-scrolled-on="py-2 shadow-[0_1px_20px_rgba(84,63,38,0.06)]" data-scrolled-off="py-4">
    <div class="shell flex items-center justify-between gap-3 sm:gap-6">
      <!-- Landing state: the mark above the wordmark. Once the page scrolls the
           same two parts line up horizontally, vertically centred on each other. -->
      <a href="/" class="group flex min-w-0 transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] flex-col items-center gap-2" data-scrolled-on="flex-row items-center gap-3" data-scrolled-off="flex-col items-center gap-2" aria-label="<?= e(site('site.name')) ?> — home">
        <img src="<?= e(local_url((string) site('site.logoMark'))) ?>" alt="" aria-hidden="true" width="360" height="180" class="w-auto shrink-0 transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] h-12 sm:h-14 md:h-16" data-scrolled-on="h-7 sm:h-8 md:h-9" data-scrolled-off="h-12 sm:h-14 md:h-16">
        <span class="text-center leading-tight text-muted uppercase transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:text-accent-dark text-[0.5rem] tracking-[0.14em] max-w-[8rem] sm:max-w-none sm:text-[0.7rem] sm:tracking-[0.2em]" data-scrolled-on="text-[0.5rem] tracking-[0.14em] max-w-[5.5rem] sm:max-w-none sm:text-[0.6rem] sm:tracking-[0.16em]" data-scrolled-off="text-[0.5rem] tracking-[0.14em] max-w-[8rem] sm:max-w-none sm:text-[0.7rem] sm:tracking-[0.2em]"><?= e(site('site.name')) ?></span>
      </a>

      <nav class="hidden items-center gap-0.5 xl:flex" aria-label="Main">
        <?php foreach ($nav as $item):
            $children = array_values($item['children'] ?? []);
            $active = $isActive($item); ?>
        <div class="relative" <?= $children ? 'data-dropdown' : '' ?>>
          <a href="<?= e($item['href']) ?>" <?= $children ? 'aria-expanded="false"' : '' ?> class="flex items-center gap-1 rounded-full px-3 py-2 text-[0.9rem] whitespace-nowrap transition-colors duration-300 <?= $active ? 'text-accent-dark' : 'text-body hover:text-accent-dark' ?>">
            <?= e($item['label']) ?>
            <?php if ($children): ?><?= icon('chevron-down', 'h-3.5 w-3.5 transition-transform duration-300', 1.4) ?><?php endif; ?>
          </a>
          <span class="pointer-events-none absolute inset-x-3 -bottom-0.5 h-px origin-left bg-accent transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] <?= $active ? 'scale-x-100' : 'scale-x-0' ?>"></span>

          <?php if ($children): ?>
          <div class="absolute top-full left-1/2 w-72 -translate-x-1/2 pt-3 transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] invisible -translate-y-1 opacity-0" data-panel data-open-on="visible translate-y-0 opacity-100" data-open-off="invisible -translate-y-1 opacity-0">
            <div class="rounded-2xl border border-hairline bg-cream p-2 shadow-[0_18px_44px_rgba(84,63,38,0.10)]">
              <?php foreach ($children as $child):
                  $grand = $childrenOf($child);
                  if (!$grand): ?>
              <a href="<?= e($child['href']) ?>" class="block rounded-xl px-3.5 py-2.5 transition-colors duration-200 hover:bg-buff">
                <span class="block text-[0.9rem] text-ink"><?= e($child['label']) ?></span>
                <?php if (($child['description'] ?? '') !== ''): ?><span class="mt-0.5 block text-xs leading-snug text-muted"><?= e($child['description']) ?></span><?php endif; ?>
              </a>
              <?php else: ?>
              <div class="relative" data-subdropdown>
                <a href="<?= e($child['href']) ?>" aria-expanded="false" class="flex items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 transition-colors duration-200 hover:bg-buff">
                  <span class="text-[0.9rem] text-ink"><?= e($child['label']) ?></span>
                  <?= icon('chevron-right', 'h-3.5 w-3.5 shrink-0 text-muted') ?>
                </a>
                <div class="absolute top-0 left-full z-10 w-64 pl-2 transition-all duration-250 ease-[cubic-bezier(0.22,1,0.36,1)] invisible -translate-x-1 opacity-0" data-subpanel data-open-on="visible translate-x-0 opacity-100" data-open-off="invisible -translate-x-1 opacity-0">
                  <div class="rounded-2xl border border-hairline bg-cream p-2 shadow-[0_18px_44px_rgba(84,63,38,0.10)]">
                    <?php foreach ($grand as $g): ?>
                    <a href="<?= e($g['href']) ?>" class="block rounded-xl px-3.5 py-2.5 text-[0.875rem] text-body transition-colors duration-200 hover:bg-buff hover:text-ink"><?= e($g['label']) ?></a>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <?php endif; endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </nav>

      <div class="flex shrink-0 items-center gap-0.5 sm:gap-2">
        <a href="/search" aria-label="<?= e($searchLabel) ?>" aria-haspopup="dialog" class="flex h-9 w-9 items-center justify-center rounded-full text-body transition-colors duration-300 hover:bg-buff hover:text-accent-dark" data-search-open>
          <?= icon('search', 'h-4.5 w-4.5') ?>
        </a>
        <a href="<?= e(site('header.donateHref')) ?>" class="rounded-full bg-accent font-medium text-cream shadow-[0_6px_18px_rgba(200,122,60,0.22)] transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:-translate-y-0.5 hover:bg-accent-dark hover:shadow-[0_10px_24px_rgba(200,122,60,0.28)] whitespace-nowrap px-4 py-2 text-[0.85rem] sm:px-6 sm:py-2.5 sm:text-[0.9rem]" data-scrolled-on="px-4 py-2 text-[0.85rem] sm:px-5 sm:py-2 sm:text-[0.85rem]" data-scrolled-off="px-4 py-2 text-[0.85rem] sm:px-6 sm:py-2.5 sm:text-[0.9rem]"><?= e(site('header.donateLabel')) ?></a>
        <button type="button" aria-label="Open menu" class="flex h-9 w-9 items-center justify-center rounded-full text-ink transition-colors hover:bg-buff xl:hidden" data-menu-open>
          <?= icon('menu', 'h-5 w-5') ?>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu. overflow-hidden matters: the panel parks off-canvas to the right. -->
  <div class="fixed inset-0 z-50 overflow-hidden xl:hidden pointer-events-none" aria-hidden="true" data-menu data-open-on="" data-open-off="pointer-events-none">
    <div class="absolute inset-0 bg-ink/25 transition-opacity duration-400 opacity-0" data-menu-backdrop data-open-on="opacity-100" data-open-off="opacity-0"></div>
    <div class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-cream transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] translate-x-full" data-menu-panel data-open-on="translate-x-0" data-open-off="translate-x-full">
      <div class="flex items-center justify-between border-b border-hairline px-6 py-4">
        <span class="font-display text-lg text-ink"><?= e(site('labels.menu', 'Menu')) ?></span>
        <button type="button" aria-label="Close menu" class="flex h-9 w-9 items-center justify-center rounded-full text-ink transition-colors hover:bg-buff" data-menu-close>
          <?= icon('close', 'h-5 w-5') ?>
        </button>
      </div>

      <nav class="flex-1 overflow-y-auto px-6 py-6" aria-label="Mobile">
        <?php if (!in_array('/', array_map(fn ($item) => rtrim((string) ($item['href'] ?? ''), '/') ?: '/', $nav), true)): ?>
        <div class="border-b border-hairline/70">
          <a href="/" class="block py-4 text-[1.05rem] <?= $path === '/' ? 'text-accent-dark' : 'text-ink' ?>"><?= e(site('labels.home') ?: 'Home') ?></a>
        </div>
        <?php endif; ?>
        <?php foreach ($nav as $item): $children = array_values($item['children'] ?? []); ?>
        <div class="border-b border-hairline/70 last:border-0" data-mobile-section>
          <div class="flex items-center justify-between">
            <a href="<?= e($item['href']) ?>" class="block py-4 text-[1.05rem] <?= $path === $item['href'] ? 'text-accent-dark' : 'text-ink' ?>"><?= e($item['label']) ?></a>
            <?php if ($children): ?>
            <button type="button" aria-label="Expand <?= e($item['label']) ?>" aria-expanded="false" class="flex h-9 w-9 items-center justify-center rounded-full text-muted transition-colors hover:bg-buff" data-mobile-toggle>
              <?= icon('chevron-down', 'h-4 w-4 transition-transform duration-300') ?>
            </button>
            <?php endif; ?>
          </div>
          <?php if ($children): ?>
          <div class="grid transition-[grid-template-rows] duration-400 ease-[cubic-bezier(0.22,1,0.36,1)] grid-rows-[0fr]" data-mobile-panel data-open-on="grid-rows-[1fr]" data-open-off="grid-rows-[0fr]">
            <div class="overflow-hidden">
              <div class="pb-3 pl-3">
                <?php foreach ($children as $child): $grand = $childrenOf($child); ?>
                <div>
                  <a href="<?= e($child['href']) ?>" class="block py-2.5 text-[0.925rem] text-body transition-colors hover:text-accent-dark"><?= e($child['label']) ?></a>
                  <?php if ($grand): ?>
                  <div class="mb-1 ml-3 border-l border-hairline pl-3">
                    <?php foreach ($grand as $g): ?>
                    <a href="<?= e($g['href']) ?>" class="block py-2 text-[0.875rem] text-muted transition-colors hover:text-accent-dark"><?= e($g['label']) ?></a>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </nav>

      <div class="border-t border-hairline px-6 py-5">
        <a href="<?= e(site('header.donateHref')) ?>" class="block rounded-full bg-accent px-6 py-3 text-center font-medium text-cream transition-colors hover:bg-accent-dark"><?= e(site('header.donateLabel')) ?></a>
      </div>
    </div>
  </div>

  <!-- Search: drops down from the top. Results arrive as you type (from
       /search.json); Enter opens the full results page at /search. -->
  <div class="fixed inset-0 z-[55] invisible transition-[visibility] duration-300" role="dialog" aria-modal="true" aria-label="<?= e($searchLabel) ?>" aria-hidden="true" data-search data-open-on="visible" data-open-off="invisible"
       data-no-results="<?= e(site('labels.searchNoResults') ?: 'Nothing found for “{query}”.') ?>" data-prompt="<?= e(site('labels.searchPrompt') ?: 'Type a word or phrase to search the site.') ?>">
    <div class="absolute inset-0 bg-ink/25 transition-opacity duration-300 opacity-0" data-search-backdrop data-open-on="opacity-100" data-open-off="opacity-0"></div>
    <div class="relative border-b border-hairline bg-cream shadow-[0_18px_44px_rgba(84,63,38,0.10)] transition-all duration-400 ease-[cubic-bezier(0.22,1,0.36,1)] -translate-y-3 opacity-0" data-search-panel data-open-on="translate-y-0 opacity-100" data-open-off="-translate-y-3 opacity-0">
      <div class="shell py-5 md:py-7">
        <form action="/search" method="get" role="search" class="flex items-center gap-3 border-b-2 border-ink/80 pb-3 focus-within:border-accent">
          <?= icon('search', 'h-5 w-5 shrink-0 text-muted md:h-6 md:w-6') ?>
          <input type="search" name="q" placeholder="<?= e(site('labels.searchPlaceholder') ?: 'Search the site') ?>" aria-label="<?= e($searchLabel) ?>" autocomplete="off" maxlength="120" class="min-w-0 flex-1 bg-transparent font-display text-[1.4rem] leading-tight text-ink placeholder:text-muted/70 focus:outline-none md:text-[1.9rem]" data-search-input>
          <button type="button" aria-label="Close search" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-ink transition-colors hover:bg-buff" data-search-close>
            <?= icon('close', 'h-5 w-5') ?>
          </button>
        </form>
        <div class="max-h-[calc(100vh-11rem)] overflow-y-auto overscroll-contain" aria-live="polite">
          <p class="pt-5 text-[0.95rem] leading-relaxed text-muted" data-search-status><?= e(site('labels.searchPrompt') ?: 'Type a word or phrase to search the site.') ?></p>
          <div data-search-results></div>
          <a href="/search" class="link-arrow mt-2 hidden pt-4 pb-1" data-search-all><span><?= e(site('labels.searchSeeAll') ?: 'See all results') ?> (<span data-search-total>0</span>)</span><?= icon('arrow-right', 'h-4 w-4') ?></a>
        </div>
      </div>
    </div>
  </div>
</header>
