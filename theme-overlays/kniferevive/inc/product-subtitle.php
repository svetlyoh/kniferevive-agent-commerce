<?php
/**
 * WooCommerce listing-subtitle field and storefront output.
 *
 * @package KnifeRevive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const KNIFEREVIVE_LISTING_SUBTITLE_META_KEY = '_kniferevive_listing_subtitle';

/**
 * Add the optional listing subtitle to Product data > General.
 */
function kniferevive_add_listing_subtitle_field(): void
{
    if (!function_exists('woocommerce_wp_text_input')) {
        return;
    }

    woocommerce_wp_text_input([
        'id' => KNIFEREVIVE_LISTING_SUBTITLE_META_KEY,
        'label' => __('Listing subtitle', 'kniferevive'),
        'description' => __('Short descriptive line shown below the product title in KnifeRevive product listings.', 'kniferevive'),
        'desc_tip' => true,
        'type' => 'text',
        'placeholder' => __('Wüsthof Classic 3.5-inch Paring Knife', 'kniferevive'),
        'custom_attributes' => [
            'maxlength' => '90',
        ],
    ]);
}
add_action('woocommerce_product_options_general_product_data', 'kniferevive_add_listing_subtitle_field');

/**
 * Save or remove the listing subtitle with the WooCommerce product object.
 */
function kniferevive_save_listing_subtitle(WC_Product $product): void
{
    if (!isset($_POST[KNIFEREVIVE_LISTING_SUBTITLE_META_KEY])) {
        return;
    }

    $subtitle = sanitize_text_field(
        wp_unslash((string) $_POST[KNIFEREVIVE_LISTING_SUBTITLE_META_KEY])
    );

    if ($subtitle === '') {
        $product->delete_meta_data(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY);
        return;
    }

    $product->update_meta_data(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY, $subtitle);
}
add_action('woocommerce_admin_process_product_object', 'kniferevive_save_listing_subtitle');

/**
 * Render the listing-subtitle field in Dokan's classic product editor.
 *
 * The same hook is present on both the create and edit forms. On edit screens,
 * Dokan passes the post and product ID; on create screens it passes no values.
 *
 * @param WP_Post|null $post       Product post when editing.
 * @param int          $product_id Product ID when editing.
 */
function kniferevive_render_dokan_listing_subtitle_field($post = null, $product_id = 0): void
{
    $subtitle = '';

    if (isset($_POST[KNIFEREVIVE_LISTING_SUBTITLE_META_KEY])) {
        $subtitle = sanitize_text_field(
            wp_unslash((string) $_POST[KNIFEREVIVE_LISTING_SUBTITLE_META_KEY])
        );
    } elseif ((int) $product_id > 0) {
        $product = wc_get_product((int) $product_id);
        if ($product instanceof WC_Product) {
            $subtitle = trim((string) $product->get_meta(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY));
        }
    }
    ?>
    <div class="dokan-form-group kniferevive-dokan-listing-subtitle">
        <label for="<?php echo esc_attr(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY); ?>">
            <?php esc_html_e('Listing subtitle', 'kniferevive'); ?>
        </label>
        <input
            type="text"
            class="dokan-form-control"
            id="<?php echo esc_attr(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY); ?>"
            name="<?php echo esc_attr(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY); ?>"
            value="<?php echo esc_attr($subtitle); ?>"
            maxlength="90"
            placeholder="<?php echo esc_attr__('Wüsthof Classic 3.5-inch Paring Knife', 'kniferevive'); ?>"
        >
        <p class="help-block">
            <?php esc_html_e('Brand, series, and knife type shown below the product title. Leave blank to generate and save it automatically.', 'kniferevive'); ?>
        </p>
    </div>
    <?php
}
add_action('dokan_new_product_form', 'kniferevive_render_dokan_listing_subtitle_field', 20, 2);

/**
 * Add the listing subtitle to Dokan 5's current React product editor.
 *
 * @param array<int, array<string, mixed>> $items      Editor schema.
 * @param int                              $product_id Product ID, or zero when creating.
 * @return array<int, array<string, mixed>>
 */
