<?php
/**
 * KnifeRevive child-theme functionality.
 *
 * @package KnifeRevive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once get_stylesheet_directory() . '/inc/brands.php';
require_once get_stylesheet_directory() . '/inc/product-subtitle.php';
require_once get_stylesheet_directory() . '/inc/product-admin-columns.php';
require_once get_stylesheet_directory() . '/inc/annex.php';
require_once get_stylesheet_directory() . '/inc/knife-card.php';
require_once get_stylesheet_directory() . '/inc/categories.php';
require_once get_stylesheet_directory() . '/inc/metacategories.php';
require_once get_stylesheet_directory() . '/inc/tech-theme.php';
require_once get_stylesheet_directory() . '/inc/catalog-search.php';
require_once get_stylesheet_directory() . '/inc/account-avatar.php';
require_once get_stylesheet_directory() . '/inc/email-branding.php';
require_once get_stylesheet_directory() . '/inc/deals.php';

// ListLab selects its own dashboard template at priority 99. Keep the seller
// overview in this theme so a theme upload also updates the metric links.
add_filter('wc_get_template', static function (string $template, string $template_name): string {
    if ($template_name !== 'myaccount/dashboard.php'
        || !is_user_logged_in()
        || !class_exists('KREV_ListLab_Dokan_Adapter')
        || !KREV_ListLab_Dokan_Adapter::can_sell()
        || !class_exists('KREV_ListLab_Account_Dashboard')) {
        return $template;
    }
    $override = get_stylesheet_directory() . '/woocommerce/myaccount/dashboard.php';
    return is_readable($override) ? $override : $template;
}, 100, 2);

add_action('after_setup_theme', static function (): void {
    add_theme_support('woocommerce');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
});

/**
 * Return the environment-safe customer account URL used after frontend login.
 */
function kr_frontend_my_account_url(): string
{
    return function_exists('wc_get_page_permalink')
        ? wc_get_page_permalink('myaccount')
        : home_url('/my-account/');
}

/**
 * Preserve a deliberate wp-admin login for administrators, but never let a
 * marketplace/vendor dashboard URL win a normal frontend-account login.
 *
 * @param mixed $user Authenticated user when supplied by the calling filter.
 */
function kr_preserve_admin_login_destination($user, string $requested = '', string $redirect = ''): bool
{
    if (!$user instanceof WP_User || !user_can($user, 'manage_options')) {
        return false;
    }

    $adminUrl = admin_url();
    foreach ([$requested, $redirect] as $candidate) {
        if ($candidate !== '' && str_starts_with($candidate, $adminUrl)) {
            return true;
        }
    }

    return false;
}

/**
 * Enforce My Account as the post-login landing page for all frontend users.
 * The high-priority filters below intentionally run after vendor/passkey
 * integrations that otherwise choose the Dokan dashboard.
 *
 * @param string $redirect  Proposed redirect URL.
 * @param mixed  $user      Authenticated user.
 * @param string $requested Requested redirect URL.
 */
function kr_force_frontend_my_account_redirect(string $redirect, $user = null, string $requested = ''): string
{
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $redirect;
    }

    if (!$user instanceof WP_User) {
        return $redirect;
    }

    if (kr_preserve_admin_login_destination($user, $requested, $redirect)) {
        return $redirect;
    }

    return kr_frontend_my_account_url();
}

add_filter('woocommerce_login_redirect', static function (string $redirect, $user): string {
    return kr_force_frontend_my_account_redirect($redirect, $user);
}, 9999, 2);

add_filter('login_redirect', static function (string $redirect, string $requested, $user): string {
    return kr_force_frontend_my_account_redirect($redirect, $user, $requested);
}, 9999, 3);

add_filter('secure_passkeys_login_redirect_url', static function (string $redirect, $user = null, string $requested = ''): string {
    return kr_force_frontend_my_account_redirect($redirect, $user, $requested);
}, 9999, 3);

add_filter('woocommerce_registration_redirect', static function (string $redirect): string {
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $redirect;
    }

    return kr_frontend_my_account_url();
}, 9999);

/**
 * Redirect every old Dokan dashboard URL to the supported account area. This
 * also catches login providers that return a seller directly to /dashboard/.
 */
add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    $path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (untrailingslashit($path) !== '/dashboard') {
        return;
    }
    wp_safe_redirect(kr_frontend_my_account_url(), 302);
    exit;
}, 0);

/**
 * Shared Bitcoin Lightning announcement used by the homepage, Annex and cart.
 */
function kr_bitcoin_lightning_badge(string $context = 'site'): string
{
    $title = __('We accept Bitcoin Lightning payments', 'kniferevive');

    ob_start();
    ?>
    <aside class="kr-lightning-badge kr-lightning-badge--<?php echo esc_attr($context); ?>" aria-label="<?php echo esc_attr($title); ?>">
        <span class="kr-lightning-mark" aria-hidden="true"><span>₿</span><b>⚡</b></span>
        <span class="kr-lightning-message">
            <strong><?php echo esc_html($title); ?></strong>
            <span><?php esc_html_e('Pay no Marketplace Fee when you choose Lightning at checkout.', 'kniferevive'); ?></span>
        </span>
    </aside>
    <?php
    return (string) ob_get_clean();
}

function kr_cart_lightning_badge_once(): string
{
    static $shown = false;
    if ($shown) {
        return '';
    }
    $shown = true;
    return kr_bitcoin_lightning_badge('cart');
}

