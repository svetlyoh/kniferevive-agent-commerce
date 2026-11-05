<?php
/**
 * Shared knife listing-card presentation and one-time legacy title migration.
 *
 * @package KnifeRevive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Match the knife cards' SALE label on WooCommerce galleries and other categories.
add_filter('woocommerce_sale_flash', static function ($html): string {
    return '<span class="onsale kr-sale-badge">' . esc_html__('SALE', 'kniferevive') . '</span>';
});

const KR_KNIFE_CARD_MIGRATION_VERSION = '2026.09.10.2';
const KR_KNIFE_CARD_MIGRATION_OPTION = 'kr_knife_card_title_migration_version';
const KR_KNIFE_CARD_TITLE_BACKUP_META = '_kniferevive_title_before_knife_card_migration';
const KR_KNIFE_CARD_PENDING_SYNC_OPTION = 'kr_knife_card_pending_merchant_sync_ids';

/** Return the first assigned taxonomy term name. */
function kr_knife_card_term_value(int $productId, string $taxonomy): string
{
    if (!taxonomy_exists($taxonomy)) {
        return '';
    }
    $terms = wp_get_post_terms($productId, $taxonomy, ['orderby' => 'term_order', 'order' => 'ASC']);
    return is_wp_error($terms) || !$terms ? '' : trim((string) $terms[0]->name);
}

/** Return an assigned MSRP as a normalized decimal, or an empty string. */
function kr_knife_card_msrp(int $productId): string
{
    $value = str_replace([',', '$'], '', kr_knife_card_term_value($productId, 'pa_msrp'));
    return preg_match('/^\d+(?:\.\d{1,2})?$/', $value) ? wc_format_decimal($value, 2) : '';
}

/** Format the dedicated return-policy taxonomy, with legacy tag fallbacks. */
function kr_knife_card_return_policy(int $productId): string
{
    if (taxonomy_exists('kr_return_policy')) {
        $terms = wp_get_post_terms($productId, 'kr_return_policy');
        if (!is_wp_error($terms) && $terms) {
            $term = $terms[0];
            $type = (string) get_term_meta($term->term_id, '_kr_policy_type', true);
            if ($type === 'no_returns') {
                return __('No returns', 'kniferevive');
            }
            $days = (int) get_term_meta($term->term_id, '_kr_return_days', true);
            $fee = get_term_meta($term->term_id, '_kr_return_fee', true);
            $label = $fee === '' || (float) $fee === 0.0
                ? __('Free', 'kniferevive')
                : wp_strip_all_tags(wc_price((float) $fee, [
                    'decimals' => (float) (int) $fee === (float) $fee ? 0 : wc_get_price_decimals(),
                ]));
            return $days > 0 ? sprintf(__('%1$s return · %2$dd', 'kniferevive'), $label, $days) : sprintf(__('%s return', 'kniferevive'), $label);
        }
    }

    if (has_term('free_returns', 'product_tag', $productId)) {
        return __('Free return · 15d', 'kniferevive');
    }
    if (has_term('5-returns', 'product_tag', $productId)) {
        return sprintf(__('%s return · 15d', 'kniferevive'), wp_strip_all_tags(wc_price(5, ['decimals' => 0])));
    }
    return '';
}

/** Accessible sale/regular price markup without changing WooCommerce values. */
function kr_knife_card_price_markup(WC_Product $product): string
{
    if ($product->is_on_sale() && is_numeric($product->get_regular_price()) && is_numeric($product->get_price())) {
        return '<ins aria-label="' . esc_attr__('Sale price', 'kniferevive') . '">' . wc_price((float) $product->get_price()) . '</ins>'
            . '<del aria-label="' . esc_attr__('Regular price', 'kniferevive') . '">' . wc_price((float) $product->get_regular_price()) . '</del>';
    }
    return wp_kses_post($product->get_price_html());
}

