<?php $p = page('resources'); $r = $p['report'] ?? []; ?>
<?= page_hero($p['hero'] ?? []) ?>

<section class="section">
  <div class="shell">
    <div class="max-w-4xl">
      <?php foreach (array_values($p['items'] ?? []) as $i => $item): ?><?= resource_link($item, $i * 120) ?><?php endforeach; ?>
    </div>
    <?php if (($p['note'] ?? '') !== ''): ?><p class="mt-12 max-w-2xl text-[0.9375rem] leading-relaxed text-muted"><?= e($p['note']) ?></p><?php endif; ?>
  </div>
</section>

<?php if (($r['title'] ?? '') !== ''): ?>
<section class="section-tight pb-24 md:pb-32">
  <div class="shell">
    <div <?= reveal() ?>>
      <a href="<?= e($r['href'] ?? '#') ?>" class="group flex max-w-3xl flex-col gap-8 rounded-3xl bg-sand/50 p-8 transition-colors duration-300 hover:bg-sand/70 sm:flex-row sm:items-center md:gap-12 md:p-10">
        <div class="w-40 shrink-0 sm:w-44"><?= media($r['image'] ?? '', ['ratio' => 'portrait', 'class' => 'shadow-[0_16px_38px_rgba(84,63,38,0.16)]', 'imageClass' => 'group-hover:scale-[1.03]']) ?></div>
        <div>
          <p class="eyebrow"><?= e($r['eyebrow'] ?? '') ?></p>
          <h2 class="mt-3 text-2xl leading-snug md:text-[1.75rem]"><?= e($r['title']) ?></h2>
          <p class="mt-3 text-[1rem] leading-relaxed text-body"><?= e($r['body'] ?? '') ?></p>
          <span class="link-arrow mt-5"><?= e($r['linkLabel'] ?? '') ?><?= icon('arrow-right', 'h-4 w-4') ?></span>
        </div>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<?= related_links($p['related'] ?? []) ?>
