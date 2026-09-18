<div class="flex flex-wrap items-center justify-between gap-4">
  <h1 class="font-display text-3xl text-ink"><?= e($meta['label']) ?></h1>
  <a href="/admin/<?= e($route) ?>/new" class="rounded-full bg-accent px-5 py-2.5 text-[0.9rem] font-medium text-cream hover:bg-accent-dark">+ New <?= e($meta['singular']) ?></a>
</div>
<?php if ($type !== 'news'): ?>
<p class="mt-2 text-[0.875rem] text-muted">Shown on the site in the order below (lowest “order” number first).</p>
<?php else: ?>
<p class="mt-2 text-[0.875rem] text-muted">Newest first. The three most recent appear on the homepage.</p>
<?php endif; ?>

<div class="mt-6 overflow-x-auto rounded-2xl border border-hairline bg-cream">
  <table class="w-full text-left text-[0.9rem]">
    <thead class="border-b border-hairline text-[0.75rem] tracking-wide text-muted uppercase">
      <tr>
        <th class="px-4 py-3 font-medium">Title</th>
        <th class="px-4 py-3 font-medium"><?= $type === 'news' ? 'Date' : 'Order' ?></th>
        <th class="px-4 py-3 font-medium">Status</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr class="border-b border-hairline/70 last:border-0">
        <td class="px-4 py-3">
          <a href="/admin/<?= e($route) ?>/<?= $item['id'] ?>" class="flex items-center gap-3 text-ink hover:text-accent-dark">
            <?php if (($item['image'] ?? '') !== ''): ?><img src="<?= e($item['image']) ?>" alt="" class="h-10 w-14 shrink-0 rounded-md object-cover" onerror="this.remove()"><?php endif; ?>
            <span><?= e($item['title'] ?? $item['slug']) ?><span class="block text-[0.75rem] text-muted"><?= e($item['url']) ?></span></span>
          </a>
        </td>
        <td class="px-4 py-3 whitespace-nowrap text-muted"><?= $type === 'news' ? e(format_date($item['date'] ?? '')) : $item['sort_order'] ?></td>
        <td class="px-4 py-3"><?= $item['published'] ? '<span class="rounded-full bg-accent-soft px-2.5 py-0.5 text-[0.75rem] text-accent-dark">Published</span>' : '<span class="rounded-full bg-sand px-2.5 py-0.5 text-[0.75rem] text-body">Draft</span>' ?></td>
        <td class="px-4 py-3 text-right whitespace-nowrap">
          <a href="/admin/<?= e($route) ?>/<?= $item['id'] ?>" class="text-accent-dark hover:underline">Edit</a>
          <?php if ($item['published']): ?><a href="<?= e($item['url']) ?>" target="_blank" class="ml-3 text-muted hover:text-ink">View ↗</a><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="4" class="px-4 py-8 text-center text-muted">Nothing here yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
