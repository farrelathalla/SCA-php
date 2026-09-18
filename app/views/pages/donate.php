<?php $p = page('donate'); $h = $p['hero'] ?? []; $c = $p['confidence'] ?? []; ?>
<?= page_hero($h, ($h['buttonLabel'] ?? '') !== '' ? button((string) $h['buttonHref'], (string) $h['buttonLabel'], 'primary', 'px-9 py-4 text-base') : '') ?>

<section id="give" class="section-tight scroll-mt-28 pb-20 md:pb-28">
  <div class="shell">
    <div class="grid gap-6 md:grid-cols-2 md:gap-8">
      <?php foreach (array_values($p['routes'] ?? []) as $i => $route): ?>
      <div <?= reveal('', $i * 120) ?>>
        <div class="flex h-full flex-col rounded-3xl border border-hairline bg-cream-deep p-8 md:p-10">
          <?= icon((string) ($route['icon'] ?? 'globe'), 'h-8 w-8 text-accent', 1.2) ?>
          <h2 class="mt-6 text-2xl leading-snug"><?= e($route['title'] ?? '') ?></h2>
          <p class="mt-3 flex-1 text-[1rem] leading-relaxed text-body"><?= e($route['body'] ?? '') ?></p>
          <div class="mt-8"><?= button((string) ($route['ctaHref'] ?? '#'), (string) ($route['ctaLabel'] ?? '')) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (($p['monthlyHtml'] ?? '') !== ''): ?>
    <div <?= reveal('', 200) ?>><p class="mt-8 max-w-2xl text-[0.9rem] leading-relaxed text-muted"><?= safe_html($p['monthlyHtml']) ?></p></div>
    <?php endif; ?>
  </div>
</section>

<section class="section-tight bg-cream-deep pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) v($p, 'impact.eyebrow'), (string) v($p, 'impact.title'), (string) v($p, 'impact.intro')) ?>
    <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 md:gap-8">
      <?php foreach (array_values(v($p, 'impact.tiers', [])) as $i => $tier): ?>
      <div <?= reveal('', ($i % 3) * 110) ?>>
        <div class="h-full rounded-2xl bg-cream p-8">
          <span class="flex h-11 w-11 items-center justify-center rounded-full bg-accent-soft"><?= icon((string) ($tier['icon'] ?? 'leaf'), 'h-5.5 w-5.5 text-accent', 1.2) ?></span>
          <p class="mt-6 font-display text-[2.25rem] leading-none text-ink"><?= e($tier['amount'] ?? '') ?></p>
          <h3 class="mt-3 font-sans text-[1.0625rem] font-medium text-ink"><?= e($tier['title'] ?? '') ?></h3>
          <p class="mt-2.5 text-[0.9375rem] leading-relaxed text-body"><?= e($tier['body'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => $c['eyebrow'] ?? '', 'title' => $c['title'] ?? '', 'image' => $c['image'] ?? '', 'imageSide' => 'right', 'align' => 'start'],
        paragraphs($c['body'] ?? []),
        fact_list($c['facts'] ?? [], 1, 'max-w-lg') . arrow_link((string) ($c['linkHref'] ?? '#'), (string) ($c['linkLabel'] ?? ''), 'mt-8')
    ) ?>
  </div>
</section>

<section class="section-tight border-t border-hairline">
  <div class="shell">
    <div <?= reveal('flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-16') ?>>
      <div class="max-w-md">
        <h2 class="text-3xl"><?= e(v($p, 'notReady.title')) ?></h2>
        <p class="mt-3 text-[1.0625rem] leading-relaxed text-body"><?= e(v($p, 'notReady.body')) ?></p>
      </div>
      <?= newsletter_form('lg:w-[28rem]', 'large') ?>
    </div>
  </div>
</section>
