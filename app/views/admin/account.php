<?php $input = 'w-full rounded-lg border border-hairline bg-white px-3 py-2 text-[0.9rem] text-ink focus:border-accent focus:outline-none'; ?>
<h1 class="font-display text-3xl text-ink">Account & users</h1>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
  <form method="post" class="space-y-4 rounded-2xl border border-hairline bg-cream p-6">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <h2 class="font-display text-xl text-ink">Change your password</h2>
    <div><label class="mb-1.5 block text-[0.85rem]">Current password</label><input type="password" name="current_password" required autocomplete="current-password" class="<?= $input ?>"></div>
    <div><label class="mb-1.5 block text-[0.85rem]">New password (at least 10 characters)</label><input type="password" name="new_password" required minlength="10" autocomplete="new-password" class="<?= $input ?>"></div>
    <button class="rounded-full bg-accent px-6 py-2.5 text-[0.9rem] font-medium text-cream hover:bg-accent-dark">Change password</button>
  </form>

  <div class="rounded-2xl border border-hairline bg-cream p-6">
    <h2 class="font-display text-xl text-ink">People who can sign in</h2>
    <ul class="mt-4 divide-y divide-hairline">
      <?php foreach ($users as $u): ?>
      <li class="flex items-center justify-between gap-3 py-2.5 text-[0.9rem]">
        <span><span class="text-ink"><?= e($u['username']) ?></span><span class="block text-[0.75rem] text-muted">Last signed in: <?= $u['last_login_at'] ? e(format_date($u['last_login_at'])) : 'never' ?></span></span>
        <?php if ((int) $u['id'] !== (int) $me['id']): ?>
        <form method="post" onsubmit="return confirm('Remove this user?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete-user"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="text-[0.8rem] text-muted hover:text-red-700">Remove</button></form>
        <?php else: ?><span class="text-[0.75rem] text-muted">you</span><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <form method="post" class="mt-5 space-y-3 border-t border-hairline pt-5">
      <?= csrf_field() ?><input type="hidden" name="action" value="add-user">
      <p class="text-[0.9rem] text-ink">Add someone</p>
      <div class="grid gap-3 sm:grid-cols-2">
        <input name="username" required placeholder="Username" class="<?= $input ?>">
        <input name="password" type="password" required minlength="10" placeholder="Password (10+ characters)" autocomplete="new-password" class="<?= $input ?>">
      </div>
      <button class="rounded-full border border-hairline bg-white px-5 py-2 text-[0.875rem] text-ink hover:border-ink/40">Add user</button>
    </form>
  </div>
</div>
