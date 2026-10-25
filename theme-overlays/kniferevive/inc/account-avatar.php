<?php
/**
 * Local KnifeRevive profile photos for WooCommerce and Dokan accounts.
 *
 * @package KnifeRevive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const KR_PROFILE_PHOTO_META = '_kniferevive_profile_photo_id';
const KR_PROFILE_PHOTO_MAX_BYTES = 5242880;

add_filter('gravatar_enhanced_hovercards_module_enabled', '__return_false', 9999);
add_filter('gravatar_enhanced_wc_my_account_module_enabled', '__return_false', 9999);

/** Resolve a WordPress user from the identifiers accepted by get_avatar(). */
function kr_profile_photo_user_id($idOrEmail): int
{
    $user = null;

    if ($idOrEmail instanceof WP_User) {
        $user = $idOrEmail;
    } elseif ($idOrEmail instanceof WP_Post) {
        $user = get_user_by('id', (int) $idOrEmail->post_author);
    } elseif ($idOrEmail instanceof WP_Comment) {
        if ((int) $idOrEmail->user_id > 0) {
            $user = get_user_by('id', (int) $idOrEmail->user_id);
        } elseif ($idOrEmail->comment_author_email !== '') {
            $user = get_user_by('email', $idOrEmail->comment_author_email);
        }
    } elseif (is_numeric($idOrEmail)) {
        $user = get_user_by('id', absint($idOrEmail));
    } elseif (is_string($idOrEmail) && is_email($idOrEmail)) {
        $user = get_user_by('email', $idOrEmail);
    }

    return $user instanceof WP_User ? (int) $user->ID : 0;
}

/** Return a user's local attachment, including an existing Dokan profile photo. */
function kr_profile_photo_attachment_id(int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $attachmentId = absint(get_user_meta($userId, KR_PROFILE_PHOTO_META, true));
    if ($attachmentId > 0 && wp_attachment_is_image($attachmentId)) {
        return $attachmentId;
    }

    $storeSettings = get_user_meta($userId, 'dokan_profile_settings', true);
    $dokanPhotoId = is_array($storeSettings) ? absint($storeSettings['gravatar'] ?? 0) : 0;
    return $dokanPhotoId > 0 && wp_attachment_is_image($dokanPhotoId) ? $dokanPhotoId : 0;
}

/** Use only locally hosted profile images; never return a Gravatar URL. */
add_filter('pre_get_avatar_data', static function (array $args, $idOrEmail): array {
    $attachmentId = kr_profile_photo_attachment_id(kr_profile_photo_user_id($idOrEmail));
    $size = max(32, absint($args['size'] ?? 96));
    $url = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, [$size, $size]) : false;

    $args['url'] = $url ?: get_stylesheet_directory_uri() . '/assets/images/default-profile-photo.svg';
    $args['found_avatar'] = $attachmentId > 0;
    $args['force_default'] = false;
    $args['referrerpolicy'] = 'same-origin';
    return $args;
}, 9999, 2);

/** Keep the Dokan storefront photo synchronized with the account photo. */
function kr_sync_dokan_profile_photo(int $userId, int $attachmentId): void
{
    $settings = get_user_meta($userId, 'dokan_profile_settings', true);
    if (!is_array($settings)) {
        $settings = [];
    }
    $settings['gravatar'] = $attachmentId;
    update_user_meta($userId, 'dokan_profile_settings', $settings);
}

