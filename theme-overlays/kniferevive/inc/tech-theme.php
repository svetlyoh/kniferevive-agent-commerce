<?php
/** One context and one canonical palette for the entire Tech category tree. */
declare(strict_types=1);
defined('ABSPATH') || exit;

function kr_is_tech_context(): bool {
    if (is_admin()) return false;
    if (is_page('technology-trade-desk')) return true;
    if (function_exists('is_product') && is_product()) return kr_is_technology_product((int) get_queried_object_id());
    if (function_exists('is_product_category') && is_product_category()) return kr_is_technology_term(get_queried_object());
    $collection = (string) get_query_var('kr_collection');
    $group = kr_metacategories()[$collection] ?? [];
    foreach ($group['slugs'] ?? [] as $slug) {
        if (in_array($slug, ['technology', 'ai-systems'], true) || kr_is_technology_term(get_term_by('slug', $slug, 'product_cat'))) return true;
    }
    if (is_page()) {
        $page = get_queried_object();
        if ($page instanceof WP_Post) {
            if (kr_is_technology_product($page->ID) || has_shortcode($page->post_content, 'kniferevive_technology_trade_desk')) return true;
            foreach (get_post_ancestors($page) as $id) {
                $parent = get_post($id);
                if ($parent instanceof WP_Post && $parent->post_name === 'technology-trade-desk') return true;
            }
        }
    }
    // Preserve the existing Dell brand's Tech presentation.
    return is_tax('product_brand', 'dell');
}
add_filter('body_class', static function(array $classes): array {
    if (kr_is_tech_context()) $classes[] = 'kr-tech-theme';
    return array_values(array_unique($classes));
}, 30);
add_action('wp_enqueue_scripts', static function(): void {
    if (kr_is_tech_context()) wp_enqueue_style('kniferevive-tech-theme', get_stylesheet_directory_uri() . '/assets/css/tech-theme.css', ['kniferevive-navigation', 'kniferevive-annex', 'kniferevive-footer', 'kniferevive-metacategories'], kr_asset_version('assets/css/tech-theme.css'));
}, 40);
