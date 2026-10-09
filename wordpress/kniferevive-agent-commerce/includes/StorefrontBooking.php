<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** The ordinary sharpening cart enters the same consent-bound booking flow. */
final class StorefrontBooking {
    private static ?array $source=null;
    public static function boot(): void {
        add_action('template_redirect',[self::class,'render'], -1);
        add_action('woocommerce_checkout_create_order',[self::class,'guardOrder'],4);
        add_action('woocommerce_checkout_validate_order_before_payment',[self::class,'guardOrder'],4);
        add_action('woocommerce_store_api_checkout_order_processed',[self::class,'guardOrder'],4);
    }
    public static function guardOrder(\WC_Order $order): void {
        $lines=[];foreach($order->get_items() as $key=>$item)$lines[$key]=['product_id'=>$item->get_product_id(),'variation_id'=>$item->get_variation_id(),'quantity'=>$item->get_quantity()];
        if(!self::selection($lines))return;
        // ListingCheckout's subsequent hook verifies the original binding, cart and total.
        if(WC()->session instanceof BookingNativeSession && Domain::validId((string)WC()->session->get('krev_booking_id')))return;
        Domain::fail('BOOKING_REQUIRED','Choose your sharpening day and pickup & return plan at KnifeRevive checkout before paying.',409);
    }
    public static function active(): bool {return self::$source!==null;}
    private static function selection(array $cart): array {
        $configured=array_column(Settings::get()['booking_services'],'product_id');$rows=[];
        foreach($cart as $key=>$line){
            $id=(int)($line['product_id']??0);
            if(!in_array($id,$configured,true) && !has_term('knife-sharpening','product_cat',$id))continue;
            if(!in_array($id,$configured,true) || !empty($line['variation_id']) || !empty($line['variation']))Domain::fail('SERVICE_REVIEW_REQUIRED','This sharpening item needs a quick merchant review. Contact KnifeRevive to arrange it; nothing has been ordered.',409);
            $quantity=$line['quantity']??0;
            if(!is_numeric($quantity) || (int)$quantity!=$quantity || $quantity<1 || $quantity>30)Domain::fail('INVALID_REQUEST','Choose 1–30 whole knives for each sharpening service.',409);
            $rows[$key]=['product_id'=>$id,'quantity'=>(int)$quantity];
        }
        ksort($rows);return $rows;
    }
    public static function begin(): bool {
        self::$source=null;
        if(!WC()->session || !WC()->cart || WC()->session instanceof BookingNativeSession)return false;
        $rows=self::selection(WC()->cart->get_cart());if(!$rows)return false;
        if(!Booking::enabled())Domain::fail('BOOKING_DISABLED','Sharpening bookings are taking a quick break. Contact KnifeRevive; your cart is saved.',503);
        $pending=wc_get_order((int)WC()->session->get('order_awaiting_payment'));
        if($pending && $pending->needs_payment())Domain::fail('CART_CONFLICT','You have an unpaid order already. Open your original order from My account before starting another checkout. Your cart is saved.',409);
        $quantities=[];foreach($rows as $row)$quantities[$row['product_id']]=($quantities[$row['product_id']]??0)+$row['quantity'];
        foreach($quantities as $quantity)if($quantity>30)Domain::fail('INVALID_REQUEST','Choose no more than 30 knives per sharpening service.',409);
        self::$source=['session'=>WC()->session,'rows'=>$rows,'fingerprint'=>Domain::digest($rows),'quantities'=>$quantities,'mixed'=>count(WC()->cart->get_cart())>count($rows),'user_id'=>get_current_user_id()];
        return true;
    }
    public static function render(): void {
        if(!is_checkout() || is_order_received_page() || is_checkout_pay_page() || isset($_GET['krev_agent']) || wp_doing_ajax() || isset($_GET['wc-ajax']))return;
        try{if(!self::begin())return;}catch(\Throwable $e){
            PrivateBrand::headers();PrivateBrand::start('Review your sharpening checkout');echo '<h1>Let’s sort your sharpening checkout</h1><p role="alert">'.esc_html($e instanceof Fault?$e->getMessage():'Your cart needs merchant review. Nothing was ordered or charged.').'</p><p><a href="'.esc_url(wc_get_cart_url()).'">Back to my cart</a> · <a href="'.esc_url(wc_get_page_permalink('myaccount')).'">My orders</a></p>';PrivateBrand::end();exit;
        }
        BookingFrontend::render();
    }
    public static function defaults(): array {return self::$source['quantities']??[];}
    public static function fields(array $values): void {
        if(!self::active())return;
        echo '<input type="hidden" name="store_cart_hash" value="'.esc_attr($values['store_cart_hash']??self::$source['fingerprint']).'">';
        echo '<p class="krev-fine-print">Your cart’s sharpening choices are ready below. You can change the knives and your pickup &amp; return plan before continuing.</p>';
        if(self::$source['mixed'])echo '<p class="krev-alert" role="status">Sharpening gets its own booking and order. Your other items stay in your cart for a separate checkout with their normal shipping.</p>';
        if(WC()->session->get('applied_coupons'))echo '<p class="krev-fine-print">Have a coupon? Add it on the secure payment screen. Your cart’s coupon does not automatically carry into a separate sharpening booking.</p>';
    }
    public static function validate(array $post): void {
        if(!self::active())return;
        if(!is_string($post['store_cart_hash']??null) || !hash_equals(self::$source['fingerprint'],$post['store_cart_hash']))Domain::fail('CART_CONFLICT','Your cart changed in another tab. Reload checkout to review the latest knives; nothing was submitted.',409);
    }
    /** Remove only transferred source lines, after the original booking is recoverable. */
    public static function complete(array $booking): void {
        if(!self::active())return;
        if(empty($booking['data']['submitted_at']) || ($booking['data']['input']['mode']!=='pay_later_dropoff' && empty($booking['data']['listing_intent'])))Domain::fail('CART_CONFLICT','Your booking handoff is not ready. Your cart is saved.',409);
        $source=self::$source;$session=$source['session'];
        // Fetch current native storage so unrelated cart additions are preserved.
        $saved=$session->get_session($session->get_customer_id(),[]);
        $cart=isset($saved['cart'])?maybe_unserialize($saved['cart']):$session->get('cart',[]);
        if(!is_array($cart))Domain::fail('CART_CONFLICT','Your original cart needs review. Open your saved booking before trying again.',409);
        if(!hash_equals($source['fingerprint'],Domain::digest(self::selection($cart))))Domain::fail('CART_CONFLICT','Your cart changed while checkout was being prepared. Your booking details are saved; review them before creating another request.',409);
        foreach(array_keys($source['rows']) as $key)unset($cart[$key]);
        $session->set('cart',$cart);$session->set('cart_totals',null);$session->save_data();
        if($source['user_id'])update_user_meta($source['user_id'],'_woocommerce_persistent_cart_'.get_current_blog_id(),['cart'=>$cart]);
        if(WC()->session===$session){
            // In the unpaid flow native cart shutdown must not restore the removed lines.
            foreach(array_keys($source['rows']) as $key)WC()->cart->remove_cart_item($key);
            WC()->cart->calculate_totals();
        }
        Store::lock('booking:'.$booking['id'],static function()use($booking){$data=Store::get($booking['id'],'booking')['data'];$data['source']='storefront_cart';Store::update($booking['id'],$data);});
        self::$source=null;
    }
}
