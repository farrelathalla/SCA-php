<?php $p = page('what-is-a-saiga'); ?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= e(v($p, 'intro.lead')) ?></p>
      <?php if (v($p, 'intro.body') !== ''): ?><p class="mt-6 text-[1.0625rem] leading-[1.8] text-body"><?= e(v($p, 'intro.body')) ?></p><?php endif; ?>
    </div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) v($p, 'factsheet.eyebrow'), (string) v($p, 'factsheet.title'), (string) v($p, 'factsheet.intro')) ?>
    <?= fact_grid(v($p, 'factsheet.facts', []), 'mt-12 md:mt-14') ?>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => v($p, 'behaviour.eyebrow'), 'title' => v($p, 'behaviour.title'), 'image' => v($p, 'behaviour.image'), 'imageSide' => 'left'],
        paragraphs(v($p, 'behaviour.body', [])),
        arrow_link((string) v($p, 'behaviour.linkHref'), (string) v($p, 'behaviour.linkLabel'))
    ) ?>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) v($p, 'distribution.eyebrow'), (string) v($p, 'distribution.title')) ?>
    <div class="mt-12 grid items-start gap-12 lg:grid-cols-[1fr_1.1fr] lg:gap-20">
      <div <?= reveal('space-y-5 text-[1.0625rem] leading-[1.8] text-body') ?>><?= paragraphs(v($p, 'distribution.body', [])) ?></div>
      <div <?= reveal('', 120) ?>>
        <?= media((string) v($p, 'distribution.mapImage'), ['ratio' => 'map']) ?>
        <p class="mt-5 text-[0.9rem] leading-relaxed text-muted"><?= e(v($p, 'distribution.caption')) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section-tight border-t border-hairline">
  <div class="shell">
    <?= section_heading((string) v($p, 'explore.eyebrow'), (string) v($p, 'explore.title')) ?>
    <?= fact_list(v($p, 'explore.items', []), 3, 'mt-12') ?>
  </div>
</section>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
