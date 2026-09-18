<?php
$p = page('projects');
$projects = entries('project');
$items = array_map(fn ($pr) => project_card($pr, implode(', ', $pr['countries'] ?? []), [$pr['type'] ?? '', implode(', ', $pr['years'] ?? [])]) + [
    'facets' => ['country' => $pr['countries'] ?? [], 'theme' => $pr['themes'] ?? [], 'year' => $pr['years'] ?? [], 'type' => $pr['type'] ?? ''],
], $projects);

// Types follow the configured order, and only those in use are offered.
$usedTypes = array_column($projects, 'type');
$types = array_values(array_filter(site('projectTypes', []), fn ($t) => in_array($t, $usedTypes, true)));
foreach (array_unique($usedTypes) as $t) {
    if ($t !== '' && !in_array($t, $types, true)) {
        $types[] = $t;
    }
}

$filters = [
    ['id' => 'country', 'label' => 'Country', 'options' => facet_options((string) v($p, 'filters.countryAll'), array_merge([], ...array_column($projects, 'countries')))],
    ['id' => 'theme', 'label' => 'Theme', 'options' => facet_options((string) v($p, 'filters.themeAll'), array_merge([], ...array_column($projects, 'themes')))],
    ['id' => 'year', 'label' => 'Year', 'options' => facet_options((string) v($p, 'filters.yearAll'), array_merge([], ...array_column($projects, 'years')), true)],
    ['id' => 'type', 'label' => 'Type', 'options' => array_merge([(string) v($p, 'filters.typeAll')], $types)],
];
?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section">
  <div class="shell"><?= archive_grid($items, $filters) ?></div>
</section>

<?php if (!empty($p['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
