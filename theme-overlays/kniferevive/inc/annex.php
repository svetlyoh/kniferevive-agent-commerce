<?php
/**
 * The Annex marketplace area.
 *
 * @package KnifeRevive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const KR_OPENCLAW_PRODUCT_TITLE = 'OpenClaw Plugin-Ready Dell OptiPlex 3020 i5 8GB 500GB Ubuntu PC';
const KR_ANNEX_PAGE_SCHEMA_VERSION = '2026.10.11.1';
const KR_ANNEX_CATEGORY_SCHEMA_VERSION = '2026.09.03.1';
const KR_OPENCLAW_PRODUCT_SCHEMA_VERSION = '2026.08.27.8';

/**
 * Ensure the public Annex routes exist after a theme ZIP is installed.
 *
 * Theme files can supply page templates, but WordPress still needs a Page record
 * before a URL such as /art-trade-gallery/ can resolve. This setup is
 * intentionally idempotent and never overwrites an existing page.
 */
function kr_ensure_annex_pages(): void
{
    if (get_option('kr_annex_page_schema_version') === KR_ANNEX_PAGE_SCHEMA_VERSION) {
        return;
    }

    $pages = [
        'art-trade-gallery' => [
            'title' => 'Art Trade Gallery',
            'shortcode' => '[kniferevive_art_gallery]',
        ],
        'technology-trade-desk' => [
            'title' => 'Technology Trade Desk',
            'shortcode' => '[kniferevive_technology_trade_desk]',
        ],
        'other-finds' => [
            'title' => 'Other Finds',
            'shortcode' => '[kniferevive_other_finds]',
        ],
        'home-garden' => ['title' => 'Home & Garden', 'shortcode' => '[kniferevive_metacategory slug="home-garden"]'],
        'collectibles' => ['title' => 'Collectibles', 'shortcode' => '[kniferevive_metacategory slug="collectibles"]'],
        'pokemon-cards' => ['title' => 'Pokémon Cards', 'shortcode' => '[kniferevive_metacategory slug="pokemon-cards"]'],
    ];
    $created = false;

    foreach ($pages as $slug => $pageData) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post) {
            continue;
        }

        $pageId = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $pageData['title'],
            'post_name' => $slug,
            'post_content' => $pageData['shortcode'],
        ], true);

        if (is_wp_error($pageId) || (int) $pageId < 1) {
            return;
        }

        update_post_meta((int) $pageId, '_wp_page_template', get_stylesheet() . '//page-' . $slug);
        $created = true;
    }

    if ($created) {
        flush_rewrite_rules(false);
    }
    update_option('kr_annex_page_schema_version', KR_ANNEX_PAGE_SCHEMA_VERSION, false);
}
add_action('init', 'kr_ensure_annex_pages', 30);

/**
 * Create or migrate the shared WooCommerce categories used by Annex pages.
 *
 * Existing legacy terms are updated in place so product relationships created
 * by WooCommerce or Dokan remain attached to the same term IDs.
 */
function kr_ensure_annex_categories(): void
{
    if (get_option('kr_annex_category_schema_version') === KR_ANNEX_CATEGORY_SCHEMA_VERSION || !taxonomy_exists('product_cat')) {
        return;
    }

    $categories = [
        [
            'name' => 'Tech',
            'slug' => 'technology',
            'description' => '• Description: Computers, AI systems, and technology selected or configured for useful everyday applications, experimentation, automation, and home computing. • Includes: OpenClaw-ready computers, Linux systems, AI-enabled hardware, networking and computing equipment, and other technology we think is worth offering.',
            'legacy_slugs' => ['ai-systems'],
            'legacy_names' => ['AI Systems'],
        ],
        [
            'name' => 'Other Finds',
            'slug' => 'other_finds',
            'description' => '• Description: A rotating collection of useful, unusual, collectible, and interesting items that fall outside KnifeRevive’s main knife, art, and technology collections. • Includes: One-off finds, household items, collectibles, parts, accessories, vintage pieces, and other products we think are worth passing along.',
            'legacy_slugs' => ['miscellaneous'],
            'legacy_names' => ['Miscellaneous'],
        ],
        [
            'name' => 'World Coins',
            'slug' => 'world-coins',
            'description' => '• Description: An evolving collection of affordable coins and small numismatic finds from Europe, Eastern Europe, the Balkans, and neighboring regions. • Includes: Circulated and collectible coins, mixed lots, older denominations, modern European issues, and interesting everyday pieces selected for history, design, origin, or curiosity.',
            'legacy_slugs' => [],
            'legacy_names' => [],
            'parent_slug' => 'other_finds',
        ],
        [
            'name' => 'World Spices',
            'slug' => 'world-spices',
            'description' => '• Description: Pantry spices and seasoning blends inspired by the everyday cooking traditions of Eastern Europe, the Balkans, Greece, and surrounding regions. • Includes: Paprika, oregano, black pepper, savory herbs, regional seasoning blends, and other packaged flavors selected from European and Eastern European food traditions.',
            'legacy_slugs' => [],
            'legacy_names' => [],
            'parent_slug' => 'other_finds',
        ],
    ];

    foreach ($categories as $categoryData) {
        $term = get_term_by('slug', $categoryData['slug'], 'product_cat');
        if (!$term instanceof WP_Term) {
            foreach ($categoryData['legacy_slugs'] as $legacySlug) {
                $term = get_term_by('slug', $legacySlug, 'product_cat');
                if ($term instanceof WP_Term) {
                    break;
                }
            }
        }
        if (!$term instanceof WP_Term) {
            foreach ($categoryData['legacy_names'] as $legacyName) {
                $term = get_term_by('name', $legacyName, 'product_cat');
                if ($term instanceof WP_Term) {
                    break;
                }
            }
        }

        $parentId = 0;
        if (!empty($categoryData['parent_slug'])) {
            $parent = get_term_by('slug', $categoryData['parent_slug'], 'product_cat');
            if (!$parent instanceof WP_Term) {
                return;
            }
            $parentId = (int) $parent->term_id;
        }

        $result = $term instanceof WP_Term
            ? wp_update_term((int) $term->term_id, 'product_cat', [
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'description' => $categoryData['description'],
                'parent' => $parentId,
            ])
            : wp_insert_term($categoryData['name'], 'product_cat', [
                'slug' => $categoryData['slug'],
                'description' => $categoryData['description'],
                'parent' => $parentId,
            ]);

        if (is_wp_error($result)) {
            return;
        }
    }

    update_option('kr_annex_category_schema_version', KR_ANNEX_CATEGORY_SCHEMA_VERSION, false);
}
add_action('init', 'kr_ensure_annex_categories', 25);

/**
 * Move the real OpenClaw computer listing into Tech without hardcoding its ID.
 * Replacing only product_cat terms keeps its tags, brand, vendor, and inventory.
 */
function kr_ensure_openclaw_product_category(): void
{
    if (get_option('kr_openclaw_product_schema_version') === KR_OPENCLAW_PRODUCT_SCHEMA_VERSION || !taxonomy_exists('product_cat')) {
        return;
    }

    $tech = get_term_by('slug', 'technology', 'product_cat');
    if (!$tech instanceof WP_Term) {
        return;
    }

    $productPost = get_page_by_path('openclaw-plugin-ready-dell-optiplex-3020-i5-8gb-500gb-ubuntu-pc', OBJECT, 'product');
    if (!$productPost instanceof WP_Post) {
        return;
    }

    $result = wp_set_object_terms((int) $productPost->ID, [(int) $tech->term_id], 'product_cat', false);
    if (is_wp_error($result)) {
        return;
    }

    clean_object_term_cache((int) $productPost->ID, 'product');
    update_option('kr_openclaw_product_schema_version', KR_OPENCLAW_PRODUCT_SCHEMA_VERSION, false);
}
add_action('init', 'kr_ensure_openclaw_product_category', 27);

