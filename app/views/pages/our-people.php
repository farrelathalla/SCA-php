<?php $p = page('our-people'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php foreach (array_values($p['groups'] ?? []) as $gi => $group): ?>
<section class="<?= $gi === 0 ? 'section' : 'section-tight pb-20 md:pb-28' ?>">
  <div class="shell">
    <?= section_heading('', (string) ($group['title'] ?? ''), (string) ($group['intro'] ?? '')) ?>
    <div class="mt-12 grid gap-8 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 md:gap-x-10 md:gap-y-12">
      <?php foreach (array_values($group['people'] ?? []) as $i => $person): ?><?= person_card($person, $i * 90) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<?= page_blocks($p) ?>
