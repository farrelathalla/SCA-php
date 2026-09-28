<?php $p = page('why-saigas-matter'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php if (shown($p, 'intro')): ?>
<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= rich($p['intro'] ?? '') ?></p></div>
  </div>
</section>
<?php endif; ?>

<?php foreach (shown($p, 'blocks') ? array_values($p['blocks'] ?? []) : [] as $i => $block): ?>
<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => $block['eyebrow'] ?? '', 'title' => $block['title'] ?? '', 'image' => $block['image'] ?? '',
         'imageSide' => ($block['imageSide'] ?? '') ?: ($i % 2 === 0 ? 'left' : 'right')],
        paragraphs($block['body'] ?? [])
    ) ?>
  </div>
</section>
<?php endforeach; ?>

<?php if (shown($p, 'summary')): ?>
<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <h2 class="text-3xl leading-[1.15] md:text-[2.25rem]"><?= e(v($p, 'summary.title')) ?></h2>
      <p class="mt-6 text-[1.0625rem] leading-[1.8] text-body"><?= rich(v($p, 'summary.body')) ?></p>
    </div>
  </div>
</section>
<?php endif; ?>

<?= page_blocks($p) ?>

<?php if (shown($p, 'related')): ?><?= related_links($p['related'] ?? []) ?><?php endif; ?>
<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
