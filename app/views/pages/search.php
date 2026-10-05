<?php
/** @var string $query */
/** @var array $results */
$terms = search_terms($query);
$count = count($results);
?>
<section class="section pb-10 md:pb-12">
  <div class="shell">
    <div class="max-w-3xl">
      <?= eyebrow_rule(site('labels.search') ?: 'Search') ?>
      <form action="/search" method="get" role="search" class="mt-6 flex items-center gap-3 border-b-2 border-ink/80 pb-3 focus-within:border-accent">
        <?= icon('search', 'h-6 w-6 shrink-0 text-muted') ?>
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(site('labels.searchPlaceholder') ?: 'Search the site') ?>" aria-label="<?= e(site('labels.search') ?: 'Search') ?>" autocomplete="off" maxlength="120" <?= $query === '' ? 'autofocus' : '' ?> class="min-w-0 flex-1 bg-transparent font-display text-[1.5rem] leading-tight text-ink placeholder:text-muted/70 focus:outline-none md:text-[2.25rem]">
        <button type="submit" class="shrink-0 rounded-full bg-accent px-5 py-2.5 text-[0.9rem] font-medium text-cream transition-colors duration-300 hover:bg-accent-dark"><?= e(site('labels.search') ?: 'Search') ?></button>
      </form>
      <p class="mt-5 text-[1.0625rem] leading-relaxed text-body" aria-live="polite">
        <?php if ($query === ''): ?>
        <?= e(site('labels.searchPrompt') ?: 'Type a word or phrase to search the site.') ?>
        <?php elseif ($count === 0): ?>
        <?= e(tpl(site('labels.searchNoResults') ?: 'Nothing found for “{query}”.', ['query' => $query])) ?>
        <?php else: ?>
        <?= e(tpl(site('labels.searchFound') ?: '{count} {results} for “{query}”', [
            'count' => $count,
            'results' => $count === 1 ? (site('labels.result') ?: 'result') : (site('labels.results') ?: 'results'),
            'query' => $query,
        ])) ?>
        <?php endif; ?>
      </p>
    </div>
  </div>
</section>

<?php if ($results): ?>
<section class="section pt-0">
  <div class="shell">
    <ol class="max-w-3xl">
      <?php foreach ($results as $i => $r): ?>
      <li <?= reveal('', min($i, 6) * 50) ?>>
        <a href="<?= e($r['url']) ?>" class="group block border-t border-hairline py-7">
          <div class="flex flex-wrap items-center gap-3">
            <?= tag($r['type']) ?>
            <?php if ($r['meta'] !== ''): ?><span class="text-[0.8rem] text-muted"><?= e($r['meta']) ?></span><?php endif; ?>
          </div>
          <h2 class="mt-3 text-[1.5rem] leading-snug transition-colors duration-300 group-hover:text-accent-dark"><?= search_mark($r['title'], $terms) ?></h2>
          <?php if ($r['snippet'] !== ''): ?><p class="mt-2.5 text-[0.9375rem] leading-relaxed text-body"><?= search_mark($r['snippet'], $terms) ?></p><?php endif; ?>
          <span class="mt-3 block text-[0.8rem] text-muted"><?= e($r['url']) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>
