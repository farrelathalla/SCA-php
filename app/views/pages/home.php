<?php
$p = page('home');
$featured = array_map(
    fn ($project) => project_card($project, (string) (($project['themes'] ?? [])[0] ?? ''), []),
    entries_by_slug('project', v($p, 'featured.projects', []))
);
$latest = array_map('article_card', array_slice(entries('news'), 0, max(1, (int) v($p, 'latest.count', 3))));
$hero = $p['hero'] ?? [];
?>
<?= page_hero($hero,
    (($hero['primaryLabel'] ?? '') !== '' ? button((string) $hero['primaryHref'], (string) $hero['primaryLabel']) : '')
    . (($hero['secondaryLabel'] ?? '') !== '' ? button((string) $hero['secondaryHref'], (string) $hero['secondaryLabel'], 'outline', '', false) : '')
) ?>
<?= page_blocks($p, 'top') ?>

<section class="section">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => v($p, 'mission.eyebrow'), 'title' => v($p, 'mission.title'), 'image' => v($p, 'mission.image'), 'imageSide' => 'left'],
        paragraphs(v($p, 'mission.body', [])),
        arrow_link((string) v($p, 'mission.linkHref'), (string) v($p, 'mission.linkLabel'))
    ) ?>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => v($p, 'species.eyebrow'), 'title' => v($p, 'species.title'), 'image' => v($p, 'species.image'), 'imageSide' => 'right', 'align' => 'start'],
        paragraphs(v($p, 'species.body', [])),
        fact_list(v($p, 'species.facts', []), 1, 'max-w-lg')
            . arrow_link((string) v($p, 'species.linkHref'), (string) v($p, 'species.linkLabel'), 'mt-8')
    ) ?>
  </div>
</section>

<?= number_band((string) v($p, 'numbers.eyebrow'), v($p, 'numbers.stats', [])) ?>

<section class="section">
  <div class="shell">
    <?= section_heading((string) v($p, 'whatWeDo.eyebrow'), (string) v($p, 'whatWeDo.title'), (string) v($p, 'whatWeDo.intro')) ?>
    <?= fact_list(v($p, 'whatWeDo.items', []), 3, 'mt-14 md:mt-16') ?>
    <div <?= reveal('', 200) ?>><?= arrow_link((string) v($p, 'whatWeDo.linkHref'), (string) v($p, 'whatWeDo.linkLabel'), 'mt-12') ?></div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section-tight bg-cream-deep">
  <div class="shell">
    <div class="flex flex-wrap items-end justify-between gap-6">
      <?= section_heading((string) v($p, 'featured.eyebrow'), (string) v($p, 'featured.title')) ?>
      <div <?= reveal('', 120) ?>><?= arrow_link((string) v($p, 'featured.linkHref'), (string) v($p, 'featured.linkLabel')) ?></div>
    </div>
    <div class="mt-14 md:mt-16">
      <?= card_grid(implode('', array_map(fn ($item, $i) => story_card($item, $i * 110), $featured, array_keys($featured)))) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="shell">
    <div class="flex flex-wrap items-end justify-between gap-6">
      <?= section_heading((string) v($p, 'latest.eyebrow'), (string) v($p, 'latest.title')) ?>
      <div <?= reveal('', 120) ?>><?= arrow_link((string) v($p, 'latest.linkHref'), (string) v($p, 'latest.linkLabel')) ?></div>
    </div>
    <div class="mt-14 md:mt-16"><?= story_list($latest) ?></div>
  </div>
</section>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-16') ?>>
      <div class="max-w-md">
        <h2 class="text-3xl"><?= e(v($p, 'stayConnected.title')) ?></h2>
        <p class="mt-3 text-[1.0625rem] leading-relaxed text-body"><?= e(v($p, 'stayConnected.body')) ?></p>
        <?php if (v($p, 'stayConnected.promptLinkLabel') !== ''): ?>
        <p class="mt-3 text-[0.9rem] text-muted">
          <?= e(v($p, 'stayConnected.promptText')) ?>
          <a href="<?= e(v($p, 'stayConnected.promptLinkHref')) ?>" class="text-accent-dark underline underline-offset-4"><?= e(v($p, 'stayConnected.promptLinkLabel')) ?></a>.
        </p>
        <?php endif; ?>
      </div>
      <?= newsletter_form('lg:w-[28rem]', 'large') ?>
    </div>
  </div>
</section>

<?= page_blocks($p) ?>