/** Capture WooCommerce's native loop CTA so AJAX and product-type behavior remain intact. */
function kr_knife_card_add_to_cart(WC_Product $cardProduct): string
{
    global $product;
    $previous = $product;
    $product = $cardProduct;
    ob_start();
    woocommerce_template_loop_add_to_cart();
    $markup = (string) ob_get_clean();
    $product = $previous;
    return $markup;
}

/** Render one complete knife card from the canonical WooCommerce product object. */
function kniferevive_render_knife_card(WC_Product $product): string
{
    $productId = $product->get_id();
    if ($productId < 1 || !kniferevive_is_knife_product($productId)) {
        return '';
    }

    $url = get_permalink($productId);
    $model = kr_knife_card_term_value($productId, 'pa_model-number');
    $msrp = kr_knife_card_msrp($productId);
    $resolved = class_exists('KREV_PA_Condition_Resolver') ? KREV_PA_Condition_Resolver::resolve($product) : null;
    // The Sharp checkbox can change the Google-facing classification to
    // Refurbished. Cards should instead show only the seller's listed condition.
    $condition = kr_knife_card_term_value($productId, 'pa_condition');
    if ($condition === '' && $resolved) {
        $condition = (string) $resolved['public_condition'];
    }
    $cosmeticGrade = $resolved ? (string) $resolved['cosmetic_grade'] : '';
    if (strcasecmp($condition, 'refurbished') === 0) {
        $condition = $cosmeticGrade;
    }
    $policy = kr_knife_card_return_policy($productId);
    $policyClass = str_starts_with(strtolower($policy), 'no returns') ? ' kr-knife-card__return-policy--neutral' : '';
    $brand = function_exists('kr_product_brand_markup') ? kr_product_brand_markup($productId, 'knife-card') : '';
    if (function_exists('kr_seller_contact_markup')) {
        $sharing = kr_seller_contact_markup($productId, 'card');
    } else {
    $sharing = '';
    if (function_exists('kniferevive_telegram_render_icon')) {
        $sharing .= (string) kniferevive_telegram_render_icon($productId, ['hide_for_owner' => true]);
    }
    if (function_exists('kniferevive_whatsapp_render_icon')) {
        $sharing .= (string) kniferevive_whatsapp_render_icon($productId, ['hide_for_owner' => true]);
    }
    }

    $details = [];
    if ($model !== '') {
        $details[] = '<span class="kr-knife-card__model"><span class="screen-reader-text">' . esc_html__('Model: ', 'kniferevive') . '</span>' . esc_html($model) . '</span>';
    }
    if ($msrp !== '') {
        $details[] = '<span class="kr-knife-card__msrp">' . esc_html__('MSRP', 'kniferevive') . ' ' . wc_price((float) $msrp) . '</span>';
    }

    $image = $product->get_image('woocommerce_thumbnail', [
        'class' => 'kr-knife-card__image',
        'loading' => 'lazy',
        'decoding' => 'async',
    ]);

    ob_start();
    ?>
    <article class="kr-knife-card" data-product-id="<?php echo esc_attr((string) $productId); ?>">
        <div class="kr-knife-card__media kr-knife-card__image-wrap">
            <a href="<?php echo esc_url($url); ?>" class="kr-knife-card__image-link" aria-label="<?php echo esc_attr(sprintf(__('View %s', 'kniferevive'), $product->get_name())); ?>"><?php echo wp_kses_post($image); ?></a>
            <?php if ($product->is_on_sale()) : ?>
                <span class="kr-knife-card__badge kr-knife-card__badge--sale kr-knife-card__sale-badge"><?php esc_html_e('SALE', 'kniferevive'); ?></span>
            <?php endif; ?>
            <?php if ($resolved ? $resolved['sharpened'] : has_term('sharpened', 'product_tag', $productId)) : ?>
                <span class="kr-knife-card__badge kr-knife-card__badge--sharp kr-knife-card__sharp-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 2 1.5 6.5L20 10l-6.5 1.5L12 18l-1.5-6.5L4 10l6.5-1.5L12 2Zm7 13 .7 3.3L23 19l-3.3.7L19 23l-.7-3.3L15 19l3.3-.7L19 15Z"/></svg><?php esc_html_e('Sharp', 'kniferevive'); ?></span>
            <?php endif; ?>
        </div>
        <div class="kr-knife-card__body kr-knife-card__content">
            <div class="kr-knife-card__identity kr-knife-card__heading-row">
                <?php if ($brand !== '') : ?><div class="kr-knife-card__brand"><?php echo wp_kses_post($brand); ?></div><?php endif; ?>
                <h2 class="kr-knife-card__title"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($product->get_name()); ?></a></h2>
            </div>
            <?php if ($details) : ?><p class="kr-knife-card__details kr-knife-card__meta"><?php echo wp_kses_post(implode('<span aria-hidden="true"> | </span>', $details)); ?></p><?php endif; ?>
            <?php if ($condition !== '' || $policy !== '') : ?>
                <div class="kr-knife-card__status kr-knife-card__trust-row">
                    <?php if ($condition !== '') : ?><span class="kr-knife-card__condition"><?php echo esc_html($condition); ?></span><?php endif; ?>
                    <?php if ($cosmeticGrade !== '' && strcasecmp($cosmeticGrade, $condition) !== 0) : ?><span class="kr-knife-card__condition" title="<?php esc_attr_e('Cosmetic grade', 'kniferevive'); ?>"><?php echo esc_html($cosmeticGrade); ?></span><?php endif; ?>
                    <?php if ($policy !== '') : ?><span class="kr-knife-card__returns kr-knife-card__return-policy<?php echo esc_attr($policyClass); ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 4 3 10l6 6v-4h6a3 3 0 1 1 0 6h-1v2h1a5 5 0 1 0 0-10H9V4Z"/></svg><?php echo esc_html($policy); ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="kr-knife-card__commerce kr-knife-card__price-row">
                <span class="kr-knife-card__price"><?php echo kr_knife_card_price_markup($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <?php if ($sharing !== '') : ?><div class="<?php echo function_exists('kr_seller_contact_markup') ? 'kr-knife-card__contact' : 'kr-knife-card__share'; ?>" aria-label="<?php esc_attr_e('Contact seller', 'kniferevive'); ?>"><?php echo $sharing; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted output from KnifeRevive messaging helpers ?></div><?php endif; ?>
            </div>
            <?php do_action('kniferevive_knife_card_after_price', $product); ?>
        </div>
        <div class="kr-knife-card__cta"><?php echo kr_knife_card_add_to_cart($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    </article>
    <?php
    return trim((string) ob_get_clean());
}

/** Show the product's condition facts beside the purchase details. */
function kr_render_single_product_condition(): void
{
    global $product;
    if (!$product instanceof WC_Product || !class_exists('KREV_PA_Condition_Resolver')) {
        return;
    }
    $resolved = KREV_PA_Condition_Resolver::resolve($product);
    if (!$resolved['is_knife']) {
        return;
    }
    echo '<div class="kr-product-condition"><p><strong>' . esc_html__('Condition:', 'kniferevive') . '</strong> ' . esc_html($resolved['public_condition']) . '</p>';
    if (!empty($resolved['sharpened'])) { echo '<p class="kr-product-sharp-status"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 2 1.5 6.5L20 10l-6.5 1.5L12 18l-1.5-6.5L4 10l6.5-1.5L12 2Zm7 13 .7 3.3L23 19l-3.3.7L19 23l-.7-3.3L15 19l3.3-.7L19 15Z"/></svg>' . esc_html__('Sharp', 'kniferevive') . '</p>'; }
    if ($resolved['cosmetic_grade'] !== '') { echo '<p><strong>' . esc_html__('Cosmetic grade:', 'kniferevive') . '</strong> ' . esc_html($resolved['cosmetic_grade']) . '</p>'; }
    if ('refurbished' === $resolved['merchant_condition'] && $resolved['evidence']['warranty_terms'] !== '') { echo '<p><strong>' . esc_html__('Warranty:', 'kniferevive') . '</strong> ' . nl2br(esc_html($resolved['evidence']['warranty_terms'])) . '</p>'; }
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'kr_render_single_product_condition', 22);

/** Replace knife items inside Product Collection, Query, and Related grids. */
function kr_replace_block_knife_cards(string $content): string
{
    if (is_admin() || $content === '' || strpos($content, 'post-') === false || strpos($content, '<li') === false) {
        return $content;
    }

    $updated = preg_replace_callback(
        '~<li\b([^>]*\bpost-(\d+)\b[^>]*)>.*?</li>~is',
        static function (array $matches): string {
            if (str_contains($matches[0], 'class="kr-knife-card"')) {
                return $matches[0];
            }
            $product = wc_get_product((int) $matches[2]);
            if (!$product instanceof WC_Product || !kniferevive_is_knife_product($product->get_id())) {
                return $matches[0];
            }
            $markup = kniferevive_render_knife_card($product);
            if ($markup === '') {
                return $matches[0];
            }
            $attrs = $matches[1];
            if (preg_match('/\bclass=(["\'])(.*?)\1/is', $attrs)) {
                $attrs = (string) preg_replace_callback('/\bclass=(["\'])(.*?)\1/is', static function (array $classMatch): string {
                    return 'class=' . $classMatch[1] . trim($classMatch[2] . ' kr-knife-card-item') . $classMatch[1];
                }, $attrs, 1);
            } else {
                $attrs .= ' class="kr-knife-card-item"';
            }
            return '<li' . $attrs . '>' . $markup . '</li>';
        },
        $content
    );
    return is_string($updated) ? $updated : $content;
}
add_filter('render_block_woocommerce/product-template', 'kr_replace_block_knife_cards', 99, 1);
add_filter('render_block_core/post-template', 'kr_replace_block_knife_cards', 99, 1);

/** Parse only the known legacy "Model ... | MSRP ..." title suffix. */
function kr_parse_legacy_knife_title(string $title): array
{
    $result = ['model' => '', 'msrp' => ''];
    if (preg_match('/(?:^|\|)\s*Model\s*:?\s*([\p{L}\p{N}._\/-]+)\s*(?=\||MSRP|$)/iu', $title, $match)) {
        $result['model'] = sanitize_text_field($match[1]);
    }
    if (preg_match('/(?:^|\||\s)MSRP\s*:?\s*\$?\s*([\d,]+(?:\.\d{1,2})?)\s*(?=\||$)/iu', $title, $match)) {
        $number = str_replace(',', '', $match[1]);
        if ((float) $number >= 0) {
            $result['msrp'] = wc_format_decimal($number, 2);
        }
    }
    return $result;
}

/** Repair the known UTF-8-as-Windows-1252 sequences found in legacy subtitles. */
function kr_normalize_legacy_listing_text(string $value): string
{
    if (!preg_match('/(?:Ã.|â..)/u', $value) || !function_exists('mb_convert_encoding')) {
        return $value;
    }
    $decoded = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
    return wp_check_invalid_utf8($decoded) !== '' ? $decoded : $value;
}

/** Determine migration changes without writing anything. */
function kr_knife_card_migration_preview(): array
{
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
    ]);
    $rows = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if (!kniferevive_is_knife_product($id)) {
            continue;
        }
        $product = wc_get_product($id);
        if (!$product instanceof WC_Product) {
            continue;
        }
        $legacy = kr_parse_legacy_knife_title($product->get_name());
        $subtitle = trim((string) $product->get_meta('_kniferevive_listing_subtitle', true, 'edit'));
        $newTitle = $subtitle !== '' ? kr_normalize_legacy_listing_text($subtitle) : $product->get_name();
        $rows[] = [
            'id' => $id,
            'old_title' => $product->get_name(),
            'new_title' => $newTitle,
            'title_change' => $newTitle !== $product->get_name(),
            'model' => kr_knife_card_term_value($id, 'pa_model-number') === '' ? $legacy['model'] : '',
            'msrp' => kr_knife_card_msrp($id) === '' ? $legacy['msrp'] : '',
            'status' => $product->get_status(),
        ];
    }
    return $rows;
}

