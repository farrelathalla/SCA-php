<?php
/**
 * Tell Bing, Yandex & co. (IndexNow) which addresses changed, so they recrawl
 * them now instead of in a few weeks. Run locally after the site is deployed:
 *
 *   php scripts/indexnow.php            # list what would be sent
 *   php scripts/indexnow.php --send     # submit
 *
 * Sends every address in the live sitemap plus the old WordPress addresses that
 * now redirect (301) or answer 410 Gone. The key file public/<key>.txt must be
 * live first. The key is not a secret (IndexNow requires it to be public).
 */
const HOST = 'saiga-conservation.org';
const ENDPOINTS = ['https://www.bing.com/indexnow', 'https://yandex.com/indexnow']; // each engine shares with the others

$keyFiles = glob(__DIR__ . '/../public/[0-9a-f]*.txt');
$keyFiles = array_filter($keyFiles, fn($f) => preg_match('/^[0-9a-f]{32}\.txt$/', basename($f)));
if (!$keyFiles) { fwrite(STDERR, "No public/<key>.txt found.\n"); exit(1); }
$key = basename(reset($keyFiles), '.txt');
$keyLocation = 'https://' . HOST . '/' . $key . '.txt';

$urls = [];
$sitemap = file_get_contents('https://' . HOST . '/sitemap.xml');
preg_match_all('#<loc>([^<]+)</loc>#', (string) $sitemap, $m);
$urls = array_merge($urls, $m[1]);

$src = file_get_contents(__DIR__ . '/../app/lib/content.php');
preg_match('/const LEGACY_PAGES = \[(.*?)\n\];/s', $src, $a);
preg_match_all("/'([^']+)'\s*=>/", $a[1] ?? '', $pages);
preg_match('/const LEGACY_ARCHIVED = \[(.*?)\n\];/s', $src, $b);
preg_match_all("/'([^']+)'/", $b[1] ?? '', $gone);
foreach (array_merge($pages[1], $gone[1]) as $p) {
    $urls[] = 'https://' . HOST . '/' . $p . '/';
    $urls[] = 'https://' . HOST . '/' . $p;
}
$urls = array_values(array_unique($urls));

echo count($urls) . " addresses\n";
if (!in_array('--send', $argv, true)) { echo implode("\n", $urls) . "\n"; exit(0); }

// The key file must be reachable, or IndexNow rejects the submission (403).
$check = @file_get_contents($keyLocation);
if (trim((string) $check) !== $key) { fwrite(STDERR, "$keyLocation is not live yet. Deploy first.\n"); exit(1); }

foreach (ENDPOINTS as $endpoint) {
    foreach (array_chunk($urls, 9000) as $chunk) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS => json_encode(['host' => HOST, 'key' => $key, 'keyLocation' => $keyLocation, 'urlList' => $chunk]),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        echo $endpoint . ' answered HTTP ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " (200/202 = accepted)
";
    }
}