add_action('woocommerce_before_cart', static function (): void {
    echo kr_cart_lightning_badge_once(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5);

add_filter('render_block', static function (string $content, array $block): string {
    if (($block['blockName'] ?? '') === 'woocommerce/cart') {
        return kr_cart_lightning_badge_once() . $content;
    }
    return $content;
}, 10, 2);

/**
 * Add the remaining Dokan-managed settings to WooCommerce's normal account
 * menu, immediately after My Listings. They are shown only to active sellers.
 *
 * @param array<string, string> $items Account menu items.
 * @return array<string, string> Account menu items.
 */
add_filter('woocommerce_account_menu_items', static function (array $items): array {
    $userId = get_current_user_id();
    if (!function_exists('dokan_is_user_seller') || !dokan_is_user_seller($userId, true)) {
        return $items;
    }

    $sellerSettings = [
        'kr-store-settings' => esc_html__('Store', 'kniferevive'),
        'kr-shipping-settings' => esc_html__('Shipping', 'kniferevive'),
    ];
    $menu = [];
    $inserted = false;

    foreach ($items as $key => $label) {
        $menu[$key] = $label;
        if ($key === 'listlab') {
            foreach ($sellerSettings as $settingsKey => $settingsLabel) {
                $menu[$settingsKey] = $settingsLabel;
            }
            $inserted = true;
        }
    }

    if (!$inserted) {
        $menu = array_merge($sellerSettings, $menu);
    }

    return $menu;
}, 40);

add_filter('woocommerce_get_endpoint_url', static function (string $url, string $endpoint): string {
    if (!function_exists('dokan_get_navigation_url')) {
        return $url;
    }

    return match ($endpoint) {
        'kr-store-settings' => dokan_get_navigation_url('settings/store'),
        'kr-shipping-settings' => dokan_get_navigation_url('settings/shipping'),
        default => $url,
    };
}, 40, 2);

function kr_asset_version(string $relativePath): string
{
    $file = get_stylesheet_directory() . '/' . ltrim($relativePath, '/');
    return is_file($file) ? (string) filemtime($file) : (string) wp_get_theme()->get('Version');
}

function kr_compact_shortcode_markup(string $markup): string
{
    return (string) preg_replace('/>\s+</', '><', trim($markup));
}

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'kniferevive-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        (string) wp_get_theme(get_template())->get('Version')
    );
    wp_enqueue_style('kniferevive-site', get_stylesheet_directory_uri() . '/assets/css/site.css', [], kr_asset_version('assets/css/site.css'));
    wp_enqueue_style('kniferevive-navigation', get_stylesheet_directory_uri() . '/assets/css/navigation.css', ['kniferevive-site'], kr_asset_version('assets/css/navigation.css'));
    wp_enqueue_style('kniferevive-footer', get_stylesheet_directory_uri() . '/assets/css/footer.css', ['kniferevive-site'], kr_asset_version('assets/css/footer.css'));
    wp_enqueue_style('kniferevive-annex', get_stylesheet_directory_uri() . '/assets/css/annex.css', ['kniferevive-site'], kr_asset_version('assets/css/annex.css'));
    wp_enqueue_style('kniferevive-account-avatar', get_stylesheet_directory_uri() . '/assets/css/account-avatar.css', ['kniferevive-site'], kr_asset_version('assets/css/account-avatar.css'));
    if (is_front_page()) {
        wp_enqueue_style('kniferevive-homepage', get_stylesheet_directory_uri() . '/assets/css/homepage.css', ['kniferevive-site'], kr_asset_version('assets/css/homepage.css'));
    }
    if (is_page('art-trade-gallery')) {
        wp_enqueue_style('kniferevive-art-gallery', get_stylesheet_directory_uri() . '/assets/css/art-gallery.css', ['kniferevive-annex'], kr_asset_version('assets/css/art-gallery.css'));
    }
    if (is_page(['technology-trade-desk', 'home-garden', 'collectibles', 'pokemon-cards'])) {
        wp_enqueue_style('kniferevive-tech-desk', get_stylesheet_directory_uri() . '/assets/css/tech-desk.css', ['kniferevive-annex'], kr_asset_version('assets/css/tech-desk.css'));
        wp_enqueue_script('kniferevive-tech-desk', get_stylesheet_directory_uri() . '/assets/js/tech-desk.js', [], kr_asset_version('assets/js/tech-desk.js'), true);
    }
    if (is_page(['other-finds', 'home-garden', 'collectibles', 'pokemon-cards'])) {
        wp_enqueue_style('kniferevive-other-finds', get_stylesheet_directory_uri() . '/assets/css/other-finds.css', ['kniferevive-annex'], kr_asset_version('assets/css/other-finds.css'));
    }
    if (is_page('categories')) {
        wp_enqueue_style('kniferevive-categories', get_stylesheet_directory_uri() . '/assets/css/categories.css', ['kniferevive-site'], kr_asset_version('assets/css/categories.css'));
    }

    wp_enqueue_style('kniferevive-knife-card', get_stylesheet_directory_uri() . '/assets/css/knife-card.css', ['kniferevive-site'], kr_asset_version('assets/css/knife-card.css'));
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style('kniferevive-product', get_stylesheet_directory_uri() . '/assets/css/product.css', ['kniferevive-site'], kr_asset_version('assets/css/product.css'));
    }
    if (is_page('return-policy')) {
        wp_enqueue_style('kniferevive-return-policy', get_stylesheet_directory_uri() . '/assets/css/return-policy.css', ['kniferevive-site'], kr_asset_version('assets/css/return-policy.css'));
    }
    $navigationDependencies = wp_script_is('wc-cart-fragments', 'registered') ? ['wc-cart-fragments'] : [];
    wp_enqueue_script('kniferevive-navigation', get_stylesheet_directory_uri() . '/assets/js/navigation.js', $navigationDependencies, kr_asset_version('assets/js/navigation.js'), true);
});

add_filter('body_class', static function (array $classes): array {
    $classes[] = 'kr-site';
    return $classes;
});

/**
 * Repair legacy return-tag labels at render time.
 *
 * The imported database uses a latin1 collation, which cannot safely store the
 * return emoji. Keep taxonomy slugs as the source of truth and normalize only
 * the two established return terms without changing unrelated taxonomy data.
 */
function kr_normalize_return_term_label($term)
{
    if (!$term instanceof WP_Term || $term->taxonomy !== 'product_tag') {
        return $term;
    }

    $labels = [
        'free_returns' => '🔄 Free',
        '5-returns' => '🔄 $5',
    ];

    if (isset($labels[$term->slug])) {
        $term->name = $labels[$term->slug];
    }

    return $term;
}

add_filter('get_term', static function ($term) {
    return kr_normalize_return_term_label($term);
});

add_filter('get_terms', static function (array $terms): array {
    foreach ($terms as $term) {
        kr_normalize_return_term_label($term);
    }
    return $terms;
});

add_filter('render_block_core/template-part', static function (string $content, array $block): string {
    $slug = (string) ($block['attrs']['slug'] ?? '');
    if ($slug === 'header') {
        return do_shortcode('[kniferevive_header]');
    }
    if ($slug === 'footer') {
        return do_shortcode('[kniferevive_footer]');
    }
    return $content;
}, 20, 2);

/**
 * Convert the saved WordPress Navigation block structure into a reusable tree.
 * Navigation ID 4 remains the source of truth.
 *
 * @return array<int, array<string, mixed>>
 */
function kr_navigation_tree(int $postId = 4): array
{
    static $cache = [];
    if (isset($cache[$postId])) {
        return $cache[$postId];
    }

    $post = get_post($postId);
    if (!$post instanceof WP_Post) {
        $cache[$postId] = $postId === 4 && function_exists('kr_metacategory_navigation_tree') ? kr_metacategory_navigation_tree([]) : [];
        return $cache[$postId];
    }

    $walk = static function (array $blocks) use (&$walk): array {
        $items = [];
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';
            $attrs = $block['attrs'] ?? [];
            if ($name === 'core/navigation-link' || $name === 'core/navigation-submenu') {
                $items[] = [
                    'label' => wp_strip_all_tags((string) ($attrs['label'] ?? '')),
                    'url' => (string) ($attrs['url'] ?? ''),
                    'target' => !empty($attrs['opensInNewTab']) ? '_blank' : '',
                    'rel' => (string) ($attrs['rel'] ?? ''),
                    'children' => $walk($block['innerBlocks'] ?? []),
                ];
                continue;
            }
            if (!empty($block['innerBlocks'])) {
                $items = array_merge($items, $walk($block['innerBlocks']));
            }
        }
        return $items;
    };

    $cache[$postId] = $walk(parse_blocks($post->post_content));
    if ($postId === 4 && function_exists('kr_replace_brand_navigation_branch')) {
        $cache[$postId] = kr_replace_brand_navigation_branch($cache[$postId]);
    }
    // The model-pages plugin rebuilds this same source tree for its brand branch.
    if ($postId === 4 && function_exists('kr_metacategory_navigation_tree')) {
        $cache[$postId] = kr_metacategory_navigation_tree($cache[$postId]);
    }
    return $cache[$postId];
}

