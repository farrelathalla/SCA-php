<?php
/** @var array $programme */
$grants = page('grants-and-awards');
$t = v($grants, 'programmeTemplate', []);
$pr = $programme;
$checkList = function (array $items): string {
    $out = '';
    foreach ($items as $item) {
        $out .= '<li class="flex gap-3 text-[1rem] leading-relaxed text-body">' . icon('chevron-right', 'mt-1 h-4 w-4 shrink-0 text-accent') . e($item) . '</li>';
    }
    return $out;
};
?>
<?= page_hero(
    ['eyebrow' => $t['heroEyebrow'] ?? '', 'title' => $pr['title'] ?? '', 'intro' => $pr['strapline'] ?? '', 'image' => $pr['image'] ?? '', 'variant' => 'split'],
    button('#how-to-apply', (string) ($t['heroButtonLabel'] ?? ''))
) ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>>
      <?= eyebrow_rule((string) ($t['aboutEyebrow'] ?? '')) ?>
      <div class="mt-6 space-y-5 text-[1.125rem] leading-[1.8] text-body"><?= paragraphs($pr['whatItIs'] ?? []) ?></div>
    </div>
  </div>
</section>

<section class="section-tight pb-20 md:pb-28">
  <div class="shell">
    <div class="grid gap-14 lg:grid-cols-2 lg:gap-24">
      <div <?= reveal() ?>>
        <h2 class="text-2xl"><?= e($t['whoTitle'] ?? '') ?></h2>
        <ul class="mt-6 space-y-4"><?= $checkList($pr['whoItsFor'] ?? []) ?></ul>
      </div>
      <div <?= reveal('', 120) ?>>
        <h2 class="text-2xl"><?= e($t['supportsTitle'] ?? '') ?></h2>
        <ul class="mt-6 space-y-4"><?= $checkList($pr['whatItSupports'] ?? []) ?></ul>
      </div>
    </div>
  </div>
</section>

<section id="how-to-apply" class="scroll-mt-28 bg-sand/70">
  <div class="shell py-16 md:py-24">
    <div <?= reveal('grid gap-12 lg:grid-cols-[1fr_1.2fr] lg:gap-24') ?>>
      <div>
        <h2 class="text-3xl"><?= e($t['applyTitle'] ?? '') ?></h2>
        <p class="mt-4 text-[1.0625rem] leading-relaxed text-body"><?= e($t['applyBody'] ?? '') ?></p>
        <div class="mt-8"><?= button((string) ($t['applyButtonHref'] ?? ''), (string) ($t['applyButtonLabel'] ?? '')) ?></div>
        <?php if (!empty($pr['applicationQuestions'])): ?>
        <div class="mt-12">
          <p class="eyebrow"><?= e($t['questionsEyebrow'] ?? '') ?></p>
          <dl class="mt-6 space-y-6">
            <?php foreach ($pr['applicationQuestions'] as $q): ?>
            <div>
              <dt class="text-[1rem] font-medium text-ink"><?= e($q['question'] ?? '') ?></dt>
              <dd class="mt-2 text-[0.9375rem] leading-relaxed text-body"><?= e($q['answer'] ?? '') ?></dd>
            </div>
            <?php endforeach; ?>
          </dl>
        </div>
        <?php endif; ?>
      </div>
      <div>
        <p class="eyebrow"><?= e($t['keyDatesEyebrow'] ?? '') ?></p>
        <dl class="mt-6">
          <?php foreach ($pr['keyDates'] ?? [] as $date): ?>
          <div class="flex items-baseline justify-between gap-6 border-b border-sand-deep/60 py-4 last:border-0">
            <dt class="text-[1rem] text-body"><?= e($date['label'] ?? '') ?></dt>
            <dd class="font-display text-lg text-ink"><?= e($date['value'] ?? '') ?></dd>
          </div>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="shell">
    <?= section_heading((string) ($t['recipientsEyebrow'] ?? ''), (string) ($t['recipientsTitle'] ?? '')) ?>
    <div class="mt-14 grid gap-10 sm:grid-cols-3 sm:gap-12">
      <?php foreach (array_values($pr['recipients'] ?? []) as $i => $r): ?>
      <div <?= reveal('', $i * 110) ?>>
        <div class="flex items-start gap-5">
          <div class="w-20 shrink-0 sm:w-24"><?= media($r['image'] ?? '', ['ratio' => 'portrait']) ?></div>
          <div>
            <h3 class="text-[1.0625rem] leading-snug font-sans font-medium text-ink"><?= e($r['project'] ?? '') ?></h3>
            <p class="mt-2 text-[0.9rem] text-accent-dark"><?= e(implode(' · ', array_filter([$r['year'] ?? '', $r['country'] ?? '']))) ?></p>
            <p class="mt-1.5 text-[0.85rem] text-muted"><?= e($r['recipient'] ?? '') ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div <?= reveal('', 160) ?>><?= arrow_link('/projects?type=' . rawurlencode((string) ($pr['archiveType'] ?? '')), tpl((string) ($t['exploreMoreLabel'] ?? ''), ['title' => $pr['title'] ?? '']), 'mt-14') ?></div>
  </div>
</section>

<?= cta_band(['title' => v($grants, 'cta.title'), 'body' => v($grants, 'cta.body')]) ?>
