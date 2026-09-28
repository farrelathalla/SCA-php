<?php
$p = page('our-work');
$h = $p['hero'] ?? [];
$strands = array_map(fn ($t) => [
    'title' => $t['title'] ?? '', 'href' => $t['url'], 'image' => $t['image'] ?? '',
    'body' => $t['summary'] ?? '', 'stat' => $t['overviewStat'] ?? null,
], entries('theme'));
if (v($p, 'grantsStrand.title') !== '' && shown($p, 'grantsStrand')) {
    $strands[] = v($p, 'grantsStrand', []);
}
?>
<section class="group/hero relative overflow-hidden border-b border-hairline bg-cream-deep">
  <?php if (($h['image'] ?? '') !== ''): ?>
  <div class="absolute inset-y-0 right-0 hidden w-[55%] lg:block">
    <?= media((string) $h['image'], ['rounded' => false, 'fill' => true, 'credit' => false]) ?>
    <div class="absolute inset-0 bg-gradient-to-r from-cream-deep via-cream-deep/70 to-transparent"></div>
    <?= photo_credit((string) $h['image'], 'hero') ?>
  </div>
  <?php endif; ?>
  <div class="shell relative py-20 md:py-28">
    <div class="max-w-xl animate-fade-up">
      <?= eyebrow_rule((string) ($h['eyebrow'] ?? '')) ?>
      <h1 class="mt-5 text-[2.6rem] leading-[1.06] md:text-6xl"><?= e($h['title'] ?? '') ?></h1>
      <p class="mt-6 text-lg leading-relaxed text-body"><?= rich($h['intro'] ?? '') ?></p>
      <?php if (($h['buttonLabel'] ?? '') !== ''): ?><div class="mt-9"><?= button((string) $h['buttonHref'], (string) $h['buttonLabel']) ?></div><?php endif; ?>
    </div>
  </div>
</section>
<?= page_blocks($p, 'top') ?>

<?php if (shown($p, 'strands')): ?>
<section class="section">
  <div class="shell">
    <?= section_heading((string) v($p, 'strands.eyebrow'), (string) v($p, 'strands.title'), (string) v($p, 'strands.intro')) ?>
    <div class="mt-16 card-row gap-y-14 [--gap-x:3.5rem] md:[--cols:2] md:[--gap-x:3rem] lg:[--cols:3] lg:gap-y-20 lg:[--gap-x:3.5rem]">
      <?php foreach (array_values($strands) as $i => $strand): ?><?= strand_card($strand, ($i % 3) * 110) ?><?php endforeach; ?>
    </div>
    <div <?= reveal('', 160) ?>><?= arrow_link((string) v($p, 'linkHref'), (string) v($p, 'linkLabel'), 'mt-16') ?></div>
  </div>
</section>
<?php endif; ?>

<?= page_blocks($p) ?>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
