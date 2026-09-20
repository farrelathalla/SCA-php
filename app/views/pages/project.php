<?php
/** @var array $project */
$t = v(page('projects'), 'projectTemplate', []);
$pr = $project;
$related = array_slice(array_values(array_filter(entries('project'), fn ($item) => $item['slug'] !== $pr['slug'])), 0, 3);
$gallery = array_values(array_filter($pr['gallery'] ?? [], fn ($src) => trim((string) $src) !== ''));
// Reports and other files are optional: most projects will not have any.
$documents = document_links($pr['documents'] ?? []);
?>
<?= page_hero(['eyebrow' => $t['heroEyebrow'] ?? '', 'title' => $pr['title'] ?? '', 'intro' => $pr['excerpt'] ?? '', 'image' => $pr['heroImage'] ?? '', 'variant' => 'overlay']) ?>

<section class="border-b border-hairline">
  <div class="shell py-8">
    <div <?= reveal('flex flex-wrap items-center gap-x-8 gap-y-4') ?>>
      <?php if (($pr['type'] ?? '') !== ''): ?><?= tag((string) $pr['type']) ?><?php endif; ?>
      <span class="text-[0.9rem] text-body"><?= e(implode('  ·  ', $pr['themes'] ?? [])) ?></span>
      <span class="text-[0.9rem] text-body"><?= e(implode('  ·  ', $pr['countries'] ?? [])) ?></span>
      <span class="text-[0.9rem] text-muted"><?= e(implode('  ·  ', $pr['years'] ?? [])) ?></span>
      <a href="/projects" class="ml-auto text-[0.9rem] whitespace-nowrap text-accent-dark transition-colors hover:text-accent"><?= e($t['backLabel'] ?? '') ?></a>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="shell-narrow prose-sca">
    <?php foreach (array_values($pr['body'] ?? []) as $i => $paragraph): ?><p <?= reveal('', $i * 60) ?>><?= e($paragraph) ?></p><?php endforeach; ?>
  </div>
</section>

<?php if (!empty($pr['outcomes'])): ?>
<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div <?= reveal() ?>><?= eyebrow_rule((string) ($t['outcomesEyebrow'] ?? '')) ?></div>
    <div class="mt-10 grid gap-10 sm:grid-cols-3 sm:gap-12">
      <?php foreach (array_values($pr['outcomes']) as $i => $outcome): ?>
      <div <?= reveal('', $i * 110) ?>>
        <p class="font-display text-[2.5rem] leading-none text-ink"><?= e($outcome['value'] ?? '') ?></p>
        <p class="mt-3 text-[0.9rem] leading-relaxed text-muted"><?= e($outcome['label'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($pr['timeline'])): ?>
<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) ($t['yearsEyebrow'] ?? ''), (string) ($t['yearsTitle'] ?? ''), (string) ($t['yearsIntro'] ?? '')) ?>
    <div class="mt-12"><?= year_accordion($pr['timeline']) ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($documents !== ''): ?>
<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <?= section_heading((string) ($t['documentsEyebrow'] ?? ''), (string) ($t['documentsTitle'] ?? '')) ?>
    <div class="mt-10"><?= $documents ?></div>
  </div>
</section>
<?php endif; ?>

<?php if ($gallery): ?>
<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div class="grid gap-8 sm:grid-cols-3 md:gap-10">
      <?php foreach ($gallery as $i => $src): ?><div <?= reveal('', $i * 110) ?>><?= media($src, ['ratio' => 'landscape']) ?></div><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($related): ?>
<section class="section-tight border-t border-hairline pb-24 md:pb-32">
  <div class="shell">
    <?= section_heading((string) ($t['relatedEyebrow'] ?? ''), (string) ($t['relatedTitle'] ?? '')) ?>
    <div class="mt-14">
      <?= card_grid(implode('', array_map(
          fn ($item, $i) => story_card(project_card($item, (string) (($item['countries'] ?? [])[0] ?? ''), [$item['type'] ?? '', implode(', ', $item['years'] ?? [])]), $i * 110),
          $related, array_keys($related)
      ))) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= cta_band() ?>
