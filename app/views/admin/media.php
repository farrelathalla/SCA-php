<div class="flex flex-wrap items-center justify-between gap-4">
  <div>
    <h1 class="font-display text-3xl text-ink">Media library</h1>
    <p class="mt-1 text-[0.875rem] text-muted">Images up to 20 MB (JPG, PNG, WebP, GIF) and documents (PDF, Word). Large photos are resized automatically.</p>
    <p class="mt-1 max-w-3xl text-[0.875rem] text-muted">Under each photo: <strong class="font-medium text-body">Crop</strong> saves a cropped (zoomed-in) copy and leaves the original untouched; <strong class="font-medium text-body">Credit &amp; description</strong> sets the photographer credit shown when visitors hover over the photo anywhere on the site, and the description read out to blind visitors.</p>
  </div>
  <label class="cursor-pointer rounded-full bg-accent px-5 py-2.5 text-[0.9rem] font-medium text-cream hover:bg-accent-dark">
    Upload files
    <input type="file" multiple class="hidden" data-media-upload accept="image/*,.pdf,.doc,.docx">
  </label>
</div>

<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
  <?php foreach ($files as $file): $croppable = (bool) preg_match('/\.(jpe?g|png|webp)$/i', $file['url']); ?>
  <div id="<?= e(substr(md5($file['url']), 0, 10)) ?>" class="scroll-mt-6 overflow-hidden rounded-xl border border-hairline bg-cream">
    <?php if ($file['isImage']): ?>
    <a href="<?= e($file['url']) ?>" target="_blank"><img src="<?= e($file['url']) ?>" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover"></a>
    <?php else: ?>
    <a href="<?= e($file['url']) ?>" target="_blank" class="flex aspect-[4/3] items-center justify-center bg-sand/50 text-accent"><?= icon('document', 'h-10 w-10', 1.2) ?></a>
    <?php endif; ?>
    <div class="space-y-1.5 p-3">
      <p class="truncate text-[0.8rem] text-ink" title="<?= e($file['name']) ?>"><?= e($file['name']) ?></p>
      <div class="flex items-center gap-2">
        <input readonly value="<?= e($file['url']) ?>" class="min-w-0 flex-1 rounded border border-hairline bg-white px-1.5 py-0.5 font-mono text-[0.7rem]" onclick="this.select()">
        <?php if ($file['deletable']): ?>
        <form method="post" action="/admin/media/delete" onsubmit="return confirm('Delete this file? Anywhere it is used will show a placeholder instead.')">
          <?= csrf_field() ?><input type="hidden" name="path" value="<?= e($file['url']) ?>">
          <button type="submit" class="text-[0.75rem] text-muted hover:text-red-700">Delete</button>
        </form>
        <?php endif; ?>
      </div>
      <?php if ($file['isImage']): ?>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pt-0.5 text-[0.75rem]">
        <?php if ($croppable): ?><button type="button" data-crop="<?= e($file['url']) ?>" class="text-accent-dark hover:underline">Crop</button><?php endif; ?>
        <?php if ($file['credit'] !== ''): ?><span class="truncate text-muted" title="<?= e($file['credit']) ?>">© <?= e($file['credit']) ?></span><?php endif; ?>
      </div>
      <details class="text-[0.75rem]"<?= ($_GET['open'] ?? '') === $file['url'] ? ' open' : '' ?>>
        <summary class="cursor-pointer text-accent-dark hover:underline">Credit &amp; description</summary>
        <form method="post" action="/admin/media/meta" class="mt-2 space-y-2">
          <?= csrf_field() ?><input type="hidden" name="path" value="<?= e($file['url']) ?>">
          <label class="block text-body">Photographer credit<input name="credit" value="<?= e($file['credit']) ?>" placeholder="e.g. Jane Smith / SCA" class="mt-1 w-full rounded border border-hairline bg-white px-2 py-1 text-[0.8rem] text-ink"></label>
          <label class="block text-body">Description (alt text)<textarea name="alt" rows="2" placeholder="<?= e(image_alt($file['url'])) ?>" class="mt-1 w-full rounded border border-hairline bg-white px-2 py-1 text-[0.8rem] text-ink"><?= e($file['alt']) ?></textarea></label>
          <button type="submit" class="rounded-full bg-ink px-3 py-1 text-[0.75rem] text-cream hover:bg-ink/85">Save</button>
        </form>
      </details>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
