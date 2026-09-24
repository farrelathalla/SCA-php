<?php

/**
 * Builds an editing form from a content document.
 *
 * Content is plain JSON, so rather than hand-writing a form per page the form
 * is derived from the data itself: strings become inputs or textareas, image
 * paths become image pickers, lists become repeaters that can grow, shrink and
 * reorder. A "shape" (the original seed content) supplies the structure of new
 * list items, so a list emptied by an editor can still be added to.
 *
 * assets/admin/admin.js turns the rendered form back into JSON on save.
 */

const LONG_TEXT_KEYS = [
    'body', 'intro', 'summary', 'excerpt', 'strapline', 'caption', 'note', 'answer', 'description',
    'statement', 'lead', 'tagline', 'pullQuote', 'postAddress', 'inlineCaption', 'ctaBody', 'applyBody',
    'yearsIntro', 'thanks', 'thanksBody', 'noResults', 'defaultNote', 'signupNote', 'whatItIs',
    'placeholderText', 'monthlyHtml', 'bodyHtml', 'captionHtml', 'hint', 'text',
];

const LABELS = [
    'href' => 'Link',
    'eyebrow' => 'Eyebrow (small label above the title)',
    'heroEyebrow' => 'Hero eyebrow',
    'showDonationBand' => 'Show the “Keep them moving” donation band',
    'celebrate' => 'Show celebratory line art',
    'listProgrammes' => 'List grant programmes in a sub-menu',
    'imageSide' => 'Image side',
    'variant' => 'Hero style',
    'meta' => 'Search engine (SEO)',
    'ctaBand' => 'Donation band (“Keep them moving”)',
    'nav' => 'Main menu',
    'heroImage' => 'Hero background image',
    'image' => 'Image',
    'mapImage' => 'Map image',
    'inlineImage' => 'Image in the middle of the article',
    'featureImage' => 'Featured cover image',
    'featureLabel' => 'Featured publication label (leave empty for none)',
    'featureHref' => 'Featured publication link',
    'relatedProjects' => 'Related projects (project slugs)',
    'projects' => 'Featured projects (project slugs)',
    'archiveType' => 'Projects archive “Type” this programme links to',
    'projectTypes' => 'Project types (filter options, in order)',
    'count' => 'Number of stories shown',
    'bodyHtml' => 'Text (links allowed: <a href="…">…</a>)',
    'monthlyHtml' => 'Text (links allowed: <a href="…">…</a>)',
    'pullQuote' => 'Pull quote (optional)',
    'date' => 'Date',
    'logo' => 'Logo (optional — replaces the name)',
    'mailchimpAction' => 'Mailchimp form action URL (from Mailchimp → Audience → Signup forms → Embedded form; leave empty to only collect sign-ups here)',
    'analyticsId' => 'Google Analytics measurement ID (e.g. G-XXXXXXXXXX — leave empty for no tracking)',
    'jumpLabel' => 'Jump link under the buttons (e.g. “See what your donation can support” — leave empty for none)',
    'jumpHref' => 'Jump link target (an anchor on this page, e.g. #impact)',
    'documents' => 'Files to download (optional)',
    'points' => 'Data points',
    'xLabel' => 'Horizontal axis label',
    'yLabel' => 'Vertical axis label',
    'captionHtml' => 'Caption (links allowed: <a href="…">…</a>)',
    'anchorId' => 'Anchor (lets a link jump straight here, e.g. “what-we-do” — optional)',
    'background' => 'Background',
    'showPhotos' => 'Show photographs',
    'newestFirst' => 'Newest year first',
    'sections' => 'Page blocks',
    'gallery' => 'Photographs',
    'stats' => 'Figures',
    'buttons' => 'Buttons',
    'reporting' => 'Reporting (the section the donate page’s annual report link jumps to: /resources#reporting)',
    'report' => 'Reporting — featured publication box',
    'reports' => 'Reports (newest first — “+ Add” puts the new one at the top)',
    'extraBlocks' => 'Extra blocks on this page',
    'placement' => 'Where on the page',
];

/** Lists whose newest item belongs at the top, so “+ Add” inserts it there. */
const PREPEND_LISTS = ['reports'];

/**
 * Item shapes for lists that start out empty, so the admin still knows what a
 * new item looks like. (A seeded list supplies its own shape from its first
 * item; these are for the lists that begin with nothing in them.)
 */
const LIST_ITEM_SHAPES = [
    'documents' => ['title' => '', 'href' => ''],
];

