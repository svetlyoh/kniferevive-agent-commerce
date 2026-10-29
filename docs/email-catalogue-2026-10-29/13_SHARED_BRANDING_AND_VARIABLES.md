# Shared email wording, branding and dynamic fields

Standard WooCommerce messages can append purchaser item lines, quantities, prices, taxes, shipping service/rate, marketplace fee, billing/shipping addresses, payment method, download links, customer notes, return-policy snapshots and support content. These values are order-specific; no real buyer address, secret link or receipt appears in this catalogue.

Seller Orders' standard support block uses “KnifeRevive Support Crew (site admin)” and the public support mailbox configured in that source. Buyer variant: “Questions about your order, cancellations, or returns?” Seller variant: “Questions about your right to cancel an order, a buyer cancellation request, or a return?” Plain variants include “Questions about seller cancellations or returns? Email us.” and “Questions about your order, cancellations, or returns? Email us.”

Theme branding/return-policy additions are reproduced below. Sender display name, From address, footer, logo, heading, subject and additional content may be overridden by production settings or filters. Beige/dark styling is presentation; it does not create a separate subject. This documentation does not alter wording, branding, flags, schedules, payment logic or queues.


### Source: [email-branding.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/themes/kniferevive/inc/email-branding.php)

SHA-256: `c69e31a5e0a97ce8be681f37d958a704c4973fc009e8cd1bec54c45be87909a4`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- s theme.json color presets. Update those values here if * the site palette changes; email clients cannot read theme CSS variables. */ declare(strict_types=1); defined(
- ) \|\| exit; /** Use the same Custom Logo attachment as the site header, at email size. */ function kr_email_logo_url(): string { $logo_id = (int) get_theme_mod(
- ) { return set_url_scheme($url,
- ); } } } // The supplied original raster artwork in this theme is used when the // Custom Logo is absent or uses a format some mail clients omit. return set_url_scheme(get_stylesheet_directory_uri() .
- ); } // Override email appearance only at render time. Do not change saved settings. add_filter(
- , static function () { return kr_email_logo_url(); }); foreach ([
- . $option, static function () use ($color) { return $color; }); } /** Additional email-safe CSS, inlined by WooCommerce
- Segoe UI
- s order summary, before its address block. * Gate by the actual email ID and context so Dokan and admin emails stay clear. */ function kr_email_buyer_return_policy($order, $sent_to_admin, $plain_text, $email): void { if ($sent_to_admin \|\| $plain_text \|\| !is_object($email) \|\| !isset($email->id)) { return; } $buyer_order_ids = [
- , 20, 4); /** Mark email items while their template renders; frontend order tables stay unchanged. */ function kr_email_item_class(string $class, $item): string { $class .=
- , static function (array $args): array { if (empty($args[
- , static function (string $html): string { remove_filter(
- , 90); return $html; }, 90); /** Append final inline styles after the normal inliner has styled upstream markup. */ function kr_email_inline(DOMElement $node, string $css): void { $node->setAttribute(
- . $css); } function kr_email_class(DOMElement $node, string $class): void { $node->setAttribute(
- . $class)); } /** Dark technology surfaces use the same black/white/yellow treatment as the site. */ function kr_email_dark_surface(DOMElement $node): void { kr_email_inline($node,
- ) as $child) { if (!$child instanceof DOMElement \|\| in_array($child->tagName, [
- ); } } } } /** * Keep each title full width even in clients that discard media queries. * Preserve the original product/meta hooks, refund markup, prices and totals. */ function kr_email_layout_document(DOMDocument $document, $email = null): void { $xpath = new DOMXPath($document); $body = $xpath->query(
- )->item(0); if (!$body instanceof DOMElement \|\| $body->hasAttribute(
- ); // Dokan shipment/received emails render Product + Quantity directly, // bypassing WooCommerce
- padding:0 14px 16px!important;background-color:#f6f6f4;border-bottom:1px solid #e5e5e2!important;
- Item total
- padding:16px;margin:0 0 16px;background-color:#f6f6f4;border:1px solid #e5e5e2;
- s full dark surface; mixed orders // keep individual Tech cards dark and the remaining receipt light. $allTech = count($rows) > 0 && $techCount === count($rows); if (!$rows && $linkedItems > 0) { $allTech = $linkedTech === $linkedItems; } if (!$rows && !$linkedItems && is_object($email) && isset($email->object) && $email->object instanceof WC_Order) { $items = $email->object->get_items(); $allTech = count($items) > 0; foreach ($items as $item) { $allTech = $allTech && function_exists(
- , static function ($inliner, $email): void { kr_email_layout_document($inliner->getDomDocument(), $email); }, 1000, 2); /** Reminder HTML uses WooCommerce

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * KnifeRevive branding for the shared WooCommerce/Dokan HTML email wrapper.
 *
 * Palette: this theme's theme.json color presets. Update those values here if
 * the site palette changes; email clients cannot read theme CSS variables.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/** Use the same Custom Logo attachment as the site header, at email size. */
function kr_email_logo_url(): string
{
    $logo_id = (int) get_theme_mod('custom_logo');
    if ($logo_id) {
        $mime = (string) get_post_mime_type($logo_id);
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            $url = (string) wp_get_attachment_image_url($logo_id, 'medium');
            if ($url !== '') {
                return set_url_scheme($url, 'https');
            }
        }
    }

    // The supplied original raster artwork in this theme is used when the
    // Custom Logo is absent or uses a format some mail clients omit.
    return set_url_scheme(get_stylesheet_directory_uri() . '/assets/images/knife-revive-email-logo.jpg', 'https');
}

// Override email appearance only at render time. Do not change saved settings.
add_filter('pre_option_woocommerce_email_header_image', static function () {
    return kr_email_logo_url();
});

foreach ([
    'woocommerce_email_background_color' => '#f6f6f4',
    'woocommerce_email_body_background_color' => '#ffffff',
    'woocommerce_email_base_color' => '#111111',
    'woocommerce_email_text_color' => '#111111',
    'woocommerce_email_footer_text_color' => '#626262',
    'woocommerce_email_header_image_width' => '170',
    'woocommerce_email_header_alignment' => 'left',
] as $option => $color) {
    add_filter('pre_option_' . $option, static function () use ($color) {
        return $color;
    });
}

/** Additional email-safe CSS, inlined by WooCommerce's normal CSS inliner. */
function kr_email_styles(string $css, $email): string
{
    $css .= <<<'CSS'

/* KnifeRevive palette: base #ffffff, contrast #111111, muted #626262,
   surface #f6f6f4, border #e5e5e2, accent #a7552a (theme.json). */
body, #outer_wrapper { background-color: #f6f6f4 !important; }
#wrapper { width: 100%; max-width: 620px; padding: 28px 0; }
#inner_wrapper, #template_container { background-color: #ffffff; border: 1px solid #e5e5e2; border-radius: 4px !important; box-shadow: none !important; }
#template_header { background-color: #ffffff !important; color: #111111 !important; border: 0; }
#template_header_image { padding: 28px 32px 0; text-align: left; }
#template_header_image p { margin: 0; text-align: left; }
#template_header_image img { display: block; width: 170px; max-width: 100%; height: auto; margin: 0; }
#header_wrapper { padding: 24px 32px 20px; border-bottom: 1px solid #e5e5e2; }
#header_wrapper h1, #template_header h1, #template_header h1 a { color: #111111 !important; background: transparent !important; font-size: 30px; font-weight: 700; line-height: 1.2; text-shadow: none; letter-spacing: -0.02em; margin: 0; text-align: left; }
#body_content, #body_content_inner { background-color: #ffffff; color: #111111; }
#body_content_inner, .font-family, .text, .address-title, .order-item-data, h1, h2, h3, #template_footer #credit { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, Helvetica, sans-serif !important; }
#body_content_inner { font-size: 16px; line-height: 1.55; }
#body_content_inner_cell { padding: 28px 32px 34px !important; }
#body_content h2 { color: #111111; font-size: 19px; line-height: 1.35; margin: 0 0 16px; }
#body_content h3 { color: #111111; font-size: 16px; }
#body_content p { line-height: 1.55; }
a, .link, #body_content a, #template_footer #credit a { color: #a7552a !important; }
.button, a.button, #body_content a.button { background-color: #111111 !important; border-color: #111111 !important; color: #ffffff !important; }
#body_content table.td { width: 100%; border: 0 !important; border-collapse: collapse; table-layout: fixed; }
#body_content table.td th, #body_content table.td td { border: 0 !important; border-bottom: 1px solid #e5e5e2 !important; padding: 11px 10px !important; color: #111111; vertical-align: top; line-height: 1.45; overflow-wrap: anywhere; word-break: normal; }
#body_content table.td thead th { background-color: #f6f6f4; color: #111111; font-size: 13px; font-weight: 700; }
#body_content table.td thead th:nth-child(2), #body_content table.td tbody td:nth-child(2) { text-align: center; }
#body_content table.td thead th:last-child, #body_content table.td tbody td:last-child { text-align: right; }
#body_content table.td .order-totals th, #body_content table.td .order-totals td { background-color: #ffffff; color: #626262; font-weight: 400; padding-top: 9px !important; padding-bottom: 9px !important; }
#body_content table.td .order-totals td { color: #111111; }
#body_content table.td .order-totals-total th, #body_content table.td .order-totals-total td, #body_content table.td .order-totals-last th, #body_content table.td .order-totals-last td { color: #111111; font-weight: 700; font-size: 17px; border-top: 2px solid #111111 !important; }
#body_content .wc-item-meta, #body_content .email-order-item-meta, #body_content .wc-item-meta-label { color: #626262; font-size: 13px; }
#addresses { border-collapse: separate; border-spacing: 0; }
#addresses > tbody > tr > td { width: 50%; vertical-align: top; }
#addresses .address-title, #addresses h2 { display: block; color: #111111; font-size: 17px; font-weight: 700; margin: 0 0 10px; }
#addresses address { background-color: #f6f6f4; border: 1px solid #e5e5e2; border-radius: 3px; color: #111111; font-size: 14px; line-height: 1.55; font-style: normal; padding: 16px !important; overflow-wrap: anywhere; word-break: normal; }
#addresses > tbody > tr > td + td { padding-left: 12px !important; }
#template_footer #credit { color: #626262; font-size: 12px; line-height: 1.5; border-top: 1px solid #e5e5e2; padding: 22px 32px 28px; text-align: left; }
.kr-email-return-policy { color: #626262; font-size: 14px; line-height: 1.5; margin: 0 0 24px; }
.kr-email-return-policy a { color: #a7552a !important; }
@media screen and (max-width: 600px) {
    #wrapper { padding: 0 !important; }
    #template_header_image { padding: 22px 20px 0 !important; }
    #header_wrapper { padding: 20px !important; }
    #header_wrapper h1 { font-size: 26px !important; }
    #body_content_inner_cell { padding: 22px 20px 28px !important; }
    #body_content_inner { font-size: 15px !important; }
    #addresses td { display: block !important; width: 100% !important; box-sizing: border-box; }
    #addresses td + td { padding: 18px 0 0 !important; }
    #template_footer #credit { padding: 22px 20px !important; }
}
@media screen and (max-width: 430px) {
    #body_content table.td th, #body_content table.td td { padding: 8px 5px !important; font-size: 13px; }
    #body_content table.td thead th { font-size: 12px; }
}
CSS;
    return $css;
}
add_filter('woocommerce_email_styles', 'kr_email_styles', 20, 2);

/**
 * This hook runs after WooCommerce's order summary, before its address block.
 * Gate by the actual email ID and context so Dokan and admin emails stay clear.
 */
function kr_email_buyer_return_policy($order, $sent_to_admin, $plain_text, $email): void
{
    if ($sent_to_admin || $plain_text || !is_object($email) || !isset($email->id)) {
        return;
    }

    $buyer_order_ids = [
        'customer_processing_order',
        'customer_completed_order',
        'customer_refunded_order',
        'customer_invoice',
        'customer_on_hold_order',
        'customer_failed_order',
        'customer_cancelled_order',
        'customer_note',
        'customer_pos_completed_order',
        'customer_pos_refunded_order',
    ];
    if (!in_array((string) $email->id, $buyer_order_ids, true)) {
        return;
    }

    echo '<p class="kr-email-return-policy"><a href="https://kniferevive.com/return-policy/">' . esc_html__('View KnifeRevive Return Policy', 'kniferevive') . '</a></p>';
}
add_action('woocommerce_email_after_order_table', 'kr_email_buyer_return_policy', 20, 4);

/** Mark email items while their template renders; frontend order tables stay unchanged. */
function kr_email_item_class(string $class, $item): string
{
    $class .= ' kr-email-line';
    if (function_exists('kr_is_technology_product') && kr_is_technology_product((int) $item->get_product_id())) {
        $class .= ' kr-email-tech';
    }
    return $class;
}
add_filter('woocommerce_email_order_items_args', static function (array $args): array {
    if (empty($args['plain_text'])) {
        add_filter('woocommerce_order_item_class', 'kr_email_item_class', 90, 2);
    }
    return $args;
});
add_filter('woocommerce_email_order_items_table', static function (string $html): string {
    remove_filter('woocommerce_order_item_class', 'kr_email_item_class', 90);
    return $html;
}, 90);

/** Append final inline styles after the normal inliner has styled upstream markup. */
function kr_email_inline(DOMElement $node, string $css): void
{
    $node->setAttribute('style', rtrim($node->getAttribute('style'), "; \t\r\n") . ';' . $css);
}

function kr_email_class(DOMElement $node, string $class): void
{
    $node->setAttribute('class', trim($node->getAttribute('class') . ' ' . $class));
}

/** Dark technology surfaces use the same black/white/yellow treatment as the site. */
function kr_email_dark_surface(DOMElement $node): void
{
    kr_email_inline($node, 'background-color:#050505!important;color:#ffffff!important;');
    if (in_array(strtolower($node->tagName), ['td', 'th', 'table'], true)) {
        $node->setAttribute('bgcolor', '#050505');
    }
    foreach ($node->getElementsByTagName('*') as $child) {
        if (!$child instanceof DOMElement || in_array($child->tagName, ['img', 'style', 'br'], true)) {
            continue;
        }
        kr_email_inline($child, 'color:#ffffff!important;background-color:#050505!important;border-color:#47574f!important;');
        if (in_array($child->tagName, ['table', 'td', 'th', 'address'], true)) {
            kr_email_inline($child, 'background-color:#050505!important;');
            $child->setAttribute('bgcolor', '#050505');
        }
        if ($child->tagName === 'a') {
            kr_email_inline($child, 'color:#f5ce42!important;');
            if (str_contains($child->getAttribute('class'), 'button') || (str_contains($child->getAttribute('style'), 'display:inline-block') && str_contains($child->getAttribute('style'), 'background:'))) {
                kr_email_inline($child, 'background-color:#f5ce42!important;border-color:#f5ce42!important;color:#050505!important;');
            }
        }
    }
}

/**
 * Keep each title full width even in clients that discard media queries.
 * Preserve the original product/meta hooks, refund markup, prices and totals.
 */
function kr_email_layout_document(DOMDocument $document, $email = null): void
{
    $xpath = new DOMXPath($document);
    $body = $xpath->query('//*[@id="body_content_inner"]')->item(0);
    if (!$body instanceof DOMElement || $body->hasAttribute('data-kr-email-layout')) {
        return;
    }
    $body->setAttribute('data-kr-email-layout', '2');
    // Dokan shipment/received emails render Product + Quantity directly,
    // bypassing WooCommerce's order-item class hook.
    foreach ($xpath->query('//*[@id="body_content_inner"]//table/thead/tr') as $headerRow) {
        $headers = $xpath->query('./th', $headerRow);
        if ($headers->length !== 2 || !in_array(trim($headers->item(0)->textContent), [__('Product', 'woocommerce'), __('Product', 'dokan')], true)
            || !in_array(trim($headers->item(1)->textContent), [__('Quantity', 'woocommerce'), __('Quantity', 'dokan')], true)) {
            continue;
        }
        $shipmentTable = $headerRow->parentNode->parentNode;
        foreach ($xpath->query('./tbody/tr[not(th)]', $shipmentTable) as $shipmentRow) {
            if ($xpath->query('./td', $shipmentRow)->length !== 2) {
                continue;
            }
            kr_email_class($shipmentRow, 'kr-email-line');
            foreach ($xpath->query('./td[1]//a[@href]', $shipmentRow) as $link) {
                $href = $link->getAttribute('href');
                if (function_exists('url_to_postid') && wp_parse_url($href, PHP_URL_HOST) === wp_parse_url(home_url('/'), PHP_URL_HOST)) {
                    $linkedId = url_to_postid($href);
                    if ($linkedId && function_exists('kr_is_technology_product') && kr_is_technology_product($linkedId)) {
                        kr_email_class($shipmentRow, 'kr-email-tech');
                    }
                }
            }
        }
    }
    $rows = iterator_to_array($xpath->query('//tr[contains(concat(" ", normalize-space(@class), " "), " kr-email-line ")]'));
    $techCount = 0;
    foreach ($rows as $row) {
        $cells = iterator_to_array($xpath->query('./td', $row));
        $columnCount = count($cells);
        if (!in_array($columnCount, [2, 3], true)) {
            continue;
        }
        $product = $cells[0];
        $quantity = $cells[1];
        $price = $cells[2] ?? null;
        $table = $row->parentNode;
        while ($table && !($table instanceof DOMElement && $table->tagName === 'table')) {
            $table = $table->parentNode;
        }
        if (!$table instanceof DOMElement) {
            continue;
        }
        kr_email_class($table, 'kr-email-items');
        kr_email_inline($table, 'width:100%!important;table-layout:fixed;border-collapse:collapse;');
        foreach (iterator_to_array($xpath->query('./thead', $table)) as $header) {
            $table->removeChild($header);
        }

        // The thumbnail must not reserve another column inside the title cell.
        foreach (iterator_to_array($xpath->query('.//table[contains(concat(" ", normalize-space(@class), " "), " order-item-data ")]', $product)) as $nested) {
            $flat = $document->createElement('div');
            foreach ($xpath->query('./tr/td|./tbody/tr/td', $nested) as $nestedCell) {
                $block = $document->createElement('div');
                kr_email_inline($block, 'margin:0 0 10px;');
                while ($nestedCell->firstChild) {
                    $block->appendChild($nestedCell->firstChild);
                }
                $flat->appendChild($block);
            }
            $nested->parentNode->replaceChild($flat, $nested);
        }
        $product->setAttribute('colspan', (string) $columnCount);
        $product->setAttribute('width', '100%');
        kr_email_class($product, 'kr-email-product');
        kr_email_inline($product, 'width:100%!important;padding:16px 14px 8px!important;text-align:left!important;font-size:16px!important;line-height:1.5!important;background-color:#f6f6f4;word-break:normal;overflow-wrap:anywhere;');
        $row->removeChild($quantity);
        if ($price) {
            $row->removeChild($price);
        }

        $metaRow = $document->createElement('tr');
        kr_email_class($metaRow, 'kr-email-item-amounts');
        $metaCell = $document->createElement('td');
        $metaCell->setAttribute('colspan', (string) $columnCount);
        kr_email_inline($metaCell, 'padding:0 14px 16px!important;background-color:#f6f6f4;border-bottom:1px solid #e5e5e2!important;');
        $metaTable = $document->createElement('table');
        foreach (['role' => 'presentation', 'width' => '100%', 'cellpadding' => '0', 'cellspacing' => '0', 'border' => '0'] as $name => $value) {
            $metaTable->setAttribute($name, $value);
        }
        kr_email_inline($metaTable, 'width:100%;table-layout:fixed;border:0;border-collapse:collapse;');
        $metaTr = $document->createElement('tr');
        foreach (array_filter([$quantity, $price]) as $index => $original) {
            $cell = $document->createElement('td');
            $cell->setAttribute('width', '50%');
            $cell->setAttribute('align', $index === 0 ? 'left' : 'right');
            kr_email_inline($cell, 'width:50%;padding:4px 0!important;border:0!important;vertical-align:top;font-size:14px;line-height:1.5;text-align:' . ($index === 0 ? 'left' : 'right') . '!important;');
            $labelText = $index === 0 ? __('Qty', 'kniferevive') . ' ' : __('Item total', 'kniferevive') . ' ';
            if ($index === 0 && $columnCount === 2 && str_contains($original->textContent, __('Qty', 'dokan'))) {
                $labelText = '';
            }
            $label = $document->createElement('span', $labelText);
            kr_email_inline($label, 'font-size:12px;font-weight:400;color:#626262;');
            $cell->appendChild($label);
            while ($original->firstChild) {
                $cell->appendChild($original->firstChild);
            }
            $metaTr->appendChild($cell);
        }
        $metaTable->appendChild($metaTr);
        $metaCell->appendChild($metaTable);
        $metaRow->appendChild($metaCell);
        $row->parentNode->insertBefore($metaRow, $row->nextSibling);
        if (str_contains(' ' . $row->getAttribute('class') . ' ', ' kr-email-tech ')) {
            $techCount++;
            kr_email_class($metaRow, 'kr-email-tech');
            kr_email_dark_surface($row);
            kr_email_dark_surface($metaRow);
        }
    }

    // A shipping address or pickup instruction gets most of the totals row.
    foreach ($xpath->query('//tr[contains(concat(" ", normalize-space(@class), " "), " order-totals ")]|//table[contains(concat(" ", normalize-space(@class), " "), " kr-email-items ")]/tfoot/tr|//table[contains(concat(" ", normalize-space(@class), " "), " kr-email-items ")]/tbody/tr[th]') as $totalRow) {
        $cells = $xpath->query('./th|./td', $totalRow);
        if ($cells->length === 2) {
            kr_email_inline($cells->item(0), 'width:34%!important;text-align:left!important;vertical-align:top;');
            kr_email_inline($cells->item(1), 'width:66%!important;text-align:right!important;vertical-align:top;');
        }
    }
    foreach ($xpath->query('//*[@id="body_content_inner"]//img') as $image) {
        kr_email_inline($image, 'max-width:100%!important;height:auto!important;');
    }
    foreach ($xpath->query('//*[@id="body_content_inner"]//ul[contains(concat(" ", normalize-space(@class), " "), " wc-item-meta ")]') as $meta) {
        kr_email_inline($meta, 'list-style:none!important;padding:0!important;margin:10px 0 0!important;font-size:13px;line-height:1.5;');
        foreach ($xpath->query('.//li', $meta) as $li) {
            kr_email_inline($li, 'margin:0 0 6px!important;padding:0!important;');
        }
        foreach ($xpath->query('.//strong|.//p', $meta) as $part) {
            kr_email_inline($part, 'float:none!important;clear:none!important;display:inline!important;margin:0!important;');
        }
    }

    // Reminder digests have heading/image/button groups rather than order rows.
    // Resolve only same-site public product links; management links stay outside.
    $linkedItems = 0;
    $linkedTech = 0;
    if (!$rows && function_exists('url_to_postid') && function_exists('wc_get_product')) {
        foreach (iterator_to_array($xpath->query('./h2', $body)) as $heading) {
            $group = [$heading];
            $productId = 0;
            for ($next = $heading->nextSibling; $next; $next = $next->nextSibling) {
                if ($next instanceof DOMElement && in_array($next->tagName, ['h1', 'h2', 'h3'], true)) {
                    break;
                }
                $group[] = $next;
                if (!$next instanceof DOMElement) {
                    continue;
                }
                foreach ($xpath->query('descendant-or-self::a[@href]', $next) as $link) {
                    $href = $link->getAttribute('href');
                    if (wp_parse_url($href, PHP_URL_HOST) !== wp_parse_url(home_url('/'), PHP_URL_HOST)) {
                        continue;
                    }
                    $candidate = url_to_postid($href);
                    $linkedProduct = $candidate ? wc_get_product($candidate) : false;
                    if ($linkedProduct && trim($heading->textContent) === trim(html_entity_decode(wp_strip_all_tags($linkedProduct->get_name()), ENT_QUOTES, 'UTF-8'))) {
                        $productId = $candidate;
                        break;
                    }
                }
                if ($productId) {
                    break;
                }
            }
            if (!$productId) {
                continue;
            }
            $card = $document->createElement('div');
            kr_email_class($card, 'kr-email-linked-item');
            kr_email_inline($card, 'padding:16px;margin:0 0 16px;background-color:#f6f6f4;border:1px solid #e5e5e2;');
            $heading->parentNode->insertBefore($card, $heading);
            foreach ($group as $part) {
                $card->appendChild($part);
            }
            $linkedItems++;
            if (function_exists('kr_is_technology_product') && kr_is_technology_product($productId)) {
                $linkedTech++;
                kr_email_dark_surface($card);
            }
        }
    }

    // Tech-only order messages use the site's full dark surface; mixed orders
    // keep individual Tech cards dark and the remaining receipt light.
    $allTech = count($rows) > 0 && $techCount === count($rows);
    if (!$rows && $linkedItems > 0) {
        $allTech = $linkedTech === $linkedItems;
    }
    if (!$rows && !$linkedItems && is_object($email) && isset($email->object) && $email->object instanceof WC_Order) {
        $items = $email->object->get_items();
        $allTech = count($items) > 0;
        foreach ($items as $item) {
            $allTech = $allTech && function_exists('kr_is_technology_product') && kr_is_technology_product((int) $item->get_product_id());
        }
    }
    if ($allTech) {
        foreach ($xpath->query('//*[@id="inner_wrapper" or @id="template_container" or @id="template_footer"]') as $surface) {
            kr_email_dark_surface($surface);
        }
        foreach ($xpath->query('//body|//*[@id="outer_wrapper"]') as $outer) {
            kr_email_inline($outer, 'background-color:#101815!important;');
            $outer->setAttribute('bgcolor', '#101815');
        }
    }
}

add_action('woocommerce_emogrifier', static function ($inliner, $email): void {
    kr_email_layout_document($inliner->getDomDocument(), $email);
}, 1000, 2);

/** Reminder HTML uses WooCommerce's wrapper but bypasses its normal send method. */
add_filter('wp_mail', static function (array $args): array {
    $html = (string) ($args['message'] ?? '');
    if (str_contains($html, 'id="body_content_inner"') && !str_contains($html, 'data-kr-email-layout=') && class_exists('WC_Email')) {
        $styler = new WC_Email();
        $styler->email_type = 'html';
        $args['message'] = $styler->style_inline($html);
    }
    return $args;
}, 50);

```

</details>


### Source: [email-footer.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/themes/kniferevive/woocommerce/emails/email-footer.php)

SHA-256: `5b59e83578fda173c84c12c2a9db51587085badb190e322237bcf27887a84576`. This is a local source reference, not a byte-for-byte live-server export.

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-footer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

$email = $email ?? null;

?>
																		</div>
																	</td>
																</tr>
															</table>
															<!-- End Content -->
														</td>
													</tr>
												</table>
												<!-- End Body -->
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Footer -->
									<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer" role="presentation">
										<tr>
											<td valign="top">
												<table border="0" cellpadding="10" cellspacing="0" width="100%" role="presentation">
													<tr>
														<td colspan="2" valign="middle" id="credit">
															<?php
															$email_footer_text = get_option( 'woocommerce_email_footer_text' );
															/**
															 * This filter is documented in templates/emails/email-styles.php
															 *
															 * @since 9.6.0
															 */
															if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
																$text_transient    = get_transient( 'woocommerce_email_footer_text' );
																$email_footer_text = false !== $text_transient ? $text_transient : $email_footer_text;
															}
															echo wp_kses_post(
																wpautop(
																	wptexturize(
																		/**
																		 * Provides control over the email footer text used for most order emails.
																		 *
																		 * @since 4.0.0
																		 *
																		 * @param string $email_footer_text
																		 */
																		apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email )
																	)
																)
															);
															?>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!-- End Footer -->
								</td>
							</tr>
						</table>
					</div>
					<!--[if mso]></td></tr></table><![endif]-->
				</td>
				<!-- KnifeRevive: the matching spacer cell was removed for mobile width. -->
			</tr>
		</table>
	</body>
</html>

```

</details>


### Source: [email-header.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/themes/kniferevive/woocommerce/emails/email-header.php)

SHA-256: `db001e93d81acdafb1a779597d8be9e28d38b3c048001589802dc78a2e0fc0d6`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- <img src="

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Email Header
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.7.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$store_name                 = $store_name ?? get_bloginfo( 'name', 'display' );

/**
 * Filter the URL used for the email header image/logo link.
 *
 * Return an empty string to disable the link.
 *
 * @since 10.7.0
 * @param string $url The URL to link to. Defaults to the site home URL.
 */
$header_image_url = apply_filters( 'woocommerce_email_header_image_url', home_url() );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( $store_name ); ?></title>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<table width="100%" id="outer_wrapper" role="presentation">
			<tr>
				<!-- KnifeRevive: let the center cell fit narrow email viewports. -->
				<td width="100%" style="max-width: 620px;">
					<!--[if mso]><table role="presentation" width="620" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper" role="presentation">
							<tr>
								<td align="center" valign="top">
									<?php
									$img = get_option( 'woocommerce_email_header_image' );
									/**
									 * This filter is documented in templates/emails/email-styles.php
									 *
									 * @since 9.6.0
									 */
									if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
										$img_transient = get_transient( 'woocommerce_email_header_image' );
										$img           = false !== $img_transient ? $img_transient : $img;
									}

									if ( $email_improvements_enabled ) :
										?>
										<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
											<tr>
												<td id="template_header_image">
													<?php
													if ( $img ) {
														$image_html = '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $store_name ) . '" />';
														if ( $header_image_url ) {
															// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $image_html is built from esc_url() and esc_attr().
															echo '<p style="margin-top:0;"><a href="' . esc_url( $header_image_url ) . '" style="display: inline-block; text-decoration: none;" target="_blank">' . $image_html . '</a></p>';
														} else {
															// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
															echo '<p style="margin-top:0;">' . $image_html . '</p>';
														}
													} elseif ( $header_image_url ) {
														echo '<p class="email-logo-text"><a href="' . esc_url( $header_image_url ) . '" style="color: inherit; text-decoration: none;" target="_blank">' . esc_html( $store_name ) . '</a></p>';
													} else {
														echo '<p class="email-logo-text">' . esc_html( $store_name ) . '</p>';
													}
													?>
												</td>
											</tr>
										</table>
									<?php else : ?>
										<div id="template_header_image">
											<?php
											if ( $img ) {
												$image_html = '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $store_name ) . '" />';
												if ( $header_image_url ) {
													// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $image_html is built from esc_url() and esc_attr().
													echo '<p style="margin-top:0;"><a href="' . esc_url( $header_image_url ) . '" style="display: inline-block; text-decoration: none;" target="_blank">' . $image_html . '</a></p>';
												} else {
													// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													echo '<p style="margin-top:0;">' . $image_html . '</p>';
												}
											}
											?>
										</div>
									<?php endif; ?>
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" role="presentation">
										<tr>
											<td align="center" valign="top">
												<!-- Header -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" role="presentation">
													<tr>
														<td id="header_wrapper">
															<h1><?php echo esc_html( $email_heading ); ?></h1>
														</td>
													</tr>
												</table>
												<!-- End Header -->
											</td>
										</tr>
										<tr>
											<td align="center" valign="top">
												<!-- Body -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation">
													<tr>
														<td valign="top" id="body_content">
															<!-- Content -->
															<table border="0" cellpadding="20" cellspacing="0" width="100%" role="presentation">
																<tr>
																	<td valign="top" id="body_content_inner_cell">
														<div id="body_content_inner">

```

</details>
