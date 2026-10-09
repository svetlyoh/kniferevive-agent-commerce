<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Uses WC's authenticated cookie, session table, save hooks and expiry cleanup. */
final class BookingNativeSession extends \WC_Session_Handler {
    public function __construct(){parent::__construct();$this->_cookie.='_krev_'.BookingSession::$id;}
    public function generate_customer_id(){return wc_rand_hash('t_',30);}
    public function init(){
        $this->init_hooks();$cookie=$this->get_session_cookie();
        // Do not import cart-token query strings or migrate into the logged-in user's
        // storefront storage. The native cookie's HMAC is verified by WooCommerce.
        if($cookie && str_starts_with((string)$cookie[0],'t_') && (int)$cookie[1]>time()){
            $this->_customer_id=$cookie[0];$this->_session_expiration=(int)$cookie[1];$this->_session_expiring=(int)$cookie[2];
            $this->_data=$this->get_session_data();
            if(($this->_data['krev_checkout_user']??null)===get_current_user_id()){$this->_has_cookie=true;return;}
        }
        $this->_customer_id=$this->generate_customer_id();$this->_data=[];$this->_has_cookie=false;$this->set_session_expiration();
        $this->set('krev_checkout_user',get_current_user_id());
    }
}
