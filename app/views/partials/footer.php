<?php $f = site('footer', []); ?>
<footer class="border-t border-hairline bg-cream-deep">
  <div class="shell py-16 md:py-20">
    <div class="grid gap-12 lg:grid-cols-[1.15fr_2fr] lg:gap-16">
      <div>
        <a href="/" class="inline-block">
          <img src="<?= e(site('site.logoFull')) ?>" alt="<?= e(site('site.name')) ?>" width="660" height="379" class="h-24 w-auto">
        </a>
        <p class="mt-6 max-w-xs text-[0.925rem] leading-relaxed text-body"><?= e($f['tagline'] ?? '') ?></p>
        <div class="mt-6 space-y-1 text-[0.9rem] text-muted">
          <?php foreach ($f['contactLines'] ?? [] as $line): ?><p><?= e($line) ?></p><?php endforeach; ?>
        </div>
        <div class="mt-7 flex gap-3">
          <?php foreach ($f['socials'] ?? [] as $social): $href = (string) ($social['href'] ?? '#'); ?>
          <a href="<?= e($href) ?>" aria-label="<?= e($social['label'] ?? '') ?>"<?= preg_match('~^https?://~', $href) ? ' target="_blank" rel="noreferrer"' : '' ?> class="flex h-11 w-11 items-center justify-center rounded-full border border-hairline text-body transition-all duration-300 hover:-translate-y-0.5 hover:border-accent hover:bg-cream hover:text-accent">
            <?= icon((string) ($social['icon'] ?? 'globe'), 'h-5 w-5') ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <div class="grid gap-10 sm:grid-cols-3">
          <?php foreach ($f['columns'] ?? [] as $column): ?>
          <div>
            <h3 class="eyebrow font-sans"><?= e($column['title'] ?? '') ?></h3>
            <ul class="mt-5 space-y-3">
              <?php foreach ($column['links'] ?? [] as $link): ?>
              <li><a href="<?= e($link['href'] ?? '') ?>" class="text-[0.925rem] text-body transition-colors duration-300 hover:text-accent-dark"><?= e($link['label'] ?? '') ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-12 border-t border-hairline pt-10">
          <h3 class="font-display text-xl text-ink"><?= e($f['signupTitle'] ?? '') ?></h3>
          <div class="mt-5 max-w-xl"><?= newsletter_form('', 'default', (string) ($f['signupNote'] ?? '')) ?></div>
        </div>
      </div>
    </div>

    <div class="mt-14 flex flex-col gap-3 border-t border-hairline pt-8 text-[0.8rem] text-muted sm:flex-row sm:items-center sm:justify-between">
      <p>© <?= date('Y') ?> <?= e($f['copyright'] ?? '') ?></p>
      <a href="<?= e($f['privacyHref'] ?? '#') ?>" class="transition-colors hover:text-accent-dark"><?= e($f['privacyLabel'] ?? '') ?></a>
    </div>
  </div>
</footer>
