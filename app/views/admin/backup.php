<?php
$btn = 'rounded-full bg-accent px-6 py-2.5 text-[0.9rem] font-medium text-cream hover:bg-accent-dark';
$size = $files_bytes >= 1048576 ? round($files_bytes / 1048576, 1) . ' MB' : max(1, (int) round($files_bytes / 1024)) . ' KB';
?>
<h1 class="font-display text-3xl text-ink">Backup</h1>
<p class="mt-3 max-w-2xl text-[0.95rem] text-muted">Keep a copy of the website on your own computer. Do this now and then (for example once a month, and before big changes), and store the files somewhere safe, such as an external drive or cloud storage.</p>

<?php if (!$available): ?>
<p class="mt-6 rounded-2xl border border-hairline bg-cream p-5 text-[0.9rem] text-red-700">Backups are not available on this server right now (the zip extension is missing). Please ask your developer.</p>
<?php else: ?>
<div class="mt-8 grid gap-6 lg:grid-cols-2">
  <form method="post" class="space-y-4 rounded-2xl border border-hairline bg-cream p-6">
    <?= csrf_field() ?><input type="hidden" name="action" value="content">
    <h2 class="font-display text-xl text-ink">1. Website content</h2>
    <p class="text-[0.9rem] text-muted">All pages, news, projects, settings, image descriptions and the messages sent through the forms. A small file that takes a few seconds. Passwords are not included.</p>
    <button class="<?= $btn ?>">Download content backup</button>
  </form>

  <form method="post" class="space-y-4 rounded-2xl border border-hairline bg-cream p-6">
    <?= csrf_field() ?><input type="hidden" name="action" value="files">
    <h2 class="font-display text-xl text-ink">2. Photos and documents</h2>
    <p class="text-[0.9rem] text-muted">Everything you uploaded in the Media library: <?= (int) $files_count ?> files, about <?= e($size) ?>. A large backup can take a minute or two to prepare, so please wait for the download to start.</p>
    <button class="<?= $btn ?>">Download files backup</button>
  </form>
</div>
<p class="mt-6 max-w-2xl text-[0.85rem] text-muted">Both downloads are needed for a complete copy. The site's design and code are stored separately by your developer.</p>
<?php endif; ?>
