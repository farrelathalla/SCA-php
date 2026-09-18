<?php
$p = page('our-work');
$h = $p['hero'] ?? [];
$strands = array_map(fn ($t) => [
    'title' => $t['title'] ?? '', 'href' => $t['url'], 'image' => $t['image'] ?? '',
    'body' => $t['summary'] ?? '', 'stat' => $t['overviewStat'] ?? null,
], entries('theme'));
if (v($p, 'grantsStrand.title') !== '') {
    $strands[] = v($p, 'grantsStrand', []);
}
?>
<section class="relative overflow-hidden border-b border-hairline bg-cream-deep">
  <?php if (($h['image'] ?? '') !== ''): ?>
  <div class="absolute inset-y-0 right-0 hidden w-[55%] lg:block">
    <?= media((string) $h['image'], ['rounded' => false, 'fill' => true]) ?>
    <div class="absolute inset-0 bg-gradient-to-r from-cream-deep via-cream-deep/70 to-transparent"></div>
  </div>
  <?php endif; ?>
  <div class="shell relative py-20 md:py-28">
    <div class="max-w-xl animate-fade-up">
      <?= eyebrow_rule((string) ($h['eyebrow'] ?? '')) ?>
      <h1 class="mt-5 text-[2.6rem] leading-[1.06] md:text-6xl"><?= e($h['title'] ?? '') ?></h1>
      <p class="mt-6 text-lg leading-relaxed text-body"><?= e($h['intro'] ?? '') ?></p>
      <?php if (($h['buttonLabel'] ?? '') !== ''): ?><div class="mt-9"><?= button((string) $h['buttonHref'], (string) $h['buttonLabel']) ?></div><?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="shell">
    <?= section_heading((string) v($p, 'strands.eyebrow'), (string) v($p, 'strands.title'), (string) v($p, 'strands.intro')) ?>
    <div class="mt-16 grid gap-14 md:grid-cols-2 md:gap-x-12 lg:grid-cols-3 lg:gap-x-14 lg:gap-y-20">
      <?php foreach (array_values($strands) as $i => $strand): ?><?= strand_card($strand, ($i % 3) * 110) ?><?php endforeach; ?>
    </div>
    <div <?= reveal('', 160) ?>><?= arrow_link((string) v($p, 'linkHref'), (string) v($p, 'linkLabel'), 'mt-16') ?></div>
  </div>
</section>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
