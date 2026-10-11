<?php
/**
 * Curated KnifeRevive category directory.
 *
 * @package KnifeRevive
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const KR_CATEGORIES_PAGE_SCHEMA_VERSION = '2026.09.10.1';

/**
 * Create the Categories page once without overwriting owner edits thereafter.
 */
function kr_ensure_categories_page(): void
{
    if (get_option('kr_categories_page_schema_version') === KR_CATEGORIES_PAGE_SCHEMA_VERSION) {
        return;
    }

    $page = get_page_by_path('categories', OBJECT, 'page');
    if (!$page instanceof WP_Post) {
        $pageId = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Explore KnifeRevive',
            'post_name' => 'categories',
            'post_content' => '<!-- wp:shortcode -->[kniferevive_categories]<!-- /wp:shortcode -->',
        ], true);

        if (is_wp_error($pageId) || (int) $pageId < 1) {
            return;
        }
        flush_rewrite_rules(false);
    }

    update_option('kr_categories_page_schema_version', KR_CATEGORIES_PAGE_SCHEMA_VERSION, false);
}
add_action('init', 'kr_ensure_categories_page', 31);

/**
 * Render a card image from an existing attachment or bundled theme asset.
 *
 * @param array<string, string> $attributes
 */
function kr_categories_image(int $attachmentId, string $themePath, string $alt, array $attributes = []): string
{
    $attributes = array_merge([
        'alt' => $alt,
        'loading' => 'lazy',
        'decoding' => 'async',
    ], $attributes);

    if ($attachmentId > 0 && get_post_type($attachmentId) === 'attachment') {
        return wp_get_attachment_image($attachmentId, 'large', false, $attributes);
    }

    return kr_theme_image($themePath, $alt, $attributes);
}

/**
 * Render the public Categories page inside its editable WordPress Page record.
 */
