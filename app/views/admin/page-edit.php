<?php [$label, $url] = $info; ?>
<div class="flex flex-wrap items-start justify-between gap-4">
  <div>
    <p class="eyebrow">Edit page</p>
    <h1 class="mt-1 font-display text-3xl text-ink"><?= e($label) ?></h1>
    <p class="mt-1 text-[0.85rem] text-muted"><?= $updatedAt ? 'Last saved ' . e(format_date($updatedAt)) . ' ' . e(substr((string) $updatedAt, 11, 5)) . ' UTC' : '' ?></p>
  </div>
  <a href="<?= e($url) ?>" target="_blank" class="rounded-full border border-hairline bg-cream px-4 py-2 text-[0.875rem] text-ink hover:border-ink/40">View page ↗</a>
</div>

<form method="post" class="mt-8" data-content-form>
  <?= csrf_field() ?>
  <input type="hidden" name="data" data-json>
  <?php if (!empty($movable)): ?>
  <section class="mb-5 rounded-2xl border border-hairline bg-white p-5 md:p-6">
    <h2 class="font-display text-xl text-ink">Web address</h2>
    <p class="mt-1 text-[0.85rem] text-muted">Where this page lives on the site. Change it if the page has been renamed — the old address keeps working (it forwards visitors to the new one), and links to it in the menus, footer and text are updated when you save.</p>
    <div class="mt-4 flex max-w-xl items-center gap-2">
      <span class="text-[0.9rem] text-muted"><?= e($_SERVER['HTTP_HOST'] ?? '') ?></span>
      <input name="page_path" value="<?= e($url) ?>" pattern="/?[a-z0-9\-]+(/[a-z0-9\-]+)*" class="w-full rounded-lg border border-hairline bg-white px-3 py-2 font-mono text-[0.85rem] text-ink focus:border-accent focus:outline-none">
    </div>
    <?php if ($originalPath !== '' && $originalPath !== $url): ?><p class="mt-2 text-[0.8rem] text-muted">Originally <?= e($originalPath) ?>.</p><?php endif; ?>
  </section>
  <?php endif; ?>
  <?= render_document_form($data, $shape) ?>

  <div class="sticky bottom-0 z-10 -mx-4 mt-8 flex flex-wrap items-center gap-3 border-t border-hairline bg-cream-deep/95 px-4 py-4 backdrop-blur md:-mx-10 md:px-10">
    <button type="submit" class="rounded-full bg-accent px-7 py-2.5 font-medium text-cream transition-colors hover:bg-accent-dark">Save</button>
    <a href="<?= e($url) ?>" target="_blank" class="text-[0.875rem] text-accent-dark hover:underline">View page ↗</a>
    <span class="text-[0.8rem] text-muted" data-dirty-note></span>
  </div>
</form>

<form method="post" class="mt-6" onsubmit="return confirm('Replace everything on this page with the original design content? Your edits to this page will be lost.')">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="restore">
  <button type="submit" class="text-[0.8rem] text-muted underline hover:text-red-700">Restore this page’s original content</button>
</form>