/** Add or replace one visible global product attribute value. */
function kr_set_product_global_attribute(WC_Product $product, string $taxonomy, string $value): bool
{
    if ($value === '' || !taxonomy_exists($taxonomy)) {
        return false;
    }
    $term = get_term_by('name', $value, $taxonomy);
    if (!$term instanceof WP_Term) {
        $created = wp_insert_term($value, $taxonomy);
        if (is_wp_error($created)) {
            return false;
        }
        $term = get_term((int) $created['term_id'], $taxonomy);
    }
    if (!$term instanceof WP_Term || is_wp_error(wp_set_object_terms($product->get_id(), [(int) $term->term_id], $taxonomy, false))) {
        return false;
    }
    $attributes = $product->get_attributes();
    $attribute = new WC_Product_Attribute();
    $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
    $attribute->set_name($taxonomy);
    $attribute->set_options([(int) $term->term_id]);
    $attribute->set_position(isset($attributes[$taxonomy]) ? $attributes[$taxonomy]->get_position() : count($attributes));
    $attribute->set_visible(true);
    $attribute->set_variation(false);
    $attributes[$taxonomy] = $attribute;
    $product->set_attributes($attributes);
    return true;
}

/** Execute the reviewed migration once, retaining a per-product title backup. */
function kr_run_knife_card_migration(): array
{
    if (get_option(KR_KNIFE_CARD_MIGRATION_OPTION) === KR_KNIFE_CARD_MIGRATION_VERSION) {
        return ['already_complete' => true, 'changed' => 0, 'titles' => 0, 'models' => 0, 'msrps' => 0, 'queued' => 0, 'deferred' => count((array) get_option(KR_KNIFE_CARD_PENDING_SYNC_OPTION, []))];
    }
    $stats = ['already_complete' => false, 'changed' => 0, 'titles' => 0, 'models' => 0, 'msrps' => 0, 'queued' => 0, 'deferred' => 0];
    $pendingSync = (array) get_option(KR_KNIFE_CARD_PENDING_SYNC_OPTION, []);
    foreach (kr_knife_card_migration_preview() as $row) {
        $product = wc_get_product((int) $row['id']);
        if (!$product instanceof WC_Product) {
            continue;
        }
        $changed = false;
        if ($row['model'] !== '' && kr_set_product_global_attribute($product, 'pa_model-number', $row['model'])) {
            $changed = true;
            $stats['models']++;
        }
        if ($row['msrp'] !== '' && kr_set_product_global_attribute($product, 'pa_msrp', $row['msrp'])) {
            $changed = true;
            $stats['msrps']++;
        }
        if ($row['title_change']) {
            if (!metadata_exists('post', $product->get_id(), KR_KNIFE_CARD_TITLE_BACKUP_META)) {
                $product->update_meta_data(KR_KNIFE_CARD_TITLE_BACKUP_META, $row['old_title']);
            }
            $product->set_name($row['new_title']);
            $changed = true;
            $stats['titles']++;
        }
        if ($changed) {
            $product->save();
            $stats['changed']++;
            if ('publish' === $product->get_status()) {
                if (class_exists('KREV_Sync_Queue') && get_option('krev_merchant_bulk_approved', false)) {
                    $stats['queued']++;
                } else {
                    $pendingSync[] = $product->get_id();
                    $stats['deferred']++;
                }
            }
        }
    }
    $pendingSync = array_values(array_unique(array_filter(array_map('absint', $pendingSync))));
    if ($pendingSync) {
        update_option(KR_KNIFE_CARD_PENDING_SYNC_OPTION, $pendingSync, false);
    }
    update_option(KR_KNIFE_CARD_MIGRATION_OPTION, KR_KNIFE_CARD_MIGRATION_VERSION, false);
    return $stats;
}

