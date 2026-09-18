<?php $n = site('notFound', []); ?>
<section class="section">
  <div class="shell">
    <div class="max-w-xl py-16">
      <?= eyebrow_rule((string) ($n['eyebrow'] ?? '404')) ?>
      <h1 class="mt-5 text-[2.5rem] leading-[1.08] md:text-5xl"><?= e($n['title'] ?? '') ?></h1>
      <p class="mt-6 text-lg leading-relaxed text-body"><?= e($n['intro'] ?? '') ?></p>
      <div class="mt-9 flex flex-wrap gap-3">
        <?= button((string) ($n['primaryHref'] ?? '/'), (string) ($n['primaryLabel'] ?? '')) ?>
        <?= button((string) ($n['secondaryHref'] ?? '/'), (string) ($n['secondaryLabel'] ?? ''), 'outline', '', false) ?>
      </div>
    </div>
  </div>
</section>
