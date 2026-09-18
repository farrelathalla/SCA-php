<?php $p = page('population-history-and-threats'); $graphImage = trim((string) v($p, 'graph.image')); ?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= e($p['intro'] ?? '') ?></p></div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) v($p, 'history.eyebrow'), (string) v($p, 'history.title'), (string) v($p, 'history.intro')) ?>
    <div class="mt-16"><?= timeline(v($p, 'history.items', []), 'asc', false) ?></div>
  </div>
</section>

<section class="bg-cream-deep">
  <div class="shell py-16 md:py-24">
    <?= section_heading((string) v($p, 'graph.eyebrow'), (string) v($p, 'graph.title'), (string) v($p, 'graph.intro')) ?>
    <div <?= reveal('mt-12', 120) ?>>
      <figure>
        <?php if ($graphImage !== ''): ?>
        <img src="<?= e($graphImage) ?>" alt="<?= e(v($p, 'graph.title')) ?>" class="w-full rounded-2xl">
        <?php else: ?>
        <div class="relative aspect-[16/9] w-full overflow-hidden rounded-2xl bg-sand/40 md:aspect-[21/9]">
          <svg aria-hidden="true" class="absolute inset-0 h-full w-full text-sand-deep" viewBox="0 0 900 380" preserveAspectRatio="none" fill="none">
            <g stroke="currentColor" stroke-width="1">
              <path d="M90 40v280h760" />
              <g opacity="0.55" stroke-dasharray="3 7"><path d="M90 250h760M90 180h760M90 110h760M90 40h760" /></g>
            </g>
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7 text-muted"><path d="M4 4v16h16" /><path d="m7 15 4-5 3 3 5-7" /></svg>
            <p class="font-mono text-[10px] tracking-[0.14em] text-muted uppercase"><?= e(v($p, 'graph.placeholderText')) ?></p>
          </div>
        </div>
        <?php endif; ?>
        <?php if (v($p, 'graph.caption') !== ''): ?><figcaption class="mt-5 max-w-2xl text-[0.9rem] leading-relaxed text-muted"><?= e(v($p, 'graph.caption')) ?></figcaption><?php endif; ?>
      </figure>
    </div>
  </div>
</section>

<section class="section">
  <div class="shell">
    <?= section_heading((string) v($p, 'threats.eyebrow'), (string) v($p, 'threats.title'), (string) v($p, 'threats.intro')) ?>
    <div class="mt-14 grid gap-x-14 gap-y-12 md:grid-cols-2 lg:gap-x-20">
      <?php foreach (array_values(v($p, 'threats.items', [])) as $i => $threat): ?>
      <div <?= reveal('', ($i % 2) * 110) ?>>
        <?= icon((string) ($threat['icon'] ?? 'leaf'), 'h-8 w-8 text-accent', 1.2) ?>
        <h3 class="mt-5 font-sans text-[1.125rem] font-medium text-ink"><?= e($threat['title'] ?? '') ?></h3>
        <p class="mt-3 text-[1rem] leading-[1.8] text-body"><?= e($threat['body'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <h2 class="text-3xl leading-[1.15] md:text-[2.25rem]"><?= e(v($p, 'summary.title')) ?></h2>
      <p class="mt-6 text-[1.0625rem] leading-[1.8] text-body"><?= e(v($p, 'summary.body')) ?></p>
    </div>
  </div>
</section>

<?= related_links($p['related'] ?? []) ?>
<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
