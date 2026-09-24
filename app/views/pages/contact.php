<?php $p = page('contact'); $o = $p['other'] ?? []; ?>
<?= page_hero($p['hero'] ?? []) ?>
<?= page_blocks($p, 'top') ?>

<section class="section">
  <div class="shell">
    <div class="grid gap-16 lg:grid-cols-[1.1fr_1fr] lg:gap-24">
      <div <?= reveal() ?>>
        <h2 class="text-2xl"><?= e(v($p, 'form.title')) ?></h2>
        <p class="mt-3 mb-8 text-[1rem] leading-relaxed text-body"><?= e(v($p, 'form.intro')) ?></p>
        <?= contact_form('contact') ?>
      </div>

      <div <?= reveal('', 140) ?>>
        <h2 class="text-2xl"><?= e($o['title'] ?? '') ?></h2>
        <div class="mt-8 space-y-7">
          <div class="flex gap-4">
            <?= icon('mail', 'mt-0.5 h-5 w-5 shrink-0 text-accent') ?>
            <div>
              <p class="text-[0.8rem] tracking-[0.1em] text-muted uppercase"><?= e($o['emailLabel'] ?? '') ?></p>
              <?php if (($o['emailPrimary'] ?? '') !== ''): ?><p class="mt-1 text-[1rem] text-ink"><a href="mailto:<?= e($o['emailPrimary']) ?>"><?= e($o['emailPrimary']) ?></a></p><?php endif; ?>
              <?php if (($o['emailSecondary'] ?? '') !== ''): ?><p class="text-[0.9rem] text-muted"><a href="mailto:<?= e($o['emailSecondary']) ?>"><?= e($o['emailSecondary']) ?></a></p><?php endif; ?>
            </div>
          </div>
          <div class="flex gap-4">
            <?= icon('pin', 'mt-0.5 h-5 w-5 shrink-0 text-accent') ?>
            <div>
              <p class="text-[0.8rem] tracking-[0.1em] text-muted uppercase"><?= e($o['postLabel'] ?? '') ?></p>
              <p class="mt-1 text-[1rem] leading-relaxed text-ink"><?= nl2br(e($o['postAddress'] ?? ''), false) ?></p>
            </div>
          </div>
        </div>

        <div class="mt-10 border-t border-hairline pt-8">
          <p class="text-[0.8rem] tracking-[0.1em] text-muted uppercase"><?= e($o['followLabel'] ?? '') ?></p>
          <div class="mt-4 flex gap-2">
            <?php foreach (site('footer.socials', []) as $social): $href = (string) ($social['href'] ?? '#'); ?>
            <a href="<?= e($href) ?>" aria-label="<?= e($social['label'] ?? '') ?>"<?= preg_match('~^https?://~', $href) ? ' target="_blank" rel="noreferrer"' : '' ?> class="flex h-10 w-10 items-center justify-center rounded-full border border-hairline text-muted transition-all duration-300 hover:-translate-y-0.5 hover:border-accent hover:text-accent">
              <?= icon((string) ($social['icon'] ?? 'globe'), 'h-4 w-4') ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?= page_blocks($p) ?>