/**
 * One-time owner-requested Featured flag. Later merchandising stays editable
 * in WooCommerce; removing the flag must not cause it to be reapplied.
 */
function kr_feature_initial_tech_product(): void
{
    if (get_option('kr_tech_featured_seed_20260829') || !function_exists('wc_get_product')) {
        return;
    }
    $post = get_page_by_path('openclaw-plugin-ready-dell-optiplex-3020-i5-8gb-500gb-ubuntu-pc', OBJECT, 'product');
    if (!$post instanceof WP_Post || !has_term('technology', 'product_cat', $post->ID)) {
        return;
    }
    $product = wc_get_product($post->ID);
    if (!$product instanceof WC_Product) {
        return;
    }
    $product->set_featured(true);
    $product->save();
    update_option('kr_tech_featured_seed_20260829', true, false);
}
add_action('init', 'kr_feature_initial_tech_product', 28);

/**
 * Prefer the bundled Annex templates over stale or empty Site Editor copies.
 *
 * @param WP_Block_Template|null $template
 * @return WP_Block_Template|null
 */
function kr_prefer_bundled_annex_page_template($template, string $id, string $templateType)
{
    if ($template !== null || $templateType !== 'wp_template' || !function_exists('get_block_file_template')) {
        return $template;
    }

    $allowed = [
        get_stylesheet() . '//page-art-trade-gallery',
        get_stylesheet() . '//page-technology-trade-desk',
        get_stylesheet() . '//page-other-finds',
    ];
    return in_array($id, $allowed, true) ? get_block_file_template($id, $templateType) : null;
}
add_filter('pre_get_block_template', 'kr_prefer_bundled_annex_page_template', 10, 3);

/**
 * Discover the brand taxonomy that is actually registered by WooCommerce.
 */
function kr_get_product_brand_taxonomy(): string
{
    return taxonomy_exists('product_brand') ? 'product_brand' : '';
}

/**
 * Resolve a product category without changing an existing slug.
 *
 * @param array<int, string> $slugs
 * @param array<int, string> $names
 */
function kr_find_product_category(array $slugs, array $names = []): ?WP_Term
{
    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term instanceof WP_Term) {
            return $term;
        }
    }
    foreach ($names as $name) {
        $term = get_term_by('name', $name, 'product_cat');
        if ($term instanceof WP_Term) {
            return $term;
        }
    }
    return null;
}

/**
 * Central source of truth for Annex destinations and taxonomy terms.
 *
 * @return array<string, array<string, mixed>>
 */
function kr_get_annex_terms(): array
{
    $art = kr_find_product_category(['art', 'fine-art'], ['Art', 'Fine Art']);
    $ai = kr_find_product_category(['technology', 'ai-systems'], ['Tech', 'AI Systems']);
    $misc = kr_find_product_category(['other_finds', 'miscellaneous'], ['Other Finds', 'Miscellaneous']);

    return [
        'art' => [
            'label' => 'Art Trade Gallery',
            'category' => $art,
            'page_url' => home_url('/art-trade-gallery/'),
        ],
        'ai' => [
            'label' => 'Technology Trade Desk',
            'category' => $ai,
            'page_url' => home_url('/technology-trade-desk/'),
        ],
        'iphones' => [
            'label' => 'iPhones',
            'category' => kr_find_product_category(['iphones'], ['iPhones']),
            'page_url' => kr_metacategory_landing_url('iphones'),
        ],
        'laptops' => [
            'label' => 'Laptops',
            'category' => kr_find_product_category(['laptops'], ['Laptops']),
            'page_url' => kr_metacategory_landing_url('laptops'),
        ],
        'mini-computers' => [
            'label' => 'Mini-Computers',
            'category' => kr_find_product_category(['mini-computers'], ['Mini-Computers']),
            'page_url' => kr_metacategory_landing_url('mini-computers'),
        ],
        'ai-tech' => [
            'label' => 'AI-Tech',
            'category' => kr_find_product_category(['ai-tech'], ['AI-Tech']),
            'page_url' => kr_metacategory_url('ai-tech'),
        ],
        'home-garden' => [
            'label' => 'Home & Garden',
            'category' => kr_find_product_category(['home-garden'], ['Home & Garden']),
            'page_url' => kr_metacategory_landing_url('home-garden'),
        ],
        'misc' => [
            'label' => 'Other Finds',
            'category' => $misc,
            'page_url' => home_url('/other-finds/'),
        ],
        'collectibles' => [
            'label' => 'Collectibles',
            'category' => kr_find_product_category(['collectibles'], ['Collectibles']),
            'page_url' => kr_metacategory_landing_url('collectibles'),
        ],
        'pokemon-cards' => [
            'label' => 'Pokémon Cards',
            'category' => kr_find_product_category(['pokemon-cards'], ['Pokémon Cards']),
            'page_url' => kr_metacategory_landing_url('pokemon-cards'),
        ],
    ];
}

/**
 * Category IDs that must stay out of knife-specific product loops.
 *
 * @return array<int, int>
 */
function kr_get_annex_category_ids(): array
{
    $ids = [];
    foreach (kr_get_annex_terms() as $destination) {
        if (($destination['category'] ?? null) instanceof WP_Term) {
            $ids[] = (int) $destination['category']->term_id;
        }
    }
    return array_values(array_unique(array_filter($ids)));
}

/**
 * Explicit allowlist for products shown in the KnifeRevive knife experience.
 * Sharpening services and every Annex category intentionally remain separate.
 *
 * @return array<int, string>
 */
function kr_get_knife_category_slugs(): array
{
    return [
        'boning-knife',
        'bread-knife',
        'butcher-knife',
        'chefs-knife',
        'cleaver',
        'fillet-knife',
        'nakiri-knife',
        'oyster-knife',
        'paring-knife',
        'santoku-knife',
        'serated-utility-knife',
        'carving-knife',
        'steak-knife',
        'tomato-knife',
        'utility-knife',
    ];
}

/**
 * @return array<int, int>
 */
function kr_get_knife_category_ids(): array
{
    $ids = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'ids',
        'slug' => kr_get_knife_category_slugs(),
    ]);
    return is_wp_error($ids) ? [] : array_map('intval', $ids);
}

/**
 * Canonical knife-scope check shared by cards, ListLab, migration, and feeds.
 * A descendant of an allowed knife category is included. Knife Sharpening is
 * always excluded, even if a malformed product also has a knife category.
 */