function kr_categories_shortcode(): string
{
    $sharpeningAttachmentId = (int) get_option('kr_categories_sharpening_attachment_id', 0);
    $kitchenImage = kr_categories_image(1631, '', 'Cutco kitchen knife on a wooden cutting board');
    $artImage = kr_categories_image(2366, '', 'Abstract modernist artwork featured in the KnifeRevive Art Trade Gallery');
    $sharpeningImage = kr_categories_image($sharpeningAttachmentId, 'assets/images/categories/Knife_Sharpening.png', 'Professional kitchen knife being sharpened on a belt sharpening system', ['loading' => 'eager', 'fetchpriority' => 'high']);
    $technologyImage = kr_categories_image(0, 'assets/images/technology/Openclaw2_Feature.png', 'Self-hosted AI computer system featured in KnifeRevive Technology Trade Desk');
    $otherFindsImage = kr_categories_image(0, 'assets/images/other-finds/coins.png', 'World coins and regional spices featured in KnifeRevive Other Finds');
    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $sellerUrl = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('listlab') : home_url('/my-account/listlab/');

    ob_start();
    ?>
    <main class="krev-categories">
        <header class="krev-categories__intro">
            <p class="krev-categories__eyebrow">KNIFEREVIVE CATEGORIES</p>
            <h1>Explore KnifeRevive.</h1>
            <p>From working kitchen knives to original art, useful technology, world coins, trading cards, regional pantry finds, and home and garden pieces, explore the collections that make up KnifeRevive.</p>
            <p>Some are practical, some collectible, and some simply caught our interest — but each has a place here for a reason.</p>
        </header>

        <section class="krev-categories__grid" aria-label="Explore KnifeRevive categories">
            <article class="krev-category-card">
                <a class="krev-category-card__image" href="<?php echo esc_url(home_url('/#knife-sharpening')); ?>"><?php echo $sharpeningImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">KNIFE SHARPENING</p><h2><a href="<?php echo esc_url(home_url('/#knife-sharpening')); ?>">Professional sharpening for the knives you already own.</a></h2><p class="krev-category-card__description">Bring tired kitchen knives back to a clean, precise working edge with local professional sharpening for customers across the San Francisco Bay Area.</p><a class="krev-category-card__cta" href="<?php echo esc_url(home_url('/#knife-sharpening')); ?>">Sharpen Your Knives <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="krev-category-card">
                <a class="krev-category-card__image" href="<?php echo esc_url($shopUrl); ?>"><?php echo $kitchenImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">KITCHEN KNIVES</p><h2><a href="<?php echo esc_url($shopUrl); ?>">Give good knives another life.</a></h2><p class="krev-category-card__description">Shop pre-owned chef's cutlery by knife type, brand, model number, condition, and price — from everyday working knives to pieces made to stay in the kitchen for decades.</p><a class="krev-category-card__cta" href="<?php echo esc_url($shopUrl); ?>">Shop Kitchen Knives <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="krev-category-card">
                <a class="krev-category-card__image" href="<?php echo esc_url(home_url('/art-trade-gallery/')); ?>"><?php echo $artImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">FINE ART</p><h2><a href="<?php echo esc_url(home_url('/art-trade-gallery/')); ?>">Original works and artists we want to share.</a></h2><p class="krev-category-card__description">Discover original painting, sculpture, and other works selected for the KnifeRevive Art Trade Gallery, including featured artists and one-of-a-kind pieces.</p><a class="krev-category-card__cta" href="<?php echo esc_url(home_url('/art-trade-gallery/')); ?>">Explore Fine Art <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="krev-category-card krev-category-card--technology">
                <a class="krev-category-card__image" href="<?php echo esc_url(home_url('/technology-trade-desk/')); ?>"><?php echo $technologyImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">TECH</p><h2><a href="<?php echo esc_url(home_url('/technology-trade-desk/')); ?>">Practical technology, with another life ahead.</a></h2><p class="krev-category-card__description">Explore computers and technology configured around useful, self-hosted AI tools, local computing, experimentation, and automation.</p><a class="krev-category-card__cta" href="<?php echo esc_url(home_url('/technology-trade-desk/')); ?>">Explore Tech <span aria-hidden="true">→</span></a>
                    <div class="krev-category-card__children krev-category-card__tech-metas">
                        <?php foreach (['iphones', 'laptops', 'mini-computers', 'bluetooth-gadgets', 'ai-tech'] as $metaSlug) : $metaTitle = kr_metacategories()[$metaSlug]['label']; ?>
                        <section class="krev-category-child"><a class="krev-category-child__image" href="<?php echo esc_url(kr_metacategory_landing_url($metaSlug)); ?>"><?php echo kr_metacategory_image($metaSlug); ?></a><div><h3><?php echo esc_html($metaTitle); ?></h3><p><?php echo esc_html(kr_metacategory_description($metaSlug)); ?></p><a href="<?php echo esc_url(kr_metacategory_landing_url($metaSlug)); ?>">Browse <?php echo esc_html($metaTitle); ?> <span aria-hidden="true">→</span></a></div></section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
            <article class="krev-category-card krev-category-card--home-garden">
                <a class="krev-category-card__image" href="<?php echo esc_url(kr_metacategory_landing_url('home-garden')); ?>"><?php echo kr_metacategory_image('home-garden'); ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">HOME &amp; GARDEN</p><h2><a href="<?php echo esc_url(kr_metacategory_landing_url('home-garden')); ?>">Useful finds for the places you live.</a></h2><p class="krev-category-card__description">Explore pieces for the home, kitchen, patio, and garden—from everyday essentials to useful objects ready for another home.</p><a class="krev-category-card__cta" href="<?php echo esc_url(kr_metacategory_landing_url('home-garden')); ?>">Browse Home &amp; Garden <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="krev-category-card krev-category-card--other-finds">
                <a class="krev-category-card__image" href="<?php echo esc_url(home_url('/other-finds/')); ?>"><?php echo $otherFindsImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">OTHER FINDS</p><h2><a href="<?php echo esc_url(home_url('/other-finds/')); ?>">Useful, collectible, and interesting things from beyond the knife drawer.</a></h2><p class="krev-category-card__description">A changing collection of international finds, small pieces of history, regional pantry flavors, and other objects that do not belong in KnifeRevive's knife, art, or technology collections.</p><a class="krev-category-card__cta" href="<?php echo esc_url(home_url('/other-finds/')); ?>">Explore Other Finds <span aria-hidden="true">→</span></a>
                    <div class="krev-category-card__children">
                        <section class="krev-category-child"><h3>World Coins</h3><p>Collectible coins from around the world — organized by country, denomination, year, composition, and condition.</p><a href="<?php echo esc_url(home_url('/product-category/world-coins/')); ?>">Browse World Coins <span aria-hidden="true">→</span></a></section>
                        <section class="krev-category-child"><h3>World Spices</h3><p>Packaged herbs, spices, and seasoning blends inspired by the everyday cooking traditions of Eastern Europe, the Balkans, Greece, and surrounding regions.</p><a href="<?php echo esc_url(home_url('/product-category/world-spices/')); ?>">Browse World Spices <span aria-hidden="true">→</span></a></section>
                    </div>
                </div>
            </article>
            <article class="krev-category-card krev-category-card--collectibles">
                <a class="krev-category-card__image" href="<?php echo esc_url(kr_metacategory_landing_url('collectibles')); ?>"><?php echo kr_metacategory_image('collectibles'); ?></a>
                <div class="krev-category-card__copy"><p class="krev-category-card__eyebrow">COLLECTIBLES</p><h2><a href="<?php echo esc_url(kr_metacategory_landing_url('collectibles')); ?>">Small pieces, lasting stories.</a></h2><p class="krev-category-card__description">Explore trading cards, stamps, scale models, memorabilia, and collectible finds, organized by their actual product category.</p><a class="krev-category-card__cta" href="<?php echo esc_url(kr_metacategory_landing_url('collectibles')); ?>">Browse Collectibles <span aria-hidden="true">→</span></a>
                    <div class="krev-category-card__children krev-category-card__tech-metas"><section class="krev-category-child"><a class="krev-category-child__image" href="<?php echo esc_url(kr_metacategory_landing_url('pokemon-cards')); ?>"><?php echo kr_metacategory_image('pokemon-cards'); ?></a><div><h3>Pokémon Cards</h3><p>Individual cards and card collections, with set, card number, rarity, finish, and grade recorded when known.</p><a href="<?php echo esc_url(kr_metacategory_landing_url('pokemon-cards')); ?>">Browse Pokémon Cards <span aria-hidden="true">→</span></a></div></section></div>
                </div>
            </article>
        </section>

        <section class="krev-categories__closing"><p class="krev-categories__eyebrow">A PRACTICAL MARKETPLACE</p><h2>Find something worth keeping.</h2><p>Whether you're replacing a kitchen knife, discovering an original work of art, setting up a self-hosted AI system, collecting coins, or looking for a familiar regional flavor, browse the part of KnifeRevive that interests you.</p><div class="krev-categories__actions"><a class="krev-categories__button" href="<?php echo esc_url($shopUrl); ?>">Shop All <span aria-hidden="true">→</span></a><a class="krev-categories__text-link" href="<?php echo esc_url($sellerUrl); ?>">Become a Seller <span aria-hidden="true">→</span></a></div></section>
    </main>
    <?php
    return kr_compact_shortcode_markup((string) ob_get_clean());
}
add_shortcode('kniferevive_categories', 'kr_categories_shortcode');

add_filter('body_class', static function (array $classes): array {
    if (is_page('categories')) {
        $classes[] = 'krev-categories-page';
    }
    return $classes;
}, 20);

add_action('wp_head', static function (): void {
    if (is_page('categories')) {
        echo '<meta name="description" content="Explore KnifeRevive categories: kitchen knives, sharpening, art, technology, collectibles, Pokémon cards, world coins, spices, home and garden, and other curated finds.">' . "\n";
    }
}, 5);
