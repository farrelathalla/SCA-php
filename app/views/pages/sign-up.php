<?php $p = page('sign-up'); ?>
<section class="section">
  <div class="shell">
    <div class="grid items-center gap-14 lg:grid-cols-[1.05fr_1fr] lg:gap-24">
      <div <?= reveal() ?>>
        <?= eyebrow_rule((string) ($p['eyebrow'] ?? '')) ?>
        <h1 class="mt-5 text-[2.5rem] leading-[1.08] md:text-[3.25rem]"><?= e($p['title'] ?? '') ?></h1>
        <p class="mt-6 max-w-lg text-lg leading-relaxed text-body"><?= e($p['intro'] ?? '') ?></p>
        <div class="mt-10 max-w-xl"><?= newsletter_form('', 'large') ?></div>
        <?php if (($p['note'] ?? '') !== ''): ?><p class="mt-8 max-w-md text-[0.9rem] leading-relaxed text-muted"><?= e($p['note']) ?></p><?php endif; ?>
      </div>
      <div <?= reveal('', 140) ?>><?= media((string) ($p['image'] ?? ''), ['ratio' => 'landscape', 'class' => 'shadow-[0_24px_60px_rgba(84,63,38,0.10)]']) ?></div>
    </div>
  </div>
</section>

<?= related_links($p['related'] ?? []) ?>
