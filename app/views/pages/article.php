<?php
/** @var array $article */
$t = v(page('news'), 'articleTemplate', []);
$a = $article;
// Each text box in the admin may hold several paragraphs, separated by an
// empty line (that is how long articles are usually pasted in).
$paragraphs = [];
foreach ((array) ($a['body'] ?? []) as $box) {
    foreach (preg_split('/\R[ \t]*\R/', trim((string) $box)) as $paragraph) {
        if (trim($paragraph) !== '') {
            $paragraphs[] = trim($paragraph);
        }
    }
}
$count = count($paragraphs);
$middle = min(2, $count);

// Photos, each placed after the paragraph number it names (else after the
// second paragraph). Articles saved before photos had captions carry one
// inlineImage instead.
$photos = photo_items($a['photos'] ?? []);
if (!$photos && trim((string) ($a['inlineImage'] ?? '')) !== '') {
    $photos = [['image' => trim((string) $a['inlineImage']), 'caption' => (string) ($a['inlineCaption'] ?? '')]];
}
$photosAt = [];
foreach ($photos as $photo) {
    $after = trim((string) ($photo['afterParagraph'] ?? ''));
    $at = ctype_digit($after) ? min((int) $after, $count) : $middle;
    $photosAt[$at][] = $photo;
}
$quote = trim((string) ($a['pullQuote'] ?? ''));

$more = array_slice(array_values(array_filter(entries('news'), fn ($item) => $item['slug'] !== $a['slug'])), 0, 3);

/** One run of paragraphs at reading width. */
$prose = function (array $run): string {
    $out = '';
    foreach ($run as $i => $paragraph) {
        $out .= '<p ' . reveal('', min($i, 4) * 60) . '>' . rich($paragraph) . '</p>';
    }
    return $run ? '<div class="shell-narrow prose-sca">' . $out . '</div>' : '';
};

/** The photos that share a place: one wide, or two or more side by side. */
$figures = function (array $group): string {
    if (count($group) === 1) {
        return '<div class="shell my-12 md:my-16"><div ' . reveal('mx-auto max-w-4xl') . '>'
            . media($group[0]['image'], ['ratio' => 'natural', 'caption' => $group[0]['caption'], 'captionClass' => 'text-center'])
            . '</div></div>';
    }
    $out = '';
    foreach ($group as $i => $photo) {
        $out .= '<div ' . reveal('', ($i % 2) * 110) . '>' . media($photo['image'], ['ratio' => 'natural', 'caption' => $photo['caption']]) . '</div>';
    }
    return '<div class="shell my-12 md:my-16"><div class="mx-auto grid max-w-5xl items-start gap-x-8 gap-y-10 sm:grid-cols-2">' . $out . '</div></div>';
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
<?php
$run = [];
for ($at = 0; $at <= $count; $at++) {
    if ($at > 0) {
        $run[] = $paragraphs[$at - 1];
    }
    $quoteHere = $quote !== '' && $at === $middle;
    if (!$quoteHere && empty($photosAt[$at])) {
        continue;
    }
    echo $prose($run);
    $run = [];
    if ($quoteHere) {
        echo '<div class="shell-narrow my-4"><div ' . reveal() . '><blockquote class="border-l-2 border-accent py-2 pl-8">'
            . '<p class="font-display text-2xl leading-snug text-ink md:text-[1.75rem]">' . rich($quote) . '</p></blockquote></div></div>';
    }
    if (!empty($photosAt[$at])) {
        echo $figures($photosAt[$at]);
    }
}
echo $prose($run);
?>
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