/**
 * The document key whose list is edited with the block builder rather than as
 * an ordinary repeater. Set by the admin router for built pages.
 */
function blocks_key(?string $set = null): ?string
{
    static $key = null;
    if ($set !== null) {
        $key = $set;
    }
    return $key;
}

/** Leaf values that are picked from a short list rather than typed. */
const CHOICE_FIELDS = [
    'align' => ['left' => 'Left', 'center' => 'Centred'],
    'tone' => ['sand' => 'Sand', 'cream' => 'Soft beige'],
    'background' => BLOCK_BACKGROUNDS,
    'columns' => ['1' => 'One per row', '2' => 'Two per row', '3' => 'Three per row', '4' => 'Four per row'],
    'style' => ['primary' => 'Solid button', 'outline' => 'Outlined button', 'ghost' => 'Light button'],
    'placement' => BLOCK_PLACEMENTS,
];

function field_label(string $key): string
{
    if (isset(LABELS[$key])) {
        return LABELS[$key];
    }
    $words = trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $key));
    return ucfirst(strtolower($words));
}

/** Decides which input a leaf value gets. */
function field_kind(string $key, $value): string
{
    if (is_bool($value)) {
        return 'bool';
    }
    if ($key === 'icon' || substr($key, -4) === 'Icon') {
        return 'icon';
    }
    if ($key === 'block') {
        return 'hidden';
    }
    if (isset(CHOICE_FIELDS[$key]) && !is_array($value)) {
        return 'choice';
    }
    if ($key === 'variant') {
        return 'variant';
    }
    if ($key === 'imageSide') {
        return 'side';
    }
    if ($key === 'date') {
        return 'date';
    }
    if ($key === 'type') {
        return 'type';
    }
    if (preg_match('/(^image$|Image$|^logo|Logo$|^gallery$|^photo)/', $key)
        || (is_string($value) && preg_match('~^/(images|uploads)/.+\.(webp|jpe?g|png|gif|avif)$~i', $value))) {
        return 'image';
    }
    if ($key === 'href' || substr($key, -4) === 'Href') {
        return 'link';
    }
    if (substr($key, -4) === 'Html' || in_array($key, LONG_TEXT_KEYS, true) || (is_string($value) && mb_strlen($value) > 90)) {
        return 'textarea';
    }
    return 'text';
}

/** An empty copy of a value, keeping its structure. */
function blank_of($shape)
{
    if (is_bool($shape)) {
        return false;
    }
    if (is_array($shape)) {
        if (array_is_list_compat($shape)) {
            return [];
        }
        $out = [];
        foreach ($shape as $k => $v) {
            $out[$k] = blank_of($v);
        }
        return $out;
    }
    return '';
}

function array_is_list_compat(array $a): bool
{
    return $a === [] || array_keys($a) === range(0, count($a) - 1);
}

/** Text used as the collapsed title of a list item. */
function item_summary($item): string
{
    if (!is_array($item)) {
        return mb_strimwidth((string) $item, 0, 80, '…');
    }
    if (isset($item['block'])) {
        $name = block_label((string) $item['block']);
        foreach (['title', 'text'] as $k) {
            if (trim((string) ($item[$k] ?? '')) !== '') {
                return mb_strimwidth($name . ' — ' . $item[$k], 0, 80, '…');
            }
        }
        return $name;
    }
    foreach (['title', 'label', 'name', 'year', 'project', 'question', 'amount', 'value'] as $k) {
        if (isset($item[$k]) && is_string($item[$k]) && trim($item[$k]) !== '') {
            $extra = ($k === 'year' && !empty($item['title'])) ? ' — ' . $item['title'] : '';
            return mb_strimwidth($item[$k] . $extra, 0, 80, '…');
        }
    }
    return 'Item';
}

