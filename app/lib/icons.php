<?php

/**
 * Line icon set, ported one-for-one from the design (components/Icons.tsx).
 * Content refers to icons by name, so the admin offers these as a list.
 */

const ICON_PATHS = [
    'grass' => '<path d="M12 21V11" /><path d="M12 14c0-4-2.5-6.5-5-8 0 4 1.5 6.5 5 8Z" /><path d="M12 14c0-4 2.5-6.5 5-8 0 4-1.5 6.5-5 8Z" /><path d="M4 21h16" />',
    'route' => '<circle cx="6" cy="6" r="2.5" /><circle cx="18" cy="18" r="2.5" /><path d="M8.5 6H14a3.5 3.5 0 0 1 0 7h-4a3.5 3.5 0 0 0 0 7h5.5" />',
    'shield' => '<path d="M12 3 5 6v6c0 4.2 2.9 7.6 7 9 4.1-1.4 7-4.8 7-9V6l-7-3Z" />',
    'users' => '<circle cx="9" cy="8.5" r="3" /><path d="M3.5 19.5c.6-3 2.8-4.75 5.5-4.75s4.9 1.75 5.5 4.75" /><path d="M16 5.75a3 3 0 0 1 0 5.5" /><path d="M17.5 14.9c2 .55 3.35 2.1 3.8 4.6" />',
    'book' => '<path d="M4 5.5A2 2 0 0 1 6 3.5h5v15H6a2 2 0 0 0-2 2v-15Z" /><path d="M20 5.5a2 2 0 0 0-2-2h-5v15h5a2 2 0 0 1 2 2v-15Z" />',
    'chart' => '<path d="M4 20V10" /><path d="M10 20V4" /><path d="M16 20v-7" /><path d="M3 20h18" />',
    'mountain' => '<path d="m3 19 6-11 4 7 2.5-4L21 19H3Z" />',
    'leaf' => '<path d="M5 19c0-8 5-13 14-13 0 9-5 13-14 13Z" /><path d="M5 19c2.5-4.5 5.5-7.2 9.5-9" />',
    'award' => '<circle cx="12" cy="9" r="5.5" /><path d="m8.5 13.8-1.5 6 5-2.5 5 2.5-1.5-6" />',
    'pin' => '<path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z" /><circle cx="12" cy="10" r="2.5" />',
    'globe' => '<circle cx="12" cy="12" r="9" /><path d="M3.5 9.5h17M3.5 14.5h17" /><path d="M12 3c-2.5 2.5-3.75 5.5-3.75 9S9.5 18.5 12 21c2.5-2.5 3.75-5.5 3.75-9S14.5 5.5 12 3Z" />',
    'document' => '<path d="M6 3.5h7l5 5v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-16a1 1 0 0 1 1-1Z" /><path d="M13 3.5v5h5" /><path d="M8.5 13h7M8.5 16.5h5" />',
    'heart' => '<path d="M12 20s-7-4.4-7-9.4A4.1 4.1 0 0 1 12 8a4.1 4.1 0 0 1 7 2.6c0 5-7 9.4-7 9.4Z" />',
    'mail' => '<rect x="3" y="5.5" width="18" height="13" rx="2" /><path d="m3.75 7 8.25 6 8.25-6" />',
    'clock' => '<circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" />',
    'gauge' => '<path d="M3.5 17a9 9 0 1 1 17 0" /><path d="m12 13.5 4-3.5" /><circle cx="12" cy="14.5" r="1.3" />',
    'ruler' => '<path d="M6 3.5h5a1.5 1.5 0 0 1 1.5 1.5v14a1.5 1.5 0 0 1-1.5 1.5H6a1.5 1.5 0 0 1-1.5-1.5V5A1.5 1.5 0 0 1 6 3.5Z" /><path d="M12.5 7.5h-3M12.5 12h-3M12.5 16.5h-3" /><path d="M16 6v12" /><path d="m14 8 2-2 2 2M14 16l2 2 2-2" />',
    'weight' => '<path d="M5.5 8.5h13l1.5 11.5H4L5.5 8.5Z" /><circle cx="12" cy="6" r="2.5" />',
    'horns' => '<path d="M9 20c-1.5-4-2-8.5-1.5-14" /><path d="M15 20c1.5-4 2-8.5 1.5-14" /><path d="M7.6 10.5h2.2M7.9 14h2.2M14.2 10.5h2.2M13.9 14h2.2" />',
    'nose' => '<path d="M13 3.5c0 3.5-1 5.5-2.5 7.5S8 15 8 16.5A3.5 3.5 0 0 0 11.5 20c2.5 0 4-1.4 4-3.2" /><path d="M10.5 16.2c.5.6 1.4.9 2.3.7" />',
    'calf' => '<path d="M4 15c0-3 2.2-5 5-5h4.5l3-2.5V10c1.6.6 2.5 2 2.5 3.5" /><path d="M4 15v3.5M9 16.5V20M14.5 16v4M19 13.5V20" /><path d="M16.6 10.6h.01" />',
    'paw' => '<ellipse cx="8" cy="7.5" rx="1.8" ry="2.3" /><ellipse cx="16" cy="7.5" rx="1.8" ry="2.3" /><ellipse cx="4.8" cy="12.5" rx="1.6" ry="2" /><ellipse cx="19.2" cy="12.5" rx="1.6" ry="2" /><path d="M12 12c2.6 0 4.6 2.1 4.6 4.4 0 1.7-1.3 2.8-3 2.8h-3.2c-1.7 0-3-1.1-3-2.8C7.4 14.1 9.4 12 12 12Z" />',
    'virus' => '<circle cx="12" cy="12" r="5.5" /><path d="M12 6.5V3.5M12 20.5v-3M17.5 12h3M3.5 12h3M15.9 8.1l2.1-2.1M6 18l2.1-2.1M15.9 15.9l2.1 2.1M6 6l2.1 2.1" />',
    'weather' => '<path d="M7.5 15.5a3.5 3.5 0 0 1 .4-7A5 5 0 0 1 17.4 9a3.25 3.25 0 0 1-.4 6.5H7.5Z" /><path d="M8.5 18.5 7.5 21M12 18.5 11 21M15.5 18.5 14.5 21" />',
    'barrier' => '<path d="M5 5v15M12 5v15M19 5v15" /><path d="M3 9.5h18M3 14.5h18" />',
    'card' => '<rect x="2.5" y="5.5" width="19" height="13" rx="2.5" /><path d="M2.5 10h19" /><path d="M6 14.5h3" />',
    'arrow-right' => '<path d="M4 12h15" /><path d="m13 6 6 6-6 6" />',
    'arrow-down' => '<path d="M12 4.5v15" /><path d="m6 13.5 6 6 6-6" />',
    'chevron-down' => '<path d="m6 9.5 6 6 6-6" />',
    'chevron-right' => '<path d="m9.5 6 6 6-6 6" />',
    'search' => '<circle cx="11" cy="11" r="6.5" /><path d="m16 16 4.5 4.5" />',
    'external' => '<path d="M13.5 4.5H19.5v6" /><path d="m19.5 4.5-8 8" /><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10" />',
    'phone' => '<path d="M6.5 3.5h3l1.5 4-2 1.5a11.5 11.5 0 0 0 6 6l1.5-2 4 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4.5 5.7 2 2 0 0 1 6.5 3.5Z" />',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16" />',
    'close' => '<path d="m6 6 12 12M18 6 6 18" />',
    'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none" />',
    'facebook' => '<path d="M14.5 8.5V7a1.5 1.5 0 0 1 1.5-1.5h1.5V3H15a4 4 0 0 0-4 4v1.5H9V11h2v10h3.5V11h2.2l.5-2.5h-2.7Z" />',
    'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3" /><path d="M8 10.5V16" /><circle cx="8" cy="7.8" r="0.9" fill="currentColor" stroke="none" /><path d="M12 16v-3a2 2 0 0 1 4 0v3" /><path d="M12 16v-5.5" />',
    'youtube' => '<rect x="2.5" y="5.5" width="19" height="13" rx="4" /><path d="m10.5 9.5 5 2.5-5 2.5v-5Z" />',
    'x' => '<path d="M4.5 4h4.3l10.7 16h-4.3L4.5 4Z" /><path d="m19.2 4-5.9 6.7M10.7 13.3 4.8 20" />',
    'tiktok' => '<path d="M13.5 3.5v11.75a3.75 3.75 0 1 1-3.75-3.75" /><path d="M13.5 3.5c.4 2.6 2.3 4.5 5 4.8" />',
    'bluesky' => '<path d="M12 10.8C10.5 7.8 7.2 4.5 5 4.5c-1.2 0-1.5 1.1-1.5 2.1 0 .9.5 4.3 1.2 5.1 1.2 1.3 3.2 1.3 4.6 1-2.4.6-4.2 1.9-2.2 4.1 2.5 2.8 3.9-.5 4.9-2.7 1 2.2 2.4 5.5 4.9 2.7 2-2.2.2-3.5-2.2-4.1 1.4.3 3.4.3 4.6-1 .7-.8 1.2-4.2 1.2-5.1 0-1-.3-2.1-1.5-2.1-2.2 0-5.5 3.3-7 6.3Z" />',
    'threads' => '<path d="M18.6 7.6C17.4 5 15.1 3.5 12 3.5c-5 0-8 3.3-8 8.5s3 8.5 8 8.5c3.9 0 6.7-2.1 6.7-5.1 0-2.6-2.2-4.2-6-4.2-2.7 0-4.2 1.2-4.2 2.8 0 1.5 1.3 2.4 3 2.3 2.6-.2 3.8-2.2 3.8-5.6 0-2.5-1.6-4-3.9-4-1.7 0-2.9.8-3.5 2" />',
    'whatsapp' => '<path d="m3.5 20.5 1.3-4.4a8.5 8.5 0 1 1 3.3 3.1l-4.6 1.3Z" /><path d="M9 8.3c-.3 3.4 3.2 6.9 6.6 6.7l.9-1.6-2.1-1-1 .9c-1.2-.5-2.3-1.6-2.8-2.8l.9-1-1-2.1L9 8.3Z" />',
    'telegram' => '<path d="M21 4 3 11.2l6.1 2.3L18.5 7l-7.4 8.1V20l3.3-3.6 4.6 3.4L21 4Z" />',
    'vimeo' => '<path d="m3 8.6.9 1.2c1.2-.9 1.9-1.2 2.4-.3.8 1.4 1.9 6.9 3 8.7 1.4 2.3 3 1.4 5.3-.7 2.5-2.4 5.3-6.3 5.8-9.3.4-3-2.7-4-5.5-1.4 1.8-.5 2.6.5 2 2.1-.8 2-2.8 5-3.6 4.2-.8-.8-1.3-5.5-2.3-7.6-1-2.1-2.7-1.4-8 3.1Z" />',
    'flickr' => '<circle cx="7.25" cy="12" r="3.75" /><circle cx="16.75" cy="12" r="3.75" />',
    'rss' => '<path d="M5 4.5A14.5 14.5 0 0 1 19.5 19" /><path d="M5 10.5a8.5 8.5 0 0 1 8.5 8.5" /><circle cx="6" cy="18" r="1.4" fill="currentColor" stroke="none" />',
    'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3.2-3.2a4 4 0 0 0-5.7-5.7l-1.2 1.2" /><path d="M14 10a4 4 0 0 0-5.7 0l-3.2 3.2a4 4 0 0 0 5.7 5.7l1.2-1.2" />',
];

