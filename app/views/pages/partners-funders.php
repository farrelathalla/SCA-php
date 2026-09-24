<?php $p = page('partners-funders'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php foreach (array_values($p['groups'] ?? []) as $gi => $group): ?>
<section class="<?= $gi === 0 ? 'section' : 'section-tight pb-24 md:pb-32' ?>">
  <div class="shell">
    <?= section_heading('', (string) ($group['title'] ?? '')) ?>
    <div class="mt-12 grid grid-cols-2 gap-x-8 gap-y-10 sm:grid-cols-3 md:grid-cols-4 md:gap-x-12 md:gap-y-14">
      <?php foreach (array_values($group['partners'] ?? []) as $i => $partner):
          $href = (string) ($partner['href'] ?? '#'); ?>
      <div <?= reveal('', $i * 60) ?>>
        <a href="<?= e($href) ?>"<?= preg_match('~^https?://~', $href) ? ' target="_blank" rel="noreferrer"' : '' ?> class="group flex h-24 items-center justify-center rounded-2xl px-6 text-center transition-colors duration-300 hover:bg-cream-deep">
          <?php if (trim((string) ($partner['logo'] ?? '')) !== ''): ?>
          <img src="<?= e($partner['logo']) ?>" alt="<?= e($partner['name'] ?? '') ?>" class="max-h-16 w-auto object-contain opacity-80 transition-opacity duration-300 group-hover:opacity-100">
          <?php else: ?>
          <span class="text-[0.8rem] leading-snug tracking-[0.08em] text-muted uppercase transition-colors duration-300 group-hover:text-accent-dark"><?= e($partner['name'] ?? '') ?></span>
          <?php endif; ?>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<?= page_blocks($p) ?>
