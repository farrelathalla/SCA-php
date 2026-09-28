<?php $p = page('our-story'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php if (shown($p, 'milestones')): ?>
<section class="section">
  <div class="shell">
    <?php /* Stored oldest-first, shown newest-first. */ ?>
    <?= timeline($p['milestones'] ?? [], 'desc') ?>
  </div>
</section>
<?php endif; ?>

<?= page_blocks($p) ?>

<?php if (shown($p, 'related')): ?><?= related_links($p['related'] ?? []) ?><?php endif; ?>
