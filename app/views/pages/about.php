<?php $p = page('about'); ?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <?= eyebrow_rule((string) v($p, 'mission.eyebrow')) ?>
      <p class="mt-6 text-xl leading-[1.6] text-ink md:text-[1.5rem]"><?= e(v($p, 'mission.statement')) ?></p>
      <p class="mt-5 text-[1.0625rem] leading-[1.8] text-body"><?= e(v($p, 'mission.body')) ?></p>
    </div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => v($p, 'approach.eyebrow'), 'title' => v($p, 'approach.title'), 'image' => v($p, 'approach.image'), 'imageSide' => 'right', 'align' => 'start'],
        paragraphs(v($p, 'approach.body', [])),
        arrow_link((string) v($p, 'approach.linkHref'), (string) v($p, 'approach.linkLabel'))
    ) ?>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div <?= reveal('max-w-3xl rounded-3xl bg-sand/60 p-8 md:p-12') ?>>
      <h2 class="text-2xl"><?= e(v($p, 'governance.title')) ?></h2>
      <p class="mt-4 text-[1.0625rem] leading-relaxed text-body"><?= safe_html(v($p, 'governance.bodyHtml')) ?></p>
    </div>
  </div>
</section>

<section class="section-tight border-t border-hairline pb-24 md:pb-32">
  <div class="shell">
    <?= section_heading((string) v($p, 'explore.eyebrow'), (string) v($p, 'explore.title')) ?>
    <?= fact_list(v($p, 'explore.items', []), 4, 'mt-12') ?>
  </div>
</section>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
