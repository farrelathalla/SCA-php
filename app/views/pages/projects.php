<?php
$p = page('projects');
$projects = entries('project');
$items = array_map(fn ($pr) => project_card($pr, implode(', ', $pr['countries'] ?? []), [$pr['type'] ?? '', implode(', ', $pr['years'] ?? [])]) + [
    'facets' => ['country' => $pr['countries'] ?? [], 'theme' => $pr['themes'] ?? [], 'year' => $pr['years'] ?? [], 'type' => $pr['type'] ?? ''],
], $projects);

// Themes, types and countries follow the lists projects are tagged from
// (Header, footer & site-wide), in that order; only options in use are offered.
$themes = used_options(project_options('themes'), array_merge([], ...array_map(fn ($pr) => (array) ($pr['themes'] ?? []), $projects)));
$types = used_options(project_options('types'), array_column($projects, 'type'));
$countries = used_options(project_options('countries'), array_merge([], ...array_map(fn ($pr) => (array) ($pr['countries'] ?? []), $projects)));

// Order requested by SCA: themes, project types, countries, years.
$filters = [
    ['id' => 'theme', 'label' => 'Theme', 'options' => array_merge([(string) v($p, 'filters.themeAll')], $themes)],
    ['id' => 'type', 'label' => 'Project type', 'options' => array_merge([(string) v($p, 'filters.typeAll')], $types)],
    ['id' => 'country', 'label' => 'Country', 'options' => array_merge([(string) v($p, 'filters.countryAll')], $countries)],
    ['id' => 'year', 'label' => 'Year', 'options' => facet_options((string) v($p, 'filters.yearAll'), array_merge([], ...array_map(fn ($pr) => (array) ($pr['years'] ?? []), $projects)), true)],
];
?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<section class="section">
  <div class="shell"><?= archive_grid($items, $filters) ?></div>
</section>

<?= page_blocks($p) ?>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
