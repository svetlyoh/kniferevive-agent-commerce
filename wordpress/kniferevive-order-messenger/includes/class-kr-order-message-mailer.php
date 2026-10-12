<?php
defined('ABSPATH') || exit;
/** Loaded only after WooCommerce's mailer has initialized WC_Email. */
class KR_Order_Messenger_Mailer extends WC_Email {
    public array $scope;
    public array $context;
    public string $message_text;
    public array $screenshot_paths;
    public array $attachment_descriptions;
    public function __construct(array $scope,string $message,array $paths,array $descriptions=[]) {
        $this->id='kr_order_message'; $this->title='KnifeRevive Order Messenger'; $this->description='Manual seller-to-buyer transactional order message.';
        $this->customer_email=true; $this->manual=true; $this->scope=$scope; $this->context=KR_Order_Messenger_Context::data($scope); $this->object=$scope['order'];
        $this->message_text=$message; $this->screenshot_paths=$paths; $this->attachment_descriptions=$descriptions;
        parent::__construct();
        $this->email_type='multipart'; $this->settings['email_type']='multipart'; $this->enabled='yes';
        $this->recipient=$this->context['recipient']; $this->subject=$this->context['subject']; $this->heading=KR_Order_Messenger_Admin::settings()['heading'];
    }
    public function init_form_fields() { $this->form_fields=[]; }
    public function get_headers() {
        $reply=KR_Order_Messenger_Admin::reply_to($this->scope);
        return 'Content-Type: text/html; charset=UTF-8'."\r\n".($reply?'Reply-To: '.sanitize_email($reply)."\r\n":'');
    }
    public function get_content_html() {
        $s=KR_Order_Messenger_Admin::settings(); $filter=null;
        if ($s['logo_url']) { $filter=static fn()=>$s['logo_url']; add_filter('pre_option_woocommerce_email_header_image',$filter,999); }
        try {
            ob_start(); wc_get_template('emails/order-message.php',['email'=>$this,'context'=>$this->context,'message'=>$this->message_text,'screenshots'=>$this->attachment_descriptions,'settings'=>$s],'kniferevive-order-messenger/',KR_OM_PATH.'templates/'); return ob_get_clean();
        } finally { if ($filter) remove_filter('pre_option_woocommerce_email_header_image',$filter,999); }
    }
    public function get_content_plain() {
        ob_start(); wc_get_template('emails/plain/order-message.php',['email'=>$this,'context'=>$this->context,'message'=>$this->message_text,'screenshots'=>$this->attachment_descriptions,'settings'=>KR_Order_Messenger_Admin::settings()],'kniferevive-order-messenger/',KR_OM_PATH.'templates/'); return ob_get_clean();
    }
    public function preview(): string { return (string)apply_filters('woocommerce_mail_content',$this->style_inline($this->get_content_html())); }
    public function deliver(?string $diagnostic_recipient=null): bool {
        $target=$diagnostic_recipient ?: $this->recipient;
        if (!is_email($target) || !KR_Order_Messenger_Admin::reply_to($this->scope)) return false;
        $this->sending=true;
        try { return $this->send($target,$diagnostic_recipient?'[KnifeRevive] Order Messenger diagnostic test':$this->subject,$this->get_content_html(),$this->get_headers(),$this->screenshot_paths); }
        finally {
            // WC normally removes these; also clean them after an exception in a custom transport.
            remove_filter('wp_mail_from',[$this,'get_from_address']); remove_filter('wp_mail_from_name',[$this,'get_from_name']); remove_filter('wp_mail_content_type',[$this,'get_content_type']); $this->sending=false;
        }
    }
    public static function styles(string $css,$email): string {
        if (!$email instanceof self) return $css; $s=KR_Order_Messenger_Admin::settings(); $font=(int)$s['font_size']; $width=(int)$s['width'];
        $css.="\n#body_content_inner{font-size:{$font}px!important;line-height:1.6!important} #wrapper{max-width:{$width}px!important;width:100%!important} .kr-om-email-summary{width:100%;border-collapse:collapse} .kr-om-email-summary td{padding:8px 0;vertical-align:top} .kr-om-email-message{white-space:normal;overflow-wrap:anywhere} .kr-om-email-button{display:inline-block;padding:12px 18px} img{max-width:100%;height:auto} @media(max-width:430px){#body_content_inner_cell{padding:20px!important}}";
        if ($s['theme']==='custom') {
            $bg=$s['background']; $surface=$s['surface']; $text=$s['text']; $accent=$s['accent'];
            $css.="\nhtml body,body #outer_wrapper{background-color:$bg!important} body #inner_wrapper,body #template_container,body #template_header,body #body_content,body #body_content_inner{background-color:$surface!important;color:$text!important} body #header_wrapper h1,body #template_header h1,body #body_content_inner p,body #body_content_inner h2,body #body_content_inner td,body #template_footer #credit{color:$text!important} body #body_content_inner a,body #template_footer a{color:$accent!important} body #body_content_inner a.kr-om-email-button{background:$accent!important;color:#fff!important}";
        }
        return $css;
    }
    public static function demo_scope(int $vendor): array {
        $order=new WC_Order(); $order->set_date_created(time()); $order->set_billing_email('preview@example.invalid');
        $item=new WC_Order_Item_Product(); $item->set_name('Example order item (preview only)'); $item->set_quantity(1); $order->add_item($item);
        return ['requested'=>$order,'order'=>$order,'buyer_order'=>$order,'vendor_id'=>$vendor,'sender_id'=>get_current_user_id()];
    }
}
