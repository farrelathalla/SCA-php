<?php $p = page('our-story'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<section class="section">
  <div class="shell">
    <?php /* Stored oldest-first, shown newest-first. */ ?>
    <?= timeline($p['milestones'] ?? [], 'desc') ?>
  </div>
</section>

<?= page_blocks($p) ?>

<?= related_links($p['related'] ?? []) ?>
