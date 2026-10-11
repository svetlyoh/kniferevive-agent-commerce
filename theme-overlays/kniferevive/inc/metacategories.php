<?php
/** Curated browsing groups; product feed classifications remain independent. */
declare(strict_types=1);
defined('ABSPATH') || exit;

function kr_metacategories(): array {
    return [
        'kitchen-knives' => ['label' => 'Kitchen Knives', 'url' => home_url('/shop/'), 'slugs' => kr_get_knife_category_slugs()],
        'art' => ['label' => 'Art', 'url' => home_url('/art-trade-gallery/'), 'slugs' => ['art', 'fine-art']],
        'tech' => ['label' => 'Tech', 'url' => home_url('/technology-trade-desk/'), 'slugs' => ['technology', 'ai-systems']],
        'iphones' => ['label' => 'iPhones', 'url' => home_url('/technology-trade-desk/#iphones'), 'slugs' => ['technology']],
        'laptops' => ['label' => 'Laptops', 'url' => home_url('/technology-trade-desk/#laptops'), 'slugs' => ['technology']],
        'mini-computers' => ['label' => 'Mini-Computers', 'url' => home_url('/technology-trade-desk/#mini-computers'), 'slugs' => ['technology']],
        'bluetooth-gadgets' => ['label' => 'Bluetooth Gadgets', 'url' => home_url('/technology-trade-desk/#bluetooth-gadgets'), 'slugs' => ['technology']],
        'cell-phone-accessories' => ['label' => 'Cell Phone Accessories', 'url' => home_url('/technology-trade-desk/#cell-phone-accessories'), 'slugs' => ['technology']],
        'ai-tech' => ['label' => 'AI-Tech', 'slugs' => ['technology']],
        'other-finds' => ['label' => 'Other Finds', 'url' => home_url('/other-finds/'), 'slugs' => ['other_finds', 'miscellaneous']],
        'world-coins' => ['label' => 'World Coins', 'slugs' => ['world-coins']],
        'world-spices' => ['label' => 'World Spices', 'slugs' => ['world-spices']],
        'home-garden' => ['label' => 'Home & Garden', 'url' => home_url('/home-garden/'), 'slugs' => ['home-garden']],
        'collectibles' => ['label' => 'Collectibles', 'url' => home_url('/collectibles/'), 'slugs' => ['collectibles']],
        'pokemon-cards' => ['label' => 'Pokémon Cards', 'url' => home_url('/pokemon-cards/'), 'slugs' => ['collectibles']],
        'knife-sharpening' => ['label' => 'Knife Sharpening', 'url' => home_url('/#knife-sharpening'), 'slugs' => ['knife-sharpening']],
    ];
}
function kr_metacategory_landing_url(string $slug): string {
    return kr_metacategories()[$slug]['url'] ?? kr_metacategory_url($slug);
}
function kr_metacategory_description(string $slug): string {
    $descriptions = [
        'iphones' => 'Pre-owned iPhones and useful Apple-compatible accessories, with storage, carrier status, and condition recorded when known.',
        'laptops' => 'Portable computers, notebooks, docking stations, and accessories for work, learning, and a desk that travels.',
        'mini-computers' => 'Compact PCs, desktop systems, servers, and streaming devices—including Chromecast—for everyday computing and home projects.',
        'bluetooth-gadgets' => 'Bluetooth speakers, headphones, transmitters, keyboards, mice, controllers, and wearable gadgets, with connectivity and condition recorded when known.',
        'cell-phone-accessories' => 'Chargers, cases, screen protectors, headphones, stands, straps, camera accessories, and other useful phone extras, with compatibility recorded when known.',
        'ai-tech' => 'Configured computers for self-hosted AI tools, automation, and Linux experimentation, including the OpenClaw-ready Dell system.',
        'home-garden' => 'Useful pieces for the home, kitchen, patio, and garden, ready for another home and organized by their actual product type.',
        'collectibles' => 'Trading cards, coins, stamps, models, and memorabilia, with identifying details and condition recorded when known.',
        'pokemon-cards' => 'Pokémon singles and card collections, with set, card number, rarity, finish, and grade recorded when supported by the listing.',
    ];
    return $descriptions[$slug] ?? '';
}
function kr_metacategory_url(string $slug, string $googleId = ''): string {
    $args = ['post_type' => 'product', 'kr_collection' => $slug];
    if ($googleId !== '') $args['kr_google_category'] = $googleId;
    return add_query_arg($args, home_url('/'));
}
function kr_metacategory_image(string $slug): string {
    $term = get_term_by('slug', $slug, 'product_cat');
    $image = $term instanceof WP_Term ? (int) get_term_meta($term->term_id, 'thumbnail_id', true) : 0;
    if ($image && get_post_type($image) === 'attachment') return wp_get_attachment_image($image, 'large', false, ['loading' => 'lazy', 'decoding' => 'async']);
    $paths = ['home-garden' => 'assets/images/categories/home-garden-2026-10-11.png', 'ai-tech' => 'assets/images/technology/Openclaw2_Feature.png', 'collectibles' => 'assets/images/metacategories/collectibles-2026-10-11.png', 'pokemon-cards' => 'assets/images/metacategories/pokemon-cards-2026-10-11.png', 'iphones' => 'assets/images/metacategories/kniferevive-iphones-1536x1024.png', 'laptops' => 'assets/images/metacategories/kniferevive-laptops-1536x1024.png', 'mini-computers' => 'assets/images/metacategories/kniferevive-mini-computers-1536x1024.png'];
    $label = kr_metacategories()[$slug]['label'] ?? $slug;
    return kr_theme_image($paths[$slug] ?? 'assets/images/metacategories/' . $slug . '-placeholder.svg', $label . ' collection theme', ['loading' => 'lazy', 'decoding' => 'async']);
}
/** Mirror the existing mapper's category precedence using read-only taxonomy/config data. */
function kr_metacategory_feed_category(WC_Product $product, array $terms): ?array {
    $candidates = [];
    foreach ((array) get_option('krev_merchant_google_category_mappings', []) as $index => $mapping) {
        if (empty($mapping['enabled']) || !preg_match('/^[1-9]\d*$/', (string) ($mapping['google_category_id'] ?? ''))) continue;
        if (($mapping['special_group'] ?? '') === 'knives') {
            $knife = array_intersect(wp_list_pluck($terms, 'slug'), kr_get_knife_category_slugs());
            if ($knife) $candidates[] = ['id' => (string) $mapping['google_category_id'], 'path' => (string) $mapping['google_category_path'], 'exact' => 0, 'depth' => 0, 'priority' => $index];
            continue;
        }
        $mapped = get_term((int) ($mapping['product_cat_term_id'] ?? 0), 'product_cat');
        if (!$mapped instanceof WP_Term) continue;
        foreach ($terms as $term) {
            $exact = $term->term_id === $mapped->term_id;
            $ancestor = !empty($mapping['include_descendants']) && in_array((int) $mapped->term_id, array_map('intval', get_ancestors($term->term_id, 'product_cat', 'taxonomy')), true);
            if ($exact || $ancestor) $candidates[] = ['id' => (string) $mapping['google_category_id'], 'path' => (string) $mapping['google_category_path'], 'exact' => $exact ? 1 : 0, 'depth' => count(get_ancestors($mapped->term_id, 'product_cat', 'taxonomy')), 'priority' => $index];
        }
    }
    if (class_exists('KREV_PA_Tech_Categories')) foreach ($terms as $term) {
        $mapped = KREV_PA_Tech_Categories::mapping($term->term_id);
        if ($mapped) $candidates[] = $mapped + ['exact' => 1, 'depth' => count(get_ancestors($term->term_id, 'product_cat', 'taxonomy')), 'priority' => 100000];
    }
    if (!$candidates) return null;
    usort($candidates, static fn($a, $b) => [$b['exact'], $b['depth'], $a['priority']] <=> [$a['exact'], $a['depth'], $b['priority']]);
    return $candidates[0];
}
/** Catalog-visible published products only; one inventory read per request. */
function kr_metacategory_inventory(): array {
    static $inventory;
    if ($inventory !== null) return $inventory;
    $inventory = [];
    if (!function_exists('wc_get_product')) return $inventory;
    $ids = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC']);
    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (!$product || $product->get_meta('_krev_listlab_archived') === 'yes' || !in_array($product->get_catalog_visibility(), ['visible', 'catalog'], true) || (get_option('woocommerce_hide_out_of_stock_items') === 'yes' && !$product->is_in_stock())) continue;
        $terms = wp_get_post_terms($id, 'product_cat');
        if (is_wp_error($terms)) continue;
        $slugs = wp_list_pluck($terms, 'slug');
        foreach ($terms as $term) foreach (get_ancestors($term->term_id, 'product_cat', 'taxonomy') as $ancestorId) {
            $ancestor = get_term($ancestorId, 'product_cat');
            if ($ancestor instanceof WP_Term) $slugs[] = $ancestor->slug;
        }
        $google = kr_metacategory_feed_category($product, $terms);
        $groups = [];
        foreach (kr_metacategories() as $slug => $group) {
            if (in_array($slug, ['iphones', 'laptops', 'mini-computers', 'bluetooth-gadgets', 'cell-phone-accessories', 'ai-tech', 'pokemon-cards'], true)) continue;
            if (array_intersect($slugs, $group['slugs'])) $groups[] = $slug;
        }
        if (in_array('tech', $groups, true) && class_exists('KREV_PA_Metacategories')) {
            $explicit = array_values(array_intersect($slugs, array_keys(KREV_PA_Metacategories::labels())));
            // The existing merchant alias already carries positive iPhone evidence.
            if (in_array('tech-iphones', $slugs, true)) $explicit[] = 'iphones';
            $brandNames = taxonomy_exists('product_brand') ? wp_get_post_terms($id, 'product_brand', ['fields' => 'names']) : [];
            $apple = !is_wp_error($brandNames) && in_array('apple', array_map('strtolower', $brandNames), true);
            $text = $product->get_name();
            foreach (['iphones', 'laptops', 'mini-computers', 'bluetooth-gadgets', 'cell-phone-accessories', 'ai-tech'] as $slug) {
                $evidence = $text;
                if ($slug === 'bluetooth-gadgets') $evidence .= ' ' . $product->get_attribute('pa_connectivity');
                if ($slug === 'cell-phone-accessories') $evidence .= ' ' . $product->get_attribute('pa_compatible-devices');
                if (KREV_PA_Metacategories::matches($slug, $google ? [$google['id']] : [], $evidence, $apple, $explicit)) $groups[] = $slug;
            }
        }
        if ($google && str_starts_with($google['path'], 'Arts & Entertainment > Hobbies & Creative Arts > Collectibles')) $groups[] = 'collectibles';
        if (in_array('collectibles', $groups, true) && class_exists('KREV_PA_Metacategories')) {
            $explicit = in_array('pokemon-cards', $slugs, true) ? ['pokemon-cards'] : [];
            if (KREV_PA_Metacategories::matches('pokemon-cards', $google ? [$google['id']] : [], $product->get_name() . ' ' . wp_strip_all_tags($product->get_short_description()), false, $explicit)) $groups[] = 'pokemon-cards';
        }
        $inventory[] = ['product' => $product, 'groups' => array_values(array_unique($groups)), 'google' => $google];
    }
    return $inventory;
}
function kr_metacategory_products(string $slug, string $googleId = ''): array {
    $products = [];
    foreach (kr_metacategory_inventory() as $row) if (in_array($slug, $row['groups'], true) && ($googleId === '' || $googleId === ($row['google']['id'] ?? ''))) $products[] = $row['product'];
    return $products;
}
function kr_metacategory_children(string $slug): array {
    $children = [];
    foreach (kr_metacategory_inventory() as $row) {
        if (!in_array($slug, $row['groups'], true) || !$row['google']) continue;
        $google = $row['google'];
        $parts = explode(' > ', $google['path']);
        $children[$google['id']] = ['label' => end($parts), 'url' => kr_metacategory_url($slug, $google['id']), 'children' => [], 'target' => '', 'rel' => ''];
    }
    uasort($children, static fn($a, $b) => strnatcasecmp($a['label'], $b['label']));
    return array_values($children);
}
/** Extend every saved Knife Type branch without duplicating existing entries. */
function kr_electric_knife_navigation_tree(array $tree): array {
    foreach ($tree as &$row) {
        $row['children'] = kr_electric_knife_navigation_tree($row['children'] ?? []);
        if (strcasecmp($row['label'] ?? '', 'Knife Type') !== 0) continue;
        foreach ($row['children'] as $child) {
            if (strcasecmp($child['label'] ?? '', 'Electric Knife') === 0 || str_contains($child['url'] ?? '', '/product-category/electric-knife/')) continue 2;
        }
        $row['children'][] = ['label' => 'Electric Knife', 'url' => home_url('/product-category/electric-knife/'), 'children' => [], 'target' => '', 'rel' => ''];
    }
    unset($row);
    return $tree;
}
add_filter('render_block_data', static function(array $block): array {
    if (($block['blockName'] ?? '') !== 'core/navigation-submenu' || strcasecmp(wp_strip_all_tags($block['attrs']['label'] ?? ''), 'Knife Type') !== 0) return $block;
    foreach ($block['innerBlocks'] ?? [] as $child) {
        if (strcasecmp($child['attrs']['label'] ?? '', 'Electric Knife') === 0 || str_contains($child['attrs']['url'] ?? '', '/product-category/electric-knife/')) return $block;
    }
    $block['innerBlocks'][] = ['blockName' => 'core/navigation-link', 'attrs' => ['label' => 'Electric Knife', 'url' => home_url('/product-category/electric-knife/'), 'kind' => 'custom'], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []];
    $block['innerContent'][] = null;
    return $block;
});
function kr_metacategory_navigation_tree(array $tree): array {
    $tree = kr_electric_knife_navigation_tree($tree);
    $knifeTypes = kr_find_navigation_item($tree, ['Knife Type']);
    $children = [];
    foreach (kr_metacategories() as $slug => $group) {
        $rows = $slug === 'kitchen-knives' ? ($knifeTypes['children'] ?? []) : kr_metacategory_children($slug);
        $children[] = ['label' => $group['label'], 'url' => $group['url'] ?? kr_metacategory_url($slug), 'children' => $rows, 'target' => '', 'rel' => '', 'browse_children' => true];
    }
    $entry = ['label' => 'Shop Categories', 'url' => home_url('/categories/'), 'children' => $children, 'target' => '', 'rel' => '', 'browse_children' => true];
    $tree = array_values(array_filter($tree, static fn($row) => strcasecmp($row['label'] ?? '', 'Shop Categories') !== 0));
    $index = count($tree);
    foreach ($tree as $key => $row) if (strcasecmp($row['label'] ?? '', 'Shop All') === 0) { $index = $key; break; }
    array_splice($tree, $index, 0, [$entry]);
    return $tree;
}
function kr_tech_metacategory_sections(): string {
    $copy = [
        'iphones' => ['Pocket-size possibilities', 'iPhones', 'Find a useful next life for an iPhone, with Apple-compatible accessories alongside the phones themselves.'],
        'laptops' => ['Work from anywhere', 'Laptops', 'Portable computers, notebook accessories, docking stations, and useful equipment for a desk that travels.'],
        'mini-computers' => ['Small systems, useful jobs', 'Mini-Computers', 'Compact PCs, desktop systems, servers, and streaming devices—including Chromecast—for everyday computing and home projects.'],
        'bluetooth-gadgets' => ['Small connections, useful possibilities', 'Bluetooth Gadgets', 'Find Bluetooth speakers, headphones, transmitters, wireless peripherals, controllers, and wearables. Explore the connectivity, compatibility, and condition each seller can verify.'],
        'cell-phone-accessories' => ['Make your phone your own', 'Cell Phone Accessories', 'Chargers, cases, screen protectors, headphones, stands, straps, and camera extras for your everyday phone. Check each listing for device compatibility, connectivity, and condition.'],
    ];
    $output = '';
    foreach ($copy as $slug => [$kicker, $title, $description]) {
        $output .= '<section class="kr-tech-section kr-tech-meta-section" id="' . esc_attr($slug) . '" aria-labelledby="kr-meta-' . esc_attr($slug) . '"><div class="kr-tech-shell"><div class="kr-tech-meta-feature"><figure>' . kr_metacategory_image($slug) . '</figure><div><p class="kr-tech-kicker">' . esc_html($kicker) . '</p><h2 id="kr-meta-' . esc_attr($slug) . '">' . esc_html($title) . '</h2><p>' . esc_html($description) . '</p><a class="kr-tech-button" href="' . esc_url(kr_metacategory_url($slug)) . '">Browse ' . esc_html($title) . '</a></div></div>';
        $products = kr_metacategory_products($slug);
        $carousel = $products ? kr_render_tech_featured_carousel($products) : '<div class="kr-tech-meta-empty"><p>No ' . esc_html($title) . ' listings right now. New finds will appear here.</p></div>';
        $carousel = str_replace(['Featured Technology listings', 'Show previous featured technology listing', 'Show next featured technology listing'], [$title . ' listings', 'Show previous ' . $title . ' listing', 'Show next ' . $title . ' listing'], $carousel);
        $output .= '<div class="kr-tech-meta-carousel" aria-label="' . esc_attr($title . ' products') . '">' . $carousel . '</div></div></section>';
    }
    return $output;
}
/** The same editorial layout as Other Finds, with live collection inventories. */
function kr_metacategory_landing_shortcode(array $attributes = []): string {
    $slug = (string) ($attributes['slug'] ?? '');
    if (!in_array($slug, ['home-garden', 'collectibles', 'pokemon-cards'], true)) return '';
    $title = kr_metacategories()[$slug]['label'];
    $products = kr_metacategory_products($slug);
    if ($slug === 'home-garden') {
        $gardenIds = [];
        foreach (kr_metacategory_inventory() as $row) if (in_array($slug, $row['groups'], true) && strpos($row['google']['path'] ?? '', 'Home & Garden > Lawn & Garden') === 0) $gardenIds[] = $row['product']->get_id();
        $garden = array_values(array_filter($products, static fn($p) => in_array($p->get_id(), $gardenIds, true)));
        $home = array_values(array_filter($products, static fn($p) => !in_array($p->get_id(), $gardenIds, true)));
        $sections = [
            ['home', 'Everyday spaces', 'For the home', 'Kitchen essentials, home accents, storage, linens, and useful pieces for everyday living. Browse the actual dimensions, materials, and condition recorded by each seller.', 'home-garden', $home, kr_metacategory_url($slug)],
            ['garden', 'A little room to grow', 'For the garden', 'Tools, planters, outdoor supplies, and finds for the patio or garden. Measurements, power requirements, and compatible equipment appear when the seller knows them.', 'home-garden', $garden, kr_metacategory_url($slug)],
        ];
    } elseif ($slug === 'collectibles') {
        $pokemon = kr_metacategory_products('pokemon-cards');
        $pokemonIds = array_map(static fn($p) => $p->get_id(), $pokemon);
        $other = array_values(array_filter($products, static fn($p) => !in_array($p->get_id(), $pokemonIds, true)));
        $sections = [
            ['pokemon-cards', 'Cards worth keeping', 'Pokémon Cards', kr_metacategory_description('pokemon-cards'), 'pokemon-cards', $pokemon, kr_metacategory_landing_url('pokemon-cards')],
            ['more-collectibles', 'Small pieces, lasting stories', 'More collectibles', 'Coins, stamps, models, and memorabilia from different places and periods. Explore the maker, origin, materials, and identifying details available for each find.', 'collectibles', $other, kr_metacategory_url($slug)],
        ];
    } else {
        $sections = [['cards', 'Cards worth keeping', 'Explore Pokémon Cards', kr_metacategory_description($slug), $slug, $products, kr_metacategory_url($slug)]];
    }
    $output = '<div class="kr-other-finds kr-meta-landing"><section class="kr-other-hero"><div class="kr-other-shell"><p class="kr-other-kicker">KnifeRevive · The Annex</p><h1>' . esc_html($title) . '</h1><p>' . esc_html(kr_metacategory_description($slug)) . '</p>' . kr_bitcoin_lightning_badge() . '</div></section><section class="kr-other-products kr-other-shell" aria-label="' . esc_attr($title . ' collections') . '">';
    foreach ($sections as $index => [$sectionId, $kicker, $heading, $description, $image, $items, $link]) {
        $output .= '<section class="kr-other-category' . ($index % 2 ? ' kr-other-category--reverse' : '') . '" id="' . esc_attr($sectionId) . '" aria-labelledby="kr-landing-' . esc_attr($sectionId) . '"><div class="kr-other-feature"><figure>' . kr_metacategory_image($image) . '</figure><div><p class="kr-other-kicker">' . esc_html($kicker) . '</p><h2 id="kr-landing-' . esc_attr($sectionId) . '">' . esc_html($heading) . '</h2><p>' . esc_html($description) . '</p><a class="kr-other-button" href="' . esc_url($link) . '">Browse ' . esc_html($image === 'pokemon-cards' ? 'Pokémon Cards' : $heading) . '</a></div></div>';
        if ($items) {
            $carousel = kr_render_tech_featured_carousel($items);
            $carousel = str_replace(['Featured Technology listings', 'Show previous featured technology listing', 'Show next featured technology listing'], [$heading . ' listings', 'Show previous ' . $heading . ' listing', 'Show next ' . $heading . ' listing'], $carousel);
            $output .= '<div class="kr-tech-desk kr-meta-landing-carousel">' . $carousel . '</div>';
        } else $output .= '<p class="kr-annex-empty">No listings in this collection right now. New finds will appear here.</p>';
        $output .= '</section>';
    }
    $output .= '<section class="kr-other-category kr-other-category--reverse"><div class="kr-other-feature"><figure>' . kr_metacategory_image($slug) . '</figure><div><p class="kr-other-kicker">Make room for the next find</p><h2>Have something to share?</h2><p>List your item with clear photos, its condition, and the details you can verify. ListLab offers specifications for the selected product type, so buyers can see what matters.</p><a class="kr-other-button" href="' . esc_url(home_url('/my-account/listlab/?action=new')) . '">List an item</a></div></div></section></section></div>';
    return kr_compact_shortcode_markup($output);
}
add_shortcode('kniferevive_metacategory', 'kr_metacategory_landing_shortcode');
add_filter('query_vars', static function(array $vars): array { return array_merge($vars, ['kr_collection', 'kr_google_category']); });
add_action('pre_get_posts', static function(WP_Query $query): void {
    if (is_admin() || !$query->is_main_query()) return;
    $slug = (string) $query->get('kr_collection');
    if (!isset(kr_metacategories()[$slug])) return;
    $googleId = (string) $query->get('kr_google_category');
    if ($googleId !== '' && !preg_match('/^[1-9]\d*$/', $googleId)) $googleId = '-1';
    $products = kr_metacategory_products($slug, $googleId);
    $query->set('post_type', 'product'); $query->set('post_status', 'publish');
    $query->set('post__in', $products ? array_map(static fn($p) => $p->get_id(), $products) : [0]);
    $query->set('orderby', 'post__in');
}, 29);
add_filter('woocommerce_page_title', static function($title) { $slug = (string) get_query_var('kr_collection'); return kr_metacategories()[$slug]['label'] ?? $title; });
add_filter('render_block_core/query-title', static function(string $content): string {
    $slug = (string) get_query_var('kr_collection');
    if (!isset(kr_metacategories()[$slug])) return $content;
    $title = kr_metacategories()[$slug]['label'];
    $googleId = (string) get_query_var('kr_google_category');
    if ($googleId !== '') foreach (kr_metacategory_inventory() as $row) {
        if (in_array($slug, $row['groups'], true) && $googleId === ($row['google']['id'] ?? '')) {
            $parts = explode(' > ', $row['google']['path']); $title .= ' · ' . end($parts); break;
        }
    }
    return preg_replace_callback('/(<h[1-6]\b[^>]*>).*?(<\/h[1-6]>)/s', static fn($match) => $match[1] . esc_html($title) . $match[2], $content, 1) ?? $content;
}, 110);
add_filter('body_class', static function(array $classes): array { $slug = (string) get_query_var('kr_collection'); if (in_array($slug, ['tech', 'iphones', 'laptops', 'mini-computers', 'bluetooth-gadgets', 'cell-phone-accessories', 'ai-tech'], true)) $classes[] = 'kr-ai-catalog'; return $classes; }, 25);
add_action('wp_enqueue_scripts', static function(): void { wp_enqueue_style('kniferevive-metacategories', get_stylesheet_directory_uri() . '/assets/css/metacategories.css', ['kniferevive-annex'], kr_asset_version('assets/css/metacategories.css')); }, 25);
