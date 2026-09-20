<?php
/**
 * A page built in the admin from blocks (app/lib/blocks.php). The hero is the
 * same one every other page uses; everything below it is whatever blocks SCA
 * have stacked, in order.
 *
 * @var array $custom
 */
$hero = $custom['hero'] ?? [];
if (trim((string) ($hero['title'] ?? '')) === '') {
    $hero['title'] = $custom['title'] ?? '';
}
?>
<?= page_hero($hero) ?>
<?= render_blocks($custom['sections'] ?? []) ?>
<?php if (!empty($custom['showDonationBand'])): ?><?= cta_band() ?><?php endif; ?>
