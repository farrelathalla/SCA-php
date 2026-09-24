<?php
$p = page('news');
$articles = entries('news');
$items = array_map(fn ($a) => article_card($a) + [
    'facets' => ['year' => substr((string) ($a['date'] ?? ''), 0, 4), 'type' => $a['category'] ?? ''],
], $articles);
$filters = [
    ['id' => 'year', 'label' => 'Year', 'options' => facet_options((string) v($p, 'filters.yearAll'), array_map(fn ($a) => substr((string) ($a['date'] ?? ''), 0, 4), $articles), true)],
    ['id' => 'type', 'label' => 'Article type', 'options' => facet_options((string) v($p, 'filters.typeAll'), array_column($articles, 'category'))],
];
?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php if (v($p, 'saigaNews.title') !== ''): ?>
<section class="section-tight pb-0">
  <div class="shell"><div class="max-w-4xl"><?= resource_link(v($p, 'saigaNews', [])) ?></div></div>
</section>
<?php endif; ?>

<section class="section pt-14 md:pt-16">
  <div class="shell"><?= archive_grid($items, $filters, false) ?></div>
</section>

<section class="section-tight border-t border-hairline">
  <div class="shell">
    <div <?= reveal('flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-16') ?>>
      <div class="max-w-md">
        <h2 class="text-3xl"><?= e(v($p, 'email.title')) ?></h2>
        <p class="mt-3 text-[1.0625rem] leading-relaxed text-body"><?= e(v($p, 'email.body')) ?></p>
      </div>
      <?= newsletter_form('lg:w-[28rem]', 'large') ?>
    </div>
  </div>
</section>

<?= page_blocks($p) ?>
