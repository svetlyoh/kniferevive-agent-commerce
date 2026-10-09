<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
/** Native totals/shipping with an ephemeral session; never registers cart persistence. */
final class ListingCart extends \WC_Cart {
    public function __construct() { $this->fees_api=new \WC_Cart_Fees(); $this->session=new \WC_Cart_Session($this); }
    public function get_cart() { return $this->cart_contents; }
    public function calculate_totals() {
        $this->totals=$this->default_totals;
        $this->fees_api->remove_all_fees();
        do_action('woocommerce_before_calculate_totals',$this);
        new \WC_Cart_Totals($this);
        do_action('woocommerce_after_calculate_totals',$this);
    }
}