/** Queue deferred migration updates after the existing Merchant safety gate opens. */
function kr_queue_pending_knife_card_merchant_sync(): int
{
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) get_option(KR_KNIFE_CARD_PENDING_SYNC_OPTION, [])))));
    if (!$ids || !class_exists('KREV_Sync_Queue') || !get_option('krev_merchant_bulk_approved', false)) {
        return 0;
    }
    foreach ($ids as $id) {
        KREV_Sync_Queue::enqueue($id);
    }
    delete_option(KR_KNIFE_CARD_PENDING_SYNC_OPTION);
    return count($ids);
}
add_action('admin_init', 'kr_queue_pending_knife_card_merchant_sync', 50);
add_action('update_option_krev_merchant_bulk_approved', static function ($oldValue, $newValue): void {
    if ($newValue) {
        kr_queue_pending_knife_card_merchant_sync();
    }
}, 20, 2);

/** Register the nonce-protected migration screen under WooCommerce. */
function kr_register_knife_card_migration_page(): void
{
    add_submenu_page(
        'woocommerce',
        __('Knife Card Migration', 'kniferevive'),
        __('Knife Card Migration', 'kniferevive'),
        'manage_woocommerce',
        'kr-knife-card-migration',
        'kr_render_knife_card_migration_page'
    );
}
add_action('admin_menu', 'kr_register_knife_card_migration_page', 80);

