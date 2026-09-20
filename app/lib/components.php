<?php

/**
 * Presentational building blocks, ported from the design's React components
 * (components/ui.tsx, Blocks.tsx, PageHero.tsx, Media.tsx, …). Class names are
 * kept identical so the compiled Tailwind output matches the design exactly.
 * Each returns an HTML string.
 */

/* -------------------------------------------------------------------- Reveal
   Restrained scroll-in: one slow fade and a short lift, once. The observer
   lives in assets/js/app.js; this only writes the attributes. */

function reveal(string $class = '', int $delay = 0): string
{
    return 'class="reveal ' . e(trim($class)) . '"' . ($delay ? ' style="transition-delay: ' . $delay . 'ms"' : '');
}

/* --------------------------------------------------------------------- Media */

const RATIO_CLASS = [
    'wide' => 'aspect-[32/9]',
    'hero' => 'aspect-[16/9] md:aspect-[21/9]',
    'map' => 'aspect-[16/10]',
    'landscape' => 'aspect-[4/3]',
    'portrait' => 'aspect-[3/4]',
    'square' => 'aspect-square',
];

/**
 * An image with the designed placeholder always sitting underneath, so a
 * missing or not-yet-supplied file still reads as part of the layout.
 */
function media(?string $src, array $o = []): string
{
    static $n = 0;
    $n++;
    $src = trim((string) $src);
    $fill = $o['fill'] ?? false;
    $box = $fill ? 'absolute inset-0 h-full w-full' : 'relative ' . RATIO_CLASS[$o['ratio'] ?? 'landscape'];
    $radius = ($o['rounded'] ?? true) ? 'rounded-2xl' : '';
    $label = $src !== '' ? basename(parse_url($src, PHP_URL_PATH) ?: $src) : 'image';
    $alt = $o['alt'] ?? ($src !== '' ? image_alt($src) : '');

    ob_start(); ?>
<div class="overflow-hidden bg-cream-deep <?= e($box) ?> <?= $radius ?> <?= e($o['class'] ?? '') ?>">
  <div class="absolute inset-0 flex items-center justify-center">
    <svg aria-hidden="true" class="absolute inset-0 h-full w-full" preserveAspectRatio="none" viewBox="0 0 400 300">
      <defs>
        <linearGradient id="sky-<?= $n ?>" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#f7efe1" /><stop offset="100%" stop-color="#efe3cd" /></linearGradient>
        <linearGradient id="ground-<?= $n ?>" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#e6d7ba" /><stop offset="100%" stop-color="#dbc8a4" /></linearGradient>
      </defs>
      <rect width="400" height="300" fill="url(#sky-<?= $n ?>)" />
      <path d="M0 196 C 70 182, 118 202, 186 192 C 252 182, 310 200, 400 188 L400 300 L0 300 Z" fill="url(#ground-<?= $n ?>)" />
      <path d="M0 196 C 70 182, 118 202, 186 192 C 252 182, 310 200, 400 188" fill="none" stroke="#cdb894" stroke-width="1" />
    </svg>
    <div class="relative flex flex-col items-center gap-2 px-4 text-center">
      <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="#a8916d" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><rect x="2.5" y="5" width="19" height="14" rx="2.5" /><circle cx="12" cy="12" r="3.25" /><path d="M7.5 5 8.75 2.75h6.5L16.5 5" /></svg>
      <span class="font-mono text-[10px] tracking-[0.14em] text-[#a8916d] uppercase"><?= e($label) ?></span>
    </div>
  </div>
  <?php if ($src !== ''): ?>
  <img src="<?= e($src) ?>" alt="<?= e($alt) ?>" loading="<?= !empty($o['priority']) ? 'eager' : 'lazy' ?>" decoding="async" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] ease-[cubic-bezier(0.22,1,0.36,1)] <?= e($o['imageClass'] ?? '') ?>">
  <?php endif; ?>
</div>
<?php
    return ob_get_clean();
}

/* ---------------------------------------------------------------- Buttons */

function button(string $href, string $label, string $variant = 'primary', string $class = '', bool $arrow = true): string
{
    $styles = [
        'primary' => 'bg-accent text-cream shadow-[0_6px_18px_rgba(200,122,60,0.22)] hover:bg-accent-dark hover:shadow-[0_12px_26px_rgba(200,122,60,0.28)]',
        'outline' => 'border border-ink/20 text-ink hover:border-ink/45 hover:bg-ink/[0.03]',
        'ghost' => 'bg-cream/85 text-ink backdrop-blur-sm hover:bg-cream',
    ];
    // Off-site buttons (the WCN and PayPal donation pages) open in a new tab.
    $external = preg_match('~^https?://~', $href) ? ' target="_blank" rel="noreferrer"' : '';
    return '<a href="' . e($href) . '"' . $external . ' class="inline-flex items-center justify-center gap-2 rounded-full px-7 py-3.5 text-[0.9375rem] font-medium transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:-translate-y-0.5 '
        . $styles[$variant] . ' ' . e($class) . '">' . e($label) . ($arrow ? icon('arrow-right', 'h-4 w-4') : '') . '</a>';
}

function arrow_link(string $href, string $label, string $class = ''): string
{
    return '<a href="' . e($href) . '" class="link-arrow ' . e($class) . '">' . e($label) . icon('arrow-right', 'h-4 w-4') . '</a>';
}

/**
 * "See what your donation can support ↓" — the same accent link as above, but
 * pointing further down the page it is already on. Every hero can carry one
 * (hero.jumpLabel / hero.jumpHref), and so can a block on a built page.
 */
function jump_link(string $href, string $label, string $class = ''): string
{
    return '<a href="' . e($href) . '" class="link-arrow link-arrow-down ' . e($class) . '">' . e($label) . icon('arrow-down', 'h-4 w-4') . '</a>';
}

function eyebrow_rule(string $text, string $rule = 'bg-sand-deep'): string
{
    return '<p class="eyebrow flex items-center gap-3"><span class="h-px w-8 ' . $rule . '"></span>' . e($text) . '</p>';
}

function tag(string $text): string
{
    return '<span class="inline-flex items-center rounded-full bg-accent-soft px-3 py-1 text-[0.7rem] font-medium tracking-[0.08em] text-accent-dark uppercase">' . e($text) . '</span>';
}

/* ---------------------------------------------------------- Section heading */

