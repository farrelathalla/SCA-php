<?php $p = page('work-with-us'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php section_start('intro'); ?>
<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= rich($p['intro'] ?? '') ?></p></div>
  </div>
</section>
<?php section_end(); ?>

<?php section_start('contact'); ?>
<section class="section-tight bg-cream-deep pb-24 md:pb-32">
  <div class="shell">
    <div class="grid gap-14 lg:grid-cols-[1fr_1.05fr] lg:gap-24">
      <div <?= reveal() ?>><?= section_heading((string) v($p, 'contact.eyebrow'), (string) v($p, 'contact.title'), (string) v($p, 'contact.intro')) ?></div>
      <div <?= reveal('', 140) ?>><?= contact_form('work-with-us') ?></div>
    </div>
  </div>
</section>
<?php section_end(); ?>

<?= implode('', ordered_sections($p)) ?>

<?= page_blocks($p) ?>

<?php if (shown($p, 'related')): ?><?= related_links($p['related'] ?? []) ?><?php endif; ?>
