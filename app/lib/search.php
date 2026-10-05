<?php

/**
 * Site search: the header's search button and /search.
 *
 * Every fixed page and every published entry (news, projects, Our Work
 * themes, grant programmes and the pages built in the admin) is read straight
 * from the database on each search, so whatever SCA edit in the admin is
 * findable at once. The site is small enough that no separate index is needed.
 */

/** Keys whose values are never shown as text: pictures, links, switches, settings. */
const SEARCH_SKIP_KEYS = [
    'image', 'heroImage', 'href', 'icon', 'variant', 'imageSide', 'align', 'tone', 'columns',
    'block', 'background', 'anchorId', 'placement', 'projects', 'relatedProjects', 'count',
    'related', 'filters', 'meta', 'showDonationBand', 'hiddenSections', 'sectionOrder',
    'showPhotos', 'newestFirst', 'applicationsOpen', 'archiveType', 'logo', 'points',
    'placeholderText', 'afterParagraph',
];

/** What each kind of result is called on the results page. */
const SEARCH_TYPES = [
    'page' => 'Page',
    'news' => 'News',
    'project' => 'Project',
    'theme' => 'Our Work',
    'programme' => 'Grants & Awards',
];

/** The words of a query: lower-case, at most eight, no duplicates. */
function search_terms(string $query): array
{
    $words = preg_split('/\s+/u', mb_strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_slice(array_values(array_unique($words)), 0, 8);
}

/** The visible text of a stored document, one string per field, in page order. */
function search_texts($value, string $key = ''): array
{
    if ($key !== '' && ($key[0] === '_' || in_array($key, SEARCH_SKIP_KEYS, true)
        || preg_match('/(Href|Image|Url|Template)$/', $key))) {
        return [];
    }
    if (is_array($value)) {
        $out = [];
        foreach ($value as $k => $item) {
            array_push($out, ...search_texts($item, is_string($k) ? $k : ''));
        }
        return $out;
    }
    if (!is_string($value)) {
        return [];
    }
    $text = plain_text($value);
    // Paths, web addresses and e-mail links are not reading text.
    if ($text === '' || preg_match('~^(/|#|https?://|mailto:|tel:)~i', $text)) {
        return [];
    }
    return [$text];
}

/**
 * Everything the search looks through: one document per page or entry, with
 * its address, title, kind, a short description and its text.
 */
function search_documents(): array
{
    $docs = [];

    foreach (fixed_pages() as $path => [, $slug]) {
        $p = page($slug);
        if (!$p) {
            continue;
        }
        // Sections switched off in the admin are not on the page, so not found.
        $shown = array_filter($p, fn ($k) => shown($p, (string) $k), ARRAY_FILTER_USE_KEY);
        $docs[] = [
            'type' => 'page',
            'url' => $path,
            'title' => plain_text(v($p, 'meta.title') ?: (v($p, 'hero.title') ?: v($p, 'title'))),
            'summary' => plain_text(v($p, 'meta.description')),
            'meta' => '',
            'texts' => search_texts($shown),
        ];
    }

    foreach (['news', 'project', 'theme', 'programme', 'custom'] as $type) {
        foreach (entries($type) as $item) {
            $title = $type === 'custom' ? (v($item, 'meta.title') ?: v($item, 'title')) : v($item, 'title');
            $meta = '';
            if ($type === 'news') {
                $meta = implode('  ·  ', array_filter([format_date($item['date'] ?? ''), (string) ($item['category'] ?? '')]));
            } elseif ($type === 'project') {
                $meta = implode('  ·  ', array_filter([(string) ($item['type'] ?? ''), implode(', ', (array) ($item['countries'] ?? []))]));
            }
            $fields = $item;
            unset($fields['id'], $fields['slug'], $fields['sort_order'], $fields['published'], $fields['url'], $fields['date']);
            $docs[] = [
                'type' => $type === 'custom' ? 'page' : $type,
                'url' => $item['url'],
                'title' => plain_text($title),
                'summary' => plain_text(($item['excerpt'] ?? '') ?: (($item['summary'] ?? '') ?: (($item['strapline'] ?? '') ?: v($item, 'meta.description')))),
                'meta' => $meta,
                'texts' => search_texts($fields),
            ];
        }
    }

    return $docs;
}

/**
 * Pages and entries containing every word of $query, best first. Each result
 * has url, title, type (its label), meta and snippet (plain text).
 */
function search_site(string $query, int $limit = 50): array
{
    $terms = search_terms($query);
    if (!$terms) {
        return [];
    }
    $phrase = implode(' ', $terms);
    $results = [];

    foreach (search_documents() as $doc) {
        $title = mb_strtolower($doc['title']);
        $body = mb_strtolower(implode("\n", $doc['texts']));

        $score = 0;
        foreach ($terms as $term) {
            $inTitle = mb_strpos($title, $term) !== false;
            $count = mb_substr_count($body, $term);
            if (!$inTitle && $count === 0) {
                continue 2; // every word has to be there
            }
            $score += ($inTitle ? 8 : 0) + min($count, 10);
        }
        if (count($terms) > 1) {
            $score += (mb_strpos($title, $phrase) !== false ? 20 : 0) + (mb_strpos($body, $phrase) !== false ? 6 : 0);
        } elseif ($title === $phrase) {
            $score += 20;
        }

        $results[] = [
            'url' => $doc['url'],
            'title' => $doc['title'],
            'type' => site('labels.searchType' . ucfirst($doc['type'])) ?: SEARCH_TYPES[$doc['type']],
            'meta' => $doc['meta'],
            'snippet' => search_snippet($doc, $terms, $phrase),
            'score' => $score,
        ];
    }

    usort($results, fn ($a, $b) => $b['score'] <=> $a['score'] ?: strcmp($a['title'], $b['title']));
    return array_slice($results, 0, $limit);
}

/** The passage that best shows why a page matched, cut to about 200 characters. */
function search_snippet(array $doc, array $terms, string $phrase): string
{
    $best = '';
    $bestHits = 0;
    foreach ($doc['texts'] as $text) {
        if ($text === $doc['title']) {
            continue;
        }
        $lower = mb_strtolower($text);
        $hits = count(array_filter($terms, fn ($t) => mb_strpos($lower, $t) !== false)) * 2
            + (mb_strpos($lower, $phrase) !== false ? 1 : 0);
        // On a tie the longer passage wins: a sentence says more than a tag.
        if ($hits > $bestHits || ($hits > 0 && $hits === $bestHits && mb_strlen($text) > mb_strlen($best))) {
            [$best, $bestHits] = [$text, $hits];
        }
    }
    if ($best === '') {
        return search_trim($doc['summary'] ?: ($doc['texts'][0] ?? ''), 0);
    }
    $lower = mb_strtolower($best);
    $at = mb_strpos($lower, $phrase);
    if ($at === false) {
        $positions = array_filter(array_map(fn ($t) => mb_strpos($lower, $t), $terms), 'is_int');
        $at = $positions ? min($positions) : 0;
    }
    return search_trim($best, $at);
}

/** About 200 characters of $text around position $at, cut at word boundaries. */
function search_trim(string $text, int $at, int $length = 200): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $start = max(0, $at - 60);
    if ($start > 0) {
        $space = mb_strpos($text, ' ', $start);
        $start = $space !== false && $space < $at ? $space + 1 : $start;
    }
    $cut = mb_substr($text, $start, $length);
    if ($start + $length < mb_strlen($text)) {
        $space = mb_strrpos($cut, ' ');
        $cut = ($space !== false && $space > $length / 2 ? mb_substr($cut, 0, $space) : $cut) . '…';
    }
    return ($start > 0 ? '…' : '') . $cut;
}

/** Escaped $text with the query's words wrapped in <mark>. */
function search_mark(string $text, array $terms): string
{
    if (!$terms) {
        return e($text);
    }
    usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    $pattern = '/(' . implode('|', array_map(fn ($t) => preg_quote($t, '/'), $terms)) . ')/iu';
    $out = '';
    foreach (preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text] as $i => $part) {
        $out .= $i % 2 ? '<mark class="rounded-[2px] bg-accent-soft text-ink">' . e($part) . '</mark>' : e($part);
    }
    return $out;
}
