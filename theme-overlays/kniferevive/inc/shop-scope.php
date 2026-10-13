<?php
/** Shop All is the default; knife-only is an explicit, shareable catalog filter. */
defined('ABSPATH') || exit;

add_filter('query_vars', static function (array $vars): array {
    $vars[] = 'kr_knives_only';
    return $vars;
});

function kr_shop_scope_visible(): bool
{
    return !is_admin() && function_exists('is_shop') && is_shop() && !is_search() && !get_query_var('kr_collection');
}

function kr_shop_scope_control(): string
{
    $checked = checked('1', (string) get_query_var('kr_knives_only'), false);
    $fields = wc_query_string_form_fields(null, ['kr_knives_only', 'paged', 'page', 'product-page', 'pagename'], '', true);
    return '<form class="kr-shop-scope-form" method="get" action="' . esc_url(wc_get_page_permalink('shop')) . '">' . $fields
        . '<label class="kr-shop-scope-label"><input type="checkbox" name="kr_knives_only" value="1" ' . $checked . '><span>Shop Knives Only</span></label>'
        . '<noscript><button type="submit">Apply</button></noscript></form>';
}

add_filter('render_block_core/query-title', static function (string $content): string {
    if (!kr_shop_scope_visible()) { return $content; }
    $heading = preg_replace('/(<h[1-6]\b[^>]*>).*?(<\/h[1-6]>)/s', '$1Shop All$2', $content, 1);
    return '<div class="alignwide kr-shop-scope-heading">' . $heading . kr_shop_scope_control() . '</div>';
}, 100);

add_filter('woocommerce_page_title', static function ($title) {
    return kr_shop_scope_visible() ? 'Shop All' : $title;
}, 100);

add_filter('woocommerce_get_breadcrumb', static function (array $crumbs): array {
    if (kr_shop_scope_visible() && $crumbs) { $crumbs[count($crumbs) - 1][0] = 'Shop All'; }
    return $crumbs;
}, 100);

add_action('wp_enqueue_scripts', static function (): void {
    if (!kr_shop_scope_visible()) { return; }
    wp_enqueue_style('kr-shop-scope', get_stylesheet_directory_uri() . '/assets/css/shop-scope.css', [], wp_get_theme()->get('Version'));
    wp_enqueue_script('kr-shop-scope', get_stylesheet_directory_uri() . '/assets/js/shop-scope.js', [], wp_get_theme()->get('Version'), true);
});
