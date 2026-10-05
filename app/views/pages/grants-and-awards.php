<?php $p = page('grants-and-awards'); $programmes = entries('programme'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php section_start('intro'); ?>
<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= rich($p['intro'] ?? '') ?></p></div>
  </div>
</section>
<?php section_end(); ?>

<?php section_start('programmes'); ?>
<section class="section-tight pb-24 md:pb-32">
  <div class="shell">
    <?= section_heading((string) v($p, 'programmes.eyebrow'), (string) v($p, 'programmes.title')) ?>
    <div class="mt-14 card-row gap-y-12 [--gap-x:3rem] md:[--cols:3] md:gap-y-10 md:[--gap-x:2.5rem] lg:gap-y-14 lg:[--gap-x:3.5rem]">
      <?php foreach ($programmes as $i => $programme): ?>
      <div <?= reveal('', $i * 120) ?>>
        <div class="group flex h-full flex-col">
          <a href="<?= e($programme['url']) ?>"><?= media($programme['image'] ?? '', ['ratio' => 'landscape', 'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', 'imageClass' => 'group-hover:scale-[1.04]', 'class' => 'transition-shadow duration-500 group-hover:shadow-[0_20px_44px_rgba(84,63,38,0.12)]']) ?></a>
          <h3 class="mt-6 text-2xl leading-snug"><a href="<?= e($programme['url']) ?>" class="transition-colors duration-300 hover:text-accent-dark"><?= e($programme['title'] ?? '') ?></a></h3>
          <p class="mt-3 flex-1 text-[0.9375rem] leading-relaxed text-body"><?= rich($programme['summary'] ?? '') ?></p>
          <div class="mt-7"><?= button($programme['url'], (string) v($p, 'programmes.buttonLabel'), 'outline', 'px-6 py-3 text-[0.875rem]') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php section_end(); ?>

<?= implode('', ordered_sections($p)) ?>

<?= page_blocks($p) ?>

<?php if (shown($p, 'cta')): ?><?= cta_band(['title' => v($p, 'cta.title'), 'body' => v($p, 'cta.body')]) ?><?php endif; ?>
