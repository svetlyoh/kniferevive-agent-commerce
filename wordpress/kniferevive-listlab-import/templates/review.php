<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Finish your listing · KnifeRevive</title><link rel="stylesheet" href="<?php echo esc_url( KREV_IMPORT_URL . 'assets/review.css?ver=' . KREV_IMPORT_VERSION ); ?>"></head><body>
<main class="krev-import-review">
  <h1>Finish your Marketplace listing</h1>
  <p>Review the details copied by Muse. Your KnifeRevive price uses the store’s import pricing rule. Shipping is calculated separately at checkout.</p>
  <p id="krev-import-message" role="status" aria-live="polite">Loading your private import…</p>
  <p id="krev-import-login" hidden><a class="button" href="<?php echo esc_url( add_query_arg( 'redirect_to', add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) ), wc_get_page_permalink( 'myaccount' ) ) ); ?>">Sign in to finish this listing</a></p>
  <form id="krev-import-form" hidden>
    <p><a id="krev-import-source" target="_blank" rel="noopener noreferrer">Original Facebook item</a></p>
    <div class="krev-import-prices"><p>Facebook price <strong id="krev-source-price"></strong></p><p>KnifeRevive item price <strong id="krev-target-price"></strong><br><small>+ native shipping at checkout</small></p></div>
    <label>KnifeRevive category <select id="krev-import-category" required></select></label>
    <button id="krev-import-refresh" type="button">Refresh price</button>
    <label>Listing title <input id="krev-import-title" maxlength="200" required></label>
    <label>Description <textarea id="krev-import-description" rows="7" maxlength="16000"></textarea></label>
    <label>Quantity you have available <input id="krev-import-quantity" type="number" min="0" step="1" placeholder="Complete in ListLab if unknown"></label>
    <p>Blank shipping fields use the store defaults: 15 oz packaged weight and 1 × 6 × 4 inches (length × width × height). Check or replace them in ListLab; entered values take priority.</p>
    <div id="krev-import-photos" class="krev-import-photos"></div>
    <p><small>Photos are copied only after you create your seller draft. If a Facebook image link expires, add that photo in ListLab.</small></p>
    <details><summary>Specifications copied by Muse</summary><pre id="krev-import-specs"></pre></details>
    <label class="krev-import-consent"><input id="krev-import-rights" type="checkbox" required> I can sell this item and use its description and photos.</label>
    <button id="krev-import-create" type="submit">Create ListLab draft</button>
  </form>
  <div id="krev-import-result" hidden></div>
  <p>You’ll choose shipping and returns, check the remaining specifications and publish from ListLab. Nothing is published from this page.</p>
</main>
<script>window.KREVImport = <?php echo wp_json_encode( $client, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;</script>
<script src="<?php echo esc_url( KREV_IMPORT_URL . 'assets/review.js?ver=' . KREV_IMPORT_VERSION ); ?>" defer></script>
</body></html>
