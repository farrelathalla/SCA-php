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