function render_value_input(string $key, $value, string $kind): string
{
    $id = 'f' . bin2hex(random_bytes(5));
    $val = is_bool($value) ? '' : (string) $value;
    $input = 'w-full rounded-lg border border-hairline bg-white px-3 py-2 text-[0.9rem] text-ink focus:border-accent focus:outline-none';

    switch ($kind) {
        case 'bool':
            return '<label class="inline-flex items-center gap-2 text-[0.9rem] text-ink"><input type="checkbox" data-input class="h-4 w-4 accent-[#c87a3c]"' . ($value ? ' checked' : '') . '> ' . e(field_label($key)) . '</label>';
        case 'textarea':
            $rows = max(2, min(10, (int) ceil(mb_strlen($val) / 90)));
            return '<textarea id="' . $id . '" data-input rows="' . $rows . '" class="' . $input . ' leading-relaxed">' . e($val) . '</textarea>';
        case 'icon':
            // Built-in line icons, plus any icon SCA upload themselves.
            $out = '<div class="flex flex-wrap items-center gap-3" data-icon-field><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent-soft text-accent" data-icon-preview>' . icon($val ?: 'leaf', 'h-5 w-5', 1.3) . '</span><select id="' . $id . '" data-input data-icon-select class="' . $input . ' min-w-0 flex-1">';
            foreach (CONTENT_ICONS as $name) {
                $out .= '<option value="' . e($name) . '"' . ($name === $val ? ' selected' : '') . '>' . e($name) . '</option>';
            }
            if (is_custom_icon($val)) {
                $out .= '<option value="' . e($val) . '" selected>Uploaded: ' . e(basename($val)) . '</option>';
            }
            return $out . '</select>'
                . '<button type="button" data-icon-upload title="A one-colour PNG or WebP on a transparent background, about 96×96 px. It takes the site’s colours automatically." class="shrink-0 rounded-full border border-hairline px-3.5 py-1.5 text-[0.8rem] text-ink hover:border-ink/40">Upload icon…</button></div>';
        case 'hidden':
            return '<input type="hidden" data-input value="' . e($val) . '">';
        case 'choice':
            $options = CHOICE_FIELDS[$key];
            break;
        case 'variant':
            $options = ['overlay' => 'Text over photo', 'split' => 'Text beside photo', 'plain' => 'No photo'];
            break;
        case 'side':
            $options = ['left' => 'Image on the left', 'right' => 'Image on the right'];
            break;
        case 'date':
            return '<input id="' . $id . '" type="date" data-input value="' . e($val) . '" class="' . $input . ' max-w-xs">';
        case 'type':
            return '<input id="' . $id . '" data-input value="' . e($val) . '" list="project-types" class="' . $input . '">';
        case 'image':
            return '<div class="flex items-start gap-4" data-image-field>'
                . '<div class="relative h-20 w-28 shrink-0 overflow-hidden rounded-lg border border-hairline bg-cream-deep">'
                . '<img data-image-preview src="' . e($val) . '" alt="" class="h-full w-full object-cover' . ($val === '' ? ' hidden' : '') . '" onerror="this.classList.add(\'hidden\')">'
                . '</div><div class="min-w-0 flex-1 space-y-2">'
                . '<input id="' . $id . '" data-input value="' . e($val) . '" placeholder="No image" class="' . $input . ' font-mono text-[0.8rem]">'
                . '<div class="flex flex-wrap gap-2">'
                . '<button type="button" data-image-upload class="rounded-full bg-ink px-3.5 py-1.5 text-[0.8rem] text-cream hover:bg-ink/85">Upload…</button>'
                . '<button type="button" data-image-library class="rounded-full border border-hairline px-3.5 py-1.5 text-[0.8rem] text-ink hover:border-ink/40">Choose from library</button>'
                . '<button type="button" data-image-crop title="Crop or zoom into this image. Saves a new copy and uses it here." class="rounded-full border border-hairline px-3.5 py-1.5 text-[0.8rem] text-ink hover:border-ink/40">Crop…</button>'
                . '<button type="button" data-image-clear class="rounded-full px-3 py-1.5 text-[0.8rem] text-muted hover:text-accent-dark">Remove</button>'
                . '</div></div></div>';
        case 'link':
            return '<div class="flex gap-2" data-link-field><input id="' . $id . '" data-input value="' . e($val) . '" placeholder="/page-path or https://…" class="' . $input . ' font-mono text-[0.8rem]">'
                . '<button type="button" data-file-upload title="Upload a file (e.g. a PDF) and link to it" class="shrink-0 rounded-lg border border-hairline px-3 text-[0.8rem] text-ink hover:border-ink/40">Upload file…</button></div>';
        default:
            return '<input id="' . $id . '" data-input value="' . e($val) . '" class="' . $input . '">';
    }

    $out = '<select id="' . $id . '" data-input class="' . $input . ' max-w-xs">';
    foreach ($options as $optValue => $optLabel) {
        $out .= '<option value="' . e($optValue) . '"' . ($optValue === $val ? ' selected' : '') . '>' . e($optLabel) . '</option>';
    }
    return $out . '</select>';
}