function kniferevive_is_knife_product(int $productId): bool
{
    if ($productId < 1 || !taxonomy_exists('product_cat')) {
        return false;
    }

    $terms = wp_get_post_terms($productId, 'product_cat');
    if (is_wp_error($terms)) {
        return false;
    }

    $allowed = array_fill_keys(kr_get_knife_category_slugs(), true);
    $matched = false;
    foreach ($terms as $term) {
        $slugs = [(string) $term->slug];
        foreach (get_ancestors((int) $term->term_id, 'product_cat', 'taxonomy') as $ancestorId) {
            $ancestor = get_term((int) $ancestorId, 'product_cat');
            if ($ancestor instanceof WP_Term) {
                $slugs[] = (string) $ancestor->slug;
            }
        }
        if (in_array('knife-sharpening', $slugs, true)) {
            return false;
        }
        if (array_intersect_key($allowed, array_fill_keys($slugs, true))) {
            $matched = true;
        }
    }

    return $matched;
}

/**
 * Render a WooCommerce shortcode while excluding all Annex categories.
 */
function kr_render_knife_products_shortcode(string $shortcode): string
{
    $filter = static function (array $queryArgs): array {
        $knifeCategories = kr_get_knife_category_ids();
        if (!$knifeCategories) {
            return $queryArgs;
        }
        $taxQuery = isset($queryArgs['tax_query']) && is_array($queryArgs['tax_query']) ? $queryArgs['tax_query'] : [];
        $taxQuery[] = [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $knifeCategories,
            'operator' => 'IN',
            'include_children' => false,
        ];
        $queryArgs['tax_query'] = $taxQuery;
        return $queryArgs;
    };

    add_filter('woocommerce_shortcode_products_query', $filter, 20, 1);
    try {
        $markup = do_shortcode($shortcode);

        // Some block-theme hosts resolve classic shortcode templates from a
        // persistent WooCommerce template cache instead of the child-theme
        // content-product.php override. Normalize the completed loop as well
        // so homepage shortcode grids use the same knife card as block grids.
        return function_exists('kr_replace_block_knife_cards')
            ? kr_replace_block_knife_cards($markup)
            : $markup;
    } finally {
        remove_filter('woocommerce_shortcode_products_query', $filter, 20);
    }
}

/**
 * Keep the Shop archive knife-only with the same positive taxonomy allowlist.
 */
add_action('pre_get_posts', static function (WP_Query $query): void {
    if (is_admin() || !$query->is_main_query() || $query->is_search() || !function_exists('is_shop') || !is_shop() || isset(kr_metacategories()[(string) $query->get('kr_collection')])) {
        return;
    }
    $knifeCategories = kr_get_knife_category_ids();
    if (!$knifeCategories) {
        return;
    }
    $taxQuery = $query->get('tax_query');
    $taxQuery = is_array($taxQuery) ? $taxQuery : [];
    $taxQuery[] = [
        'taxonomy' => 'product_cat',
        'field' => 'term_id',
        'terms' => $knifeCategories,
        'operator' => 'IN',
        'include_children' => false,
    ];
    $query->set('tax_query', $taxQuery);
}, 30);

/**
 * Annex links used by desktop, mobile, and footer navigation.
 */
function kr_annex_navigation_links(): string
{
    $output = '';
    foreach (kr_get_annex_terms() as $destination) {
        $output .= '<a href="' . esc_url((string) $destination['page_url']) . '">' . esc_html((string) $destination['label']) . '</a>';
    }
    return $output;
}

function kr_annex_desktop_navigation(): string
{
    return '<details class="kr-annex-menu"><summary><span class="kr-annex-menu__label">The Annex</span><span aria-hidden="true">+</span></summary><div class="kr-annex-menu-panel"><a class="kr-annex-view-all" href="' . esc_url(home_url('/categories/')) . '">View all Annex categories</a>' . kr_annex_navigation_links() . '</div></details>';
}

function kr_annex_drawer_navigation(): string
{
    return '<div class="kr-annex-drawer"><details><summary><span class="kr-annex-drawer__label">The Annex</span><span class="kr-new-badge">New</span><span class="kr-annex-chevron" aria-hidden="true">+</span></summary><div class="kr-annex-drawer-links"><a class="kr-annex-view-all" href="' . esc_url(home_url('/categories/')) . '">View all Annex categories</a>' . kr_annex_navigation_links() . '</div></details></div>';
}

function kr_annex_footer_group(): string
{
    return '<details class="kr-footer-group" open><summary>The Annex<span aria-hidden="true">+</span></summary><nav class="kr-footer-annex" aria-label="' . esc_attr__('The Annex', 'kniferevive') . '">' . kr_annex_navigation_links() . '</nav></details>';
}

/**
 * Fetch WooCommerce products for a landing page using taxonomy queries.
 *
 * @return array<int, WC_Product>
 */
function kr_get_products_for_landing_page(string $category, ?string $brand = null, int $limit = -1): array
{
    if (!function_exists('wc_get_product')) {
        return [];
    }

    $taxQuery = [
        [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => [$category],
            'include_children' => false,
        ],
    ];
    if (function_exists('wc_get_product_visibility_term_ids')) {
        $visibilityTerms = wc_get_product_visibility_term_ids();
        $excludedVisibilityTerms = array_filter([
            (int) ($visibilityTerms['exclude-from-catalog'] ?? 0),
            get_option('woocommerce_hide_out_of_stock_items') === 'yes' ? (int) ($visibilityTerms['outofstock'] ?? 0) : 0,
        ]);
        if ($excludedVisibilityTerms) {
            $taxQuery[] = [
                'taxonomy' => 'product_visibility',
                'field' => 'term_taxonomy_id',
                'terms' => $excludedVisibilityTerms,
                'operator' => 'NOT IN',
            ];
        }
    }
    $brandTaxonomy = kr_get_product_brand_taxonomy();
    if ($brand !== null && $brandTaxonomy !== '') {
        $taxQuery[] = [
            'taxonomy' => $brandTaxonomy,
            'field' => 'slug',
            'terms' => [$brand],
        ];
    }

    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'no_found_rows' => true,
        'tax_query' => $taxQuery, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    ]);

    $products = [];
    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product instanceof WC_Product) {
            $products[] = $product;
        }
    }
    return $products;
}

/**
 * Retrieve the newest WooCommerce-featured Art products for Selected Works.
 *
 * The WooCommerce Featured star is stored as the "featured" product_visibility
 * taxonomy term. This intentionally does not require a product to be
 * purchasable, so exhibition listings without a price can still be shown.
 *
 * @return array<int, WC_Product>
 */
function kr_get_art_featured_products(int $limit = 4): array
{
    if (!function_exists('wc_get_product') || !function_exists('wc_get_product_visibility_term_ids')) {
        return [];
    }

    $category = kr_find_product_category(['art', 'fine-art'], ['Art', 'Fine Art']);
    if (!$category instanceof WP_Term) {
        return [];
    }

    $visibilityTerms = wc_get_product_visibility_term_ids();
    $featuredTerm = (int) ($visibilityTerms['featured'] ?? 0);
    if (!$featuredTerm) {
        return [];
    }

    $taxQuery = [[
        'taxonomy' => 'product_cat',
        'field' => 'term_id',
        'terms' => [(int) $category->term_id],
        'include_children' => false,
    ], [
        'taxonomy' => 'product_visibility',
        'field' => 'term_taxonomy_id',
        'terms' => [$featuredTerm],
        'operator' => 'IN',
    ]];
    $excludedVisibilityTerms = array_filter([
        (int) ($visibilityTerms['exclude-from-catalog'] ?? 0),
        get_option('woocommerce_hide_out_of_stock_items') === 'yes' ? (int) ($visibilityTerms['outofstock'] ?? 0) : 0,
    ]);
    if ($excludedVisibilityTerms) {
        $taxQuery[] = [
            'taxonomy' => 'product_visibility',
            'field' => 'term_taxonomy_id',
            'terms' => $excludedVisibilityTerms,
            'operator' => 'NOT IN',
        ];
    }

    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => min(4, max(1, $limit)),
        'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
        'no_found_rows' => true,
        'tax_query' => $taxQuery, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    ]);

    $products = [];
    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product instanceof WC_Product && $product->is_visible()) {
            $products[] = $product;
        }
    }

    return $products;
}

