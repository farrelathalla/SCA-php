<?php

/**
 * Built pages: the blocks SCA can stack in the admin to make a new page
 * without a developer.
 *
 * A built page is an entry of the 'custom' collection. Its `sections` value is
 * a plain list of blocks, each one an object whose `block` key names its type.
 * BLOCK_TYPES below is the single source of truth: it gives the admin the
 * fields to show (the "shape", exactly as the seed files do for fixed pages)
 * and gives render_block() the values to draw. Adding a block type here makes
 * it appear in the "Add new block" menu and on the site, with no other change.
 *
 * Every block also carries:
 *   background — the strip it sits on (default, soft beige or sand)
 *   anchorId   — an id, so a hero jump link or a menu item can point at it
 */

const BLOCK_TYPES = [
    'intro' => [
        'label' => 'Intro paragraph',
        'hint' => 'One large opening paragraph, as at the top of “Why saigas matter”.',
        'fields' => ['text' => ''],
    ],
    'heading' => [
        'label' => 'Section heading',
        'hint' => 'Eyebrow, heading and a short standfirst, to open a section.',
        'fields' => ['eyebrow' => '', 'title' => '', 'intro' => '', 'align' => 'left'],
    ],
    'text' => [
        'label' => 'Text',
        'hint' => 'Running text at reading width, with an optional heading.',
        'fields' => ['title' => '', 'body' => ['']],
    ],
    'photoText' => [
        'label' => 'Text with photo',
        'hint' => 'A photograph beside text, with optional link and button.',
        'fields' => [
            'eyebrow' => '', 'title' => '', 'image' => '', 'imageSide' => 'left', 'body' => [''],
            'linkLabel' => '', 'linkHref' => '', 'buttonLabel' => '', 'buttonHref' => '',
        ],
    ],
    'facts' => [
        'label' => 'List with symbols',
        'hint' => 'Icon, short title and a line of text — up to four per row.',
        'fields' => [
            'eyebrow' => '', 'title' => '', 'intro' => '', 'columns' => '3',
            'items' => [['icon' => 'leaf', 'title' => '', 'body' => '', 'href' => '']],
        ],
    ],
    'numbers' => [
        'label' => 'Numbers section',
        'hint' => 'Three large figures that count up, on a coloured strip.',
        'fields' => ['eyebrow' => '', 'tone' => 'sand', 'stats' => [['value' => '', 'unit' => '', 'label' => '']]],
    ],
    'timeline' => [
        'label' => 'Timeline',
        'hint' => 'Years down a centre line, as on “Our story”.',
        'fields' => [
            'eyebrow' => '', 'title' => '', 'intro' => '', 'showPhotos' => true, 'newestFirst' => false,
            'items' => [['year' => '', 'title' => '', 'body' => '', 'image' => '']],
        ],
    ],
    'gallery' => [
        'label' => 'Photo gallery',
        'hint' => 'Photographs in a row of three.',
        'fields' => ['title' => '', 'gallery' => ['']],
    ],
    'callout' => [
        'label' => 'Highlighted box',
        'hint' => 'A short passage set on a sand panel.',
        'fields' => ['title' => '', 'body' => ['']],
    ],
    'resourceLinks' => [
        'label' => 'Links & resources list',
        'hint' => 'A list of links or downloads, each with a line of explanation.',
        'fields' => [
            'eyebrow' => '', 'title' => '',
            'items' => [['icon' => 'document', 'title' => '', 'body' => '', 'href' => '']],
        ],
    ],
    'downloads' => [
        'label' => 'Downloads',
        'hint' => 'Files to download — PDFs, reports, forms.',
        'fields' => ['eyebrow' => '', 'title' => '', 'documents' => [['title' => '', 'href' => '']]],
    ],
    'actions' => [
        'label' => 'Buttons & jump link',
        'hint' => 'Buttons, and the orange “↓” link that jumps further down the page.',
        'fields' => [
            'buttons' => [['label' => '', 'href' => '', 'style' => 'primary']],
            'jumpLabel' => '', 'jumpHref' => '',
        ],
    ],
    'contact' => [
        'label' => 'Contact form',
        'hint' => 'The same form as Contact Us. Messages arrive under Messages.',
        'fields' => ['eyebrow' => '', 'title' => '', 'intro' => ''],
    ],
    'newsletter' => [
        'label' => 'Newsletter sign-up',
        'hint' => 'The inline email sign-up, with a short heading.',
        'fields' => ['title' => '', 'body' => ''],
    ],
    'relatedLinks' => [
        'label' => 'Related links',
        'hint' => 'The “You may also be interested in” row at the foot of a page.',
        'fields' => ['title' => 'You may also be interested in', 'links' => [['label' => '', 'href' => '']]],
    ],
    'donationBand' => [
        'label' => 'Donation bar',
        'hint' => 'The “Keep them moving” band. Its wording is under Header, footer & site-wide.',
        'fields' => [],
    ],
];