function kniferevive_add_dokan_product_editor_subtitle(array $items, $product_id = 0): array
{
    $field = [
        'id' => KNIFEREVIVE_LISTING_SUBTITLE_META_KEY,
        'section_id' => 'general',
        'type' => 'field',
        'label' => __('Listing subtitle', 'kniferevive'),
        'variant' => 'text',
        'placeholder' => __('Wüsthof Classic 3.5-inch Paring Knife', 'kniferevive'),
        'description' => __('Brand, series, and knife type shown below the product title. Leave blank to generate and save it automatically.', 'kniferevive'),
        'required' => false,
        'visibility' => true,
    ];

    foreach ($items as $index => $item) {
        if (($item['id'] ?? '') === 'name') {
            array_splice($items, $index + 1, 0, [$field]);
            return $items;
        }
    }

    $items[] = $field;
    return $items;
}
add_filter('dokan_product_editor_schema', 'kniferevive_add_dokan_product_editor_subtitle', 20, 2);

/**
 * Save the listing subtitle submitted by either Dokan product editor.
 *
 * Dokan fires these actions only after its own nonce/REST authorization and
 * product-ownership checks. The ownership check below is retained as defense in
 * depth and also permits shop managers/administrators to edit vendor products.
 *
 * @param int                  $product_id Product ID.
 * @param array<string, mixed> $data       Submitted Dokan product data.
 */
function kniferevive_save_dokan_listing_subtitle($product_id, $data = []): void
{
    $product_id = (int) $product_id;
    if ($product_id < 1 || !is_array($data)) {
        return;
    }

    if (!array_key_exists(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY, $data)) {
        return;
    }

    $product_post = get_post($product_id);
    if (!$product_post instanceof WP_Post) {
        return;
    }

    $is_owner = (int) $product_post->post_author === get_current_user_id();
    if (!$is_owner && !current_user_can('edit_post', $product_id)) {
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product instanceof WC_Product) {
        return;
    }

    $subtitle = sanitize_text_field(
        wp_unslash((string) $data[KNIFEREVIVE_LISTING_SUBTITLE_META_KEY])
    );

    if ($subtitle === '') {
        $product->delete_meta_data(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY);
    } else {
        $product->update_meta_data(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY, $subtitle);
    }

    $product->save_meta_data();
}
add_action('dokan_new_product_added', 'kniferevive_save_dokan_listing_subtitle', 20, 2);
add_action('dokan_product_updated', 'kniferevive_save_dokan_listing_subtitle', 20, 2);

/**
 * Return the most useful term names assigned to a product.
 *
 * @param int      $product_id Product ID.
 * @param string[] $taxonomies Taxonomies to inspect in priority order.
 * @return string[]
 */
function kniferevive_get_product_term_names(int $product_id, array $taxonomies): array
{
    foreach ($taxonomies as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = wp_get_post_terms($product_id, $taxonomy);
        if (is_wp_error($terms) || $terms === []) {
            continue;
        }

        usort($terms, static function (WP_Term $left, WP_Term $right) use ($taxonomy): int {
            $left_depth = count(get_ancestors($left->term_id, $taxonomy, 'taxonomy'));
            $right_depth = count(get_ancestors($right->term_id, $taxonomy, 'taxonomy'));
            return $right_depth <=> $left_depth;
        });

        $names = [];
        foreach ($terms as $term) {
            $name = trim((string) $term->name);
            if ($name !== '' && !in_array(strtolower($name), ['uncategorized', 'other'], true)) {
                $names[] = $name;
            }
        }

        if ($names !== []) {
            return array_values(array_unique($names));
        }
    }

    return [];
}

/**
 * Generate a useful subtitle from the product's catalog data.
 *
 * Explicit subtitles always take precedence. This fallback keeps every product
 * card populated while vendors can still refine individual wording in Dokan.
 */
