<?php $p = page('our-story'); ?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section">
  <div class="shell">
    <?php /* Stored oldest-first, shown newest-first. */ ?>
    <?= timeline($p['milestones'] ?? [], 'desc') ?>
  </div>
</section>

<?= related_links($p['related'] ?? []) ?>
