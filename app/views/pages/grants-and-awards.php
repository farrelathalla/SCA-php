<?php $p = page('grants-and-awards'); $programmes = entries('programme'); ?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section-tight">
  <div class="shell">
    <div <?= reveal('max-w-3xl') ?>><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]"><?= e($p['intro'] ?? '') ?></p></div>
  </div>
</section>

<section class="section-tight pb-24 md:pb-32">
  <div class="shell">
    <?= section_heading((string) v($p, 'programmes.eyebrow'), (string) v($p, 'programmes.title')) ?>
    <div class="mt-14 grid gap-12 md:grid-cols-3 md:gap-10 lg:gap-14">
      <?php foreach ($programmes as $i => $programme): ?>
      <div <?= reveal('', $i * 120) ?>>
        <div class="group flex h-full flex-col">
          <a href="<?= e($programme['url']) ?>"><?= media($programme['image'] ?? '', ['ratio' => 'landscape', 'imageClass' => 'group-hover:scale-[1.04]', 'class' => 'transition-shadow duration-500 group-hover:shadow-[0_20px_44px_rgba(84,63,38,0.12)]']) ?></a>
          <h3 class="mt-6 text-2xl leading-snug"><a href="<?= e($programme['url']) ?>" class="transition-colors duration-300 hover:text-accent-dark"><?= e($programme['title'] ?? '') ?></a></h3>
          <p class="mt-3 flex-1 text-[0.9375rem] leading-relaxed text-body"><?= e($programme['summary'] ?? '') ?></p>
          <div class="mt-7"><?= button($programme['url'], (string) v($p, 'programmes.buttonLabel'), 'outline', 'px-6 py-3 text-[0.875rem]') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?= cta_band(['title' => v($p, 'cta.title'), 'body' => v($p, 'cta.body')]) ?>