/** These block types draw their own <section>, so they are not wrapped again. */
const SELF_CONTAINED_BLOCKS = ['numbers', 'relatedLinks', 'donationBand'];

const BLOCK_BACKGROUNDS = ['' => 'Page background', 'cream-deep' => 'Soft beige', 'sand' => 'Sand'];

/**
 * The site's own pages (Home, About, Donate, …) keep their designed layout,
 * and SCA can add blocks to them too: an `extraBlocks` list on the page, each
 * block placed straight under the page header or at the end of the page
 * (above the related links and the donation band).
 */
const BLOCK_PLACEMENTS = ['end' => 'At the end of the page', 'top' => 'Straight under the page header'];

/** True while the admin edits a fixed page, whose blocks carry a placement. */
function block_placement(?bool $set = null): bool
{
    static $on = false;
    if ($set !== null) {
        $on = $set;
    }
    return $on;
}

/** The extra blocks of a fixed page that belong at one placement. */
function page_blocks(array $page, string $placement = 'end'): string
{
    $blocks = array_filter($page['extraBlocks'] ?? [], fn ($b) => is_array($b)
        && (($b['placement'] ?? 'end') === 'top' ? 'top' : 'end') === $placement);
    return render_blocks(array_values($blocks));
}

/**
 * Everything a built page has besides its blocks. This is the "shape" the
 * admin builds the form from, exactly as database/seed/pages.json is for the
 * fixed pages, and it doubles as the starting point for a new page.
 */
function custom_page_shape(): array
{
    return [
        'title' => '',
        'meta' => ['title' => '', 'description' => ''],
        'hero' => [
            'eyebrow' => '', 'title' => '', 'intro' => '', 'image' => '', 'variant' => 'overlay',
            'jumpLabel' => '', 'jumpHref' => '',
        ],
        'sections' => [],
        'showDonationBand' => true,
    ];
}

/** An empty block of one type, as the admin's "Add new block" inserts it. */
function block_shape(string $type): array
{
    return array_merge(
        ['block' => $type],
        block_placement() ? ['placement' => 'end'] : [],
        ['background' => '', 'anchorId' => ''],
        BLOCK_TYPES[$type]['fields'] ?? []
    );
}

function block_label(string $type): string
{
    return BLOCK_TYPES[$type]['label'] ?? ucfirst($type);
}

/** Every block on a built page, in order. Unknown types are skipped. */
function render_blocks(array $sections): string
{
    $out = '';
    foreach ($sections as $section) {
        if (is_array($section) && isset(BLOCK_TYPES[(string) ($section['block'] ?? '')])) {
            $out .= render_block((string) $section['block'], $section);
        }
    }
    return $out;
}

function render_block(string $type, array $b): string
{
    $body = block_body($type, $b);
    if (trim($body) === '') {
        return '';
    }

    $anchor = trim((string) ($b['anchorId'] ?? ''));
    $id = $anchor !== '' ? ' id="' . e(slugify($anchor)) . '"' : '';

    if (in_array($type, SELF_CONTAINED_BLOCKS, true)) {
        return $id !== '' ? '<div' . $id . ' class="scroll-mt-28">' . $body . '</div>' : $body;
    }

    $background = (string) ($b['background'] ?? '');
    $tone = $background === 'sand' ? ' bg-sand/70' : ($background === 'cream-deep' ? ' bg-cream-deep' : '');
    return '<section' . $id . ' class="section-tight scroll-mt-28' . $tone . '"><div class="shell">' . $body . '</div></section>';
}

