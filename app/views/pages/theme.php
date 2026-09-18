<?php
/** @var array $theme */
$t = v(page('our-work'), 'themeTemplate', []);
$vars = ['title' => $theme['title'] ?? '', 'title_lower' => mb_strtolower((string) ($theme['title'] ?? ''))];
$related = entries_by_slug('project', $theme['relatedProjects'] ?? []);
?>
<?= page_hero(['eyebrow' => $t['heroEyebrow'] ?? '', 'title' => $theme['title'] ?? '', 'intro' => $theme['strapline'] ?? '', 'image' => $theme['heroImage'] ?? '', 'variant' => 'overlay']) ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= e($theme['summary'] ?? '') ?></p></div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= photo_text(
        ['eyebrow' => v($theme, 'challenge.eyebrow'), 'title' => v($theme, 'challenge.title'), 'image' => $theme['image'] ?? '', 'imageSide' => 'left'],
        paragraphs(v($theme, 'challenge.body', []))
    ) ?>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) ($t['roleEyebrow'] ?? ''), (string) ($t['roleTitle'] ?? '')) ?>
    <?= fact_list($theme['actions'] ?? [], 3, 'mt-12 md:mt-14') ?>
  </div>
</section>

<?= number_band((string) ($t['impactEyebrow'] ?? ''), $theme['stats'] ?? []) ?>

<?php if ($related): ?>
<section class="section">
  <div class="shell">
    <div class="flex flex-wrap items-end justify-between gap-6">
      <?= section_heading((string) ($t['relatedEyebrow'] ?? ''), (string) ($t['relatedTitle'] ?? '')) ?>
      <div <?= reveal('', 120) ?>><?= arrow_link('/projects?theme=' . rawurlencode((string) ($theme['title'] ?? '')), tpl((string) ($t['exploreAllLabel'] ?? ''), $vars)) ?></div>
    </div>
    <div class="mt-14">
      <?= card_grid(implode('', array_map(
          fn ($project, $i) => story_card(project_card($project, (string) (($project['countries'] ?? [])[0] ?? ''), [$project['type'] ?? '', implode('–', $project['years'] ?? [])]), $i * 110),
          $related, array_keys($related)
      ))) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (v($theme, 'futureOpportunities.title') !== ''): ?>
<section class="section-tight border-t border-hairline pb-20 md:pb-28">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <?= eyebrow_rule((string) ($t['futureEyebrow'] ?? '')) ?>
      <h2 class="mt-4 text-3xl leading-[1.15] md:text-[2.25rem]"><?= e(v($theme, 'futureOpportunities.title')) ?></h2>
      <p class="mt-6 text-[1.0625rem] leading-[1.8] text-body"><?= e(v($theme, 'futureOpportunities.body')) ?></p>
    </div>
  </div>
</section>
<?php endif; ?>

<?= cta_band(['title' => tpl((string) ($t['ctaTitle'] ?? ''), $vars), 'body' => tpl((string) ($t['ctaBody'] ?? ''), $vars)]) ?>
