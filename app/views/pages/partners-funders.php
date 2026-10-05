<?php $p = page('partners-funders'); ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<?php foreach (shown($p, 'groups') ? array_values($p['groups'] ?? []) : [] as $gi => $group): ?>
<section class="<?= $gi === 0 ? 'section' : 'section-tight pb-24 md:pb-32' ?>">
  <div class="shell">
    <?= section_heading('', (string) ($group['title'] ?? '')) ?>
    <div class="mt-12 card-row [--cols:2] [--gap-x:2rem] gap-y-10 sm:[--cols:3] md:[--cols:4] md:gap-y-14 md:[--gap-x:3rem]">
      <?php foreach (array_values($group['partners'] ?? []) as $i => $partner):
          $href = (string) ($partner['href'] ?? '#'); ?>
      <div <?= reveal('relative hover:z-30 focus-within:z-30', $i * 60) ?>>
        <a href="<?= e($href) ?>"<?= preg_match('~^https?://~', $href) ? ' target="_blank" rel="noreferrer"' : '' ?> class="group relative flex h-24 items-center justify-center rounded-2xl px-6 text-center transition-colors duration-300 hover:bg-cream-deep focus-visible:bg-cream-deep">
          <?php if (trim((string) ($partner['logo'] ?? '')) !== ''): ?>
          <img src="<?= e($partner['logo']) ?>" alt="<?= e($partner['name'] ?? '') ?>" class="max-h-16 w-auto object-contain opacity-80 transition-opacity duration-300 group-hover:opacity-100">
          <?php if (trim((string) ($partner['name'] ?? '')) !== ''): ?>
          <!-- The organisation's name, shown on hover or keyboard focus. -->
          <span aria-hidden="true" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 w-max max-w-[16rem] -translate-x-1/2 translate-y-1 rounded-lg bg-ink px-3 py-2 text-[0.8rem] leading-snug text-cream opacity-0 shadow-[0_10px_24px_rgba(46,35,26,0.18)] transition-all duration-200 group-hover:translate-y-0 group-hover:opacity-100 group-focus-visible:translate-y-0 group-focus-visible:opacity-100"><?= e($partner['name']) ?><span class="absolute top-full left-1/2 -translate-x-1/2 border-x-[6px] border-t-[6px] border-x-transparent border-t-ink"></span></span>
          <?php endif; ?>
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
