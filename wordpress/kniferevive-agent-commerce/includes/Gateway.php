<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
/** Never offered in normal checkout. Owns refunds for this adapter's completed sessions. */
final class Gateway extends \WC_Payment_Gateway {
    public function __construct() {
        $this->id='krev_agent_checkout'; $this->method_title='KnifeRevive Agent Checkout'; $this->title='Stripe hosted Checkout';
        $this->enabled='no'; $this->supports=['products','refunds'];
    }
    public function is_available() { return false; }
    public function process_payment($order_id) { return ['result'=>'failure']; }
    public function process_refund($order_id, $amount=null, $reason='') { return Payments::refund((int)$order_id,$amount,(string)$reason); }
}