/** The inside of one block — components only, no section or shell. */
function block_body(string $type, array $b): string
{
    switch ($type) {
        case 'intro':
            $text = trim((string) ($b['text'] ?? ''));
            return $text === '' ? '' : '<div ' . reveal('max-w-3xl') . '><p class="text-xl leading-[1.7] text-body md:text-[1.375rem]">' . e($text) . '</p></div>';

        case 'heading':
            return trim((string) ($b['title'] ?? '')) === '' ? '' : section_heading(
                (string) ($b['eyebrow'] ?? ''),
                (string) $b['title'],
                (string) ($b['intro'] ?? ''),
                ($b['align'] ?? 'left') === 'center' ? 'center' : 'left'
            );

        case 'text':
            $paragraphs = paragraphs($b['body'] ?? []);
            if ($paragraphs === '') {
                return '';
            }
            $title = trim((string) ($b['title'] ?? ''));
            return '<div ' . reveal('mx-auto max-w-[46rem]') . '>'
                . ($title !== '' ? '<h2 class="text-3xl leading-[1.15] md:text-[2.25rem]">' . e($title) . '</h2>' : '')
                . '<div class="prose-sca' . ($title !== '' ? ' mt-6' : '') . '">' . $paragraphs . '</div></div>';

        case 'photoText':
            $footer = '';
            if (trim((string) ($b['buttonLabel'] ?? '')) !== '') {
                $footer .= button((string) ($b['buttonHref'] ?? '#'), (string) $b['buttonLabel']);
            }
            if (trim((string) ($b['linkLabel'] ?? '')) !== '') {
                $footer .= arrow_link((string) ($b['linkHref'] ?? '#'), (string) $b['linkLabel'], $footer !== '' ? 'mt-6 block' : '');
            }
            return photo_text([
                'eyebrow' => $b['eyebrow'] ?? '',
                'title' => $b['title'] ?? '',
                'image' => $b['image'] ?? '',
                'imageSide' => $b['imageSide'] ?? 'left',
                'align' => 'start',
            ], paragraphs($b['body'] ?? []), $footer);

        case 'facts':
            $items = block_items($b, 'items', ['title', 'body']);
            if (!$items) {
                return '';
            }
            $columns = (int) ($b['columns'] ?? 3);
            $heading = trim((string) ($b['title'] ?? '')) !== ''
                ? section_heading((string) ($b['eyebrow'] ?? ''), (string) $b['title'], (string) ($b['intro'] ?? '')) . '<div class="mt-14">'
                : '<div>';
            return $heading . fact_list($items, in_array($columns, [1, 2, 3, 4], true) ? $columns : 3) . '</div>';

        case 'numbers':
            $stats = block_items($b, 'stats', ['value', 'label']);
            return $stats ? number_band((string) ($b['eyebrow'] ?? ''), $stats, (string) ($b['tone'] ?? 'sand')) : '';

        case 'timeline':
            $items = block_items($b, 'items', ['year', 'title', 'body']);
            if (!$items) {
                return '';
            }
            $heading = trim((string) ($b['title'] ?? '')) !== ''
                ? section_heading((string) ($b['eyebrow'] ?? ''), (string) $b['title'], (string) ($b['intro'] ?? '')) . '<div class="mt-16">'
                : '<div>';
            return $heading . timeline($items, empty($b['newestFirst']) ? 'asc' : 'desc', !empty($b['showPhotos'])) . '</div>';

        case 'gallery':
            $images = array_values(array_filter($b['gallery'] ?? [], fn ($src) => trim((string) $src) !== ''));
            if (!$images) {
                return '';
            }
            $title = trim((string) ($b['title'] ?? ''));
            $out = $title !== '' ? '<h2 ' . reveal('mb-10 text-3xl') . '>' . e($title) . '</h2>' : '';
            $out .= '<div class="grid gap-8 sm:grid-cols-3 md:gap-10">';
            foreach ($images as $i => $src) {
                $out .= '<div ' . reveal('', ($i % 3) * 110) . '>' . media($src, ['ratio' => 'landscape']) . '</div>';
            }
            return $out . '</div>';

        case 'callout':
            $paragraphs = paragraphs($b['body'] ?? []);
            return $paragraphs === '' && trim((string) ($b['title'] ?? '')) === ''
                ? ''
                : callout((string) ($b['title'] ?? ''), $paragraphs);

        case 'resourceLinks':
            $items = block_items($b, 'items', ['title']);
            if (!$items) {
                return '';
            }
            $out = trim((string) ($b['title'] ?? '')) !== ''
                ? section_heading((string) ($b['eyebrow'] ?? ''), (string) $b['title']) . '<div class="mt-6 max-w-4xl">'
                : '<div class="max-w-4xl border-t border-hairline">';
            foreach ($items as $i => $item) {
                $out .= resource_link($item, $i * 90);
            }
            return $out . '</div>';

        case 'downloads':
            $documents = document_links($b['documents'] ?? []);
            if ($documents === '') {
                return '';
            }
            $heading = trim((string) ($b['title'] ?? '')) !== ''
                ? section_heading((string) ($b['eyebrow'] ?? ''), (string) $b['title']) . '<div class="mt-10">'
                : '<div>';
            return $heading . $documents . '</div>';

        case 'actions':
            $buttons = '';
            foreach (array_values($b['buttons'] ?? []) as $item) {
                if (is_array($item) && trim((string) ($item['label'] ?? '')) !== '') {
                    $style = in_array($item['style'] ?? '', ['outline', 'ghost'], true) ? $item['style'] : 'primary';
                    $buttons .= button((string) ($item['href'] ?? '#'), (string) $item['label'], $style);
                }
            }
            $jump = trim((string) ($b['jumpLabel'] ?? '')) !== ''
                ? '<div class="' . ($buttons !== '' ? 'mt-6' : '') . '">' . jump_link((string) ($b['jumpHref'] ?? '#'), (string) $b['jumpLabel']) . '</div>'
                : '';
            if ($buttons === '' && $jump === '') {
                return '';
            }
            return '<div ' . reveal() . '>' . ($buttons !== '' ? '<div class="flex flex-wrap gap-3">' . $buttons . '</div>' : '') . $jump . '</div>';

        case 'contact':
            $heading = trim((string) ($b['title'] ?? '')) !== ''
                ? section_heading((string) ($b['eyebrow'] ?? ''), (string) $b['title'], (string) ($b['intro'] ?? ''))
                : '';
            return '<div class="grid gap-12 lg:grid-cols-2 lg:gap-20">'
                . '<div ' . reveal() . '>' . $heading . '</div>'
                . '<div ' . reveal('', 120) . '>' . contact_form('built-page') . '</div></div>';

        case 'newsletter':
            return '<div ' . reveal('flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-16') . '>'
                . '<div class="max-w-md"><h2 class="text-3xl">' . e($b['title'] ?? '') . '</h2>'
                . '<p class="mt-3 text-[1.0625rem] leading-relaxed text-body">' . e($b['body'] ?? '') . '</p></div>'
                . newsletter_form('lg:w-[28rem]', 'large') . '</div>';

        case 'relatedLinks':
            return related_links(['title' => $b['title'] ?? '', 'links' => $b['links'] ?? []]);

        case 'donationBand':
            return cta_band();
    }
    return '';
}

/** List items that have something in at least one of the keys that matter. */
function block_items(array $b, string $key, array $required): array
{
    return array_values(array_filter($b[$key] ?? [], function ($item) use ($required) {
        if (!is_array($item)) {
            return false;
        }
        foreach ($required as $field) {
            if (trim((string) ($item[$field] ?? '')) !== '') {
                return true;
            }
        }
        return false;
    }));
}