/**
 * Find the first saved navigation item matching one of the requested labels.
 *
 * @param array<int, array<string, mixed>> $items
 * @param array<int, string>               $labels
 * @return array<string, mixed>|null
 */
function kr_find_navigation_item(array $items, array $labels): ?array
{
    $wanted = array_map(static fn(string $label): string => strtolower(trim($label)), $labels);
    foreach ($items as $item) {
        if (in_array(strtolower(trim((string) $item['label'])), $wanted, true)) {
            return $item;
        }
        if (!empty($item['children'])) {
            $found = kr_find_navigation_item($item['children'], $labels);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

function kr_navigation_link(array $item, string $class = ''): string
{
    $label = esc_html((string) ($item['label'] ?? ''));
    $url = trim((string) ($item['url'] ?? ''));
    if ($url === '' || $url === '#') {
        return '<span class="' . esc_attr($class) . '">' . $label . '</span>';
    }

    $target = !empty($item['target']) ? ' target="_blank"' : '';
    $rel = trim((string) ($item['rel'] ?? ''));
    if ($target !== '') {
        $rel = trim($rel . ' noopener');
    }
    $relAttribute = $rel !== '' ? ' rel="' . esc_attr($rel) . '"' : '';
    $expand = !empty($item['browse_children']) && !empty($item['children']) && $class === 'kr-menu-link' ? ' data-kr-expand-submenu' : '';
    return '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '"' . $target . $relAttribute . $expand . '>' . $label . '</a>';
}

/** Prices of visible listings for the drawer's current catalog context. */
function kr_navigation_prices(): array
{
    static $prices = null;
    if ($prices !== null) { return $prices; }
    $prices = [];
    if (!function_exists('wc_get_product')) { return $prices; }

    $search = is_search() && get_query_var('post_type') === 'product'
        ? trim(get_search_query(false)) : '';
    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ];
    if ($search !== '') {
        $args['s'] = $search;
        $args['krev_catalog_search'] = true;
    }
    $query = new WP_Query($args);
    foreach ($query->posts as $id) {
        if (get_post_meta((int) $id, '_krev_listlab_archived', true) === 'yes') { continue; }
        if ($search === '' && function_exists('kniferevive_is_knife_product')
            && !kniferevive_is_knife_product((int) $id)) { continue; }
        $product = wc_get_product((int) $id);
        if (!$product || !$product->is_visible()) { continue; }
        $price = $product->get_price();
        if ($price !== '' && is_numeric($price)) { $prices[] = (float) $price; }
    }
    sort($prices, SORT_NUMERIC);
    return $prices;
}

/** Double-ended price filter shown in the catalog drawer on every page. */
function kr_navigation_price_filter(): string
{
    $prices = kr_navigation_prices();
    if (!$prices) {
        return '<p class="kr-price-filter__empty">No priced listings available. <a href="' . esc_url(home_url('/shop/')) . '">Browse the catalog</a>.</p>';
    }

    $floor = max(0, (int) floor($prices[0]));
    $ceiling = max($floor + 1, (int) ceil(end($prices)));
    $rawMin = isset($_GET['min_price']) && is_scalar($_GET['min_price']) ? wp_unslash($_GET['min_price']) : null;
    $rawMax = isset($_GET['max_price']) && is_scalar($_GET['max_price']) ? wp_unslash($_GET['max_price']) : null;
    $min = $rawMin !== null && is_numeric($rawMin) ? (int) $rawMin : $floor;
    $max = $rawMax !== null && is_numeric($rawMax) ? (int) $rawMax : $ceiling;
    $min = max($floor, min($ceiling, $min));
    $max = max($min, min($ceiling, $max));
    $count = count(array_filter($prices, static fn(float $price): bool => $price >= $min && $price <= $max));
    $search = is_search() && get_query_var('post_type') === 'product'
        ? trim(get_search_query(false)) : '';
    $action = $search !== '' ? home_url('/') : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/'));

    $output = '<form class="kr-price-filter" data-kr-price-filter data-prices="' . esc_attr(wp_json_encode($prices)) . '" method="get" action="' . esc_url($action) . '">';
    $output .= '<div class="kr-price-filter__intro"><strong>Choose your price</strong><span data-kr-price-count aria-live="polite">' . esc_html(sprintf(_n('%d listing', '%d listings', $count, 'kniferevive'), $count)) . '</span></div>';
    $output .= '<div class="kr-price-filter__range" style="--kr-price-low:' . esc_attr((string) (100 * ($min - $floor) / ($ceiling - $floor))) . '%;--kr-price-high:' . esc_attr((string) (100 * ($max - $floor) / ($ceiling - $floor))) . '%">';
    $output .= '<div class="kr-price-filter__track" aria-hidden="true"></div>';
    $output .= '<input type="range" name="min_price" min="' . esc_attr((string) $floor) . '" max="' . esc_attr((string) $ceiling) . '" value="' . esc_attr((string) $min) . '" aria-label="Minimum price" data-kr-price-min-range>';
    $output .= '<input type="range" name="max_price" min="' . esc_attr((string) $floor) . '" max="' . esc_attr((string) $ceiling) . '" value="' . esc_attr((string) $max) . '" aria-label="Maximum price" data-kr-price-max-range></div>';
    $output .= '<div class="kr-price-filter__fields"><label>Min <span>$<input type="number" min="' . esc_attr((string) $floor) . '" max="' . esc_attr((string) $ceiling) . '" value="' . esc_attr((string) $min) . '" inputmode="numeric" aria-label="Minimum price amount" data-kr-price-min-number></span></label>';
    $output .= '<label>Max <span>$<input type="number" min="' . esc_attr((string) $floor) . '" max="' . esc_attr((string) $ceiling) . '" value="' . esc_attr((string) $max) . '" inputmode="numeric" aria-label="Maximum price amount" data-kr-price-max-number></span></label></div>';
    if ($search !== '') {
        $output .= '<input type="hidden" name="s" value="' . esc_attr($search) . '"><input type="hidden" name="post_type" value="product">';
    }
    $output .= '<button type="submit" class="kr-price-filter__apply"' . ($count ? '' : ' disabled') . ' data-kr-price-apply>' . esc_html(sprintf(_n('Show %d listing', 'Show %d listings', $count, 'kniferevive'), $count)) . '</button></form>';
    return $output;
}

/** Give shoppers a useful next step when a product collection is empty. */
add_filter('render_block_woocommerce/product-collection-no-results', static function (string $content): string {
    if ($content === '' || is_admin()) { return $content; }

    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $reset = $shop;
    $filtered = isset($_GET['min_price'], $_GET['max_price']);
    if ($filtered && function_exists('is_product_taxonomy') && is_product_taxonomy()) {
        $termUrl = get_term_link(get_queried_object());
        if (!is_wp_error($termUrl)) { $reset = $termUrl; }
    } elseif ($filtered && is_search() && get_query_var('post_type') === 'product') {
        $reset = add_query_arg(['s' => get_search_query(false), 'post_type' => 'product'], home_url('/'));
    }

    $headline = $filtered ? 'No listings in this price range' : 'No listings found';
    $message = $filtered
        ? 'Try a wider price range, or search for a brand, model, or item.'
        : 'Try a different search term or browse the full catalog.';
    $search = is_search() ? get_search_query(false) : '';

    return '<section class="kr-catalog-empty" aria-labelledby="kr-catalog-empty-title">'
        . '<span class="kr-catalog-empty__eyebrow">Keep exploring</span>'
        . '<h2 id="kr-catalog-empty-title">' . esc_html($headline) . '</h2>'
        . '<p>' . esc_html($message) . '</p>'
        . '<form role="search" method="get" action="' . esc_url(home_url('/')) . '">'
        . '<label for="kr-catalog-empty-search">Search the catalog</label>'
        . '<div class="kr-catalog-empty__search"><input id="kr-catalog-empty-search" type="search" name="s" value="' . esc_attr($search) . '" placeholder="Brand, model, or item" required>'
        . '<input type="hidden" name="post_type" value="product"><button type="submit">Search</button></div></form>'
        . '<nav aria-label="Continue shopping"><a class="kr-catalog-empty__primary" href="' . esc_url($reset) . '">' . esc_html($filtered ? 'Clear price filter' : 'Browse all listings') . '</a>'
        . ($reset !== $shop ? '<a href="' . esc_url($shop) . '">Browse all listings</a>' : '')
        . '</nav></section>';
}, 20);

/**
 * Render every saved Navigation ID 4 item while keeping submenus collapsed.
 *
 * @param array<int, array<string, mixed>> $items
 */
function kr_render_navigation_tree(array $items, int $depth = 0, string $idPrefix = 'kr-menu'): string
{
    static $sequence = 0;
    $class = $depth === 0 ? 'kr-drawer-menu' : 'kr-submenu';
    $output = '<ul class="' . esc_attr($class) . '" data-kr-menu-depth="' . esc_attr((string) $depth) . '">';

    foreach ($items as $item) {
        $sequence++;
        $children = (array) ($item['children'] ?? []);
        $hasChildren = count($children) > 0;
        $submenuId = $idPrefix . '-submenu-' . $sequence;
        $label = (string) ($item['label'] ?? '');
        $slug = sanitize_title($label);
        $output .= '<li class="kr-menu-item' . ($hasChildren ? ' kr-menu-item--parent' : '') . '" data-kr-menu-label="' . esc_attr($slug) . '">';
        $output .= '<div class="kr-menu-row">';
        $output .= kr_navigation_link($item, 'kr-menu-link');
        if ($hasChildren) {
            $output .= '<button class="kr-submenu-toggle" type="button" aria-expanded="false" aria-controls="' . esc_attr($submenuId) . '" aria-label="' . esc_attr(sprintf(__('Open %s menu', 'kniferevive'), $label)) . '"><span aria-hidden="true">›</span></button>';
        }
        $output .= '</div>';
        if ($hasChildren) {
            $output .= '<div class="kr-submenu-panel" id="' . esc_attr($submenuId) . '" hidden>';
            $url = trim((string) ($item['url'] ?? ''));
            if ($url !== '' && $url !== '#') {
                $viewAll = $item;
                $viewAll['label'] = sprintf(__('View all %s', 'kniferevive'), $label);
                $output .= kr_navigation_link($viewAll, 'kr-submenu-view-all');
            }
            $output .= $slug === 'filter-price'
                ? kr_navigation_price_filter()
                : kr_render_navigation_tree($children, $depth + 1, $idPrefix);
            $output .= '</div>';
        }
        $output .= '</li>';
    }

    return $output . '</ul>';
}

function kr_product_search_form(string $class = ''): string
{
    return '<form class="kr-search ' . esc_attr($class) . '" role="search" method="get" action="' . esc_url(home_url('/')) . '">
        <label class="screen-reader-text" for="kr-product-search-' . esc_attr(sanitize_html_class($class ?: 'default')) . '">' . esc_html__('Search KnifeRevive products', 'kniferevive') . '</label>
        <input id="kr-product-search-' . esc_attr(sanitize_html_class($class ?: 'default')) . '" type="search" name="s" value="' . esc_attr(get_search_query()) . '" placeholder="Search products, brands, models, artists, specs…" autocomplete="off">
        <input type="hidden" name="post_type" value="product">
        <button type="submit" aria-label="' . esc_attr__('Submit product search', 'kniferevive') . '">Search</button>
    </form>';
}

function kr_brand_markup(bool $compact = false): string
{
    $logoId = (int) get_theme_mod('custom_logo');
    $logo = $logoId ? wp_get_attachment_image($logoId, 'thumbnail', false, ['class' => 'kr-brand-logo', 'loading' => 'eager']) : '';
    return '<a class="kr-brand' . ($compact ? ' kr-brand--compact' : '') . '" href="' . esc_url(home_url('/')) . '" aria-label="' . esc_attr__('KnifeRevive home', 'kniferevive') . '">' . $logo . '<span>KnifeRevive</span></a>';
}

function kr_icon(string $name): string
{
    if ($name === 'menu') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
    }
    if ($name === 'cart') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H7M10 20h.01M17 20h.01"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg>';
}

function kr_cart_link_markup(): string
{
    $cartUrl = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $cartCount = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
    $cartLabel = sprintf(
        _n('Cart, %d item', 'Cart, %d items', $cartCount, 'kniferevive'),
        $cartCount
    );
    return '<a class="kr-cart-link" href="' . esc_url($cartUrl) . '" aria-label="' . esc_attr($cartLabel) . '">' . kr_icon('cart') . '<span>Cart</span><span class="kr-cart-count">' . esc_html((string) $cartCount) . '</span></a>';
}

add_filter('woocommerce_add_to_cart_fragments', static function (array $fragments): array {
    $fragments['a.kr-cart-link'] = kr_cart_link_markup();
    return $fragments;
});

function kr_render_quick_link(?array $item, string $class = ''): string
{
    return $item === null ? '' : kr_navigation_link($item, $class);
}

function kr_header_shortcode(): string
{
    $tree = kr_navigation_tree(4);
    $sharpening = kr_find_navigation_item($tree, ['Sharpening']);
    $sharpeningAnchorUrl = home_url('/#knife-sharpening');
    if ($sharpening === null) {
        $sharpening = [
            'label' => 'Sharpening',
            'url' => $sharpeningAnchorUrl,
            'target' => '',
            'rel' => '',
            'children' => [],
        ];
    } else {
        $sharpening['url'] = $sharpeningAnchorUrl;
        $sharpening['target'] = '';
        $sharpening['rel'] = '';
    }
    $shop = kr_find_navigation_item($tree, ['Shop All']);
    $deals = kr_find_navigation_item($tree, ['Shop Deals']);
    $models = kr_find_navigation_item($tree, ['Models']);
    $accountUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    $sellUrl = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('listlab') : home_url('/my-account/listlab/');

    ob_start();
    ?>
    <div class="kr-header" data-kr-header>
        <div class="kr-header-row kr-container">
            <button class="kr-icon-button kr-menu-button" type="button" data-kr-open-menu aria-label="<?php esc_attr_e('Browse catalog', 'kniferevive'); ?>" aria-expanded="false" aria-controls="kr-catalog-drawer">
                <?php echo kr_icon('menu'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span class="kr-menu-button-label">Browse</span>
            </button>

            <?php echo kr_brand_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <nav class="kr-desktop-shortcuts" aria-label="<?php esc_attr_e('Store shortcuts', 'kniferevive'); ?>">
                <?php echo kr_render_quick_link($sharpening, 'kr-header-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_render_quick_link($shop, 'kr-header-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_render_quick_link($deals, 'kr-header-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_render_quick_link($models, 'kr-header-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_annex_desktop_navigation(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </nav>

            <?php echo kr_product_search_form('kr-search--desktop'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <div class="kr-header-actions">
                <a class="kr-account-link" href="<?php echo esc_url($accountUrl); ?>">Account</a>
                <?php echo kr_cart_link_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <a class="kr-button kr-sell-button" href="<?php echo esc_url($sellUrl); ?>">Sell an Item</a>
            </div>
        </div>

        <div class="kr-mobile-search kr-container">
            <?php echo kr_product_search_form('kr-search--mobile'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>

    <div class="kr-drawer-backdrop" data-kr-drawer-backdrop hidden></div>
    <aside class="kr-drawer" id="kr-catalog-drawer" data-kr-drawer role="dialog" aria-modal="true" aria-labelledby="kr-drawer-title" hidden>
        <div class="kr-drawer-header">
            <div id="kr-drawer-title"><?php echo kr_brand_markup(true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <button class="kr-icon-button kr-close-button" type="button" data-kr-close-menu aria-label="<?php esc_attr_e('Close navigation', 'kniferevive'); ?>">
                <?php echo kr_icon('close'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </button>
        </div>
        <div class="kr-drawer-scroll">
            <?php echo kr_product_search_form('kr-search--drawer'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="kr-drawer-quick-links" aria-label="<?php esc_attr_e('Popular destinations', 'kniferevive'); ?>">
                <?php echo kr_render_quick_link($sharpening, 'kr-drawer-quick-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_render_quick_link($shop, 'kr-drawer-quick-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_render_quick_link($deals, 'kr-drawer-quick-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_render_quick_link($models, 'kr-drawer-quick-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <nav class="kr-drawer-navigation" aria-label="<?php esc_attr_e('KnifeRevive catalog navigation', 'kniferevive'); ?>" data-kr-navigation-source="4">
                <?php echo kr_render_navigation_tree($tree); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </nav>
            <?php echo kr_annex_drawer_navigation(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <a class="kr-button kr-drawer-sell" href="<?php echo esc_url($sellUrl); ?>">Sell an Item</a>
        </div>
    </aside>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_header', 'kr_header_shortcode');

function kr_home_link(array $tree, array $labels, string $fallback): string
{
    $item = kr_find_navigation_item($tree, $labels);
    $url = $item ? trim((string) ($item['url'] ?? '')) : '';
    return ($url !== '' && $url !== '#') ? $url : $fallback;
}

/**
 * Print the preference check before front-page styles render, preventing a
 * saved hidden state from briefly displaying the full tips banner.
 */
add_action('wp_head', static function (): void {
    if (!is_front_page()) {
        return;
    }
    ?>
    <script>(function(){try{if(window.localStorage.getItem('kniferevive_home_tips_hidden')==='1'){document.documentElement.classList.add('krev-home-tips-is-hidden');}}catch(e){}}());</script>
    <?php
}, 1);

/**
 * Render the progressive-enhancement homepage tips banner. The first tip is
 * normal HTML so it remains useful if JavaScript is unavailable.
 */
function kr_home_tips_banner(string $sellUrl, string $modelsUrl, string $tiktokUrl = ''): string
{
    $sharpeningUrl = home_url('/#knife-sharpening');
    $cutcoUrl = home_url('/brand/cutco/');
    $hasTikTokUrl = $tiktokUrl !== ''
        && $tiktokUrl !== '#'
        && (bool) preg_match('#^https?://(?:www\\.)?tiktok\\.com/#i', $tiktokUrl);
    $tips = [
        [
            'label' => 'Knife sharpening',
            'title' => 'A sharper knife in three clear steps.',
            'description' => 'Local professional knife sharpening for home cooks and customers throughout the San Francisco Bay Area.',
            'cta' => 'Sharpen Your Knives →',
            'url' => $sharpeningUrl,
        ],
        [
            'label' => 'KnifeRevive marketplace',
            'title' => "Your next favorite knife may already be in someone else's kitchen.",
            'description' => 'Sell knives you\'re no longer using and discover something different — German, American, Japanese, French and beyond.',
            'cta' => 'Start Selling →',
            'url' => $sellUrl,
        ],
        [
            'label' => 'Bitcoin Lightning',
            'title' => 'Pay with Bitcoin Lightning. Save the marketplace fee.',
            'description' => 'Choose Lightning at checkout and pay no KnifeRevive Marketplace Fee on your purchase.',
            'url' => '',
            'variant' => 'lightning',
        ],
        [
            'label' => 'Knife restoration',
            'title' => 'See worn knives come back to life.',
            'description' => 'Follow KnifeRevive for sharpening, edge work, restoration, and before-and-after knife videos.',
            'cta' => 'Watch KnifeRevive →',
            'url' => $hasTikTokUrl ? $tiktokUrl : '',
        ],
        [
            'label' => 'Knife details',
            'title' => 'We obsess over the details stamped into the blade.',
            'description' => 'Model numbers. Steel. Edge geometry. Country of manufacture. Sharpening angles. The details can tell the story of a knife.',
            'cta' => 'Explore by Model Number →',
            'url' => $modelsUrl,
        ],
        [
            'label' => 'Blade care',
            'title' => "STEEL DOESN'T EXPIRE.",
            'description' => 'A quality kitchen knife can serve for decades — even generations — when its edge is maintained and the blade is properly cared for.',
            'secondary' => 'Good steel was not made to be disposable.',
            'url' => '',
            'variant' => 'steel',
        ],
        [
            'label' => 'American knife history',
            'title' => 'American-made in Olean, New York since 1949.',
            'description' => 'Discover Cutco knives, their distinctive handles and edges, and the manufacturing history behind an American kitchen-cutlery brand.',
            'cta' => 'Explore Cutco Knives →',
            'url' => $cutcoUrl,
        ],
    ];

    ob_start();
    ?>
    <section class="krev-home-tips" data-krev-home-tips aria-label="<?php esc_attr_e('KnifeRevive tips', 'kniferevive'); ?>">
        <div class="krev-home-tips__slides">
            <?php foreach ($tips as $index => $tip) :
                $url = (string) ($tip['url'] ?? '');
                $hasLink = $url !== '';
                $variant = (string) ($tip['variant'] ?? 'default');
                ?>
                <article class="krev-home-tips__slide krev-home-tips__slide--<?php echo esc_attr($variant); ?><?php echo $index === 0 ? ' is-active' : ''; ?>" data-krev-home-tips-slide<?php echo $index === 0 ? '' : ' hidden'; ?>>
                    <div class="krev-home-tips__copy">
                        <?php if ($hasLink) : ?><a class="krev-home-tips__content-link" href="<?php echo esc_url($url); ?>"><?php endif; ?>
                            <span class="krev-home-tips__label"><?php echo esc_html((string) $tip['label']); ?></span>
                            <p class="krev-home-tips__title"><?php echo esc_html((string) $tip['title']); ?></p>
                            <p class="krev-home-tips__description"><?php echo esc_html((string) $tip['description']); ?></p>
                            <?php if (!empty($tip['secondary'])) : ?><p class="krev-home-tips__secondary"><?php echo esc_html((string) $tip['secondary']); ?></p><?php endif; ?>
                        <?php if ($hasLink) : ?></a><?php endif; ?>
                        <?php if ($hasLink && !empty($tip['cta'])) : ?><a class="krev-home-tips__cta" href="<?php echo esc_url($url); ?>"><?php echo esc_html((string) $tip['cta']); ?></a><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="krev-home-tips__toolbar">
            <button class="krev-home-tips__hide" type="button" data-krev-home-tips-hide aria-label="<?php esc_attr_e('Hide KnifeRevive tips', 'kniferevive'); ?>">Tips <span aria-hidden="true">×</span></button>
            <div class="krev-home-tips__controls" aria-label="<?php esc_attr_e('Tips navigation', 'kniferevive'); ?>">
                <button class="krev-home-tips__button" type="button" data-krev-home-tips-prev aria-label="<?php esc_attr_e('Previous tip', 'kniferevive'); ?>"><span aria-hidden="true">‹</span></button>
                <span class="krev-home-tips__count" data-krev-home-tips-count aria-live="off">1 / <?php echo esc_html((string) count($tips)); ?></span>
                <button class="krev-home-tips__button" type="button" data-krev-home-tips-next aria-label="<?php esc_attr_e('Next tip', 'kniferevive'); ?>"><span aria-hidden="true">›</span></button>
            </div>
        </div>
    </section>
    <div class="krev-home-tips__restore-wrap"><button class="krev-home-tips__restore" type="button" data-krev-home-tips-restore aria-label="<?php esc_attr_e('Show KnifeRevive tips', 'kniferevive'); ?>" hidden>Tips</button></div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Resolve a Media Library image by its uploaded filename instead of a
 * database-specific attachment ID.
 */
function kr_attachment_id_by_filename(string $filename): int
{
    static $cache = [];
    $filename = sanitize_file_name($filename);
    if ($filename === '') {
        return 0;
    }
    if (isset($cache[$filename])) {
        return $cache[$filename];
    }

    $attachmentIds = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            [
                'key' => '_wp_attached_file',
                'value' => $filename,
                'compare' => 'LIKE',
            ],
        ],
    ]);

    $cache[$filename] = isset($attachmentIds[0]) ? (int) $attachmentIds[0] : 0;
    return $cache[$filename];
}

function kr_homepage_shortcode(): string
{
    $tree = kr_navigation_tree(4);
    $serviceUrl = kr_home_link($tree, ['Sharpening'], home_url('/product-category/knife-sharpening/'));
    $shopUrl = kr_home_link($tree, ['Shop All'], home_url('/shop/'));
    $dealsUrl = kr_home_link($tree, ['Shop Deals'], home_url('/product-tag/deals/'));
    $modelsUrl = kr_home_link($tree, ['Models'], home_url('/model-directory/'));
    $typeLabels = ["Chef's Knife", 'Paring Knife', 'Santoku Knife', 'Bread Knife', 'Serrated Utility Knife', 'Slicing/Carving Knife', 'Electric Knife'];
    $brandRoots = function_exists('kr_native_brand_roots') ? kr_native_brand_roots() : [];
    $tiktokItem = kr_find_navigation_item(kr_navigation_tree(1232), ['TikTok']);
    $tiktokUrl = $tiktokItem ? trim((string) ($tiktokItem['url'] ?? '')) : '';
    $mapsUrl = 'https://maps.app.goo.gl/xxbDKNdBWHUCY9be9';
    $sellUrl = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('listlab') : home_url('/my-account/listlab/');
    $accountUrl = kr_frontend_my_account_url();
    $sharpeningStepImageBaseUrl = trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/sharpening-steps/';
    $sharpeningSteps = [
        [
            'number' => '01',
            'title' => 'Pick your service',
            'description' => 'Small knives (3–5″) or large knives (6″+). Add any notes.',
            'image' => 'Pick your service.png',
        ],
        [
            'number' => '02',
            'title' => 'Book & choose handoff',
            'description' => 'Prepay online and pick one: we swing by your place, or you drop at our spot.',
            'image' => 'Book & choose handoff.png',
        ],
        [
            'number' => '03',
            'title' => 'Blade check',
            'description' => 'We service smooth and serrated blades. Micro-serrated coming soon.',
            'image' => 'Blade check.png',
        ],
        [
            'number' => '04',
            'title' => '4–6 Days + free loaner',
            'description' => 'Orders are ready in 4–6 days. Get a free loaner while you wait (limit 1 chef’s + 1 paring per customer per service).',
            'image' => '4–6 Days + free loaner.png',
        ],
        [
            'number' => '05',
            'title' => 'Sharpen & shape',
            'description' => 'Precision belt sharpening tailored to your steel and edge profile.',
            'image' => 'Sharpen & shape.png',
        ],
        [
            'number' => '06',
            'title' => 'Hone & test',
            'description' => 'Refined, honed, and sharpness-tested for clean, safe cuts.',
            'image' => 'Hone & test.png',
        ],
        [
            'number' => '07',
            'title' => 'Pay',
            'description' => 'Prepaid online or pay at our POS (cash, card, or mobile).',
            'image' => 'Pay.png',
        ],
        [
            'number' => '08',
            'title' => 'Get them back',
            'description' => 'We return to you or you pick up.',
            'image' => 'Get them back.png',
        ],
    ];
    $sharpeningImage = wp_get_attachment_image(
        kr_attachment_id_by_filename('IMG_0758-rotated.jpg'),
        'large',
        false,
        [
            'class' => 'kr-belt-feature-image',
            'alt' => __('Close view of a professionally sharpened KnifeRevive chef knife edge', 'kniferevive'),
            'loading' => 'lazy',
            'decoding' => 'async',
        ]
    );

    ob_start();
    ?>
    <div class="kr-home">
        <section class="kr-hero">
            <div class="kr-container kr-hero-inner">
                <p class="kr-eyebrow">Marketplace for Kitchen Knives, Technology, and World Finds</p>
                <h1>Sharpen better. Buy smarter.</h1>
                <p class="kr-hero-copy">Professional knife sharpening and quality pre-owned chef's knives, brought together in one practical marketplace.</p>
                <div class="kr-actions">
                    <a class="kr-button" href="#knife-sharpening">Knife Sharpening</a>
                    <a class="kr-button kr-button--secondary" href="<?php echo esc_url($shopUrl); ?>">Shop Pre-Owned Knives</a>
                </div>
                <?php if (class_exists('KREV_Commissions_Promotion') && KREV_Commissions_Settings::available()) : ?>
                    <aside class="kr-seller-offer" aria-label="New marketplace seller offer">
                        <div class="kr-seller-offer__copy">
                            <span class="kr-seller-offer__tag">NEW MARKETPLACE SELLERS</span>
                            <h2><strong>0% Commission</strong> for 6 months</h2>
                            <p>Create a new seller account and pay 0% marketplace commission for six months from signup.</p>
                        </div>
                        <a class="kr-button" href="<?php echo esc_url($sellUrl); ?>">Sell</a>
                    </aside>
                <?php endif; ?>
                <?php
                echo function_exists('krev_home_tips_render')
                    ? krev_home_tips_render() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    : kr_bitcoin_lightning_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>
        </section>

        <section class="kr-value-strip" aria-label="<?php esc_attr_e('KnifeRevive services', 'kniferevive'); ?>">
            <div class="kr-container kr-value-grid">
                <span>Professional sharpening</span>
                <span>Pre-owned chef's cutlery</span>
                <span>Browse by model number</span>
            </div>
        </section>

        <section class="kr-section kr-section--products" id="deals">
            <div class="kr-container">
                <div class="kr-section-heading"><div><p class="kr-eyebrow">Smart finds</p><h2>Deals &amp; Steals</h2></div><a href="<?php echo esc_url($dealsUrl); ?>">View all deals</a></div>
                <div class="kr-products"><?php echo kr_render_knife_products_shortcode('[products tag="deals" limit="4" columns="4" orderby="date" order="DESC"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            </div>
        </section>

        <section class="kr-section kr-section--surface">
            <div class="kr-container">
                <div class="kr-section-heading"><div><p class="kr-eyebrow">Find your blade</p><h2>Shop by knife type</h2></div><button type="button" class="kr-text-button" data-kr-open-menu data-kr-open-section="knife-type" aria-expanded="false" aria-controls="kr-catalog-drawer">View all knife types</button></div>
                <div class="kr-link-grid kr-link-grid--types">
                    <?php foreach ($typeLabels as $label) : $item = kr_find_navigation_item($tree, [$label]); ?>
                        <?php if ($item) : ?><a href="<?php echo esc_url((string) $item['url']); ?>"><span><?php echo esc_html((string) $item['label']); ?></span><span aria-hidden="true">→</span></a><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="kr-section kr-section--products">
            <div class="kr-container">
                <div class="kr-section-heading"><div><p class="kr-eyebrow">Fresh to the marketplace</p><h2>Featured &amp; recently added</h2></div><a href="<?php echo esc_url($shopUrl); ?>">Shop all knives</a></div>
                <div class="kr-products"><?php echo kr_render_knife_products_shortcode('[products limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            </div>
        </section>

        <?php echo kr_render_annex_cards(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <section class="kr-section kr-brands">
            <div class="kr-container">
                <div class="kr-section-heading"><div><p class="kr-eyebrow">Trusted makers</p><h2>Shop by brand</h2></div><button type="button" class="kr-text-button" data-kr-open-menu data-kr-open-section="brand" aria-expanded="false" aria-controls="kr-catalog-drawer">View all brands</button></div>
                <div class="kr-brand-grid">
                    <?php foreach ($brandRoots as $item) : ?>
                        <a href="<?php echo esc_url((string) $item['url']); ?>"><?php echo esc_html((string) $item['label']); ?><span aria-hidden="true">↗</span></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="kr-section kr-model-module">
            <div class="kr-container kr-model-inner">
                <div><p class="kr-eyebrow">Model number discovery</p><h2>Find the exact knife.</h2><p>Replacing one knife from a set? Search the catalog or browse the model directory.</p></div>
                <div class="kr-model-actions">
                    <?php echo kr_product_search_form('kr-search--model'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <a class="kr-button kr-button--secondary" href="<?php echo esc_url($modelsUrl); ?>">Browse Model Directory</a>
                </div>
            </div>
        </section>

        <section class="kr-section kr-seller">
            <div class="kr-container kr-split">
                <div><p class="kr-eyebrow">Give good knives another life</p><h2>Sell your knives.</h2><p>List individual knives or fill gaps for cooks searching by brand, type, and model.</p></div>
                <div class="kr-actions">
                    <a class="kr-button" href="<?php echo esc_url($sellUrl); ?>">Sell an Item</a>
                    <a class="kr-button kr-button--secondary" href="<?php echo esc_url(home_url('/listing-guide/')); ?>">Listing Guide</a>
                    <a class="kr-inline-link" href="<?php echo esc_url($accountUrl); ?>">Go to My Account</a>
                </div>
            </div>
        </section>

        <section class="kr-section kr-sharpening" id="knife-sharpening">
            <div class="kr-container">
                <div class="kr-sharpening-intro">
                    <p class="kr-eyebrow">Professional sharpening · SF Bay Area</p>
                    <h2>A sharper knife in three clear steps.</h2>
                    <p class="kr-sharpening-lead">Local professional knife sharpening for customers across the San Francisco Bay Area.</p>
                    <div class="kr-actions kr-sharpening-contact-actions">
                        <a class="kr-button" href="tel:+14154832814">Call 415-483-2814</a>
                        <a class="kr-button kr-button--secondary" href="<?php echo esc_url($mapsUrl); ?>" target="_blank" rel="noopener noreferrer">View Bay Area Location</a>
                    </div>
                </div>

                <div class="kr-service-heading">
                    <div>
                        <p class="kr-eyebrow">Choose a service</p>
                        <h3>Start with the knives in your kitchen.</h3>
                    </div>
                    <a href="<?php echo esc_url($serviceUrl); ?>">View sharpening services</a>
                </div>
                <div class="kr-products kr-products--services"><?php echo do_shortcode('[products category="knife-sharpening" limit="2" columns="2" orderby="menu_order" order="ASC"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <div class="kr-sharpening-learn">
                    <a class="kr-button kr-button--secondary" href="#how-sharpening-works">Learn more about our knife sharpening</a>
                </div>
            </div>
        </section>

        <section class="kr-section kr-belt-feature">
            <div class="kr-container kr-belt-feature-grid">
                <figure class="kr-belt-feature-media">
                    <?php if ($sharpeningImage !== '') : ?>
                        <?php echo $sharpeningImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <figcaption>A finished KnifeRevive edge after sharpening and honing.</figcaption>
                    <?php else : ?>
                        <div class="kr-belt-feature-placeholder" role="img" aria-label="Professional KnifeRevive sharpening image placeholder"></div>
                    <?php endif; ?>
                </figure>
                <div class="kr-belt-feature-copy">
                    <p class="kr-eyebrow">Professional knife sharpening</p>
                    <h2>Precision sharpening for the knives you actually use.</h2>
                    <p>We use precision belt sharpening tailored to the steel, blade geometry, and existing edge profile, followed by honing and a final sharpness check.</p>
                    <p>KnifeRevive sharpening is available locally in the San Francisco Bay Area.</p>
                    <div class="kr-actions kr-belt-feature-actions">
                        <?php if ($tiktokUrl !== '' && $tiktokUrl !== '#') : ?>
                            <a class="kr-button" href="<?php echo esc_url($tiktokUrl); ?>" target="_blank" rel="noopener noreferrer">Watch Sharpening Videos on TikTok</a>
                        <?php endif; ?>
                        <a class="kr-button kr-button--secondary" href="<?php echo esc_url($mapsUrl); ?>" target="_blank" rel="noopener noreferrer">View Bay Area Location</a>
                        <a class="kr-button kr-button--secondary" href="tel:+14154832814">Call 415-483-2814</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="kr-section kr-how-sharpening" id="how-sharpening-works">
            <div class="kr-container">
                <div class="kr-how-sharpening-heading">
                    <p class="kr-eyebrow">Knife sharpening</p>
                    <h2>HOW SHARPENING WORKS</h2>
                </div>
                <ol class="kr-how-grid">
                    <?php foreach ($sharpeningSteps as $step) : ?>
                        <li>
                            <img
                                class="kr-how-step-image"
                                src="<?php echo esc_url($sharpeningStepImageBaseUrl . rawurlencode($step['image'])); ?>"
                                alt="<?php echo esc_attr(sprintf(__('%s illustration', 'kniferevive'), $step['title'])); ?>"
                                width="1024"
                                height="1024"
                                loading="lazy"
                                decoding="async"
                            >
                            <div class="kr-how-step-copy">
                                <span><?php echo esc_html($step['number']); ?></span>
                                <h3><?php echo esc_html($step['title']); ?></h3>
                                <p><?php echo esc_html($step['description']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <div class="kr-bay-area-closing">
                    <p>KNIFE SHARPENING — FAST PICKUP &amp; EASY DELIVERY ACROSS THE SAN FRANCISCO BAY AREA</p>
                    <div class="kr-actions kr-bay-area-actions">
                        <a class="kr-button kr-button--light" href="<?php echo esc_url($serviceUrl); ?>">Choose a Service</a>
                        <a class="kr-button kr-button--outline-light" href="tel:+14154832814">Call 415-483-2814</a>
                        <a class="kr-button kr-button--outline-light" href="<?php echo esc_url($mapsUrl); ?>" target="_blank" rel="noopener noreferrer">View Bay Area Location</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="kr-section kr-section--surface kr-trust">
            <div class="kr-container">
                <div class="kr-section-heading"><div><p class="kr-eyebrow">A practical cutlery marketplace</p><h2>Buy with better context.</h2></div></div>
                <div class="kr-card-grid">
                    <article><h3>Why buy used?</h3><p>Find useful chef's cutlery while extending the working life of quality knives.</p><a href="<?php echo esc_url(home_url('/about/')); ?>">About KnifeRevive</a></article>
                    <article><h3>Find a matching model</h3><p>Use model-number discovery when you need to replace one knife from an existing set.</p><a href="<?php echo esc_url($modelsUrl); ?>">Model Directory</a></article>
                    <article><h3>Understand the listing</h3><p>Review the marketplace's listing guidance before you buy or sell.</p><a href="<?php echo esc_url(home_url('/listing-guide/')); ?>">Listing Guide</a></article>
                </div>
            </div>
        </section>

        <section class="kr-section kr-learning">
            <div class="kr-container kr-split">
                <div><p class="kr-eyebrow">Keep learning</p><h2>Know your cutlery.</h2><p>Explore makers, knife types, model numbers, and care information as you build or restore a working set.</p></div>
                <div class="kr-actions">
                    <a class="kr-button" href="<?php echo esc_url($modelsUrl); ?>">Explore Models</a>
                    <button type="button" class="kr-button kr-button--secondary" data-kr-open-menu data-kr-open-section="brand" aria-expanded="false" aria-controls="kr-catalog-drawer">Browse Brands</button>
                </div>
            </div>
        </section>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_homepage', 'kr_homepage_shortcode');

function kr_footer_group(int $navigationId, string $heading, string $supplementaryContent = ''): string
{
    $navigationBlock = sprintf('<!-- wp:navigation {"ref":%d,"overlayMenu":"never"} /-->', $navigationId);
    return '<details class="kr-footer-group" open><summary>' . esc_html($heading) . '<span aria-hidden="true">+</span></summary>' . do_blocks($navigationBlock) . $supplementaryContent . '</details>';
}

function kr_footer_shortcode(): string
{
    $logoId = (int) get_theme_mod('custom_logo');
    $logo = $logoId ? wp_get_attachment_image($logoId, 'thumbnail', false, ['class' => 'kr-footer-logo', 'loading' => 'lazy']) : '<span class="kr-footer-logo-fallback">KR</span>';
    ob_start();
    ?>
    <div class="kr-footer">
        <section class="kr-footer-newsletter">
            <div class="kr-container kr-footer-newsletter-inner">
                <div><p class="kr-eyebrow">KnifeRevive updates</p><h2>Stay close to new finds.</h2><p>Manage product and marketplace communications in one place.</p></div>
                <a class="kr-button kr-button--light" href="<?php echo esc_url(home_url('/communication-preferences/')); ?>">Communication preferences</a>
            </div>
        </section>
        <div class="kr-container kr-footer-grid">
            <div class="kr-footer-brand">
                <a class="kr-footer-logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('KnifeRevive home', 'kniferevive'); ?>"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                <a class="kr-footer-title" href="<?php echo esc_url(rtrim(home_url('/'), '/')); ?>">KnifeRevive</a>
                <p>Professional sharpening and quality pre-owned chef's cutlery.</p>
            </div>
            <div class="kr-footer-links">
                <?php echo kr_footer_group(1547, 'About', '<a class="kr-footer-link kr-footer-noverel" href="https://noverel.net/">Produced by Noverel &middot; ' . esc_html(wp_date('Y')) . '</a>'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_footer_group(1546, 'Support & Legal'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_footer_group(1232, 'Social'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo kr_annex_footer_group(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <div class="kr-container kr-footer-legal">
            <span>&copy; <?php echo esc_html(wp_date('Y')); ?> KnifeRevive</span>
            <span>Designed with <a href="https://wordpress.org" rel="nofollow">WordPress</a></span>
        </div>
    </div>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_footer', 'kr_footer_shortcode');

/**
 * Present Dokan's marketplace role with KnifeRevive's public term, Seller.
 *
 * The replacement occurs after a plugin supplies its translatable UI string;
 * it does not alter Dokan's hook names, URLs, roles, or stored data.
 */
function kr_vendor_label_to_seller(string $translation): string
{
    if (stripos($translation, 'vendor') === false) {
        return $translation;
    }

    return strtr($translation, [
        'VENDORS' => 'SELLERS',
        'VENDOR'  => 'SELLER',
        'Vendors' => 'Sellers',
        'Vendor'  => 'Seller',
        'vendors' => 'sellers',
        'vendor'  => 'seller',
    ]);
}

add_filter('gettext', static function (string $translation, string $text, string $domain): string {
    return kr_vendor_label_to_seller($translation);
}, 20, 3);

add_filter('ngettext', static function (string $translation, string $single, string $plural, int $number, string $domain): string {
    return kr_vendor_label_to_seller($translation);
}, 20, 5);
