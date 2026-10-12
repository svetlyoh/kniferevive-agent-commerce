<?php defined('ABSPATH') || exit; ?>
<div class="kr-om-root" data-order-id="<?php echo (int)$order_id; ?>">
    <div class="kr-om-order-actions"><button type="button" class="button kr-om-button kr-om-launch"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M4 5h16v7M4 5v14h8M4 5l8 7 8-7M15 16h6m-3-3 3 3-3 3"/></svg> Quick Update</button><noscript><p>Enable JavaScript to compose an order email here.</p></noscript></div>
    <dialog class="kr-om-dialog" aria-labelledby="kr-om-title">
        <header class="kr-om-header"><div class="kr-om-heading"><img data-kr-om-logo alt="KnifeRevive" hidden><div><h2 id="kr-om-title">Quick Update</h2><p data-kr-om-heading-order>Send a quick order update</p></div></div><button type="button" class="kr-om-close" data-kr-om-close aria-label="Close composer">×</button></header>
        <div class="kr-om-scroll">
            <p class="kr-om-status" role="status" aria-live="polite"></p>
            <div class="kr-om-editor">
                <dl class="kr-om-summary kr-om-recipient"><dt>To</dt><dd data-kr-om-to></dd></dl>
                <label for="kr-om-message">Your message</label><textarea id="kr-om-message" maxlength="10000" rows="6" placeholder="Hey! Drop your update here. You can paste a screenshot too."></textarea>
                <label class="kr-om-upload kr-om-button" for="kr-om-files" role="button" tabindex="0" aria-controls="kr-om-files">Add Screenshot</label><input class="kr-om-file-input" id="kr-om-files" type="file" tabindex="-1" aria-label="Screenshot files" accept="image/png,image/jpeg,.png,.jpg,.jpeg" multiple>
                <p class="kr-om-help" data-kr-om-limits>PNG or JPEG. You can paste an image or choose a file.</p>
                <div class="kr-om-thumbnails" data-kr-om-shots></div>
                <details class="kr-om-details"><summary>Order &amp; email details</summary><dl class="kr-om-summary"><dt>Replies go to</dt><dd data-kr-om-reply></dd><dt>Order</dt><dd data-kr-om-number></dd><dt>Placed</dt><dd data-kr-om-placed></dd><dt>Seller</dt><dd data-kr-om-store></dd></dl><h3>Items</h3><ul data-kr-om-items></ul><label for="kr-om-subject">Subject</label><input id="kr-om-subject" type="text" readonly aria-readonly="true"><p class="kr-om-help">You’re emailing this order’s buyer. Their reply goes to your registered seller email.</p></details>
            </div>
            <section class="kr-om-preview" hidden aria-label="Email preview"><h3>Preview Email</h3><p data-kr-om-preview-summary></p><iframe title="Branded email preview" sandbox="" referrerpolicy="no-referrer"></iframe><h4>Screenshots delivered as image attachments</h4><div class="kr-om-thumbnails" data-kr-om-preview-shots></div></section>
            <details class="kr-om-history"><summary>Order message history</summary><div data-kr-om-history></div></details>
        </div>
        <footer class="kr-om-footer"><button type="button" class="kr-om-button kr-om-secondary" data-kr-om-preview>Preview Email</button><button type="button" class="kr-om-button kr-om-secondary" data-kr-om-edit hidden>Keep Editing</button><button type="button" class="kr-om-button" data-kr-om-send>Send Update</button><button type="button" class="kr-om-button kr-om-secondary" data-kr-om-check hidden>Check Status</button><button type="button" class="kr-om-button kr-om-secondary" data-kr-om-close>Back to Order</button></footer>
        <div class="kr-om-discard" role="alertdialog" aria-modal="true" aria-labelledby="kr-om-discard-title" hidden><div><h3 id="kr-om-discard-title">Keep editing or discard this draft?</h3><p>Unsaved text and screenshots will be removed. Closing does not resend an uncertain email.</p><button type="button" class="kr-om-button" data-kr-om-keep>Keep Editing</button><button type="button" class="kr-om-button kr-om-secondary" data-kr-om-discard>Discard Draft</button></div></div>
    </dialog>
</div>