/** Remove Gravatar Enhanced's injected My Account card and remote editor. */
function kr_disable_gravatar_account_card(): void
{
    global $wp_filter;

    $class = 'Automattic\\Gravatar\\GravatarEnhanced\\Woocommerce\\MyAccount';
    $hooks = [
        'woocommerce_before_account_navigation' => 'start_capture_page',
        'woocommerce_after_account_navigation' => 'end_capture_page',
    ];

    foreach ($hooks as $hookName => $methodName) {
        $hook = $wp_filter[$hookName] ?? null;
        if (!$hook instanceof WP_Hook) {
            continue;
        }
        foreach ($hook->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;
                if (is_array($function) && is_object($function[0] ?? null) && is_a($function[0], $class) && ($function[1] ?? '') === $methodName) {
                    remove_action($hookName, $function, (int) $priority);
                }
            }
        }
    }

    // These plugin callbacks otherwise rewrite or proxy every avatar URL after
    // KnifeRevive has selected the local attachment.
    foreach (['pre_get_avatar_data', 'get_avatar_url', 'get_avatar', 'avatar_defaults'] as $hookName) {
        $hook = $wp_filter[$hookName] ?? null;
        if (!$hook instanceof WP_Hook) {
            continue;
        }
        foreach ($hook->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'] ?? null;
                $object = is_array($function) && is_object($function[0] ?? null) ? $function[0] : null;
                if ($object && str_starts_with(get_class($object), 'Automattic\\Gravatar\\GravatarEnhanced\\')) {
                    remove_filter($hookName, $function, (int) $priority);
                }
            }
        }
    }
}
add_action('after_setup_theme', 'kr_disable_gravatar_account_card', 100);
add_action('init', 'kr_disable_gravatar_account_card', 100);

/** Return to My Account after processing a photo action. */
function kr_profile_photo_redirect(string $status): void
{
    $accountUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    wp_safe_redirect(add_query_arg('kr-profile-photo', sanitize_key($status), $accountUrl));
    exit;
}

/** Process the local upload or removal before the account page renders. */
add_action('template_redirect', static function (): void {
    if (
        is_admin()
        || !is_user_logged_in()
        || !function_exists('is_account_page')
        || !is_account_page()
        || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
        || !isset($_POST['kr_profile_photo_action'])
    ) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['kr_profile_photo_nonce'] ?? ''));
    if (!wp_verify_nonce($nonce, 'kr_update_profile_photo')) {
        kr_profile_photo_redirect('security-error');
    }

    $userId = get_current_user_id();
    if ($userId < 1 || !current_user_can('edit_user', $userId)) {
        kr_profile_photo_redirect('permission-error');
    }

    $action = sanitize_key(wp_unslash($_POST['kr_profile_photo_action']));
    if ($action === 'remove') {
        delete_user_meta($userId, KR_PROFILE_PHOTO_META);
        kr_sync_dokan_profile_photo($userId, 0);
        kr_profile_photo_redirect('removed');
    }

    if ($action !== 'upload' || empty($_FILES['kr_profile_photo']['tmp_name'])) {
        kr_profile_photo_redirect('missing-file');
    }

    $file = $_FILES['kr_profile_photo'];
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        kr_profile_photo_redirect('upload-error');
    }
    if ((int) ($file['size'] ?? 0) > KR_PROFILE_PHOTO_MAX_BYTES) {
        kr_profile_photo_redirect('too-large');
    }

    $allowedMimes = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    $checked = wp_check_filetype_and_ext(
        (string) $file['tmp_name'],
        sanitize_file_name((string) ($file['name'] ?? 'profile-photo')),
        $allowedMimes
    );
    if (empty($checked['ext']) || empty($checked['type'])) {
        kr_profile_photo_redirect('invalid-type');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachmentId = media_handle_upload('kr_profile_photo', 0, [], [
        'test_form' => false,
        'mimes' => $allowedMimes,
    ]);
    if (is_wp_error($attachmentId)) {
        kr_profile_photo_redirect('upload-error');
    }

    wp_update_post([
        'ID' => (int) $attachmentId,
        'post_author' => $userId,
        'post_title' => sprintf(__('Profile photo for %s', 'kniferevive'), wp_get_current_user()->display_name),
    ]);
    update_user_meta($userId, KR_PROFILE_PHOTO_META, (int) $attachmentId);
    kr_sync_dokan_profile_photo($userId, (int) $attachmentId);
    kr_profile_photo_redirect('updated');
}, 5);