/**
 * Render editorial product cards for Annex landing pages.
 *
 * @param array<int, WC_Product> $products
 */
function kr_render_annex_product_cards(array $products, string $buttonLabel = 'View Work', string $cardType = 'default'): string
{
    if (!$products) {
        return '<p class="kr-annex-empty">New listings are being prepared. Please check back soon.</p>';
    }

    $output = '<div class="kr-annex-product-grid">';
    foreach ($products as $product) {
        $output .= kr_render_annex_product_card($product, $buttonLabel, $cardType);
    }
    return $output . '</div>';
}

/**
 * Render one editorial Annex card so progressive loading reuses the exact
 * initial card markup.
 */
function kr_render_annex_product_card(WC_Product $product, string $buttonLabel = 'View Work', string $cardType = 'default'): string
{
    $url = $product->get_permalink();
    $subtitle = trim((string) $product->get_meta(defined('KNIFEREVIVE_LISTING_SUBTITLE_META_KEY') ? KNIFEREVIVE_LISTING_SUBTITLE_META_KEY : '_kniferevive_listing_subtitle'));
    if ($subtitle === '' && function_exists('kniferevive_generate_listing_subtitle')) {
        $subtitle = kniferevive_generate_listing_subtitle($product);
    }
    $output = '<article class="kr-annex-product-card kr-annex-product-card--' . esc_attr($cardType) . '">';
    $output .= '<a class="kr-annex-product-image" href="' . esc_url($url) . '">' . $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'decoding' => 'async']) . '</a>';
    $output .= '<div class="kr-annex-product-copy"><h3><a href="' . esc_url($url) . '">' . esc_html($product->get_name()) . '</a></h3>';
    if ($subtitle !== '') {
        $output .= '<p class="kniferevive-product-subtitle">' . esc_html($subtitle) . '</p>';
    }
    if ($cardType === 'spice') {
        $brandNames = [];
        if (function_exists('kr_product_brand_terms')) {
            $brandNames = wp_list_pluck(kr_product_brand_terms($product->get_id()), 'name');
        }
        if (!$brandNames) {
            $attributeBrand = trim((string) $product->get_attribute('pa_brand'));
            if ($attributeBrand !== '') {
                $brandNames[] = $attributeBrand;
            }
        }
        if ($brandNames) {
            $output .= '<p class="kr-annex-card-brand"><strong>' . esc_html__('Brand:', 'kniferevive') . '</strong> ' . esc_html(implode(', ', array_unique($brandNames))) . '</p>';
        }
    }
    $telegram = function_exists('kniferevive_telegram_render_icon') ? kniferevive_telegram_render_icon($product->get_id()) : '';
    $whatsapp = function_exists('kniferevive_whatsapp_render_icon') ? kniferevive_whatsapp_render_icon($product->get_id()) : '';
    $contacts = $telegram . $whatsapp;
    $output .= '<div class="kr-product-price-row kr-product-price-row--annex"><div class="kr-product-price kr-annex-product-price">' . wp_kses_post($product->get_price_html()) . '</div>' . ($contacts !== '' ? '<div class="kr-product-contact kr-product-contact-actions">' . $contacts . '</div>' : '') . '</div>';
    if ($cardType === 'coin') {
        $tags = wp_get_post_terms($product->get_id(), 'product_tag');
        if (!is_wp_error($tags)) {
            $tags = array_values(array_filter($tags, static fn(WP_Term $tag): bool => $tag->slug !== 'sharpened'));
            if ($tags) {
                $output .= '<div class="kr-annex-card-status" aria-label="' . esc_attr__('Product status', 'kniferevive') . '">';
                foreach ($tags as $tag) {
                    $tagUrl = get_term_link($tag);
                    if (!is_wp_error($tagUrl)) {
                        $output .= '<a href="' . esc_url($tagUrl) . '">' . esc_html($tag->name) . '</a>';
                    }
                }
                $output .= '</div>';
            }
        }
    }
    if (function_exists('kniferevive_render_whatnot_demo_card')) {
        $output .= kniferevive_render_whatnot_demo_card($product->get_id());
    }
    return $output . '<a class="kr-annex-card-link" href="' . esc_url($url) . '">' . esc_html($buttonLabel) . ' <span aria-hidden="true">→</span></a></div></article>';
}

/**
 * Render a real WooCommerce purchase control for a featured listing.
 */
function kr_render_product_purchase_button(WC_Product $product): string
{
    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        return '';
    }

    $classes = ['kr-tech-button', 'kr-tech-buy-button', 'product_type_' . $product->get_type()];
    if ($product->supports('ajax_add_to_cart')) {
        $classes[] = 'add_to_cart_button';
        $classes[] = 'ajax_add_to_cart';
    }
    $label = $product->is_type('simple') ? __('Buy This System', 'kniferevive') : $product->add_to_cart_text();

    return sprintf(
        '<a href="%1$s" data-quantity="1" class="%2$s" data-product_id="%3$d" data-product_sku="%4$s" aria-label="%5$s" rel="nofollow">%6$s</a>',
        esc_url($product->add_to_cart_url()),
        esc_attr(implode(' ', $classes)),
        $product->get_id(),
        esc_attr($product->get_sku()),
        esc_attr(sprintf(__('Add “%s” to your cart', 'kniferevive'), $product->get_name())),
        esc_html($label)
    );
}

/**
 * Tech inventory, split exclusively by WooCommerce's Featured flag.
 * Both sections use newest-first ordering, never a product name or tag.
 *
 * @return array<int, WC_Product>
 */
