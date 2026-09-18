<?php
$isNew = empty($entry['id']);
$publicUrl = $entry['slug'] !== '' ? $meta['base'] . $entry['slug'] : null;
$input = 'w-full rounded-lg border border-hairline bg-white px-3 py-2 text-[0.9rem] text-ink focus:border-accent focus:outline-none';
?>
<div class="flex flex-wrap items-start justify-between gap-4">
  <div>
    <p class="eyebrow"><a href="/admin/<?= e($route) ?>" class="hover:text-accent-dark"><?= e($meta['label']) ?></a></p>
    <h1 class="mt-1 font-display text-3xl text-ink"><?= $isNew ? 'New ' . e($meta['singular']) : e($data['title'] ?? $entry['slug']) ?></h1>
  </div>
  <?php if (!$isNew && $entry['published']): ?><a href="<?= e($publicUrl) ?>" target="_blank" class="rounded-full border border-hairline bg-cream px-4 py-2 text-[0.875rem] text-ink hover:border-ink/40">View on site ↗</a><?php endif; ?>
</div>

<form method="post" class="mt-8" data-content-form>
  <?= csrf_field() ?>
  <input type="hidden" name="data" data-json>

  <section class="mb-5 grid gap-4 rounded-2xl border border-hairline bg-white p-5 md:grid-cols-[2fr_1fr_1fr] md:p-6">
    <div>
      <label class="mb-1.5 block text-[0.85rem] text-body" for="slug">Web address</label>
      <div class="flex items-center gap-1"><span class="shrink-0 font-mono text-[0.8rem] text-muted"><?= e($meta['base']) ?></span><input id="slug" name="slug" value="<?= e($entry['slug']) ?>" placeholder="made from the title if left empty" class="<?= $input ?> font-mono text-[0.8rem]" data-slug></div>
    </div>
    <div>
      <label class="mb-1.5 block text-[0.85rem] text-body" for="sort_order"><?= $type === 'news' ? 'Order (ties only)' : 'Order' ?></label>
      <input id="sort_order" name="sort_order" type="number" value="<?= (int) $entry['sort_order'] ?>" class="<?= $input ?>">
    </div>
    <div class="flex items-end pb-2">
      <label class="inline-flex items-center gap-2 text-[0.9rem] text-ink"><input type="checkbox" name="published" value="1" class="h-4 w-4 accent-[#c87a3c]"<?= $entry['published'] ? ' checked' : '' ?>> Published</label>
    </div>
  </section>

  <?= render_document_form($data, $shape) ?>

  <div class="sticky bottom-0 z-10 -mx-4 mt-8 flex flex-wrap items-center gap-3 border-t border-hairline bg-cream-deep/95 px-4 py-4 backdrop-blur md:-mx-10 md:px-10">
    <button type="submit" class="rounded-full bg-accent px-7 py-2.5 font-medium text-cream transition-colors hover:bg-accent-dark">Save</button>
    <a href="/admin/<?= e($route) ?>" class="text-[0.875rem] text-muted hover:text-ink">Back to list</a>
    <span class="text-[0.8rem] text-muted" data-dirty-note></span>
  </div>
</form>

<?php if (!$isNew): ?>
<form method="post" class="mt-6" onsubmit="return confirm('Delete this <?= e($meta['singular']) ?> permanently?')">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete">
  <button type="submit" class="text-[0.8rem] text-muted underline hover:text-red-700">Delete this <?= e($meta['singular']) ?></button>
</form>
<?php endif; ?>
