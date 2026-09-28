<?php $p = page('our-people'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php foreach (shown($p, 'groups') ? array_values($p['groups'] ?? []) : [] as $gi => $group): ?>
<section class="<?= $gi === 0 ? 'section' : 'section-tight pb-20 md:pb-28' ?>">
  <div class="shell">
    <?= section_heading('', (string) ($group['title'] ?? ''), (string) ($group['intro'] ?? '')) ?>
    <div class="mt-12 card-row [--cols:2] gap-y-8 [--gap-x:2rem] md:[--cols:3] lg:[--cols:4] md:gap-y-12 md:[--gap-x:2.5rem]">
      <?php foreach (array_values($group['people'] ?? []) as $i => $person): ?><?= person_card($person, $i * 90) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<?= page_blocks($p) ?>