function kniferevive_generate_listing_subtitle(WC_Product $product): string
{
    $catalog_overrides = [
        'japanese-steak-knives-set-of-3-rachel-ray' => 'Rachael Ray Japanese Damascus Steak Knife Set',
        'salt-home-depot-brand-paring-knife-3-5-inch' => 'SALT 3.5-inch Paring Knife',
        'zwilling-paring-3-5-inch-knife' => 'Zwilling Four Star 3.5-inch Paring Knife',
        'salt-8-inch-chefs-knife' => 'SALT 8-inch Chef\'s Knife',
    ];

    $product_slug = $product->get_slug();
    if (isset($catalog_overrides[$product_slug])) {
        return trim((string) apply_filters(
            'kniferevive_listing_subtitle_fallback',
            $catalog_overrides[$product_slug],
            $product
        ));
    }

    $product_id = $product->get_id();
    $brand_terms = kniferevive_get_product_term_names(
        $product_id,
        ['product_brand']
    );
    $category_terms = kniferevive_get_product_term_names($product_id, ['product_cat']);

    $parts = [];
    if ($brand_terms !== []) {
        $parts[] = $brand_terms[0];
    }
    if ($category_terms !== []) {
        $parts[] = $category_terms[0];
    }

    $parts = array_values(array_unique(array_filter(array_map('trim', $parts))));
    $subtitle = trim(implode(' ', $parts));

    if ($subtitle === '') {
        $subtitle = trim($product->get_name());
    }

    /**
     * Filter the catalog-data fallback used when no explicit subtitle is saved.
     *
     * @param string     $subtitle Generated subtitle.
     * @param WC_Product $product  Product object.
     */
    return trim((string) apply_filters('kniferevive_listing_subtitle_fallback', $subtitle, $product));
}

/**
 * Save the generated fallback when a new product has no entered subtitle.
 *
 * WooCommerce fires its new-product hook after the product has been created.
 * Dokan saves custom form values on its own hook, so the later Dokan callback
 * ensures a blank submission is populated after Dokan finishes saving.
 *
 * @param int $product_id Newly created WooCommerce product ID.
 */
function kniferevive_populate_new_listing_subtitle($product_id): void
{
    $product = wc_get_product((int) $product_id);
    if (!$product instanceof WC_Product) {
        return;
    }

    $stored = trim((string) $product->get_meta(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY));
    if ($stored !== '') {
        return;
    }

    $generated = sanitize_text_field(kniferevive_generate_listing_subtitle($product));
    if ($generated === '') {
        return;
    }

    $product->update_meta_data(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY, $generated);
    $product->save_meta_data();
}
add_action('woocommerce_new_product', 'kniferevive_populate_new_listing_subtitle', 30, 1);
add_action('dokan_new_product_added', 'kniferevive_populate_new_listing_subtitle', 30, 1);

/**
 * Return the current product's explicit or automatically generated subtitle.
 */
function kniferevive_get_listing_subtitle(): string
{
    global $product;

    if (!$product instanceof WC_Product) {
        return '';
    }

    $subtitle = trim((string) $product->get_meta(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY));
    if ($subtitle !== '') {
        return $subtitle;
    }

    return kniferevive_generate_listing_subtitle($product);
}

