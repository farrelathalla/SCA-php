<?php
$cards = [
    ['href' => '/admin/news/new', 'title' => 'Write a news story', 'body' => 'Publish a new update to News & Updates and the homepage.'],
    ['href' => '/admin/pages/home', 'title' => 'Edit the homepage', 'body' => 'Hero, mission, numbers, what we do and more.'],
    ['href' => '/admin/projects/new', 'title' => 'Add a project', 'body' => 'A new entry in the filterable projects archive.'],
    ['href' => '/admin/media', 'title' => 'Media library', 'body' => 'Upload photographs and documents to use across the site.'],
];
?>
<h1 class="font-display text-3xl text-ink">Welcome back</h1>
<p class="mt-2 max-w-2xl text-[0.95rem] leading-relaxed">Every piece of text and every image on the website can be changed from here. Choose a page on the left, edit it, and press <strong>Save</strong> — the live site updates immediately.</p>

<div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($cards as $card): ?>
  <a href="<?= e($card['href']) ?>" class="group rounded-2xl border border-hairline bg-cream p-5 transition-colors hover:border-accent/50">
    <h2 class="font-display text-lg text-ink group-hover:text-accent-dark"><?= e($card['title']) ?> →</h2>
    <p class="mt-1.5 text-[0.875rem] leading-relaxed"><?= e($card['body']) ?></p>
  </a>
  <?php endforeach; ?>
</div>

<div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
  <div class="rounded-2xl border border-hairline bg-cream p-5">
    <p class="eyebrow">Content</p>
    <ul class="mt-3 space-y-1.5 text-[0.9rem]">
      <li><a class="hover:text-accent-dark" href="/admin/news"><?= $counts['news'] ?> news stories</a></li>
      <li><a class="hover:text-accent-dark" href="/admin/projects"><?= $counts['projects'] ?> projects</a></li>
      <li><a class="hover:text-accent-dark" href="/admin/themes"><?= $counts['themes'] ?> Our Work themes</a></li>
      <li><a class="hover:text-accent-dark" href="/admin/programmes"><?= $counts['programmes'] ?> grant programmes</a></li>
    </ul>
  </div>
  <div class="rounded-2xl border border-hairline bg-cream p-5">
    <p class="eyebrow">Messages</p>
    <p class="mt-3 text-[0.9rem]"><a class="hover:text-accent-dark" href="/admin/messages"><?= $unread ?> unread contact message<?= $unread === 1 ? '' : 's' ?></a></p>
    <p class="mt-1.5 text-[0.9rem]"><a class="hover:text-accent-dark" href="/admin/messages?kind=newsletter"><?= $subscribers ?> newsletter sign-up<?= $subscribers === 1 ? '' : 's' ?></a></p>
  </div>
  <div class="rounded-2xl border border-hairline bg-cream p-5">
    <p class="eyebrow">Tips</p>
    <ul class="mt-3 list-disc space-y-1.5 pl-4 text-[0.875rem] leading-relaxed">
      <li>Lists (paragraphs, facts, people…) can be reordered with ↑ ↓ and extended with “+ Add”.</li>
      <li>Leave a field empty to hide it where that makes sense.</li>
      <li>Drafts in collections stay hidden until “Published” is ticked.</li>
    </ul>
  </div>
</div>