/** Render migration preview/run controls and audit results. */
function kr_render_knife_card_migration_page(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You are not allowed to run this migration.', 'kniferevive'));
    }
    $rows = [];
    $stats = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('kr_knife_card_migration');
        $action = isset($_POST['kr_migration_action']) ? sanitize_key(wp_unslash($_POST['kr_migration_action'])) : '';
        if ($action === 'preview') {
            $rows = kr_knife_card_migration_preview();
        } elseif ($action === 'run') {
            $stats = kr_run_knife_card_migration();
            $rows = kr_knife_card_migration_preview();
        }
    }
    $complete = get_option(KR_KNIFE_CARD_MIGRATION_OPTION) === KR_KNIFE_CARD_MIGRATION_VERSION;
    ?>
    <div class="wrap"><h1><?php esc_html_e('Knife Listing Card Migration', 'kniferevive'); ?></h1>
        <p><?php esc_html_e('Preview first. Run migrates only knife products, stores a title backup, extracts Model/MSRP when missing, and is idempotent.', 'kniferevive'); ?></p>
        <?php if ($complete) : ?><div class="notice notice-success inline"><p><?php esc_html_e('This migration version is complete.', 'kniferevive'); ?></p></div><?php endif; ?>
        <?php if (is_array($stats)) : ?><div class="notice notice-info inline"><p><?php echo esc_html(sprintf('Changed %d products: %d titles, %d models, %d MSRP values. %d published products queued; %d deferred until the existing Merchant bulk-approval gate opens.', $stats['changed'], $stats['titles'], $stats['models'], $stats['msrps'], $stats['queued'], $stats['deferred'])); ?></p></div><?php endif; ?>
        <form method="post"><?php wp_nonce_field('kr_knife_card_migration'); ?>
            <button class="button" name="kr_migration_action" value="preview"><?php esc_html_e('Dry-run preview', 'kniferevive'); ?></button>
            <button class="button button-primary" name="kr_migration_action" value="run" <?php disabled($complete); ?>><?php esc_html_e('Run migration', 'kniferevive'); ?></button>
        </form>
        <?php if ($rows) : ?>
            <h2><?php echo esc_html(sprintf(_n('%d knife product reviewed', '%d knife products reviewed', count($rows), 'kniferevive'), count($rows))); ?></h2>
            <table class="widefat striped"><thead><tr><th>ID</th><th>Current title</th><th>Proposed title</th><th>Model to add</th><th>MSRP to add</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($rows as $row) : ?><tr><td><?php echo esc_html((string) $row['id']); ?></td><td><?php echo esc_html($row['old_title']); ?></td><td><?php echo esc_html($row['new_title']); ?></td><td><?php echo esc_html($row['model']); ?></td><td><?php echo esc_html($row['msrp']); ?></td><td><?php echo esc_html($row['status']); ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
    <?php
}
