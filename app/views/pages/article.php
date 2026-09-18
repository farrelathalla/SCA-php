<?php
/** @var array $article */
$t = v(page('news'), 'articleTemplate', []);
$a = $article;
$body = array_values(array_filter($a['body'] ?? [], fn ($p) => trim((string) $p) !== ''));
$firstHalf = array_slice($body, 0, 2);
$secondHalf = array_slice($body, 2);
$more = array_slice(array_values(array_filter(entries('news'), fn ($item) => $item['slug'] !== $a['slug'])), 0, 3);
$prose = function (array $paragraphs): string {
    $out = '';
    foreach ($paragraphs as $i => $paragraph) {
        $out .= '<p ' . reveal('', $i * 60) . '>' . e($paragraph) . '</p>';
    }
    return $out;
};
?>
<?= page_hero(['eyebrow' => $a['category'] ?? '', 'title' => $a['title'] ?? '', 'intro' => $a['excerpt'] ?? '', 'image' => $a['heroImage'] ?? '', 'variant' => 'overlay']) ?>

<section class="border-b border-hairline">
  <div class="shell py-8">
    <div <?= reveal('flex flex-wrap items-center gap-x-8 gap-y-3') ?>>
      <?php if (($a['category'] ?? '') !== ''): ?><?= tag((string) $a['category']) ?><?php endif; ?>
      <span class="text-[0.9rem] text-body"><?= e(format_date($a['date'] ?? '')) ?></span>
      <?php if (($a['author'] ?? '') !== ''): ?><span class="text-[0.9rem] text-muted"><?= e(trim(($t['byPrefix'] ?? '') . ' ' . $a['author'])) ?></span><?php endif; ?>
      <a href="/news" class="ml-auto text-[0.9rem] text-accent-dark transition-colors hover:text-accent"><?= e($t['backLabel'] ?? '') ?></a>
    </div>
  </div>
</section>

<article class="section-tight">
  <div class="shell-narrow prose-sca"><?= $prose($firstHalf) ?></div>

  <?php if (trim((string) ($a['pullQuote'] ?? '')) !== ''): ?>
  <div class="shell-narrow my-4">
    <div <?= reveal() ?>>
      <blockquote class="border-l-2 border-accent py-2 pl-8">
        <p class="font-display text-2xl leading-snug text-ink md:text-[1.75rem]"><?= e($a['pullQuote']) ?></p>
      </blockquote>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($secondHalf): ?>
    <?php if (trim((string) ($a['inlineImage'] ?? '')) !== ''): ?>
    <div class="shell my-14 md:my-20">
      <div <?= reveal('mx-auto max-w-4xl') ?>>
        <?= media((string) $a['inlineImage'], ['ratio' => 'hero']) ?>
        <?php if (($a['inlineCaption'] ?? '') !== ''): ?><p class="mt-4 text-center text-[0.85rem] text-muted"><?= e($a['inlineCaption']) ?></p><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="shell-narrow prose-sca"><?= $prose($secondHalf) ?></div>
  <?php endif; ?>
</article>

<?php if ($more): ?>
<section class="section-tight border-t border-hairline pb-24 md:pb-32">
  <div class="shell">
    <?= section_heading((string) ($t['moreEyebrow'] ?? ''), (string) ($t['moreTitle'] ?? '')) ?>
    <div class="mt-14"><?= story_list(array_map('article_card', $more)) ?></div>
  </div>
</section>
<?php endif; ?>

<?= cta_band() ?>