function kr_get_tech_products(bool $featured): array
{
    if (!function_exists('wc_get_products')) {
        return [];
    }

    $technology = get_term_by('slug', 'technology', 'product_cat');
    if (!$technology instanceof WP_Term) {
        return [];
    }
    $products = wc_get_products([
        'status' => 'publish',
        'category' => [$technology->slug],
        'featured' => $featured,
        'visibility' => 'catalog',
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    return array_values(array_filter($products, static function (WC_Product $product): bool {
        return $product->is_visible();
    }));
}

/** Render the shared ListLab/WooCommerce timed-offer component. */
function kr_render_timed_offer(WC_Product $product, string $mode = 'full'): string
{
    return class_exists('KREV_ListLab_Timed_Offers') ? KREV_ListLab_Timed_Offers::render($product, $mode) : '';
}

/**
 * Shared full-width listing rows for featured and recently added Tech.
 * All merchandising content comes from the product, including its full
 * description (falling back to the short description when necessary).
 *
 * @param array<int, WC_Product> $products
 */
function kr_render_tech_product_rows(array $products): string
{
    ob_start();
    ?>
    <div class="kr-tech-product-list">
        <?php foreach ($products as $product) :
            $url = $product->get_permalink();
            $description = trim($product->get_description()) !== '' ? $product->get_description() : $product->get_short_description();
            $specs = kr_get_product_spec_rows($product);
            ?>
            <article class="kr-tech-product-row" data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>">
                <div class="kr-tech-product-grid">
                    <a class="kr-tech-product-media" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>"><?php echo $product->get_image('large', ['loading' => 'lazy', 'decoding' => 'async']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <div class="kr-tech-product-copy">
                        <h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
                        <?php $telegram = function_exists('kniferevive_telegram_render_icon') ? kniferevive_telegram_render_icon($product->get_id()) : ''; $whatsapp = function_exists('kniferevive_whatsapp_render_icon') ? kniferevive_whatsapp_render_icon($product->get_id()) : ''; $contacts = $telegram . $whatsapp; ?>
                        <div class="kr-product-price-row kr-product-price-row--tech"><div class="kr-product-price kr-tech-price"><?php echo wp_kses_post($product->get_price_html()); ?></div><?php if ($contacts !== '') : ?><div class="kr-product-contact kr-product-contact-actions"><?php echo $contacts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped shared renderers. ?></div><?php endif; ?></div>
                        <?php echo kr_render_timed_offer($product, 'compact'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php if (function_exists('kniferevive_render_whatnot_demo_card')) echo kniferevive_render_whatnot_demo_card($product->get_id()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <div class="kr-tech-stock"><?php echo wp_kses_post(wc_get_stock_html($product)); ?></div>
                        <div class="kr-tech-description"><?php echo wp_kses_post(wpautop(do_blocks($description))); ?></div>
                        <div class="kr-actions"><?php echo kr_render_product_purchase_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a class="kr-tech-button kr-tech-button--ghost" href="<?php echo esc_url($url); ?>">View Details</a></div>
                    </div>
                </div>
                <?php if ($specs) : ?><dl class="kr-tech-specs"><?php foreach ($specs as $label => $value) : ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}

/**
 * Compact, horizontally scrollable cards for featured Technology products.
 * This is intentionally supplied only with the technology-category query.
 *
 * @param array<int, WC_Product> $products
 */
function kr_render_tech_featured_carousel(array $products): string
{
    if (!$products) {
        return '<p class="kr-annex-empty">' . esc_html__('No featured Tech listings right now. Explore recently added listings below.', 'kniferevive') . '</p>';
    }

    ob_start();
    ?>
    <div class="kr-tech-featured-carousel" data-kr-tech-carousel>
        <div class="kr-tech-carousel-controls">
            <button type="button" class="kr-tech-carousel-button" data-kr-tech-carousel-prev aria-label="<?php esc_attr_e('Show previous featured technology listing', 'kniferevive'); ?>">←</button>
            <button type="button" class="kr-tech-carousel-button" data-kr-tech-carousel-next aria-label="<?php esc_attr_e('Show next featured technology listing', 'kniferevive'); ?>">→</button>
        </div>
        <div class="kr-tech-carousel-track" data-kr-tech-carousel-track aria-label="<?php esc_attr_e('Featured Technology listings', 'kniferevive'); ?>" tabindex="0">
            <?php foreach ($products as $product) :
                $url = $product->get_permalink();
                $telegram = function_exists('kniferevive_telegram_render_icon') ? kniferevive_telegram_render_icon($product->get_id()) : '';
                $whatsapp = function_exists('kniferevive_whatsapp_render_icon') ? kniferevive_whatsapp_render_icon($product->get_id()) : '';
                $contacts = $telegram . $whatsapp;
                ?>
                <article class="kr-tech-carousel-card" data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>">
                    <a class="kr-tech-carousel-media" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>"><?php echo $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'decoding' => 'async']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <div class="kr-tech-carousel-copy">
                        <h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
                        <div class="kr-product-price-row kr-product-price-row--tech-carousel"><div class="kr-product-price"><?php echo wp_kses_post($product->get_price_html()); ?></div><?php if ($contacts !== '') : ?><div class="kr-product-contact kr-product-contact-actions"><?php echo $contacts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped shared renderers. ?></div><?php endif; ?></div>
                        <?php echo kr_render_timed_offer($product, 'compact'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php if (function_exists('kniferevive_render_whatnot_demo_card')) echo kniferevive_render_whatnot_demo_card($product->get_id()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <a class="kr-tech-carousel-link" href="<?php echo esc_url($url); ?>"><?php esc_html_e('View listing', 'kniferevive'); ?> <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}

/**
 * Selected specification rows from real product attributes only.
 *
 * @return array<string, string>
 */
function kr_get_product_spec_rows(WC_Product $product): array
{
    $wanted = ['operating system', 'os', 'processor', 'cpu', 'memory', 'ram size', 'ram', 'storage', 'hard drive capacity', 'storage type', 'model', 'form factor', 'connectivity', 'gpu'];
    $rows = [];
    foreach ($product->get_attributes() as $attribute) {
        if (!$attribute instanceof WC_Product_Attribute) {
            continue;
        }
        $label = wc_attribute_label($attribute->get_name());
        if (!in_array(strtolower($label), $wanted, true)) {
            continue;
        }
        $values = $attribute->is_taxonomy()
            ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])
            : $attribute->get_options();
        $rows[$label] = implode(', ', array_map('wp_strip_all_tags', $values));
    }
    return array_filter($rows);
}

function kr_attachment_image_by_filename(string $filename, string $size, array $attributes): string
{
    if (!function_exists('kr_attachment_id_by_filename')) {
        return '';
    }
    $id = kr_attachment_id_by_filename($filename);
    return $id > 0 ? (string) wp_get_attachment_image($id, $size, false, $attributes) : '';
}

/**
 * Render an image bundled with the child theme.
 *
 * @param array<string, string> $attributes
 */
function kr_theme_image(string $relativePath, string $alt, array $attributes = []): string
{
    $relativePath = ltrim($relativePath, '/');
    $filePath = get_stylesheet_directory() . '/' . $relativePath;
    if (!is_file($filePath)) {
        return '';
    }

    $imageAttributes = array_merge([
        'src' => get_stylesheet_directory_uri() . '/' . $relativePath,
        'alt' => $alt,
        'loading' => 'lazy',
        'decoding' => 'async',
    ], $attributes);
    $dimensions = @getimagesize($filePath);
    if (is_array($dimensions)) {
        $imageAttributes['width'] = (string) $dimensions[0];
        $imageAttributes['height'] = (string) $dimensions[1];
    }

    $markup = '<img';
    foreach ($imageAttributes as $name => $value) {
        $markup .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
    }
    return $markup . '>';
}

/**
 * Homepage Annex cards.
 */
function kr_render_annex_cards(): string
{
    $terms = kr_get_annex_terms();
    $artCategory = $terms['art']['category'];
    $artProducts = $artCategory instanceof WP_Term ? kr_get_products_for_landing_page($artCategory->slug, 'jordan_ivanov', 1) : [];
    if (!$artProducts && $artCategory instanceof WP_Term) {
        $artProducts = kr_get_products_for_landing_page($artCategory->slug, null, 1);
    }
    $artImage = $artProducts ? $artProducts[0]->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'decoding' => 'async']) : '';
    $techImage = kr_theme_image('assets/images/technology/Front_Image_claw2.png', __('OpenClaw 2 self-hosted AI system on Dell hardware', 'kniferevive'));
    $otherFindsImage = kr_theme_image('assets/images/other-finds/World_finds2.png', __('World coins, Eastern European spices and international finds', 'kniferevive'));

    ob_start();
    ?>
    <section class="kr-section kr-annex-home" id="the-annex" aria-labelledby="kr-annex-heading">
        <div class="kr-container">
            <p class="kr-annex-kicker">The Annex <span>New</span></p>
            <div class="kr-annex-intro">
                <h2 id="kr-annex-heading">Beyond the blade.</h2>
                <p>We’re expanding into a few things we genuinely love: original art, custom AI systems, and other interesting finds.</p>
            </div>
            <div class="kr-annex-card-grid">
                <article class="kr-annex-home-card kr-annex-home-card--art">
                    <div class="kr-annex-home-media"><?php echo $artImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <div><p>Fine art</p><h3>Art Trade Gallery</h3><span>Featured artist: Jordan Ivanov</span><a href="<?php echo esc_url((string) $terms['art']['page_url']); ?>">View the Gallery <span aria-hidden="true">→</span></a></div>
                </article>
                <article class="kr-annex-home-card kr-annex-home-card--tech">
                    <div class="kr-annex-home-media"><?php echo $techImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <div><p>Custom AI systems</p><h3>Technology Trade Desk</h3><span>Practical systems for self-hosted AI tools.</span><a href="<?php echo esc_url((string) $terms['ai']['page_url']); ?>">Explore AI Systems <span aria-hidden="true">→</span></a></div>
                </article>
                <article class="kr-annex-home-card kr-annex-home-card--misc">
                    <div class="kr-annex-home-media"><?php echo $otherFindsImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <div><p>One-offs and useful objects</p><h3>Other Finds</h3><span>Interesting things that don’t fit the knife drawer.</span><a href="<?php echo esc_url((string) $terms['misc']['page_url']); ?>">Browse Other Finds <span aria-hidden="true">→</span></a></div>
                </article>
            </div>
        </div>
    </section>
    <?php
    return function_exists('kr_compact_shortcode_markup') ? kr_compact_shortcode_markup((string) ob_get_clean()) : (string) ob_get_clean();
}

/**
 * Art Trade Gallery shortcode.
 */
function kr_art_gallery_shortcode(): string
{
    $category = kr_find_product_category(['art', 'fine-art'], ['Art', 'Fine Art']);
    $categorySlug = $category instanceof WP_Term ? $category->slug : 'art';
    $featured = kr_get_art_featured_products(4);
    $all = kr_get_products_for_landing_page($categorySlug, null, -1);
    $heroImage = $featured ? $featured[0]->get_image('large', ['class' => 'kr-art-hero-image', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async']) : '';

    ob_start();
    ?>
    <div class="kr-art-gallery">
        <section class="kr-art-hero">
            <div class="kr-art-shell kr-art-hero-grid">
                <div><p class="kr-art-kicker">KnifeRevive · The Annex</p><h1>Art Trade Gallery</h1><p>Original works and artists we want to share.</p><?php echo kr_bitcoin_lightning_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <figure><?php echo $heroImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
            </div>
        </section>
        <section class="kr-artist-profile kr-art-shell" aria-labelledby="kr-featured-artist">
            <p class="kr-art-kicker">Featured artist</p>
            <div class="kr-artist-profile-grid"><h2 id="kr-featured-artist">Jordan Ivanov</h2><p>Jordan Ivanov (born 1967) is a Bulgarian-American painter and sculptor based in New Orleans. He earned a Master of Fine Arts degree from the National Academy of Art in Sofia in 1993 and moved to New Orleans in 1998. His career spans painting, sculpture, public art, and large-scale festival production.</p></div>
        </section>
        <section class="kr-art-section kr-art-shell" aria-labelledby="kr-featured-works">
            <div class="kr-art-heading"><p class="kr-art-kicker">Selected works</p><h2 id="kr-featured-works">Featured works</h2></div>
            <?php if ($featured) : ?>
                <?php echo kr_render_annex_product_cards($featured, 'View Work'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <p class="kr-annex-empty">New listings are being prepared. Please check back soon.</p>
            <?php endif; ?>
            <p class="kr-art-collection-cta"><a class="kr-art-collection-link" href="#kr-all-art">Explore all artwork <span aria-hidden="true">↓</span></a></p>
        </section>
        <section class="kr-art-about"><div class="kr-art-shell kr-art-about-grid"><p class="kr-art-kicker">Practice and materials</p><div><h2>Surface, memory, and the human figure.</h2><p>Ivanov combines classical fine-art training with experimental materials and processes. Working across painting and sculpture, he incorporates steel and oxidized iron shavings to build layered surfaces with color, texture, and physical depth.</p><p>His abstract figurative sculptures distill the human body into elongated, gestural silhouettes. Weathering and oxidizing processes create richly varied patinas, balancing flowing forms with the strength and rugged texture of metal. His atmospheric paintings likewise move between abstraction and figurative suggestion, inviting sustained looking and personal interpretation.</p></div></div></section>
        <section class="kr-art-career kr-art-shell" aria-labelledby="kr-art-career-heading">
            <div class="kr-art-career-intro"><p class="kr-art-kicker">Biography and career</p><div><h2 id="kr-art-career-heading">From Sofia to New Orleans.</h2><p>Ivanov’s European education and his life in Louisiana meet in a practice shaped by classical form, New Orleans culture, and continuous material experimentation.</p></div></div>
            <div class="kr-art-milestones" aria-label="Jordan Ivanov career milestones">
                <article><span>1967</span><strong>Born in Bulgaria</strong><p>The beginning of a cross-cultural artistic life.</p></article>
                <article><span>1993</span><strong>MFA in Sofia</strong><p>Graduated from Bulgaria’s National Academy of Art.</p></article>
                <article><span>1998</span><strong>New Orleans</strong><p>Moved to Louisiana and became part of its artistic community.</p></article>
                <article><span>Since 2003</span><strong>Mardi Gras artist</strong><p>Designing and painting parade floats as a production artist.</p></article>
            </div>
            <div class="kr-art-career-grid">
                <article><h3>Exhibitions and public art</h3><p>Ivanov has participated in more than 40 solo and group exhibitions across the United States, Europe, and Canada, including presentations in New Orleans, New York, Chicago, Atlanta, Bulgaria, Austria, and Greece. His murals and large-scale sculptures have also appeared in public spaces in New Orleans and Chicago, with additional sculpture installations in Europe. His work is held in private collections internationally.</p></article>
                <article><h3>Artistic vision</h3><p>At the center of Ivanov’s practice is an effort to create an emotional connection between artwork and viewer. His compositions leave room for introspection and individual interpretation, bringing together Bulgarian artistic heritage, the cultural energy of New Orleans, and forms drawn from the human figure and the natural environment.</p></article>
            </div>
            <div class="kr-art-research"><span>Artist research</span><a href="https://www.jillianmac.com/jordan-ivanov" target="_blank" rel="noopener noreferrer">Jillian Mac Fine Art</a><a href="https://www.theneworleansartcenter.com/spotlightbmj6low9b28" target="_blank" rel="noopener noreferrer">New Orleans Art Center</a><a href="https://bnr.bg/en/post/101955659/bulgarian-american-exhibition-in-baton-rouge" target="_blank" rel="noopener noreferrer">Bulgarian National Radio</a></div>
        </section>
        <section class="kr-art-section kr-art-shell" aria-labelledby="kr-all-art">
            <div class="kr-art-heading"><p class="kr-art-kicker">The collection</p><h2 id="kr-all-art">All <?php echo esc_html($category instanceof WP_Term ? $category->name : 'Art'); ?></h2></div>
            <?php echo kr_render_annex_product_cards($all, 'View Artwork Details'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </section>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_art_gallery', 'kr_art_gallery_shortcode');

/**
 * Technology Trade Desk shortcode.
 */
function kr_technology_trade_desk_shortcode(): string
{
    $featuredProducts = kr_get_tech_products(true);
    $recentProducts = kr_get_tech_products(false);
    $ai = kr_find_product_category(['technology', 'ai-systems'], ['Tech', 'AI Systems']);
    $allSystemsUrl = $ai instanceof WP_Term ? get_term_link($ai) : home_url('/product-category/technology/');
    if (is_wp_error($allSystemsUrl)) {
        $allSystemsUrl = home_url('/product-category/technology/');
    }
    $heroImage = kr_theme_image('assets/images/technology/Openclaw2_Feature.png', __('OpenClaw 2 release featured on a self-hosted Dell AI computer', 'kniferevive'), [
        'class' => 'kr-tech-hero-image',
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ]);
    $ubuntuImage = kr_attachment_image_by_filename('openclaw-ubuntu-terminal.jpg', 'large', ['alt' => __('Ubuntu desktop with the OpenClaw environment shown in Terminal', 'kniferevive'), 'loading' => 'lazy', 'decoding' => 'async']);
    $folderImage = kr_attachment_image_by_filename('openclaw-ubuntu-folder.jpg', 'large', ['alt' => __('Ubuntu file browser showing an OpenClaw working folder', 'kniferevive'), 'loading' => 'lazy', 'decoding' => 'async']);
    $hardwareFrontImage = kr_theme_image('assets/images/technology/Front_Image.png', __('OpenClaw Dell OptiPlex 3020 front presentation', 'kniferevive'));
    $hardwareInsideImage = kr_theme_image('assets/images/technology/Dell_Inside.png', __('Dell OptiPlex 3020 interior and component overview', 'kniferevive'));
    $hardwareManualImage = kr_theme_image('assets/images/technology/Manual.png', __('OpenClaw PC new owner instruction manual', 'kniferevive'));
    $hardwareUbuntuImage = kr_theme_image('assets/images/technology/Ubuntu.png', __('Ubuntu desktop running the OpenClaw environment', 'kniferevive'));

    ob_start();
    ?>
    <div class="kr-tech-desk">
        <section class="kr-tech-hero"><div class="kr-tech-shell kr-tech-hero-grid"><div class="kr-tech-hero-copy"><p class="kr-tech-kicker">Technology Trade Desk</p><h1>Your own AI system.<br>On your hardware.</h1><p>Preconfigured Linux computers for OpenClaw, automation and experimentation — without starting from a blank machine.</p><div class="kr-actions"><?php if ($featuredProducts) : ?><a class="kr-tech-button" href="#featured-system">See the Featured System</a><?php endif; ?><a class="kr-tech-button kr-tech-button--ghost" href="#what-is-openclaw">See What OpenClaw Can Do</a></div><?php echo kr_bitcoin_lightning_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figure><?php echo $heroImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure></div></section>

        <section class="kr-tech-section kr-tech-product-spotlight" id="featured-system" aria-labelledby="kr-featured-tech-heading"><div class="kr-tech-shell"><p class="kr-tech-kicker">Selected Tech</p><h2 id="kr-featured-tech-heading">Featured system<?php echo count($featuredProducts) > 1 ? 's' : ''; ?></h2>
            <?php echo kr_render_tech_featured_carousel($featuredProducts); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div></section>

        <?php echo kr_tech_metacategory_sections(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <section class="kr-tech-section kr-tech-explainer" id="what-is-openclaw"><div class="kr-tech-shell kr-tech-copy-grid"><p class="kr-tech-kicker">What OpenClaw is</p><div><h2>AI you can message from anywhere.</h2><p><a href="https://openclaw.ai/" target="_blank" rel="noopener noreferrer">OpenClaw</a> is a self-hosted gateway that connects an AI agent running on your computer to chat apps you already use. Message it from Telegram, WhatsApp, or other supported channels while the gateway, sessions, and configuration run on your machine.</p><div class="kr-tech-feature-grid"><article><span>01</span><h3>One gateway</h3><p>Connect supported chat channels to a single local gateway and agent setup.</p></article><article><span>02</span><h3>Browser Control UI</h3><p>Use the browser dashboard for chat, configuration, sessions, and nodes.</p></article><article><span>03</span><h3>Extensible tools</h3><p>Build workflows with tools, memory, skills, and channel plugins under your chosen policies.</p></article></div></div></div></section>

        <section class="kr-tech-section kr-tech-telegram"><div class="kr-tech-shell kr-tech-telegram-grid"><div><p class="kr-tech-kicker">Chat from Telegram</p><h2>Message your AI from your phone.</h2><div class="kr-tech-stack"><article><h3>Private bot pairing</h3><p>Telegram direct messages use pairing by default for unknown senders. The owner approves access before the bot responds.</p></article><article><h3>Control UI at the computer</h3><p>When you are at the system, use OpenClaw’s browser dashboard for local chat, configuration, and session management.</p></article></div></div><figure><?php echo $folderImage !== '' ? $folderImage : $ubuntuImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><figcaption>Owner-provided Ubuntu/OpenClaw setup image. No credentials or tokens are displayed.</figcaption></figure></div></section>

        <section class="kr-tech-section kr-tech-hardware"><div class="kr-tech-shell"><p class="kr-tech-kicker">The hardware story</p><h2>Built on real hardware.</h2><div class="kr-tech-hardware-grid"><figure><div><?php echo $hardwareFrontImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figcaption><span>OpenClaw-ready system</span><strong>Dell OptiPlex 3020.</strong></figcaption></figure><figure><div><?php echo $hardwareInsideImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figcaption><span>System overview</span><strong>Components documented.</strong></figcaption></figure><figure><div><?php echo $hardwareManualImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figcaption><span>Owner’s manual</span><strong>Setup guidance included.</strong></figcaption></figure><figure><div><?php echo $hardwareUbuntuImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><figcaption><span>Ubuntu environment</span><strong>OpenClaw configured.</strong></figcaption></figure></div><p class="kr-tech-hardware-note">Used/refurbished hardware. No new Dell warranty or Dell endorsement is implied.</p></div></section>

        <section class="kr-tech-section kr-tech-proof"><div class="kr-tech-shell"><p class="kr-tech-kicker">OpenClaw community</p><h2>What people say about OpenClaw</h2><div class="kr-tech-proof-grid"><article><p>Community members describe coordinating email, calendars, WordPress, and server workflows from Telegram.</p><a href="https://openclaw.ai/#what-people-say" target="_blank" rel="noopener noreferrer">OpenClaw community source →</a></article><article><p>People highlight running workflows from a phone while away from the computer hosting their gateway.</p><a href="https://openclaw.ai/#what-people-say" target="_blank" rel="noopener noreferrer">OpenClaw community source →</a></article><article><p>Users often point to extensible skills and self-hosted control as reasons the project feels adaptable.</p><a href="https://openclaw.ai/#what-people-say" target="_blank" rel="noopener noreferrer">OpenClaw community source →</a></article></div><p class="kr-tech-proof-note">These are paraphrased OpenClaw community themes, not KnifeRevive customer testimonials.</p></div></section>

        <section class="kr-tech-section kr-tech-open-source"><div class="kr-tech-shell kr-tech-copy-grid"><p class="kr-tech-kicker">Ownership</p><div><h2>Open source. Your machine. Your setup.</h2><p>OpenClaw is developed in the open by the OpenClaw Foundation and its core project is available under the <a href="https://github.com/openclaw/openclaw/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">MIT License</a>. KnifeRevive’s featured hardware is a separate marketplace offering configured for practical OpenClaw and API-driven automation use.</p><p class="kr-tech-disclosure">OpenClaw is an open-source project developed by the OpenClaw Foundation. KnifeRevive’s computer offerings are independently configured and sold unless a specific affiliation is explicitly stated.</p></div></div></section>

        <section class="kr-tech-final"><div class="kr-tech-shell"><p class="kr-tech-kicker">Technology Trade Desk</p><h2>Find your next system.</h2><div class="kr-actions"><?php if ($featuredProducts) : ?><a class="kr-tech-button" href="#featured-system">View Featured Tech</a><?php endif; ?><a class="kr-tech-button kr-tech-button--ghost" href="<?php echo esc_url((string) $allSystemsUrl); ?>">See All Tech</a></div></div></section>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_technology_trade_desk', 'kr_technology_trade_desk_shortcode');

function kr_other_finds_shortcode(): string
{
    $coinProducts = kr_get_products_for_landing_page('world-coins', null, 8);
    $spiceProducts = kr_get_products_for_landing_page('world-spices', null, 8);
    $coinsImage = kr_theme_image('assets/images/other-finds/coins.png', __('Assorted affordable world coins from Europe and Eastern Europe', 'kniferevive'));
    $spicesImage = kr_theme_image('assets/images/other-finds/spices.png', __('Packaged spices and seasonings from Eastern Europe and Greece', 'kniferevive'));
    ob_start();
    ?>
    <div class="kr-other-finds">
        <section class="kr-other-hero"><div class="kr-other-shell"><p class="kr-other-kicker">KnifeRevive · The Annex</p><h1>Other Finds</h1><p>One-offs, useful objects, and things that don’t fit the knife drawer.</p><?php echo kr_bitcoin_lightning_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></section>
        <section class="kr-other-products kr-other-shell" aria-labelledby="kr-other-products">
            <header class="kr-other-products-intro"><p class="kr-other-kicker">Current finds</p><h2 id="kr-other-products">Browse the collection</h2><p>Finds from Europe, Eastern Europe and beyond — from small pieces of monetary history to familiar pantry flavors.</p></header>
            <section class="kr-other-category" aria-labelledby="kr-world-coins-heading">
                <div class="kr-other-feature"><figure><?php echo $coinsImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure><div><p class="kr-other-kicker">World market finds</p><h3 id="kr-world-coins-heading">World Coins</h3><p>Small pieces of history from across Europe and Eastern Europe. Browse affordable circulated coins, mixed finds, older denominations, and interesting designs from the Balkans and beyond.</p><p class="kr-other-supporting">The selection changes as new finds arrive.</p><a class="kr-other-button" href="<?php echo esc_url(home_url('/product-category/world-coins/')); ?>">Browse World Coins</a></div></div>
                <?php echo kr_render_annex_product_cards($coinProducts, 'View Coin', 'coin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </section>
            <section class="kr-other-category kr-other-category--reverse" aria-labelledby="kr-world-spices-heading">
                <div class="kr-other-feature"><figure><?php echo $spicesImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure><div><p class="kr-other-kicker">Regional pantry</p><h3 id="kr-world-spices-heading">World Spices</h3><p>Everyday flavors from Eastern Europe, the Balkans and Greece. Explore packaged herbs, spices and seasoning blends used in regional home cooking, from paprika and oregano to savory herbs and traditional mixed seasonings.</p><p class="kr-other-supporting">A rotating pantry of familiar regional flavors and harder-to-find imports.</p><a class="kr-other-button" href="<?php echo esc_url(home_url('/product-category/world-spices/')); ?>">Browse World Spices</a></div></div>
                <?php echo kr_render_annex_product_cards($spiceProducts, 'View Spice', 'spice'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </section>
        </section>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_other_finds', 'kr_other_finds_shortcode');

function kr_is_technology_term($term): bool
{
    if (!$term instanceof WP_Term) {
        return false;
    }
    if (in_array($term->slug, ['technology', 'ai-systems'], true)) {
        return true;
    }
    foreach (get_ancestors($term->term_id, 'product_cat', 'taxonomy') as $ancestorId) {
        $ancestor = get_term($ancestorId, 'product_cat');
        if ($ancestor instanceof WP_Term && in_array($ancestor->slug, ['technology', 'ai-systems'], true)) {
            return true;
        }
    }
    return false;
}

function kr_is_technology_product(int $productId): bool
{
    $terms = get_the_terms($productId, 'product_cat');
    if (!$terms || is_wp_error($terms)) {
        return false;
    }
    foreach ($terms as $term) {
        if (kr_is_technology_term($term)) {
            return true;
        }
    }
    return false;
}

add_filter('body_class', static function (array $classes): array {
    if (is_page('art-trade-gallery')) {
        $classes[] = 'kr-art-gallery-page';
    }
    if (is_page('technology-trade-desk')) {
        $classes[] = 'kr-tech-desk-page';
    }
    if (is_page(['other-finds', 'home-garden', 'collectibles', 'pokemon-cards'])) {
        $classes[] = 'kr-other-finds-page';
    }
    if (function_exists('is_product') && is_product()) {
        if (has_term(['art', 'fine-art'], 'product_cat')) {
            $classes[] = 'kr-art-product';
        }
        if (kr_is_technology_product((int) get_queried_object_id())) {
            $classes[] = 'kr-ai-product';
        }
    }
    if (function_exists('is_product_category') && is_product_category() && kr_is_technology_term(get_queried_object())) {
        $classes[] = 'kr-ai-catalog';
    }
    return $classes;
}, 20);

add_action('woocommerce_single_product_summary', static function (): void {
    if (is_product() && kr_is_technology_product(get_the_ID())) {
        echo '<div class="kr-ai-product-note"><strong>' . esc_html__('Technology Trade Desk', 'kniferevive') . '</strong><span>' . esc_html__('Configured systems for practical, API-driven AI automation and Linux experimentation.', 'kniferevive') . '</span></div>';
    }
}, 25);

add_action('wp_head', static function (): void {
    $descriptions = [
        'art-trade-gallery' => 'Explore original fine art and featured works by Jordan Ivanov in KnifeRevive’s Art Trade Gallery.',
        'technology-trade-desk' => 'Explore configured computers for practical self-hosted AI tools, OpenClaw workflows, and Linux experimentation.',
        'other-finds' => 'Browse one-off products and useful objects in KnifeRevive’s curated Other Finds collection.',
        'home-garden' => kr_metacategory_description('home-garden'),
        'collectibles' => kr_metacategory_description('collectibles'),
        'pokemon-cards' => kr_metacategory_description('pokemon-cards'),
    ];
    foreach ($descriptions as $slug => $description) {
        if (is_page($slug)) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            break;
        }
    }
}, 5);
