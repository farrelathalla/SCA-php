<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Sign in — SCA Admin</title>
  <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=Playfair+Display:wght@400..700&display=swap">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="flex min-h-screen items-center justify-center bg-cream-deep px-4">
  <div class="w-full max-w-sm">
    <div class="mb-8 text-center">
      <img src="<?= e(local_url((string) site('site.logoFull'))) ?>" alt="<?= e(site('site.name')) ?>" class="mx-auto h-20 w-auto">
      <p class="mt-4 eyebrow">Content admin</p>
    </div>
    <form method="post" action="/admin/login" class="space-y-4 rounded-2xl border border-hairline bg-cream p-7 shadow-[0_18px_44px_rgba(84,63,38,0.08)]">
      <?= csrf_field() ?>
      <?php if ($error): ?><p class="rounded-lg bg-red-50 px-3 py-2 text-[0.875rem] text-red-800"><?= e($error) ?></p><?php endif; ?>
      <div>
        <label for="username" class="mb-1.5 block text-[0.875rem] text-body">Username</label>
        <input id="username" name="username" required autofocus autocomplete="username" class="w-full rounded-xl border border-hairline bg-white px-4 py-2.5 text-ink focus:border-accent focus:outline-none">
      </div>
      <div>
        <label for="password" class="mb-1.5 block text-[0.875rem] text-body">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-hairline bg-white px-4 py-2.5 text-ink focus:border-accent focus:outline-none">
      </div>
      <button type="submit" class="w-full rounded-full bg-accent px-6 py-3 font-medium text-cream transition-colors hover:bg-accent-dark">Sign in</button>
    </form>
    <p class="mt-6 text-center text-[0.8rem] text-muted"><a href="/" class="hover:text-accent-dark">← Back to the website</a></p>
  </div>
</body>
</html>