/** Names offered in the admin icon picker (content icons + social). */
const CONTENT_ICONS = [
    'grass', 'route', 'shield', 'users', 'book', 'chart', 'mountain', 'leaf', 'award', 'pin', 'globe', 'document',
    'heart', 'mail', 'phone', 'clock', 'gauge', 'ruler', 'weight', 'horns', 'nose', 'calf', 'paw', 'virus', 'weather',
    'barrier', 'card', 'link', 'rss', 'instagram', 'facebook', 'linkedin', 'youtube', 'x', 'bluesky', 'threads',
    'tiktok', 'whatsapp', 'telegram', 'vimeo', 'flickr',
];

/**
 * An icon by name — or, for an icon SCA uploaded themselves, by image path.
 * An uploaded icon (a one-colour PNG or WebP on a transparent background) is
 * drawn as a CSS mask filled with the text colour, so it takes the same colour
 * and hover colour as the built-in line icons around it.
 */
function icon(string $name, string $class = 'h-4 w-4', float $strokeWidth = 1.4): string
{
    if (is_custom_icon($name)) {
        $url = "url('" . str_replace(["'", '"', '(', ')', ' '], ['%27', '%22', '%28', '%29', '%20'], $name) . "')";
        $mask = $url . ' center / contain no-repeat';
        return '<span class="inline-block bg-current ' . e($class) . '" style="' . e('-webkit-mask:' . $mask . ';mask:' . $mask) . '" aria-hidden="true"></span>';
    }
    $paths = ICON_PATHS[$name] ?? ICON_PATHS['leaf'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $strokeWidth
        . '" stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '" aria-hidden="true">' . $paths . '</svg>';
}

function is_custom_icon(string $name): bool
{
    return (bool) preg_match('~^/(uploads|images)/[A-Za-z0-9/_.-]+\.(png|webp|gif)$~i', $name) && strpos($name, '..') === false;
}
