<?php $p = page('policy-and-protection'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= e($p['intro'] ?? '') ?></p></div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) v($p, 'agreements.eyebrow'), (string) v($p, 'agreements.title')) ?>
    <div class="mt-12 grid gap-14 lg:grid-cols-2 lg:gap-20">
      <?php foreach (array_values(v($p, 'agreements.items', [])) as $i => $agreement): ?>
      <div <?= reveal('', $i * 120) ?>>
        <h3 class="text-2xl leading-snug"><?= e($agreement['name'] ?? '') ?></h3>
        <div class="mt-5 space-y-4 text-[1.0625rem] leading-[1.8] text-body"><?= paragraphs($agreement['body'] ?? []) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <h2 class="text-3xl leading-[1.15] md:text-[2.25rem]"><?= e(v($p, 'national.title')) ?></h2>
      <?php foreach (array_values(v($p, 'national.body', [])) as $i => $paragraph): ?>
      <p class="<?= $i === 0 ? 'mt-6' : 'mt-4' ?> text-[1.0625rem] leading-[1.8] text-body"><?= e($paragraph) ?></p>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-tight pb-24 md:pb-32">
  <div class="shell"><?= callout((string) v($p, 'callout.title'), '<p>' . e(v($p, 'callout.body')) . '</p>') ?></div>
</section>

<?= page_blocks($p) ?>

<?= related_links($p['related'] ?? []) ?>
<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