/**
 * Renders one node. $key is null for list items. $shape is the matching part
 * of the seed content, used for structure only.
 */
function render_node(?string $key, $value, $shape, int $depth = 0, string $parentKey = ''): string
{
    $keyAttr = $key !== null ? ' data-key="' . e($key) . '"' : '';
    $labelKey = $key ?? $parentKey;

    // The block builder replaces the ordinary repeater for a built page's blocks.
    if ($key !== null && $key === blocks_key() && $depth === 0) {
        return render_blocks_field(is_array($value) ? $value : [], $key);
    }

    // Lists
    if (is_array($value) && array_is_list_compat($value) && (!is_array($shape) || array_is_list_compat($shape))) {
        $itemShape = (is_array($shape) && isset($shape[0]))
            ? $shape[0]
            : (LIST_ITEM_SHAPES[(string) $key] ?? ($value[0] ?? ''));
        $template = render_list_item(blank_of($itemShape), $itemShape, $depth + 1, (string) $key);

        $out = '<div data-node="list"' . $keyAttr . ' class="space-y-2">';
        if ($key !== null) {
            $out .= '<div class="text-[0.8rem] font-medium tracking-wide text-body uppercase">' . e(field_label($key)) . '</div>';
        }
        $out .= '<template data-item-template>' . $template . '</template><div data-items class="space-y-2">';
        foreach ($value as $item) {
            $out .= render_list_item($item, $itemShape, $depth + 1, (string) $key);
        }
        $prepend = in_array((string) $key, PREPEND_LISTS, true);
        $out .= '</div><button type="button" data-add-item' . ($prepend ? '="top"' : '') . ' class="rounded-full border border-dashed border-accent/50 px-4 py-1.5 text-[0.8rem] text-accent-dark hover:bg-accent-soft">'
            . ($prepend ? '+ Add a new one at the top' : '+ Add ' . e(strtolower(field_label((string) $key)) ?: 'item')) . '</button></div>';
        return $out;
    }

    // Groups
    if (is_array($value) || (is_array($shape) && !array_is_list_compat($shape) && $value === null)) {
        $value = is_array($value) ? $value : [];
        $shape = is_array($shape) ? $shape : [];
        $fields = '';
        foreach (array_unique(array_merge(array_keys($value), array_keys($shape))) as $k) {
            $k = (string) $k;
            if ($k === '' || $k[0] === '_') {
                continue;
            }
            $v = array_key_exists($k, $value) ? $value[$k] : blank_of($shape[$k]);
            $fields .= render_node($k, $v, $shape[$k] ?? null, $depth + 1, $k);
        }
        if ($key === null) {
            return '<div data-node="group" class="space-y-4">' . $fields . '</div>';
        }
        if ($depth === 0) {
            return '<section data-node="group"' . $keyAttr . ' class="rounded-2xl border border-hairline bg-white p-5 md:p-6"><h2 class="mb-4 font-display text-xl text-ink">' . e(field_label($key)) . '</h2><div class="space-y-4">' . $fields . '</div></section>';
        }
        return '<fieldset data-node="group"' . $keyAttr . ' class="rounded-xl border border-hairline/80 bg-cream/60 p-4"><legend class="px-1 text-[0.8rem] font-medium tracking-wide text-body uppercase">' . e(field_label($key)) . '</legend><div class="space-y-4">' . $fields . '</div></fieldset>';
    }

    // Leaf values
    $kind = field_kind($labelKey, $value ?? (is_bool($shape) ? false : ''));
    if ($kind === 'bool') {
        $value = (bool) $value;
    }
    $input = render_value_input($labelKey, $value, $kind);
    $type = is_bool($value) ? 'bool' : 'string';
    $wrapper = '<div data-node="value" data-type="' . $type . '"' . $keyAttr . '>';
    if ($key !== null && $kind !== 'bool' && $kind !== 'hidden') {
        $wrapper .= '<label class="mb-1.5 block text-[0.85rem] text-body">' . e(field_label($key)) . '</label>';
    }
    if ($depth === 0 && $key !== null) {
        return '<section class="rounded-2xl border border-hairline bg-white p-5 md:p-6">' . $wrapper . $input . '</div></section>';
    }
    return $wrapper . $input . '</div>';
}

