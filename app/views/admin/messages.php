<div class="flex flex-wrap items-center justify-between gap-4">
  <h1 class="font-display text-3xl text-ink">Messages</h1>
  <a href="/admin/messages/export?kind=<?= e($kind) ?>" class="rounded-full border border-hairline bg-cream px-4 py-2 text-[0.875rem] text-ink hover:border-ink/40">Download CSV</a>
</div>

<div class="mt-5 flex gap-2">
  <a href="/admin/messages" class="rounded-full px-4 py-1.5 text-[0.875rem] <?= $kind === 'contact' ? 'bg-ink text-cream' : 'text-ink hover:bg-buff' ?>">Contact messages</a>
  <a href="/admin/messages?kind=newsletter" class="rounded-full px-4 py-1.5 text-[0.875rem] <?= $kind === 'newsletter' ? 'bg-ink text-cream' : 'text-ink hover:bg-buff' ?>">Newsletter sign-ups</a>
</div>

<div class="mt-6 space-y-3">
  <?php foreach ($rows as $row): ?>
  <div class="rounded-2xl border border-hairline bg-cream p-5 <?= $kind === 'contact' && !$row['is_read'] ? 'border-l-4 border-l-accent' : '' ?>">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-ink"><?= e($row['name'] ?: $row['email']) ?> <?php if ($row['name']): ?><a href="mailto:<?= e($row['email']) ?>" class="text-[0.875rem] text-accent-dark hover:underline">&lt;<?= e($row['email']) ?>&gt;</a><?php endif; ?></p>
        <p class="text-[0.8rem] text-muted"><?= e(format_date($row['created_at'])) ?> <?= e(substr($row['created_at'], 11, 5)) ?> UTC · <?= e($row['source']) ?></p>
      </div>
      <div class="flex gap-2">
        <?php if ($kind === 'contact' && !$row['is_read']): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button class="rounded-full border border-hairline px-3 py-1 text-[0.8rem] hover:border-ink/40">Mark read</button></form>
        <?php endif; ?>
        <form method="post" onsubmit="return confirm('Delete this entry?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $row['id'] ?>"><button class="rounded-full px-3 py-1 text-[0.8rem] text-muted hover:text-red-700">Delete</button></form>
      </div>
    </div>
    <?php if ($row['message']): ?><p class="mt-3 text-[0.9rem] leading-relaxed whitespace-pre-line text-body"><?= e($row['message']) ?></p><?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?><p class="rounded-2xl border border-hairline bg-cream p-8 text-center text-muted">Nothing yet.</p><?php endif; ?>
</div>