/** Read a few public, saved specifications; never infer values from a title. */
function kniferevive_tech_listing_attributes(WC_Product $product): array
{
    $priority = ['processor', 'memory', 'storage', 'video-resolution', 'resolution', 'display-size', 'connectivity', 'compatible-devices', 'connector-type', 'carrier-status', 'network-technology', 'operating-system', 'compatible-services', 'power-requirements', 'included-accessories', 'model-code', 'model-number', 'color'];
    if (class_exists('KREV_PA_Specifications')) {
        foreach ($product->get_category_ids() as $category_id) {
            $profile = KREV_PA_Specifications::profile($category_id);
            if (($profile['group'] ?? '') === 'technology' && !empty($profile['google_id'])) {
                $priority = array_values(array_unique(array_merge($profile['fields'], $priority)));
                break;
            }
        }
    }

    $available = [];
    foreach ($product->get_attributes() as $attribute) {
        if (!$attribute->get_visible()) {
            continue;
        }
        $name = $attribute->get_name();
        $key = sanitize_title((string) preg_replace('/^pa_/', '', $name));
        if (!in_array($key, array_merge($priority, ['condition']), true)) {
            continue;
        }
        $value = trim(html_entity_decode(wp_strip_all_tags($product->get_attribute($name)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '' || in_array(strtolower($value), ['n/a', 'unknown', 'not specified', 'none'], true)) {
            continue;
        }
        $available[$key] = ['label' => ucfirst(wc_attribute_label($name, $product)), 'value' => $value];
    }

    $specs = [];
    foreach ($priority as $key) {
        if (isset($available[$key])) {
            $specs[] = $available[$key];
        }
    }
    $specs = array_slice($specs, 0, isset($available['condition']) ? 2 : 3);
    if (isset($available['condition'])) {
        $specs[] = $available['condition'];
    }
    return $specs;
}

/** Keep written subtitles; replace only empty or legacy category-only copy. */
function kniferevive_tech_card_description(WC_Product $product): string
{
    $subtitle = trim((string) $product->get_meta(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY));
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    $categories = is_wp_error($categories) ? [] : $categories;
    $legacy = $categories;
    static $google_category_labels;
    if ($google_category_labels === null) {
        $google_category_labels = [];
        if (class_exists('KREV_PA_Tech_Categories')) {
            foreach (KREV_PA_Tech_Categories::catalog('technology') as $category) {
                $google_category_labels[] = (string) ($category['name'] ?? '');
                $google_category_labels[] = (string) ($category['path'] ?? '');
            }
        }
    }
    $legacy = array_merge($legacy, $google_category_labels);
    $brands = kniferevive_get_product_term_names($product->get_id(), ['product_brand']);
    foreach ($categories as $category) {
        foreach ($brands as $brand) {
            $legacy[] = $brand . ' ' . $category;
        }
    }
    $normalize = static fn(string $text): string => strtolower(trim(html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($subtitle !== '' && !in_array($normalize($subtitle), array_map($normalize, $legacy), true)) {
        return '<p class="kniferevive-product-subtitle">' . esc_html($subtitle) . '</p>';
    }

    $specs = kniferevive_tech_listing_attributes($product);
    if (!$specs) {
        return '';
    }
    $html = '<dl class="kr-tech-card-specs" aria-label="' . esc_attr__('Product highlights', 'kniferevive') . '">';
    foreach ($specs as $spec) {
        $html .= '<div class="kr-tech-card-spec"><dt>' . esc_html($spec['label']) . '</dt><dd>' . esc_html($spec['value']) . '</dd></div>';
    }
    return $html . '</dl>';
}

/** Render a subtitle or saved tech highlights in standard product loops. */
function kniferevive_render_loop_listing_subtitle(): void
{
    global $product;
    if ($product instanceof WC_Product && function_exists('kniferevive_is_knife_product') && kniferevive_is_knife_product($product->get_id())) {
        return;
    }

    if ($product instanceof WC_Product && function_exists('kr_is_technology_product') && kr_is_technology_product($product->get_id())) {
        echo kniferevive_tech_card_description($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped by the renderer.
        return;
    }

    $subtitle = kniferevive_get_listing_subtitle();
    if ($subtitle === '') {
        return;
    }

    echo '<p class="kniferevive-product-subtitle">' . esc_html($subtitle) . '</p>';
}
add_action('woocommerce_shop_loop_item_title', 'kniferevive_render_loop_listing_subtitle', 11);

/** Match the shop, related-product and query grids without editing product data. */
function kniferevive_refresh_tech_listing_cards(string $content, array $block): string
{
    if (is_admin() || !in_array($block['blockName'] ?? '', ['woocommerce/product-template', 'core/post-template', 'woocommerce/related-products'], true)) {
        return $content;
    }
    return (string) preg_replace_callback('~<li\b([^>]*\bpost-(\d+)\b[^>]*)>.*?</li>~is', static function (array $matches): string {
        $product = wc_get_product((int) $matches[2]);
        if (!$product instanceof WC_Product || !function_exists('kr_is_technology_product') || !kr_is_technology_product($product->get_id())) {
            return $matches[0];
        }
        $card = $matches[0];
        if (!str_contains($card, 'kr-tech-listing-card')) {
            $card = (string) preg_replace('~(<li\b[^>]*\bclass=["\'])~i', '$1kr-tech-listing-card ', $card, 1);
        }
        $card = (string) preg_replace('~<dl\b[^>]*\bclass=["\'][^"\']*\bkr-tech-card-specs\b[^"\']*["\'][^>]*>.*?</dl>~is', '', $card);
        $card = (string) preg_replace('~<p\b[^>]*\bclass=["\'][^"\']*\bkniferevive-product-subtitle\b[^"\']*["\'][^>]*>.*?</p>~is', '', $card);
        $card = (string) preg_replace_callback('~(<h[1-6]\b[^>]*>\s*<a\b[^>]*>).*?(</a>\s*</h[1-6]>)~is', static fn(array $heading): string => $heading[1] . esc_html($product->get_name()) . $heading[2], $card, 1);
        $description = kniferevive_tech_card_description($product);
        return (string) preg_replace_callback('~</h[1-6]>~i', static fn(array $heading): string => $heading[0] . $description, $card, 1);
    }, $content);
}
foreach (['woocommerce/product-template', 'core/post-template', 'woocommerce/related-products'] as $kr_tech_card_block) {
    add_filter('render_block_' . $kr_tech_card_block, 'kniferevive_refresh_tech_listing_cards', 120, 2);
}

/**
 * Add subtitles to cards rendered by the WooCommerce Related Products block.
 *
 * Unlike standard WooCommerce loops, this block renders a Core Post Title and
 * does not fire woocommerce_shop_loop_item_title. Processing the complete block
 * keeps the fix limited to related-product cards and avoids duplicate subtitles
 * in Shop, shortcode, and Product Collection loops.
 *
 * @param string               $content Rendered related-products block.
 * @param array<string, mixed> $block   Parsed block data.
 */
function kniferevive_add_related_product_subtitles(string $content, array $block): string
{
    if ($content === '') {
        return $content;
    }

    $updated = preg_replace_callback(
        '~<li\b[^>]*\bpost-(\d+)\b[^>]*>.*?</li>~is',
        static function (array $matches): string {
            $card = $matches[0];
            if (strpos($card, 'kniferevive-product-subtitle') !== false) {
                return $card;
            }

            $product = wc_get_product((int) $matches[1]);
            if (!$product instanceof WC_Product) {
                return $card;
            }
            if (function_exists('kniferevive_is_knife_product') && kniferevive_is_knife_product($product->get_id())) {
                return $card;
            }

            $subtitle = trim((string) $product->get_meta(KNIFEREVIVE_LISTING_SUBTITLE_META_KEY));
            if ($subtitle === '') {
                $subtitle = kniferevive_generate_listing_subtitle($product);
            }
            if ($subtitle === '') {
                return $card;
            }

            $subtitle_markup = '<p class="kniferevive-product-subtitle">' . esc_html($subtitle) . '</p>';
            $card_with_subtitle = preg_replace(
                '~(</h[1-6]>)~i',
                '$1' . $subtitle_markup,
                $card,
                1
            );

            return is_string($card_with_subtitle) ? $card_with_subtitle : $card;
        },
        $content
    );

    return is_string($updated) ? $updated : $content;
}
add_filter(
    'render_block_woocommerce/related-products',
    'kniferevive_add_related_product_subtitles',
    20,
    2
);

/**
 * Remove redundant brand and category rows from product listing cards.
 *
 * The subtitle already combines those values. Product tags remain visible, so
 * condition and return-policy labels such as "Free" and "like new" are kept.
 *
 * @param string               $content  Rendered Post Terms block.
 * @param array<string, mixed> $block    Parsed block data.
 * @param WP_Block             $instance Block instance with post context.
 */
function kniferevive_remove_listing_brand_and_category(
    string $content,
    array $block,
    WP_Block $instance
): string {
    $taxonomy = (string) ($block['attrs']['term'] ?? '');
    $post_type = (string) ($instance->context['postType'] ?? '');

    if (
        $post_type === 'product'
        && in_array($taxonomy, ['product_brand', 'product_cat'], true)
    ) {
        return '';
    }

    return $content;
}
add_filter(
    'render_block_core/post-terms',
    'kniferevive_remove_listing_brand_and_category',
    20,
    3
);

/**
 * Render the complete subtitle directly below the single-product title.
 */
function kniferevive_render_single_listing_subtitle(): void
{
    $subtitle = kniferevive_get_listing_subtitle();
    if ($subtitle === '') {
        return;
    }

    echo '<p class="kniferevive-single-product-subtitle">' . esc_html($subtitle) . '</p>';
}
add_action('woocommerce_single_product_summary', 'kniferevive_render_single_listing_subtitle', 6);

/** Seller-owned contact actions for the Shop All grid. Never substitute another seller. */
function kr_shop_seller_contacts(int $productId): string
{
    $icons = '';
    if (function_exists('kniferevive_telegram_render_icon')) {
        $icons .= kniferevive_telegram_render_icon($productId, ['hide_for_owner' => false]);
    }
    if (function_exists('kniferevive_whatsapp_render_icon')) {
        $icons .= kniferevive_whatsapp_render_icon($productId, ['hide_for_owner' => false]);
    }
    $output = '<span class="kr-shop-seller-contact"><span class="kr-shop-seller-contact__label">' . esc_html__('Ask the seller', 'kniferevive') . '</span>';
    if ($icons !== '') {
        return $output . '<span class="kr-shop-seller-contact__actions">' . $icons . '</span></span>';
    }
    $sellerId = (int) get_post_field('post_author', $productId);
    $storeUrl = $sellerId && function_exists('dokan_get_store_url') ? dokan_get_store_url($sellerId) : get_permalink($productId);
    $output .= '<span class="kr-shop-seller-contact__status">' . esc_html__('Messaging not set up', 'kniferevive') . '</span><a class="kr-shop-seller-store" href="' . esc_url($storeUrl) . '">' . esc_html__('View store', 'kniferevive') . '</a>';
    if (function_exists('kr_can_edit_storefront_messaging') && kr_can_edit_storefront_messaging($sellerId)) {
        $url = wc_get_account_endpoint_url('storefront');
        if ($sellerId !== get_current_user_id()) {
            $url = add_query_arg('seller_id', $sellerId, $url);
        }
        $output .= '<a class="kr-shop-seller-contact__setup" href="' . esc_url($url . '#buyer-messages') . '">' . esc_html__('Set up messaging', 'kniferevive') . '</a>';
    }
    return $output . '</span>';
}

function kr_refresh_shop_seller_contacts(string $content): string
{
    if (is_admin() || !function_exists('is_shop') || !is_shop()) {
        return $content;
    }
    return preg_replace_callback('~<li\b[^>]*\bpost-(\d+)\b[^>]*>.*?</li>~is', static function (array $match): string {
        $card = $match[0];
        if (str_contains($card, 'kr-shop-seller-contact') || !wc_get_product((int) $match[1])) {
            return $card;
        }
        $contacts = kr_shop_seller_contacts((int) $match[1]);
        $count = 0;
        $updated = preg_replace_callback('~(<(div|span)\b[^>]*\bclass="[^"]*\b(?:kr-product-contact|kr-knife-card__share)\b[^"]*"[^>]*>).*?</\2>~is', static fn(array $container): string => $container[1] . $contacts . '</' . $container[2] . '>', $card, 1, $count);
        if ($count) {
            return $updated;
        }
        // Missing contact groups still get a clear state beside the price.
        return preg_replace_callback('~(<div\b[^>]*\bclass="[^"]*\b(?:kr-product-price-row|kr-knife-card__commerce)\b[^"]*"[^>]*>)~i', static fn(array $opening): string => $opening[1] . $contacts, $card, 1);
    }, $content) ?? $content;
}
add_filter('render_block_woocommerce/product-template', 'kr_refresh_shop_seller_contacts', 150);
add_filter('render_block_core/post-template', 'kr_refresh_shop_seller_contacts', 150);