/** Render the local profile-photo controls on the My Account dashboard. */
function kr_render_profile_photo_uploader(): void
{
    if (!is_user_logged_in()) {
        return;
    }

    $user = wp_get_current_user();
    $attachmentId = kr_profile_photo_attachment_id((int) $user->ID);
    $status = sanitize_key(wp_unslash($_GET['kr-profile-photo'] ?? ''));
    $messages = [
        'updated' => __('Profile picture updated.', 'kniferevive'),
        'removed' => __('Profile picture removed.', 'kniferevive'),
        'missing-file' => __('Choose a picture before uploading.', 'kniferevive'),
        'too-large' => __('That picture is larger than 5 MB.', 'kniferevive'),
        'invalid-type' => __('Use a JPG, PNG, or WebP image.', 'kniferevive'),
        'upload-error' => __('The picture could not be uploaded. Please try another image.', 'kniferevive'),
        'security-error' => __('The upload session expired. Please try again.', 'kniferevive'),
        'permission-error' => __('This account cannot update that profile picture.', 'kniferevive'),
    ];
    $success = in_array($status, ['updated', 'removed'], true);
    ?>
    <section class="kr-profile-photo-card" aria-labelledby="kr-profile-photo-title">
        <div class="kr-profile-photo-card__preview">
            <?php echo get_avatar($user->ID, 128, '', sprintf(__('%s profile picture', 'kniferevive'), $user->display_name), ['class' => 'kr-profile-photo-card__image']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <div class="kr-profile-photo-card__content">
            <h2 id="kr-profile-photo-title"><?php esc_html_e('Profile picture', 'kniferevive'); ?></h2>
            <p><?php esc_html_e('Upload a picture stored directly on KnifeRevive. JPG, PNG, or WebP up to 5 MB.', 'kniferevive'); ?></p>
            <?php if (isset($messages[$status])) : ?>
                <p class="kr-profile-photo-card__notice<?php echo $success ? ' is-success' : ' is-error'; ?>" role="status"><?php echo esc_html($messages[$status]); ?></p>
            <?php endif; ?>
            <form class="kr-profile-photo-card__form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('kr_update_profile_photo', 'kr_profile_photo_nonce'); ?>
                <label for="kr-profile-photo-file"><?php esc_html_e('Choose profile picture', 'kniferevive'); ?></label>
                <input id="kr-profile-photo-file" name="kr_profile_photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                <button class="button" type="submit" name="kr_profile_photo_action" value="upload"><?php esc_html_e('Upload picture', 'kniferevive'); ?></button>
            </form>
            <?php if ($attachmentId > 0) : ?>
                <form class="kr-profile-photo-card__remove" method="post">
                    <?php wp_nonce_field('kr_update_profile_photo', 'kr_profile_photo_nonce'); ?>
                    <button type="submit" name="kr_profile_photo_action" value="remove"><?php esc_html_e('Remove picture', 'kniferevive'); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
add_action('woocommerce_account_dashboard', 'kr_render_profile_photo_uploader', 5);

/** Count paid seller units once, even when Dokan indexes parent and child orders. */
function kr_seller_paid_units(int $sellerId, array $orders): int
{
    $replacedParents = [];
    foreach ($orders as $order) {
        if ($order->get_parent_id() > 0) {
            $replacedParents[$order->get_parent_id()] = true;
        }
    }

    $sold = 0;
    $seen = [];
    foreach ($orders as $order) {
        $id = $order->get_id();
        if (isset($seen[$id]) || isset($replacedParents[$id])) {
            continue;
        }
        $seen[$id] = true;
        if (!in_array($order->get_status(), ['processing', 'completed'], true)
            || $order->get_meta('_krev_cancellation_' . $sellerId)) {
            continue;
        }
        foreach ($order->get_items('line_item') as $itemId => $item) {
            $productId = (int) $item->get_product_id();
            if ($productId < 1 || (int) get_post_field('post_author', $productId) !== $sellerId) {
                continue;
            }
            if (class_exists('KREV_Sharpening_Orders') && KREV_Sharpening_Orders::is_sharpening_product($item->get_product())) {
                continue;
            }
            $quantity = max(0, (int) $item->get_quantity());
            $refunded = abs((int) $order->get_qty_refunded_for_item($itemId));
            $sold += max(0, $quantity - $refunded);
        }
    }
    return $sold;
}

/** Public activity only: no order IDs, customer details or revenue are rendered. */
function kr_seller_activity_data(int $sellerId): array
{
    global $wpdb;
    $data = ['sold' => null, 'since' => ''];
    $user = get_userdata($sellerId);
    if (!$user) {
        return $data;
    }
    $joined = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $user->user_registered, new DateTimeZone('UTC'));
    if ($joined && $joined->format('Y-m-d H:i:s') === $user->user_registered && $joined->getTimestamp() <= time()) {
        $data['since'] = wp_date('M Y', $joined->getTimestamp(), new DateTimeZone('UTC'));
    }

    $cacheKey = 'kr_public_sold_v2_' . $sellerId;
    $cached = get_transient($cacheKey);
    if (is_numeric($cached)) {
        $data['sold'] = max(0, (int) $cached);
        return $data;
    }
    if (!function_exists('wc_get_orders')) {
        return $data;
    }

    try {
        // WooCommerce's item tables are shared by HPOS and legacy storage.
        // Discover the full history instead of relying on a dashboard backfill.
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT item.order_id
             FROM {$wpdb->prefix}woocommerce_order_items AS item
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS product
                 ON product.order_item_id = item.order_item_id AND product.meta_key = '_product_id'
             INNER JOIN {$wpdb->posts} AS listing ON listing.ID = CAST(product.meta_value AS UNSIGNED)
             WHERE listing.post_author = %d AND listing.post_type = 'product'
                 AND item.order_item_type = 'line_item'",
            $sellerId
        ));
        if ($wpdb->last_error !== '') {
            throw new RuntimeException('Seller history lookup failed.');
        }
        $ids = array_values(array_unique(array_map('absint', $ids)));
        $orders = [];
        foreach (array_chunk($ids, 100) as $chunk) {
            foreach (wc_get_orders(['include' => $chunk, 'limit' => 100, 'type' => 'shop_order', 'status' => array_keys(wc_get_order_statuses())]) as $order) {
                $orders[$order->get_id()] = $order;
            }
        }
        $data['sold'] = kr_seller_paid_units($sellerId, $orders);
        set_transient($cacheKey, $data['sold'], 5 * MINUTE_IN_SECONDS);
    } catch (Throwable $error) {
        // An unavailable counter must never masquerade as a zero-sale seller.
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->warning('Public seller activity could not be loaded.', ['source' => 'kniferevive-seller-activity']);
        }
    }
    return $data;
}