function render_list_item($item, $shape, int $depth, string $parentKey): string
{
    $controls = '<div class="flex shrink-0 items-center gap-1">'
        . '<button type="button" data-move="-1" title="Move up" class="rounded-md px-2 py-1 text-muted hover:bg-sand/60 hover:text-ink">↑</button>'
        . '<button type="button" data-move="1" title="Move down" class="rounded-md px-2 py-1 text-muted hover:bg-sand/60 hover:text-ink">↓</button>'
        . '<button type="button" data-remove-item title="Remove" class="rounded-md px-2 py-1 text-muted hover:bg-red-50 hover:text-red-700">✕</button></div>';

    if (is_array($item) || is_array($shape)) {
        $item = is_array($item) ? $item : blank_of($shape);
        $body = render_node(null, $item, $shape, $depth, $parentKey);
        return '<details data-item class="group/item rounded-xl border border-hairline bg-white">'
            . '<summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5">'
            . '<span class="min-w-0 truncate text-[0.9rem] text-ink" data-item-summary>' . e(item_summary($item)) . '</span>' . $controls . '</summary>'
            . '<div class="border-t border-hairline/70 p-4">' . $body . '</div></details>';
    }

    return '<div data-item class="flex items-start gap-2"><div class="min-w-0 flex-1">'
        . render_node(null, $item, $shape, $depth, $parentKey) . '</div>' . $controls . '</div>';
}

/**
 * The block builder: the blocks already on a built page, one collapsible panel
 * each, plus a "+ Add new block" picker. Every block type gets a <template>
 * holding an empty copy of itself, which assets/admin/admin.js clones — the
 * same mechanism the ordinary repeaters use, with a menu in front of it.
 */
function render_blocks_field(array $blocks, string $key): string
{
    $select = 'rounded-lg border border-hairline bg-white px-3 py-2 text-[0.9rem] text-ink focus:border-accent focus:outline-none';

    $items = '';
    foreach ($blocks as $block) {
        $type = is_array($block) ? (string) ($block['block'] ?? '') : '';
        if (isset(BLOCK_TYPES[$type])) {
            $items .= render_list_item($block, block_shape($type), 1, $key);
        }
    }

    $templates = '';
    $options = '';
    foreach (BLOCK_TYPES as $type => $meta) {
        $templates .= '<template data-block-template="' . e($type) . '">'
            . render_list_item(block_shape($type), block_shape($type), 1, $key) . '</template>';
        $options .= '<option value="' . e($type) . '" data-hint="' . e($meta['hint']) . '">' . e($meta['label']) . '</option>';
    }
    $firstHint = BLOCK_TYPES[array_key_first(BLOCK_TYPES)]['hint'];

    return '<section data-node="list" data-key="' . e($key) . '" class="rounded-2xl border border-hairline bg-white p-5 md:p-6">'
        . '<h2 class="mb-1 font-display text-xl text-ink">' . e(field_label($key)) . '</h2>'
        . '<p class="mb-4 text-[0.85rem] text-muted">' . (block_placement()
            ? 'Add new sections to this page with the same building blocks the rest of the site is made of — for example a new text section, a photo, a list of links or downloads. Each block goes straight under the page header or at the end of the page (above the related links and the donation band). Order them with ↑ ↓, and leave a field empty to hide that part of a block.'
            : 'Stack the same building blocks the rest of the site is made of. Drag order with ↑ ↓, and leave a field empty to hide that part of a block.') . '</p>'
        . $templates
        . '<div data-items class="space-y-2">' . $items . '</div>'
        . '<div class="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-dashed border-accent/50 p-3">'
        . '<select data-block-picker class="' . $select . '">' . $options . '</select>'
        . '<button type="button" data-add-block class="rounded-full bg-accent px-4 py-2 text-[0.85rem] font-medium text-cream hover:bg-accent-dark">+ Add new block</button>'
        . '<span class="basis-full text-[0.8rem] text-muted sm:basis-auto" data-block-hint>' . e($firstHint) . '</span>'
        . '</div></section>';
}

/** The whole editing form body for a document. */
function render_document_form(array $data, array $shape): string
{
    $out = '<div data-node="group" data-root class="space-y-5">';
    foreach (array_unique(array_merge(array_keys($data), array_keys($shape))) as $k) {
        $k = (string) $k;
        if ($k === '' || $k[0] === '_') {
            continue;
        }
        $v = array_key_exists($k, $data) ? $data[$k] : blank_of($shape[$k]);
        $out .= render_node($k, $v, $shape[$k] ?? null, 0, $k);
    }
    return $out . '</div>';
}
