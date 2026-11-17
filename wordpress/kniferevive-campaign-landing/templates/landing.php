<?php
/** Read-only campaign content, within the normal WordPress Page header/footer. */
namespace KnifeRevive\CampaignLanding;
defined('ABSPATH') || exit;
?>
<div class="kr-campaign" data-kr-campaign-page>
  <section class="kr-campaign-hero" id="discover" aria-labelledby="kr-campaign-title">
    <div class="kr-campaign-shell kr-campaign-hero-grid">
      <div class="kr-campaign-intro">
        <p class="kr-campaign-eyebrow"><span aria-hidden="true"></span> KnifeRevive · Buy + sell marketplace</p>
        <h1 id="kr-campaign-title">Your next find.<br><span>Your first sale.</span></h1>
        <p class="kr-campaign-hero-line">Buy &amp; sell kitchen knives, tech + more.</p>
        <p class="kr-campaign-lead">Find useful gear, distinctive pieces and items worth keeping. KnifeRevive brings buyers and independent sellers together — and gives new sellers a place to start.</p>
        <div class="kr-campaign-actions"><?php echo self::button("Explore what's for sale",'/shop/','browse_click'); ?><?php echo self::button('Start your shop','/sell/','seller_signup_click','kr-campaign-button-light'); ?></div>
        <a class="kr-campaign-text-link" href="#dispatch" data-kr-campaign-event="dispatch_explore_click">See how Dispatch works <span aria-hidden="true">↓</span></a>
        <ul class="kr-campaign-small-print" aria-label="Shopping details"><li>Seller photos</li><li>Real item details</li><li>Checkout on KnifeRevive</li></ul>
      </div>
      <div class="kr-campaign-collage" aria-label="The real items featured in our campaign">
        <?php foreach($products as $key=>$p): ?>
          <a class="kr-campaign-photo-card kr-campaign-photo-card-<?php echo esc_attr($key); ?>" href="<?php echo esc_url($p['url']); ?>" data-kr-campaign-event="featured_product_click" data-kr-campaign-slot="<?php echo esc_attr($key); ?>" aria-label="<?php echo esc_attr(($p['available']?'View item: ':'Explore similar to ').$p['label']); ?>">
            <div class="kr-campaign-photo"><?php echo self::image($key,true); ?></div>
            <div class="kr-campaign-photo-caption"><span><?php echo esc_html($p['label']); ?></span><span class="kr-campaign-arrow" aria-hidden="true">↗</span></div>
          </a>
        <?php endforeach; ?>
        <p class="kr-campaign-collage-note">Actual seller photographs. Availability changes.</p>
      </div>
    </div>
    <div class="kr-campaign-shell"><a class="kr-campaign-dispatch-strip" href="#dispatch" data-kr-campaign-event="dispatch_explore_click"><span><strong>Meet Dispatch.</strong> Sellers helping sellers ship.</span><span aria-hidden="true">↗</span></a></div>
  </section>

  <section class="kr-campaign-section kr-campaign-shell" aria-labelledby="kr-campaign-marketplace">
    <div class="kr-campaign-section-head"><p class="kr-campaign-eyebrow">Good things. Another chapter.</p><h2 id="kr-campaign-marketplace">A marketplace for the things<br class="kr-campaign-desktop-break"> worth passing on.</h2><p>It started with the idea that a good kitchen knife can have another chapter. Today, you’ll also find technology, books, original art, coins, regional pantry finds and more. Browse what sellers have listed, check the details, or give something of yours a new home.</p></div>
    <div class="kr-campaign-split">
      <article class="kr-campaign-path-card"><span class="kr-campaign-number" aria-hidden="true">01 / FIND</span><h3>Here to find something?</h3><p>Search listings, compare photos and condition, then purchase through the normal KnifeRevive checkout.</p><?php echo self::button('Browse listings','/shop/','browse_click','kr-campaign-button-light'); ?></article>
      <article class="kr-campaign-path-card kr-campaign-path-sell"><span class="kr-campaign-number" aria-hidden="true">02 / SELL</span><h3>Here to sell something?</h3><p>Create your shop, add your own items with clear photos and descriptions, and manage listings in My Listings.</p><?php echo self::button('Become a seller','/sell/','seller_signup_click'); ?></article>
    </div>
  </section>

  <section class="kr-campaign-section kr-campaign-featured" id="featured" aria-labelledby="kr-campaign-finds">
    <div class="kr-campaign-shell"><div class="kr-campaign-heading-row"><div><p class="kr-campaign-eyebrow">Seen in the campaign</p><h2 id="kr-campaign-finds">Real finds.<br>Real listing details.</h2></div><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/shop/')); ?>" data-kr-campaign-event="browse_click">Shop all finds <span aria-hidden="true">↗</span></a></div>
      <div class="kr-campaign-product-grid">
        <?php foreach($products as $key=>$p): ?>
        <article class="kr-campaign-item"><div class="kr-campaign-item-image"><?php echo self::image($key); ?></div><div class="kr-campaign-item-body"><p class="kr-campaign-eyebrow"><?php echo $p['available']?'Seller-listed item':'Availability changes'; ?></p><h3><?php echo esc_html($p['title']); ?></h3><p><?php echo $p['available']?'See the full description, condition and what’s included.':'This campaign item is not currently available. Explore the category for other listings.'; ?></p><a class="kr-campaign-item-link" href="<?php echo esc_url($p['url']); ?>" data-kr-campaign-event="featured_product_click" data-kr-campaign-slot="<?php echo esc_attr($key); ?>"><?php echo $p['available']?'View item':'Explore similar'; ?><span aria-hidden="true">↗</span></a></div></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="kr-campaign-section kr-campaign-shell" aria-labelledby="kr-campaign-categories">
    <div class="kr-campaign-heading-row"><div><p class="kr-campaign-eyebrow">Follow your curiosity</p><h2 id="kr-campaign-categories">More than the knife drawer.</h2></div><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/categories/')); ?>" data-kr-campaign-event="browse_click">Explore categories <span aria-hidden="true">↗</span></a></div>
    <div class="kr-campaign-category-grid">
    <?php foreach(self::categories() as $cat): ?>
      <a class="kr-campaign-category kr-campaign-category-<?php echo esc_attr($cat['icon']); ?>" href="<?php echo esc_url($cat['url']); ?>" data-kr-campaign-event="browse_click"><span class="kr-campaign-category-mark" aria-hidden="true"><?php echo esc_html(['knife'=>'↗','tech'=>'⌘','book'=>'Aa','art'=>'✳','finds'=>'+'][$cat['icon']]); ?></span><h3><?php echo esc_html($cat['label']); ?></h3><p><?php echo esc_html($cat['live']?$cat['copy']:'Explore current categories and see what sellers have listed.'); ?></p><span class="kr-campaign-category-link"><?php echo $cat['live']?'Take a look':'Explore categories'; ?> <span aria-hidden="true">↗</span></span></a>
    <?php endforeach; ?>
    </div>
  </section>

  <section class="kr-campaign-section kr-campaign-seller" aria-labelledby="kr-campaign-sell">
    <div class="kr-campaign-shell"><div class="kr-campaign-section-head"><p class="kr-campaign-eyebrow">From your shelf to someone’s next find</p><h2 id="kr-campaign-sell">Got something worth listing?</h2><p>Start with a shop. Add photos. Tell buyers what they should know. You’re on your way.</p></div>
      <ol class="kr-campaign-steps"><li><span aria-hidden="true">01</span><h3>Make your account</h3><p>Follow seller setup. Choose a passkey or password where supported.</p></li><li><span aria-hidden="true">02</span><h3>Set up your shop</h3><p>Choose your storefront details in the existing registration flow.</p></li><li><span aria-hidden="true">03</span><h3>List the real item</h3><p>Add photos, brand or model, condition, included pieces, shipping and returns.</p></li><li><span aria-hidden="true">04</span><h3>Manage the sale</h3><p>Handle fulfillment and your order updates through the seller workspace.</p></li></ol>
      <div class="kr-campaign-actions"><?php echo self::button('Start selling on KnifeRevive','/sell/','seller_signup_click'); ?><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/listing-guide/')); ?>">See listing guide <span aria-hidden="true">↗</span></a></div><p class="kr-campaign-fine">Already an eligible seller? <a href="<?php echo esc_url(self::url('/my-account/listlab/')); ?>">Open ListLab</a>. <a href="<?php echo esc_url(self::url('/terms-and-conditions/',false)); ?>">See current seller terms &amp; fees</a>.</p>
    </div>
  </section>

  <section class="kr-campaign-section kr-campaign-dispatch" id="dispatch" aria-labelledby="kr-campaign-dispatch-title">
    <div class="kr-campaign-shell"><div class="kr-campaign-dispatch-grid"><div><p class="kr-campaign-eyebrow">Seller tool · Dispatch</p><span class="kr-campaign-status">Feature in development</span><h2 id="kr-campaign-dispatch-title">Your sale.<br>A little backup.</h2><p class="kr-campaign-dispatch-lead">Meet Dispatch.<br>Sellers helping sellers ship.</p><p>Sometimes another seller may have an eligible matching item and be better positioned to fulfill an order. Dispatch is designed to help sellers explore a handoff where marketplace rules allow it. Any handoff must be checked, accepted and handled through the approved order workflow.</p><p class="kr-campaign-dispatch-note">Live handoffs are not available through this page. Participation, matching items and fulfillment depend on current marketplace rules. No guaranteed arrival time or earnings.</p></div>
      <figure class="kr-campaign-dispatch-art"><?php echo self::image('dispatch'); ?><div class="kr-campaign-art-labels" aria-hidden="true"><span>Your order</span><span>Another seller fulfills</span></div><figcaption>Conceptual illustration — not an order screen.</figcaption></figure></div>
      <ol class="kr-campaign-route"><li><span>01</span>An order needs fulfillment</li><li><span>02</span>Check matching stock and seller availability</li><li><span>03</span>Confirm a permitted handoff</li><li><span>04</span>Participating seller ships; buyer receives order updates</li></ol>
      <div class="kr-campaign-actions"><?php echo self::button('See current seller setup','/sell/','seller_signup_click','kr-campaign-button-lime'); ?><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/help/dispatch-terms/',false)); ?>">Read Dispatch terms <span aria-hidden="true">↗</span></a><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/terms-and-conditions/',false)); ?>">Marketplace rules <span aria-hidden="true">↗</span></a></div>
    </div>
  </section>

  <section class="kr-campaign-section kr-campaign-shell kr-campaign-trust" aria-labelledby="kr-campaign-details"><div><p class="kr-campaign-eyebrow">A little context goes a long way</p><h2 id="kr-campaign-details">Details matter.</h2><p>Look for seller photos, stated condition, what’s included, shipping information, return terms and seller history where available. Used items have stories; clear descriptions help you decide.</p><p class="kr-campaign-fine">Knife grades don’t independently verify the tip, edge or handle. For tech, functional testing is different from cosmetic appearance. Read the actual listing.</p><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/help/',false)); ?>">Visit Help <span aria-hidden="true">↗</span></a></div><div class="kr-campaign-trust-list"><article><span aria-hidden="true">01</span><h3>Condition and wear, up close</h3><p>Compare the photos and seller-reported details. <a href="<?php echo esc_url(self::url('/listing-guide/',false)); ?>">Read the listing guide</a>.</p></article><article><span aria-hidden="true">02</span><h3>Who you’re buying from</h3><p>Open the seller’s storefront from a listing to learn more.</p></article><article><span aria-hidden="true">03</span><h3>Shipping and return terms</h3><p>Check the terms on each item before buying. <a href="<?php echo esc_url(self::url('/return-policy/',false)); ?>">Return policy</a>.</p></article><article><span aria-hidden="true">04</span><h3>A normal shopping checkout</h3><p>Make your purchase through the existing KnifeRevive checkout.</p></article></div></section>

  <section class="kr-campaign-section kr-campaign-faq"><div class="kr-campaign-shell kr-campaign-faq-grid"><div><p class="kr-campaign-eyebrow">Good questions</p><h2>Before you<br>jump in.</h2><a class="kr-campaign-text-link" href="<?php echo esc_url(self::url('/contact/',false)); ?>">Need a hand? <span aria-hidden="true">↗</span></a></div><div>
    <?php foreach([
      'Is KnifeRevive only for knives?'=>'No. The marketplace includes kitchen knives plus tech, books, art and other changing categories.',
      'Can I shop without becoming a seller?'=>'Yes. Browse available items and check the seller’s product information before purchasing.',
      'How do I start selling?'=>'Use seller setup to create an account and shop, then add your own listings with photos and honest descriptions. Seller eligibility and payout setup follow current platform rules.',
      'Can I sell used items?'=>'Many categories feature pre-owned goods. Follow the category rules, accurately describe condition, and list only permitted items you can legally sell.',
      'What is Dispatch?'=>'A seller-to-seller fulfillment handoff feature in development. Availability, participation and any compensation depend on current marketplace rules and feature status.',
      'Does KnifeRevive guarantee every item’s condition?'=>'No. Read the listing’s seller-reported details, photos, tests (if supplied) and terms before buying.',
    ] as $q=>$a): ?><details><summary><?php echo esc_html($q); ?><span aria-hidden="true">+</span></summary><p><?php echo esc_html($a); ?></p></details><?php endforeach; ?>
    <details><summary>What fees do sellers pay?<span aria-hidden="true">+</span></summary><p>See <a href="<?php echo esc_url(self::url('/terms-and-conditions/',false)); ?>">current seller terms and fees</a> for introductory periods, category fees and payment-processing costs.</p></details>
    </div></div></section>

  <section class="kr-campaign-final"><div class="kr-campaign-shell"><p class="kr-campaign-eyebrow">Find it. List it. Give it another chapter.</p><h2>Find your next thing.<br>Or sell your first.</h2><div class="kr-campaign-actions"><?php echo self::button('Explore KnifeRevive','/categories/','browse_click'); ?><?php echo self::button('Start a shop','/sell/','seller_signup_click','kr-campaign-button-light'); ?></div><nav class="kr-campaign-legal" aria-label="Campaign help"><a href="<?php echo esc_url(self::url('/categories/',false)); ?>">How to browse</a><a href="<?php echo esc_url(self::url('/terms-and-conditions/',false)); ?>">Terms &amp; fees</a><a href="<?php echo esc_url(self::url('/contact/',false)); ?>">Contact</a><a href="<?php echo esc_url(self::url('/help/privacy-policy/',false)); ?>">Privacy</a></nav></div></section>
</div>