/** One compact identity strip shared by product cards, storefronts and directory cards. */
function kr_seller_activity_markup(int $sellerId): string
{
    $data = kr_seller_activity_data($sellerId);
    $output = '<div class="kr-seller-activity"><p class="kr-seller-activity__kicker">' . esc_html__('Seller lore', 'kniferevive') . '</p><dl>';
    $output .= '<div title="' . esc_attr__('Paid item quantities on KnifeRevive, minus quantity refunds. Refreshes within five minutes.', 'kniferevive') . '"><dt>' . esc_html__('Sold here', 'kniferevive') . '</dt><dd>';
    $output .= $data['sold'] === null ? esc_html__('Unavailable', 'kniferevive') : esc_html(number_format_i18n($data['sold']));
    $output .= '</dd></div>';
    if ($data['since'] !== '') {
        $output .= '<div title="' . esc_attr__('Based on this seller’s KnifeRevive account join date.', 'kniferevive') . '"><dt>' . esc_html__('Selling since', 'kniferevive') . '</dt><dd>' . esc_html($data['since']) . '</dd></div>';
    }
    return $output . '</dl></div>';
}

add_action('dokan_product_single_after_store_name', static function ($vendor): void {
    echo kr_seller_activity_markup((int) $vendor->get_id()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5);

add_action('kniferevive_storefront_after_actions', static function ($sellerId): void {
    echo kr_seller_activity_markup((int) $sellerId); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5);

add_action('dokan_seller_listing_after_store_data', static function ($seller): void {
    echo kr_seller_activity_markup((int) $seller->ID); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5);

add_action('dokan_store_header_info_fields', static function ($sellerId): void {
    echo '<li class="kr-seller-activity-field">' . kr_seller_activity_markup((int) $sellerId) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5);

add_action('dokan_product_seller_tab_end', static function ($seller): void {
    echo '<li class="kr-seller-activity-field">' . kr_seller_activity_markup((int) $seller->ID) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5);

/** Administrators who also sell through Dokan still own their storefront contacts. */
function kr_storefront_contact_owner_eligible($eligible, $sellerId): bool
{
    return (bool) $eligible || (function_exists('dokan_is_user_seller')
        && dokan_is_user_seller((int) $sellerId, true)
        && user_can((int) $sellerId, 'edit_products'));
}
add_filter('kniferevive_telegram_is_merchant', 'kr_storefront_contact_owner_eligible', 20, 2);
add_filter('kniferevive_whatsapp_is_merchant', 'kr_storefront_contact_owner_eligible', 20, 2);

function kr_can_edit_storefront_messaging(int $sellerId): bool
{
    if (!is_user_logged_in() || $sellerId < 1 || !class_exists('KR_Telegram_Helper') || !class_exists('KR_WhatsApp_Helper')) {
        return false;
    }
    $owner = get_current_user_id() === $sellerId;
    $manager = current_user_can('manage_woocommerce') && current_user_can('edit_user', $sellerId);
    return ($owner || $manager) && (KR_Telegram_Helper::is_merchant($sellerId) || KR_WhatsApp_Helper::is_merchant($sellerId));
}

/** Validate both channels before writing either seller setting. */
function kr_validate_storefront_messaging(array $input)
{
    $read = static fn(string $key): string => isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';
    $telegram = $read('kr_store_telegram_enabled') === '1';
    $whatsapp = $read('kr_store_whatsapp_enabled') === '1';
    $mode = $read('kr_store_telegram_mode');
    if (!in_array($mode, ['personal', 'bot'], true)) {
        return new WP_Error('telegram_mode', __('Choose a Telegram account type.', 'kniferevive'));
    }
    $personalRaw = $read('kr_store_telegram_username');
    $botRaw = $read('kr_store_telegram_bot');
    $personal = KR_Telegram_Helper::normalize_username($personalRaw);
    $bot = KR_Telegram_Helper::normalize_username($botRaw);
    $phoneRaw = $read('kr_store_whatsapp_number');
    $phone = class_exists('\KnifeRevive\SellerContact\Resolver') ? \KnifeRevive\SellerContact\Resolver::number($phoneRaw) : KR_WhatsApp_Helper::normalize_number($phoneRaw);
    if (($personalRaw !== '' && !$personal) || ($botRaw !== '' && !$bot) || ($telegram && !($mode === 'bot' ? $bot : $personal))) {
        return new WP_Error('telegram_username', __('Enter a public Telegram username, or turn Telegram off.', 'kniferevive'));
    }
    if (($phoneRaw !== '' && !$phone) || ($whatsapp && !$phone)) {
        return new WP_Error('whatsapp_number', __('Enter your WhatsApp number with country code, or turn WhatsApp off.', 'kniferevive'));
    }
    return [
        KR_Telegram_Helper::ENABLED_META_KEY => $telegram ? '1' : '0',
        KR_Telegram_Helper::MODE_META_KEY => $mode,
        KR_Telegram_Helper::USERNAME_META_KEY => $personal,
        KR_Telegram_Helper::BOT_USERNAME_META_KEY => $bot,
        KR_WhatsApp_Helper::ENABLED_META_KEY => $whatsapp ? '1' : '0',
        KR_WhatsApp_Helper::NUMBER_META_KEY => $phone,
    ];
}

add_action('template_redirect', static function (): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['kr_store_messaging_action']) || !class_exists('KREV_Storefront_Editor') || !KREV_Storefront_Editor::is_endpoint()) {
        return;
    }
    $sellerId = isset($_POST['kr_messaging_seller_id']) && is_scalar($_POST['kr_messaging_seller_id']) ? absint($_POST['kr_messaging_seller_id']) : 0;
    if (!kr_can_edit_storefront_messaging($sellerId)) {
        wp_die(esc_html__('You cannot edit this seller’s messaging settings.', 'kniferevive'), '', ['response' => 403]);
    }
    check_admin_referer('kr_store_messaging_' . $sellerId, 'kr_store_messaging_nonce');
    $settings = kr_validate_storefront_messaging(wp_unslash($_POST));
    if (is_wp_error($settings)) {
        wc_add_notice($settings->get_error_message(), 'error');
    } else {
        foreach ($settings as $key => $value) {
            update_user_meta($sellerId, $key, $value);
        }
        wc_add_notice(__('Messaging settings saved.', 'kniferevive'), 'success');
    }
    $url = wc_get_account_endpoint_url('storefront');
    if ($sellerId !== get_current_user_id()) {
        $url = add_query_arg('seller_id', $sellerId, $url);
    }
    wp_safe_redirect($url . '#buyer-messages');
    exit;
}, 6);

add_action('kniferevive_storefront_editor_socials', static function ($sellerId): void {
    $sellerId = (int) $sellerId;
    if (!kr_can_edit_storefront_messaging($sellerId)) {
        return;
    }
    $telegram = get_user_meta($sellerId, KR_Telegram_Helper::ENABLED_META_KEY, true) === '1';
    $whatsapp = get_user_meta($sellerId, KR_WhatsApp_Helper::ENABLED_META_KEY, true) === '1';
    $mode = KR_Telegram_Helper::get_mode($sellerId) ?: (get_user_meta($sellerId, KR_Telegram_Helper::MODE_META_KEY, true) ?: 'personal');
    ?>
    <section class="kr-store-messaging" id="buyer-messages" aria-labelledby="kr-buyer-messages-title">
        <h3 id="kr-buyer-messages-title"><?php esc_html_e('Buyer messages', 'kniferevive'); ?></h3>
        <p><?php esc_html_e('Connect your own accounts. Enabled contact details are public.', 'kniferevive'); ?></p>
        <form method="post">
            <?php wp_nonce_field('kr_store_messaging_' . $sellerId, 'kr_store_messaging_nonce'); ?>
            <input type="hidden" name="kr_store_messaging_action" value="save">
            <input type="hidden" name="kr_messaging_seller_id" value="<?php echo esc_attr((string) $sellerId); ?>">
            <div class="kr-store-messaging__fields">
                <fieldset><legend>Telegram</legend>
                    <label><input type="checkbox" name="kr_store_telegram_enabled" value="1" <?php checked($telegram); ?>> <?php esc_html_e('Allow buyers to contact me on Telegram', 'kniferevive'); ?></label>
                    <label><?php esc_html_e('Account type', 'kniferevive'); ?><select name="kr_store_telegram_mode"><option value="personal" <?php selected($mode, 'personal'); ?>><?php esc_html_e('Personal account', 'kniferevive'); ?></option><option value="bot" <?php selected($mode, 'bot'); ?>><?php esc_html_e('Seller bot', 'kniferevive'); ?></option></select></label>
                    <label><?php esc_html_e('Public username', 'kniferevive'); ?><input type="text" name="kr_store_telegram_username" maxlength="64" placeholder="@yourstore" autocomplete="off" value="<?php echo esc_attr((string) get_user_meta($sellerId, KR_Telegram_Helper::USERNAME_META_KEY, true)); ?>"></label>
                    <label><?php esc_html_e('Bot username (for seller bots)', 'kniferevive'); ?><input type="text" name="kr_store_telegram_bot" maxlength="64" placeholder="@YourStoreBot" autocomplete="off" value="<?php echo esc_attr((string) get_user_meta($sellerId, KR_Telegram_Helper::BOT_USERNAME_META_KEY, true)); ?>"></label>
                </fieldset>
                <fieldset><legend>WhatsApp</legend>
                    <label><input type="checkbox" name="kr_store_whatsapp_enabled" value="1" <?php checked($whatsapp); ?>> <?php esc_html_e('Allow buyers to contact me on WhatsApp', 'kniferevive'); ?></label>
                    <label><?php esc_html_e('Number with country code', 'kniferevive'); ?><input type="tel" name="kr_store_whatsapp_number" maxlength="32" placeholder="+1 555 123 4567" autocomplete="tel" value="<?php echo esc_attr((string) get_user_meta($sellerId, KR_WhatsApp_Helper::NUMBER_META_KEY, true)); ?>"></label>
                </fieldset>
            </div>
            <button class="button" type="submit"><?php esc_html_e('Save messaging', 'kniferevive'); ?></button>
            <small><?php esc_html_e('Applies to all your listings. Saves separately from storefront design.', 'kniferevive'); ?></small>
        </form>
    </section>
    <?php
}, 20);