function section_heading(string $eyebrow, string $title, string $intro = '', string $align = 'left', string $class = ''): string
{
    $centred = $align === 'center';
    ob_start(); ?>
<div <?= reveal(($centred ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl') . ' ' . $class) ?>>
  <?php if ($eyebrow !== ''): ?>
  <p class="eyebrow flex items-center gap-3"><?php if (!$centred): ?><span class="h-px w-8 bg-sand-deep"></span><?php endif; ?><?= e($eyebrow) ?></p>
  <?php endif; ?>
  <h2 class="mt-4 text-3xl leading-[1.15] md:text-[2.6rem]"><?= e($title) ?></h2>
  <?php if ($intro !== ''): ?><p class="mt-5 text-[1.0625rem] leading-relaxed text-body"><?= e($intro) ?></p><?php endif; ?>
</div>
<?php
    return ob_get_clean();
}

/* ----------------------------------------------------------------- Fact list
   Icon + bold label + one line, sitting directly on the background. */

function fact_list(array $facts, int $columns = 3, string $class = ''): string
{
    $grid = [
        1 => 'grid-cols-1 gap-8',
        2 => 'grid-cols-1 gap-10 sm:grid-cols-2 sm:gap-12',
        3 => 'grid-cols-1 gap-10 sm:grid-cols-3 sm:gap-12',
        4 => 'grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4 sm:gap-12',
    ][$columns];

    ob_start(); ?>
<div class="grid <?= $grid ?> <?= e($class) ?>">
  <?php foreach (array_values($facts) as $i => $fact):
      $href = trim((string) ($fact['href'] ?? ''));
      $inner = icon((string) ($fact['icon'] ?? 'leaf'), 'h-7 w-7 text-accent', 1.2)
          . '<h3 class="mt-4 flex items-center gap-1.5 text-[1.0625rem] font-medium text-ink"><span class="font-sans">' . e($fact['title'] ?? '') . '</span>'
          . ($href !== '' ? icon('chevron-right', 'h-4 w-4 text-accent transition-transform duration-300 group-hover:translate-x-1') : '')
          . '</h3><p class="mt-2 text-[0.9375rem] leading-relaxed text-body">' . e($fact['body'] ?? '') . '</p>'; ?>
  <div <?= reveal('', $i * 110) ?>>
    <?php if ($href !== ''): ?><a href="<?= e($href) ?>" class="group block"><?= $inner ?></a><?php else: ?><div><?= $inner ?></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php
    return ob_get_clean();
}

/* ----------------------------------------------------------------- Fact grid */

function fact_grid(array $facts, string $class = ''): string
{
    ob_start(); ?>
<div class="grid grid-cols-1 gap-x-10 gap-y-7 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 <?= e($class) ?>">
  <?php foreach (array_values($facts) as $i => $fact): ?>
  <div <?= reveal('flex gap-4 border-t border-hairline pt-5', ($i % 4) * 80) ?>>
    <?= icon((string) ($fact['icon'] ?? 'leaf'), 'mt-1 h-6 w-6 shrink-0 text-accent', 1.2) ?>
    <div>
      <p class="eyebrow"><?= e($fact['label'] ?? '') ?></p>
      <p class="mt-2 text-[1.0625rem] leading-snug text-ink"><?= e($fact['value'] ?? '') ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------- Related links */

function related_links(array $related): string
{
    $links = array_filter($related['links'] ?? [], fn ($l) => trim((string) ($l['label'] ?? '')) !== '');
    if (!$links) {
        return '';
    }
    ob_start(); ?>
<section class="section-tight border-t border-hairline">
  <div class="shell">
    <div <?= reveal() ?>>
      <p class="eyebrow"><?= e(($related['title'] ?? '') ?: 'You may also be interested in') ?></p>
      <div class="mt-5 flex flex-wrap gap-x-10 gap-y-4">
        <?php foreach ($links as $link): ?><?= arrow_link((string) $link['href'], (string) $link['label']) ?><?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------ Page hero
   overlay — text over the photograph with a soft cream scrim
   split   — text and photograph side by side
   plain   — no photograph at all */

function page_hero(array $hero, string $actions = ''): string
{
    $eyebrow = (string) ($hero['eyebrow'] ?? '');
    $title = (string) ($hero['title'] ?? '');
    $intro = (string) ($hero['intro'] ?? '');
    $image = trim((string) ($hero['image'] ?? ''));
    $variant = $hero['variant'] ?? 'overlay';
    if ($image === '') {
        $variant = 'plain';
    }

    $eyebrowHtml = $eyebrow !== '' ? eyebrow_rule($eyebrow) : '';
    $actionsHtml = $actions !== '' ? '<div class="mt-9 flex flex-wrap gap-3">' . $actions . '</div>' : '';
    $jumpLabel = trim((string) ($hero['jumpLabel'] ?? ''));
    $jumpHtml = $jumpLabel !== ''
        ? '<div class="' . ($actionsHtml !== '' ? 'mt-5' : 'mt-9') . '">' . jump_link((string) ($hero['jumpHref'] ?? '#'), $jumpLabel) . '</div>'
        : '';

    ob_start();
    if ($variant === 'plain'): ?>
<section class="border-b border-hairline bg-cream-deep">
  <div class="shell py-20 md:py-28">
    <div class="max-w-3xl animate-fade-up">
      <?= $eyebrowHtml ?>
      <h1 class="mt-5 text-[2.6rem] leading-[1.08] md:text-6xl"><?= e($title) ?></h1>
      <?php if ($intro !== ''): ?><p class="mt-6 max-w-xl text-lg leading-relaxed text-body"><?= e($intro) ?></p><?php endif; ?>
      <?= $actionsHtml ?>
      <?= $jumpHtml ?>
    </div>
  </div>
</section>
<?php elseif ($variant === 'split'): ?>
<section class="border-b border-hairline bg-cream-deep">
  <div class="shell py-16 md:py-24">
    <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.05fr] lg:gap-20">
      <div class="animate-fade-up">
        <?= $eyebrowHtml ?>
        <h1 class="mt-5 text-[2.5rem] leading-[1.08] md:text-[3.5rem]"><?= e($title) ?></h1>
        <?php if ($intro !== ''): ?><p class="mt-6 max-w-xl text-lg leading-relaxed text-body"><?= e($intro) ?></p><?php endif; ?>
        <?= $actionsHtml ?>
      <?= $jumpHtml ?>
      </div>
      <div class="animate-fade-up" style="animation-delay: 160ms">
        <?= media($image, ['ratio' => 'landscape', 'priority' => true, 'class' => 'shadow-[0_24px_60px_rgba(84,63,38,0.10)]']) ?>
      </div>
    </div>
  </div>
</section>
<?php else: ?>
<section class="relative border-b border-hairline">
  <div class="absolute inset-0"><?= media($image, ['rounded' => false, 'priority' => true, 'fill' => true]) ?></div>
  <div class="absolute inset-0 bg-gradient-to-r from-cream from-20% via-cream/70 via-50% to-transparent"></div>
  <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-cream/45 to-transparent"></div>
  <div class="shell relative py-20 md:py-28 lg:py-36">
    <div class="max-w-xl animate-fade-up">
      <?= $eyebrowHtml ?>
      <h1 class="mt-5 text-[2.6rem] leading-[1.06] md:text-6xl"><?= e($title) ?></h1>
      <?php if ($intro !== ''): ?><p class="mt-6 text-lg leading-relaxed text-body"><?= e($intro) ?></p><?php endif; ?>
      <?= $actionsHtml ?>
      <?= $jumpHtml ?>
    </div>
  </div>
</section>
<?php endif;
    return ob_get_clean();
}

/* ------------------------------------------------------------ Photo + text */

function paragraphs(array $paragraphs, string $class = ''): string
{
    $out = '';
    foreach ($paragraphs as $p) {
        if (trim((string) $p) !== '') {
            $out .= '<p' . ($class ? ' class="' . $class . '"' : '') . '>' . e($p) . '</p>';
        }
    }
    return $out;
}

function photo_text(array $o, string $body, string $footer = '', string $imageFooter = ''): string
{
    $right = ($o['imageSide'] ?? 'left') === 'right';
    $align = ($o['align'] ?? 'center') === 'start' ? 'items-start' : 'items-center';
    ob_start(); ?>
<div class="grid gap-12 lg:grid-cols-2 lg:gap-20 <?= $align ?>">
  <div <?= reveal('group ' . ($right ? 'lg:order-2' : '')) ?>>
    <?= media($o['image'] ?? '', ['class' => 'shadow-[0_24px_60px_rgba(84,63,38,0.09)]', 'imageClass' => 'group-hover:scale-[1.03]']) ?>
    <?php if ($imageFooter !== ''): ?><div class="mt-8"><?= $imageFooter ?></div><?php endif; ?>
  </div>
  <div <?= reveal($right ? 'lg:order-1' : '', 120) ?>>
    <?php if (($o['eyebrow'] ?? '') !== ''): ?><?= eyebrow_rule((string) $o['eyebrow']) ?><?php endif; ?>
    <h2 class="mt-4 text-3xl leading-[1.15] md:text-[2.5rem]"><?= e($o['title'] ?? '') ?></h2>
    <div class="mt-6 space-y-4 text-[1.0625rem] leading-[1.8] text-body"><?= $body ?></div>
    <?php if ($footer !== ''): ?><div class="mt-8"><?= $footer ?></div><?php endif; ?>
  </div>
</div>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------ CTA band
   The recurring donation prompt, with drawn migration line art behind it. */

function cta_band(array $overrides = []): string
{
    $c = array_merge(site('ctaBand', []), array_filter($overrides, fn ($v) => $v !== null && $v !== ''));
    ob_start(); ?>
<section class="relative overflow-hidden bg-accent-soft">
  <svg aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full text-accent" viewBox="0 0 1440 320" preserveAspectRatio="none" fill="none">
    <g stroke="currentColor" stroke-linecap="round" opacity="0.22">
      <path d="M0 236 C 240 222, 420 250, 700 238 C 980 226, 1180 252, 1440 240" stroke-width="1.5" />
      <path d="M-20 190 C 220 118, 430 214, 690 160 C 950 106, 1160 196, 1460 138" stroke-width="1.5" stroke-dasharray="2 10" />
    </g>
    <g fill="currentColor" opacity="0.3"><circle cx="220" cy="150" r="3.5" /><circle cx="690" cy="160" r="3.5" /><circle cx="1160" cy="171" r="3.5" /></g>
    <g stroke="currentColor" stroke-width="1.4" stroke-linecap="round" opacity="0.28">
      <path d="M96 236c0-11-4-18-9-23M96 236c0-11 4-18 9-23M96 236v-16" />
      <path d="M352 244c0-11-4-18-9-23M352 244c0-11 4-18 9-23M352 244v-16" />
      <path d="M604 238c0-9-3-15-8-20M604 238c0-9 4-15 9-20" />
      <path d="M1012 240c0-11-4-18-9-23M1012 240c0-11 4-18 9-23M1012 240v-16" />
      <path d="M1288 242c0-9-3-15-8-20M1288 242c0-9 4-15 9-20" />
    </g>
  </svg>
  <div class="shell relative py-16 md:py-24">
    <div <?= reveal('flex flex-col gap-10 lg:flex-row lg:items-center lg:justify-between lg:gap-20') ?>>
      <div class="max-w-xl">
        <?= eyebrow_rule((string) ($c['eyebrow'] ?? ''), 'bg-accent/40') ?>
        <h2 class="mt-4 text-3xl leading-tight md:text-[2.75rem]"><?= e($c['title'] ?? '') ?></h2>
        <p class="mt-4 text-[1.0625rem] leading-relaxed text-body"><?= e($c['body'] ?? '') ?></p>
      </div>
      <div class="shrink-0 lg:text-right">
        <?= button((string) ($c['ctaHref'] ?? ''), (string) ($c['ctaLabel'] ?? '')) ?>
        <p class="mt-3.5 text-[0.8rem] text-muted"><?= e($c['note'] ?? '') ?></p>
      </div>
    </div>
  </div>
</section>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------- Cards */

function story_card(array $item, int $delay = 0): string
{
    $category = (string) ($item['category'] ?? '');
    $date = (string) ($item['date'] ?? '');
    ob_start(); ?>
<div <?= reveal('h-full', $delay) ?>>
  <a href="<?= e($item['href']) ?>" class="group flex h-full flex-col">
    <?= media($item['image'] ?? '', ['ratio' => 'landscape', 'imageClass' => 'group-hover:scale-[1.04]', 'class' => 'transition-shadow duration-500 group-hover:shadow-[0_20px_44px_rgba(84,63,38,0.12)]']) ?>
    <div class="mt-5 flex flex-1 flex-col">
      <?php if ($category !== '' || $date !== ''): ?>
      <div class="flex flex-wrap items-center gap-3">
        <?php if ($category !== ''): ?><?= tag($category) ?><?php endif; ?>
        <?php if ($date !== ''): ?><span class="text-[0.8rem] text-muted"><?= e($date) ?></span><?php endif; ?>
      </div>
      <?php endif; ?>
      <h3 class="mt-3 text-xl leading-snug transition-colors duration-300 group-hover:text-accent-dark"><?= e($item['title'] ?? '') ?></h3>
      <p class="mt-2.5 text-[0.9375rem] leading-relaxed text-body"><?= e($item['excerpt'] ?? '') ?></p>
      <?php if (!empty($item['meta'])): ?><p class="mt-3 text-[0.8rem] text-muted"><?= e(implode('  ·  ', array_filter($item['meta']))) ?></p><?php endif; ?>
      <span class="link-arrow mt-auto pt-5"><?= e(site('labels.readMore', 'Read more')) ?><?= icon('arrow-right', 'h-4 w-4') ?></span>
    </div>
  </a>
</div>
<?php
    return ob_get_clean();
}

/**
 * The story card without the photograph. News and updates carry no photos
 * anywhere on the site (client request): the articles moved over from the old
 * website have none, so a rule above each item gives the structure instead.
 */
function story_text_card(array $item, int $delay = 0): string
{
    ob_start(); ?>
<div <?= reveal('h-full', $delay) ?>>
  <a href="<?= e($item['href']) ?>" class="group flex h-full flex-col border-t border-hairline pt-6">
    <div class="flex flex-wrap items-center gap-3">
      <?php if (($item['category'] ?? '') !== ''): ?><?= tag((string) $item['category']) ?><?php endif; ?>
      <?php if (($item['date'] ?? '') !== ''): ?><span class="text-[0.8rem] text-muted"><?= e($item['date']) ?></span><?php endif; ?>
    </div>
    <h3 class="mt-4 text-[1.4rem] leading-snug transition-colors duration-300 group-hover:text-accent-dark"><?= e($item['title'] ?? '') ?></h3>
    <p class="mt-3 text-[0.9375rem] leading-relaxed text-body"><?= e($item['excerpt'] ?? '') ?></p>
    <span class="link-arrow mt-auto pt-6"><?= e(site('labels.readMore', 'Read more')) ?><?= icon('arrow-right', 'h-4 w-4') ?></span>
  </a>
</div>
<?php
    return ob_get_clean();
}

function story_list(array $items): string
{
    $cards = '';
    foreach (array_values($items) as $i => $item) {
        $cards .= story_text_card($item, $i * 110);
    }
    return '<div class="grid gap-x-12 gap-y-10 md:grid-cols-3">' . $cards . '</div>';
}

function card_grid(string $cards): string
{
    return '<div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-x-12 lg:gap-y-16">' . $cards . '</div>';
}

/** Card data for a project entry. */
function project_card(array $p, string $category, array $meta): array
{
    return ['title' => $p['title'] ?? '', 'href' => $p['url'], 'image' => $p['image'] ?? '', 'category' => $category, 'excerpt' => $p['excerpt'] ?? '', 'meta' => $meta];
}

/** Card data for a news entry. */
function article_card(array $a): array
{
    return ['title' => $a['title'] ?? '', 'href' => $a['url'], 'image' => $a['image'] ?? '', 'category' => $a['category'] ?? '', 'date' => format_date($a['date'] ?? ''), 'excerpt' => $a['excerpt'] ?? ''];
}

function person_card(array $person, int $delay = 0): string
{
    ob_start(); ?>
<div <?= reveal('', $delay) ?>>
  <div class="group">
    <?= media($person['image'] ?? '', ['ratio' => 'portrait', 'imageClass' => 'group-hover:scale-[1.03]']) ?>
    <h3 class="mt-5 text-lg"><?= e($person['name'] ?? '') ?></h3>
    <p class="mt-1 text-[0.9rem] text-accent-dark"><?= e($person['role'] ?? '') ?></p>
    <?php if (($person['note'] ?? '') !== ''): ?><p class="mt-2 text-[0.9rem] leading-relaxed text-muted"><?= e($person['note']) ?></p><?php endif; ?>
  </div>
</div>
<?php
    return ob_get_clean();
}

function strand_card(array $s, int $delay = 0): string
{
    $stat = $s['stat'] ?? null;
    ob_start(); ?>
<div <?= reveal('', $delay) ?>>
  <div class="group flex h-full flex-col">
    <a href="<?= e($s['href']) ?>"><?= media($s['image'] ?? '', ['ratio' => 'landscape', 'imageClass' => 'group-hover:scale-[1.04]', 'class' => 'transition-shadow duration-500 group-hover:shadow-[0_20px_44px_rgba(84,63,38,0.12)]']) ?></a>
    <h3 class="mt-6 text-2xl leading-snug"><a href="<?= e($s['href']) ?>" class="transition-colors duration-300 hover:text-accent-dark"><?= e($s['title'] ?? '') ?></a></h3>
    <p class="mt-3 flex-1 text-[0.9375rem] leading-relaxed text-body"><?= e($s['body'] ?? '') ?></p>
    <?php if ($stat && ($stat['value'] ?? '') !== ''): ?>
    <div class="mt-7 flex items-center gap-4">
      <?= icon((string) ($stat['icon'] ?? 'leaf'), 'h-7 w-7 shrink-0 text-accent', 1.2) ?>
      <div>
        <p class="font-display text-[1.375rem] leading-none text-accent-dark"><?= e($stat['value']) ?></p>
        <p class="mt-1.5 text-[0.8rem] leading-snug text-muted"><?= e($stat['label'] ?? '') ?></p>
      </div>
    </div>
    <?php endif; ?>
    <?= arrow_link((string) $s['href'], (string) site('labels.learnMore', 'Learn more'), 'mt-6') ?>
  </div>
</div>
<?php
    return ob_get_clean();
}

function resource_link(array $r, int $delay = 0): string
{
    $href = (string) ($r['href'] ?? '#');
    ob_start(); ?>
<div <?= reveal('', $delay) ?>>
  <a href="<?= e($href) ?>"<?= preg_match('~^https?://~', $href) ? ' target="_blank" rel="noreferrer"' : '' ?> class="group flex items-start gap-6 border-b border-hairline py-8 transition-colors duration-300 hover:border-accent/40">
    <?= icon((string) ($r['icon'] ?? 'document'), 'mt-1 h-7 w-7 shrink-0 text-accent', 1.2) ?>
    <div class="flex-1">
      <h3 class="text-xl transition-colors duration-300 group-hover:text-accent-dark"><?= e($r['title'] ?? '') ?></h3>
      <p class="mt-2 max-w-2xl text-[0.9375rem] leading-relaxed text-body"><?= e($r['body'] ?? '') ?></p>
    </div>
    <?= icon('external', 'mt-2 h-5 w-5 shrink-0 text-muted transition-all duration-300 group-hover:-translate-y-0.5 group-hover:text-accent') ?>
  </a>
</div>
<?php
    return ob_get_clean();
}

/**
 * Files attached to an entry — project reports and the like. Optional: an item
 * without both a title and a link is skipped, and an empty list renders nothing.
 */
function document_links(array $documents): string
{
    $items = array_values(array_filter($documents, fn ($d) => is_array($d)
        && trim((string) ($d['title'] ?? '')) !== '' && trim((string) ($d['href'] ?? '')) !== ''));
    if (!$items) {
        return '';
    }
    ob_start(); ?>
<ul class="max-w-3xl border-t border-hairline">
  <?php foreach ($items as $i => $doc):
      $href = (string) $doc['href'];
      $external = (bool) preg_match('~^https?://~', $href);
      $ext = strtoupper((string) pathinfo((string) parse_url($href, PHP_URL_PATH), PATHINFO_EXTENSION)); ?>
  <li <?= reveal('border-b border-hairline', $i * 70) ?>>
    <a href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noreferrer"' : ' download' ?> class="group flex items-center gap-5 py-5 transition-colors duration-300">
      <?= icon('document', 'h-6 w-6 shrink-0 text-accent', 1.2) ?>
      <span class="flex-1 text-[1.0625rem] leading-snug text-ink transition-colors duration-300 group-hover:text-accent-dark"><?= e($doc['title']) ?></span>
      <?php if ($ext !== ''): ?><span class="shrink-0 text-[0.7rem] font-medium tracking-[0.1em] text-muted uppercase"><?= e($ext) ?></span><?php endif; ?>
      <?= icon($external ? 'external' : 'arrow-down', 'h-5 w-5 shrink-0 text-muted transition-all duration-300 group-hover:text-accent' . ($external ? ' group-hover:-translate-y-0.5' : ' group-hover:translate-y-0.5')) ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------- Chart
   A single editable line series (the global saiga population over time).
   Inline SVG rather than a charting library: it stays sharp, uses the site
   palette and costs nothing to load. Axis range, gridlines and ticks are
   derived from the data, so the chart keeps working when SCA edit the points
   in the admin. The tooltip is wired up in assets/js/app.js. */

/** A readable gridline step (1, 2, 2.5 or 5 × a power of ten) for a range. */
function chart_step(float $span, int $target = 5): float
{
    $raw = $span / max(1, $target);
    $magnitude = pow(10, floor(log10(max($raw, 1e-9))));
    foreach ([1, 2, 2.5, 5] as $factor) {
        if ($raw <= $factor * $magnitude) {
            return $factor * $magnitude;
        }
    }
    return 10 * $magnitude;
}

/** 2889440 → "2.9M": short enough for an axis label. */
function chart_short_number(float $n): string
{
    foreach ([1000000 => 'M', 1000 => 'k'] as $unit => $suffix) {
        if ($n >= $unit) {
            return rtrim(rtrim(number_format($n / $unit, 1, '.', ''), '0'), '.') . $suffix;
        }
    }
    return (string) round($n);
}

/**
 * @param array $points [['year' => '1981', 'value' => '1251000'], …]
 * @param array $o      xLabel, yLabel, title (used for the screen-reader table)
 */
function line_chart(array $points, array $o = []): string
{
    $data = [];
    foreach ($points as $point) {
        $year = trim((string) (is_array($point) ? ($point['year'] ?? '') : ''));
        $value = preg_replace('/[^0-9.]/', '', (string) (is_array($point) ? ($point['value'] ?? '') : ''));
        if ($year === '' || !is_numeric($year) || $value === '' || !is_numeric($value)) {
            continue;
        }
        $data[] = ['x' => (float) $year, 'y' => (float) $value];
    }
    if (count($data) < 2) {
        return '';
    }
    usort($data, fn ($a, $b) => $a['x'] <=> $b['x']);

    $width = 920;
    $height = 460;
    $left = 84;
    $right = 26;
    $top = 28;
    $bottom = 64;
    $plotW = $width - $left - $right;
    $plotH = $height - $top - $bottom;

    $xMin = $data[0]['x'];
    $xMax = $data[count($data) - 1]['x'];
    $yMax = max(array_column($data, 'y'));
    $gridStep = chart_step($yMax, 5);
    $yTop = ceil($yMax / $gridStep) * $gridStep;

    $px = fn (float $x): float => $left + ($xMax > $xMin ? ($x - $xMin) / ($xMax - $xMin) : 0.5) * $plotW;
    $py = fn (float $y): float => $top + $plotH - ($yTop > 0 ? min(1, $y / $yTop) : 0) * $plotH;

    // Catmull-Rom through every point, converted to cubic Béziers. The tension
    // is held back and the handles are clamped inside the plot so a steep step
    // (2020 → 2024) curves without bulging off the chart.
    $pts = array_map(fn ($d) => ['x' => $px($d['x']), 'y' => $py($d['y'])], $data);
    $line = 'M' . round($pts[0]['x'], 1) . ' ' . round($pts[0]['y'], 1);
    $clamp = fn (float $v, float $lo, float $hi): float => max($lo, min($hi, $v));
    for ($i = 0; $i < count($pts) - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)];
        $p1 = $pts[$i];
        $p2 = $pts[$i + 1];
        $p3 = $pts[min(count($pts) - 1, $i + 2)];
        $c1x = $clamp($p1['x'] + ($p2['x'] - $p0['x']) / 6 * 0.75, $p1['x'], $p2['x']);
        $c1y = $clamp($p1['y'] + ($p2['y'] - $p0['y']) / 6 * 0.75, $top, $top + $plotH);
        $c2x = $clamp($p2['x'] - ($p3['x'] - $p1['x']) / 6 * 0.75, $p1['x'], $p2['x']);
        $c2y = $clamp($p2['y'] - ($p3['y'] - $p1['y']) / 6 * 0.75, $top, $top + $plotH);
        $line .= ' C' . round($c1x, 1) . ' ' . round($c1y, 1) . ', ' . round($c2x, 1) . ' ' . round($c2y, 1)
            . ', ' . round($p2['x'], 1) . ' ' . round($p2['y'], 1);
    }
    $area = $line . ' L' . round($pts[count($pts) - 1]['x'], 1) . ' ' . ($top + $plotH)
        . ' L' . round($pts[0]['x'], 1) . ' ' . ($top + $plotH) . ' Z';

    $yTicks = [];
    for ($value = 0.0; $value <= $yTop + 0.5; $value += $gridStep) {
        $yTicks[] = $value;
    }
    // Decades, plus the first and last year actually in the data.
    $xTicks = [$xMin, $xMax];
    for ($year = ceil($xMin / 10) * 10; $year <= $xMax; $year += 10) {
        if (abs($year - $xMin) > 3 && abs($year - $xMax) > 3) {
            $xTicks[] = $year;
        }
    }
    sort($xTicks);

    $id = 'chart-' . bin2hex(random_bytes(4));
    $xLabel = (string) ($o['xLabel'] ?? '');
    $yLabel = (string) ($o['yLabel'] ?? '');

    ob_start(); ?>
<div class="relative" data-chart>
  <svg viewBox="0 0 <?= $width ?> <?= $height ?>" class="w-full" role="img" aria-labelledby="<?= $id ?>-title" preserveAspectRatio="xMidYMid meet">
    <title id="<?= $id ?>-title"><?= e($o['title'] ?? 'Line chart') ?></title>
    <defs>
      <linearGradient id="<?= $id ?>-fill" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="currentColor" stop-opacity="0.16" />
        <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
      </linearGradient>
    </defs>

    <g class="text-accent">
      <?php foreach ($yTicks as $value): $y = round($py((float) $value), 1); ?>
      <line x1="<?= $left ?>" y1="<?= $y ?>" x2="<?= $left + $plotW ?>" y2="<?= $y ?>" class="stroke-hairline" stroke-width="1" <?= $value > 0 ? 'stroke-dasharray="3 7"' : '' ?> />
      <text x="<?= $left - 14 ?>" y="<?= $y + 4 ?>" text-anchor="end" class="fill-muted text-[13px]"><?= e(chart_short_number((float) $value)) ?></text>
      <?php endforeach; ?>

      <?php foreach ($xTicks as $year): ?>
      <text x="<?= round($px((float) $year), 1) ?>" y="<?= $top + $plotH + 30 ?>" text-anchor="middle" class="fill-muted text-[13px]"><?= e((string) (int) $year) ?></text>
      <?php endforeach; ?>

      <path d="<?= e($area) ?>" fill="url(#<?= $id ?>-fill)" />
      <path d="<?= e($line) ?>" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

      <?php foreach ($data as $i => $d): $x = round($px($d['x']), 1); $y = round($py($d['y']), 1); ?>
      <g data-chart-point data-year="<?= e((string) (int) $d['x']) ?>" data-value="<?= e(number_format($d['y'], 0, '.', ',')) ?>" tabindex="0" class="focus:outline-none">
        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="16" fill="transparent" />
        <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4.5" fill="currentColor" stroke="var(--color-cream-deep)" stroke-width="2" class="transition-[r] duration-200" data-chart-dot />
      </g>
      <?php endforeach; ?>

      <?php if ($xLabel !== ''): ?>
      <text x="<?= $left + $plotW / 2 ?>" y="<?= $height - 8 ?>" text-anchor="middle" class="fill-muted text-[13px] tracking-[0.12em] uppercase"><?= e($xLabel) ?></text>
      <?php endif; ?>
      <?php if ($yLabel !== ''): ?>
      <text transform="translate(20 <?= $top + $plotH / 2 ?>) rotate(-90)" text-anchor="middle" class="fill-muted text-[13px] tracking-[0.12em] uppercase"><?= e($yLabel) ?></text>
      <?php endif; ?>
    </g>
  </svg>

  <div class="pointer-events-none absolute top-0 left-0 hidden -translate-x-1/2 -translate-y-full rounded-xl border border-hairline bg-cream px-3.5 py-2 text-center whitespace-nowrap shadow-[0_12px_30px_rgba(84,63,38,0.14)]" role="status" data-chart-tooltip>
    <span class="block font-display text-[1.05rem] leading-none text-ink" data-chart-value></span>
    <span class="mt-1.5 block text-[0.7rem] tracking-[0.12em] text-muted uppercase" data-chart-year></span>
  </div>

  <table class="sr-only">
    <caption><?= e($o['title'] ?? '') ?></caption>
    <thead><tr><th><?= e($xLabel ?: 'Year') ?></th><th><?= e($yLabel ?: 'Value') ?></th></tr></thead>
    <tbody>
      <?php foreach ($data as $d): ?><tr><td><?= e((string) (int) $d['x']) ?></td><td><?= e(number_format($d['y'], 0, '.', ',')) ?></td></tr><?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
    return ob_get_clean();
}

function callout(string $title, string $body): string
{
    return '<div ' . reveal('rounded-3xl bg-sand/60 p-8 md:p-12') . '><h3 class="text-2xl">' . e($title)
        . '</h3><div class="mt-4 space-y-3 text-[1rem] leading-relaxed text-body">' . $body . '</div></div>';
}

/* -------------------------------------------------------------- Number band
   Three equal cards on the beige strip; only the figure counts up. */

function number_band(string $eyebrow, array $stats, string $tone = 'sand'): string
{
    ob_start(); ?>
<section class="<?= $tone === 'sand' ? 'bg-sand/70' : 'bg-cream-deep' ?>">
  <div class="shell py-14 md:py-20">
    <?php if ($eyebrow !== ''): ?><?= eyebrow_rule($eyebrow) ?><?php endif; ?>
    <div class="grid gap-5 md:grid-cols-3 md:gap-6 <?= $eyebrow !== '' ? 'mt-8 md:mt-10' : '' ?>">
      <?php foreach (array_values($stats) as $i => $stat): ?>
      <div class="rounded-2xl bg-cream px-8 py-9 md:px-9 md:py-11">
        <p class="flex flex-wrap items-baseline gap-x-3">
          <span class="relative font-display text-[3.25rem] leading-none text-ink md:text-[4rem]" data-countup="<?= e($stat['value'] ?? '') ?>" data-delay="<?= $i * 120 ?>">
            <span aria-hidden="true" class="invisible"><?= e($stat['value'] ?? '') ?></span>
            <span class="absolute inset-0" data-countup-text><?= e($stat['value'] ?? '') ?></span>
          </span>
          <span class="font-display text-[1.35rem] leading-none text-accent-dark md:text-[1.5rem]"><?= e($stat['unit'] ?? '') ?></span>
        </p>
        <p class="mt-4 text-[1.0625rem] leading-relaxed text-body"><?= e($stat['label'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
    return ob_get_clean();
}

/* ----------------------------------------------------------------- Timeline
   Shared by Our Story (newest first, with photos) and the population history
   (forwards, no photos). */

function timeline(array $items, string $order = 'asc', bool $photos = true): string
{
    $items = array_values($items);
    if ($order === 'desc') {
        $items = array_reverse($items);
    }
    ob_start(); ?>
<div class="relative mx-auto max-w-4xl">
  <span aria-hidden="true" class="absolute top-2 bottom-2 left-[7px] w-px bg-hairline md:left-1/2 md:-translate-x-1/2"></span>
  <ol class="space-y-20 md:space-y-28">
    <?php foreach ($items as $i => $m):
        $alignRight = $i % 2 === 1;
        $hasFeature = trim((string) ($m['featureLabel'] ?? '')) !== ''; ?>
    <li class="relative">
      <span aria-hidden="true" class="absolute top-2.5 left-0 h-3.5 w-3.5 rounded-full border-2 border-cream bg-accent md:left-1/2 md:-translate-x-1/2"></span>
      <div <?= reveal('relative pl-10 md:w-1/2 md:pl-0 ' . ($alignRight ? 'md:ml-auto md:pl-16' : 'md:pr-16 md:text-right')) ?>>
        <?php if (!empty($m['celebrate'])): ?>
        <svg aria-hidden="true" viewBox="0 0 160 120" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.4" class="pointer-events-none absolute -top-12 hidden h-28 w-36 text-accent opacity-40 md:block <?= $alignRight ? '-left-6' : '-right-6' ?>">
          <g><path d="M40 54v-14M40 54l-12-8M40 54l12-8M40 54l-9 10M40 54l9 10" /><path d="M32 30c1.5-4 5-6 9-5.5" /></g>
          <g opacity="0.85"><path d="M104 40v-9M104 40l-8-5M104 40l8-5M104 40l-6 7M104 40l6 7" /></g>
          <g opacity="0.7"><path d="M74 84v-7M74 84l-6-4M74 84l6-4" /><circle cx="126" cy="72" r="2" /><circle cx="58" cy="26" r="1.6" /><circle cx="92" cy="96" r="1.6" /></g>
        </svg>
        <?php endif; ?>
        <p class="relative font-display text-2xl text-accent-dark"><?= e($m['year'] ?? '') ?></p>
        <h2 class="mt-2 text-2xl"><?= e($m['title'] ?? '') ?></h2>
        <p class="mt-3 text-[1rem] leading-relaxed text-body"><?= e($m['body'] ?? '') ?></p>
        <?php if ($hasFeature): ?>
        <a href="<?= e($m['featureHref'] ?? '#') ?>" class="group mt-7 flex items-center gap-5 rounded-2xl bg-accent-soft/70 p-4 transition-colors duration-300 hover:bg-accent-soft md:p-5 <?= $alignRight ? '' : 'md:flex-row-reverse md:text-right' ?>">
          <div class="w-24 shrink-0 md:w-28"><?= media($m['featureImage'] ?? '', ['ratio' => 'portrait', 'class' => 'shadow-[0_10px_26px_rgba(84,63,38,0.14)]', 'imageClass' => 'group-hover:scale-[1.03]']) ?></div>
          <span class="flex-1">
            <span class="block text-[1rem] leading-snug text-ink"><?= e($m['featureLabel']) ?></span>
            <span class="link-arrow mt-2.5"><?= e(site('labels.readReport', 'Read the report')) ?><?= icon('arrow-right', 'h-4 w-4') ?></span>
          </span>
        </a>
        <?php endif; ?>
        <?php if ($photos && trim((string) ($m['image'] ?? '')) !== ''): ?>
        <?= media($m['image'], ['ratio' => 'landscape', 'class' => 'mt-7 shadow-[0_18px_44px_rgba(84,63,38,0.09)]']) ?>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
</div>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------ Year accordion
   Optional year-by-year detail on a project; the most recent year opens. */

function year_accordion(array $entries): string
{
    $entries = array_reverse(array_values($entries));
    ob_start(); ?>
<div class="border-t border-hairline" data-accordion>
  <?php foreach ($entries as $i => $entry): $open = $i === 0; ?>
  <div <?= reveal('border-b border-hairline', $i * 70) ?> data-accordion-item>
    <h3>
      <button type="button" aria-expanded="<?= $open ? 'true' : 'false' ?>" class="group flex w-full items-baseline gap-6 py-6 text-left" data-accordion-toggle>
        <span class="font-display text-xl text-accent-dark md:text-2xl"><?= e($entry['year'] ?? '') ?></span>
        <span class="flex-1 text-[1.0625rem] text-ink transition-colors duration-300 group-hover:text-accent-dark"><?= e($entry['title'] ?? '') ?></span>
        <?= icon('chevron-down', 'mt-1 h-4 w-4 shrink-0 text-muted transition-transform duration-400 ' . ($open ? 'rotate-180' : '')) ?>
      </button>
    </h3>
    <div class="grid transition-[grid-template-rows] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] <?= $open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]' ?>" data-accordion-panel>
      <div class="overflow-hidden">
        <div class="max-w-2xl space-y-4 pb-8 md:pl-[5.5rem]"><?= paragraphs($entry['body'] ?? [], 'text-[1rem] leading-[1.8] text-body') ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php
    return ob_get_clean();
}

/* ----------------------------------------------------------------- Forms */

function newsletter_form(string $class = '', string $variant = 'default', ?string $note = null): string
{
    $large = $variant === 'large';
    $note = $note ?? (string) site('newsletter.defaultNote');
    $pad = $large ? 'py-4 sm:py-3' : 'py-3.5 sm:py-2.5';
    $id = 'newsletter-' . $variant . '-' . bin2hex(random_bytes(3));
    // Subscriptions go to SCA's Mailchimp list (the same list the old site used).
    $mailchimp = trim((string) site('newsletter.mailchimpAction'));
    ob_start(); ?>
<form action="/forms/newsletter" method="post" class="w-full <?= e($class) ?>" data-ajax-form<?= $mailchimp !== '' ? ' data-mailchimp="' . e($mailchimp) . '"' : '' ?> data-done-class="rounded-full border border-accent/30 bg-accent-soft px-6 <?= $large ? 'py-4' : 'py-3.5' ?> text-center text-[0.925rem] text-ink <?= e($class) ?>" data-done-text="<?= e(site('newsletter.thanks')) ?>">
  <div class="flex w-full flex-col gap-2.5 rounded-2xl sm:flex-row sm:items-center sm:gap-0 sm:rounded-full sm:border sm:border-hairline sm:bg-cream sm:p-1.5 sm:transition-colors sm:duration-300 sm:focus-within:border-accent/50">
    <label for="<?= $id ?>" class="sr-only">Email address</label>
    <input id="<?= $id ?>" type="email" name="email" required placeholder="<?= e(site('newsletter.placeholder')) ?>" class="w-full rounded-full border border-hairline bg-cream px-5 text-[0.95rem] text-ink placeholder:text-muted/80 focus:outline-none sm:border-0 sm:bg-transparent sm:px-5 <?= $pad ?>">
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
    <button type="submit" class="shrink-0 rounded-full bg-accent px-7 font-medium text-cream transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:bg-accent-dark <?= $pad ?> text-[0.925rem]"><?= e(site('newsletter.buttonLabel')) ?></button>
  </div>
  <p class="mt-3 hidden text-xs text-accent-dark" role="alert" data-form-error></p>
  <?php if ($note !== ''): ?><p class="mt-3 text-xs text-muted"><?= e($note) ?></p><?php endif; ?>
</form>
<?php
    return ob_get_clean();
}

function contact_form(string $source = 'contact'): string
{
    $f = site('contactForm', []);
    $field = 'w-full rounded-xl border border-hairline bg-cream px-4 py-3 text-[0.95rem] text-ink transition-colors duration-300 placeholder:text-muted/70 focus:border-accent/50 focus:outline-none';
    ob_start(); ?>
<div data-contact-wrap>
  <form action="/forms/contact" method="post" class="space-y-5" data-ajax-form data-contact>
    <input type="hidden" name="source" value="<?= e($source) ?>">
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
    <div>
      <label for="contact-name-<?= e($source) ?>" class="mb-2 block text-[0.875rem] text-body"><?= e($f['nameLabel'] ?? '') ?></label>
      <input id="contact-name-<?= e($source) ?>" name="name" required class="<?= $field ?>" placeholder="<?= e($f['namePlaceholder'] ?? '') ?>">
    </div>
    <div>
      <label for="contact-email-<?= e($source) ?>" class="mb-2 block text-[0.875rem] text-body"><?= e($f['emailLabel'] ?? '') ?></label>
      <input id="contact-email-<?= e($source) ?>" name="email" type="email" required class="<?= $field ?>" placeholder="<?= e($f['emailPlaceholder'] ?? '') ?>">
    </div>
    <div>
      <label for="contact-message-<?= e($source) ?>" class="mb-2 block text-[0.875rem] text-body"><?= e($f['messageLabel'] ?? '') ?></label>
      <textarea id="contact-message-<?= e($source) ?>" name="message" rows="5" required class="<?= $field ?> resize-none" placeholder="<?= e($f['messagePlaceholder'] ?? '') ?>"></textarea>
    </div>
    <p class="hidden text-[0.875rem] text-accent-dark" role="alert" data-form-error></p>
    <button type="submit" class="rounded-full bg-accent px-8 py-3.5 text-[0.9375rem] font-medium text-cream shadow-[0_6px_18px_rgba(200,122,60,0.22)] transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] hover:-translate-y-0.5 hover:bg-accent-dark"><?= e($f['buttonLabel'] ?? '') ?></button>
  </form>
  <div class="hidden rounded-2xl bg-accent-soft p-8" role="status" data-contact-done>
    <h3 class="text-xl"><?= e($f['thanksTitle'] ?? '') ?></h3>
    <p class="mt-2 text-[0.95rem] leading-relaxed text-body"><?= e($f['thanksBody'] ?? '') ?></p>
  </div>
</div>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------ Archive grid
   Shared filterable card archive for projects and news. Every card is
   rendered; assets/js/app.js filters, pages (six at a time) and applies any
   filter passed in the query string. */

function archive_grid(array $items, array $filters, bool $photos = true): string
{
    ob_start(); ?>
<div data-archive data-page-size="6">
  <div <?= reveal('flex flex-wrap items-center gap-3') ?>>
    <?php foreach ($filters as $filter): ?>
    <div class="relative">
      <label for="filter-<?= e($filter['id']) ?>" class="sr-only"><?= e($filter['label']) ?></label>
      <select id="filter-<?= e($filter['id']) ?>" data-filter="<?= e($filter['id']) ?>" class="cursor-pointer appearance-none rounded-full border border-hairline bg-cream py-2.5 pr-11 pl-5 text-[0.9rem] text-ink transition-colors duration-300 hover:border-accent/50 focus:border-accent focus:outline-none">
        <?php foreach ($filter['options'] as $i => $option): ?><option value="<?= $i === 0 ? '' : e($option) ?>"><?= e($option) ?></option><?php endforeach; ?>
      </select>
      <?= icon('chevron-down', 'pointer-events-none absolute top-1/2 right-4 h-4 w-4 -translate-y-1/2 text-muted') ?>
    </div>
    <?php endforeach; ?>
    <button type="button" data-archive-reset class="hidden rounded-full px-4 py-2.5 text-[0.875rem] text-accent-dark transition-colors duration-300 hover:bg-accent-soft"><?= e(site('labels.clearFilters')) ?></button>
    <span class="ml-auto text-[0.85rem] text-muted" data-archive-count data-one="<?= e(site('labels.result')) ?>" data-many="<?= e(site('labels.results')) ?>"><?= count($items) ?> <?= e(count($items) === 1 ? site('labels.result') : site('labels.results')) ?></span>
  </div>

  <div class="mt-12 md:mt-14">
    <div class="<?= $photos ? 'grid gap-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-x-12 lg:gap-y-16' : 'grid gap-x-12 gap-y-10 md:grid-cols-3' ?>" data-archive-grid>
      <?php foreach (array_values($items) as $i => $item): ?>
      <div data-archive-item data-facets="<?= e(json_encode($item['facets'], JSON_UNESCAPED_UNICODE)) ?>"<?= $i >= 6 ? ' hidden' : '' ?>><?= $photos ? story_card($item, ($i % 3) * 110) : story_text_card($item, ($i % 3) * 110) ?></div>
      <?php endforeach; ?>
    </div>
    <p class="hidden py-16 text-center text-[1rem] text-muted" data-archive-empty><?= e(site('labels.noResults')) ?></p>
  </div>

  <div class="mt-16 flex items-center justify-center gap-6<?= count($items) > 6 ? '' : ' hidden' ?>" data-archive-more>
    <span aria-hidden="true" class="h-px w-16 bg-hairline"></span>
    <button type="button" class="link-arrow"><?= e(site('labels.loadMore')) ?></button>
    <span aria-hidden="true" class="h-px w-16 bg-hairline"></span>
  </div>
</div>
<?php
    return ob_get_clean();
}
