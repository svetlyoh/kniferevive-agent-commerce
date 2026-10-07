<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** In-memory only: no WooCommerce session hooks, cart persistence, or customer writes. */
final class QuoteSession extends \WC_Session {
    public function __construct() { $this->_customer_id='krev-agent-quote'; }
    // Fee plugins require a session marker to inspect the selected rail. This session never persists.
    public function has_session() { return true; }
    public function save_data() {}
}
final class QuoteCart extends \WC_Cart {
    public function __construct() { $this->fees_api=new \WC_Cart_Fees(); }
    public function get_cart() { return $this->cart_contents; }
    public function needs_shipping() { return false; }
    public function calculate_shipping() { return []; }
    public function calculate_totals() {
        $this->totals=$this->default_totals;
        $this->fees_api->remove_all_fees();
        do_action('woocommerce_before_calculate_totals',$this);
        new \WC_Cart_Totals($this);
        // Deliberately omit cart-session persistence on woocommerce_after_calculate_totals.
    }
}
