<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Finish your listing · KnifeRevive</title><link rel="stylesheet" href="<?php echo esc_url( KREV_IMPORT_URL . 'assets/review.css?ver=' . KREV_IMPORT_VERSION ); ?>"></head><body>
<main class="krev-import-review">
  <h1 id="krev-import-heading">Marketplace inbox</h1>
  <p><a href="<?php echo esc_url( KREV_Import_Inbox::url() ); ?>">Marketplace inbox</a> · <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'listlab' ) ); ?>">My ListLab listings</a></p>
  <p>Review the details copied by Muse. Your KnifeRevive price uses the store’s import pricing rule. Shipping is calculated separately at checkout.</p>
  <p id="krev-import-message" role="status" aria-live="polite">Loading your private import…</p>
  <p id="krev-import-login" hidden><a class="button" href="<?php echo esc_url( add_query_arg( 'redirect_to', KREV_Marketplace_Imports::requested_review_url(), wc_get_page_permalink( 'myaccount' ) ) ); ?>">Sign in to your seller account</a></p>
  <section id="krev-inbox" hidden aria-label="Your Marketplace inbox">
    <p>Muse sends items here. Open an item to create its private draft, then finish it in ListLab. Waiting items stay until you complete or archive them.</p>
    <div id="krev-inbox-tabs" class="krev-inbox-actions" aria-label="Inbox filters"></div>
    <p id="krev-inbox-count"></p><div id="krev-inbox-items"></div>
    <div class="krev-inbox-actions"><button id="krev-inbox-prev" type="button">Previous page</button><span id="krev-inbox-page"></span><button id="krev-inbox-next" type="button">Next page</button><button id="krev-inbox-refresh" type="button">Refresh inbox</button></div>
    <details id="krev-inbox-settings"><summary>Connect Muse to my inbox</summary>
      <p id="krev-inbox-connection"></p>
      <p>A private delivery key lets Muse send items to this account. It cannot read your inbox or create, edit or publish products. Reconnecting replaces the key; existing items stay.</p>
      <form id="krev-inbox-connect">
        <label class="krev-import-consent"><input id="krev-inbox-authorize" type="checkbox" required checked> Allow Muse to queue items in my inbox.</label>
        <label class="krev-import-consent"><input id="krev-inbox-preserve" type="checkbox"> Preserve supplied photos now in my media library. I authorize sending these items and using their photos.</label>
        <p>Preserving photos avoids expired Facebook image links while items wait. Up to ten photos per item. Without it, photos copy when you create the draft.</p>
        <button type="submit">Create / replace Muse connection</button>
        <button id="krev-inbox-disconnect" type="button" hidden>Disconnect Muse</button>
      </form>
      <div id="krev-inbox-key-box" hidden><label>Private setup prompt — shown once<textarea id="krev-inbox-key" rows="7" readonly></textarea></label><button id="krev-inbox-copy" type="button">Copy private setup prompt</button><p>Paste privately into Muse. Keep this key out of listing text, URLs, screenshots and public posts.</p></div>
    </details>
  </section>
  <form id="krev-import-form" hidden>
    <p><a id="krev-import-source" target="_blank" rel="noopener noreferrer">Original Facebook item</a></p>
    <div class="krev-import-prices"><p>Facebook price <strong id="krev-source-price"></strong></p><p>KnifeRevive item price <strong id="krev-target-price"></strong><br><small>+ native shipping at checkout</small></p></div>
    <label>KnifeRevive category <select id="krev-import-category" required></select></label>
    <button id="krev-import-refresh" type="button">Refresh price</button>
    <label>Listing title <input id="krev-import-title" maxlength="200" required></label>
    <label>Description <textarea id="krev-import-description" rows="7" maxlength="16000"></textarea></label>
    <label>Quantity you have available <input id="krev-import-quantity" type="number" min="0" step="1" placeholder="1"></label>
    <p>Quantity defaults to 1 when omitted. Check the number you have available; this is a store default, not a verified Facebook stock count.</p>
    <p>Blank shipping fields use the store defaults: 15 oz packaged weight and 1 × 6 × 4 inches (length × width × height). Check or replace them in ListLab; entered values take priority.</p>
    <div id="krev-import-photos" class="krev-import-photos"></div>
    <p><small>Preserved inbox photos are reused. Other photos copy when you create the draft. If a source image link expires, add that photo in ListLab.</small></p>
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
