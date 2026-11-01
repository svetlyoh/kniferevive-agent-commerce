<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Screens {
    private static function e(mixed $s): string{return esc_html((string)$s);}
    public static function caseUrl(int $id): string {return home_url('/?kr_return_case='.$id);}
    public static function assets(): void {
        $admin=is_admin()&&($_GET['page']??'')==='kr-return-desk';
        if(!$admin&&!is_account_page()&&!isset($_GET['kr_return_case'],$_GET['kr_returns_order'])&&!isset($_GET['kr_return_access'])&&!isset($_GET['kr_return_case'])&&!isset($_GET['kr_returns_order']))return;
        wp_enqueue_style('kr-buyer-returns',plugins_url('assets/returns.css',KR_RETURNS_FILE),[],KR_RETURNS_VERSION);
        wp_enqueue_script('kr-buyer-returns',plugins_url('assets/returns.js',KR_RETURNS_FILE),[],KR_RETURNS_VERSION,true);
        $guest=!get_current_user_id()?Guest::session():null;
        wp_localize_script('kr-buyer-returns','KRBuyerReturns',['root'=>rest_url(Rest::NS.'/'),'nonce'=>get_current_user_id()?wp_create_nonce('wp_rest'):'','csrf'=>$guest['csrf']??'','origin'=>home_url()]);
    }
    public static function route(): void {
        if(!isset($_GET['kr_return_case'])&&!isset($_GET['kr_returns_order'])&&!isset($_GET['kr_return_access']))return;
        nocache_headers();header('Cache-Control: private, no-store, max-age=0');header('Referrer-Policy: no-referrer');
        if(isset($_GET['kr_return_access'])){get_header();echo '<main class="kr-returns"><h1>Your private return workspace</h1><p>Open a secure email link, or request a new one below. This does not sign you into another account.</p><div data-kr-guest-exchange role="status" aria-live="polite"></div>';
            self::form('guest/request','Email me a secure link',[['order_id','Order number','number'],['email','Checkout email','email']]);echo '</main>';get_footer();exit;}
        try{
            if(isset($_GET['kr_return_case'])){$r=Store::row(absint($_GET['kr_return_case']));Access::view($r);get_header();echo '<main class="kr-returns">';self::card(wc_get_order($r['order_id']),$r);echo '</main>';get_footer();exit;}
            $o=wc_get_order(absint($_GET['kr_returns_order']));if(!$o||!Access::buyer($o))Support::fail('not_found','Order not found. Sign in or request a fresh secure link.',404);
            get_header();self::buyer($o);get_footer();exit;
        }catch(\Throwable $e){status_header(403);get_header();echo '<main class="kr-returns"><h1>Private return details</h1><p>Sign in as the purchasing customer or assigned seller to continue.</p><p><a href="'.esc_url(wp_login_url(home_url($_SERVER['REQUEST_URI']??'/my-account/'))).'">Sign in</a> · <a href="'.esc_url(home_url('/?kr_return_access=1')).'">Guest order access</a></p></main>';get_footer();exit;}
    }
    public static function buyer(\WC_Order $o): void {
        if(!Access::buyer($o)&&!Access::admin())return;
        echo '<section class="kr-returns" aria-labelledby="kr-returns-title"><p class="kr-eyebrow">ORDER SUPPORT</p><h2 id="kr-returns-title">Returns, without the runaround.</h2><p>Tell us what happened. We’ll review the sold policy and help with the next step. Don’t send anything back until your return is approved and a label is ready.</p>';
        foreach(\KREV_Returns::for_order($o->get_id())as$r)self::card($o,$r);
        if(Access::buyer($o))foreach($o->get_items()as$item){if(!$item instanceof \WC_Order_Item_Product)continue;$p=Policy::resolve($o,$item);echo '<details class="kr-panel"><summary>Request a return · '.self::e($item->get_name()).'</summary><p>'.self::e($p['policy']['label']??$p['policy']['name']??'Purchase-time policy needs review').'</p>';
            foreach($p['review_reasons']as$reason)echo '<p class="kr-muted">'.self::e($reason).'</p>';
            if(!Policy::settings()['new_requests'])echo '<p>New requests are paused. <a href="'.esc_url(home_url('/help/contact/')).'">Contact support</a>; your request can still be reviewed.</p>';
            else self::form('orders/'.$o->get_id().'/cases','Submit request',[
                ['order_item_id','Purchased line','hidden',$item->get_id()],['quantity','Quantity','number',1,[],max(1,$p['quantity'])],
                ['reason','What happened?','select','',Cases::REASONS],['resolution','Preferred resolution','select','refund',['refund'=>'Refund','manual_exchange'=>'Discuss an exchange','help'=>'Help with my order']],['notes','Tell us more','textarea'],
            ]);
            echo '</details>';
        }echo '<p><a href="'.esc_url(home_url('/help/contact/')).'">Need help?</a> · <a href="'.esc_url(home_url('/?kr_return_access=1')).'">Guest order access</a></p></section>';
    }
    private static function addressFields(): array {return [['address[name]','Recipient name'],['address[address_1]','Street address'],['address[address_2]','Apartment / suite (optional)','text','',[],null,false],['address[city]','City'],['address[state]','State (two letters)'],['address[postcode]','ZIP code'],['address[country]','Country','text','US'],['confirm','I confirm this address for this return','checkbox']];}
    public static function form(string $path,string $title,array $fields,int $revision=0): void {
        echo '<details class="kr-action"><summary>'.self::e($title).'</summary><form data-kr-return-path="'.esc_attr($path).'" enctype="multipart/form-data">';
        if($revision)echo '<input type="hidden" name="expected_revision" value="'.$revision.'">';
        foreach($fields as$f){[$name,$label]=$f;$type=$f[2]??'text';$value=$f[3]??'';$options=$f[4]??[];$max=$f[5]??null;$required=$f[6]??true;$id='kr-'.substr(hash('sha256',$path.':'.$name.':'.wp_rand()),0,12);
            if($type==='hidden'){echo '<input type="hidden" name="'.esc_attr($name).'" value="'.esc_attr($value).'">';continue;}
            echo '<label for="'.$id.'">'.self::e($label).'</label>';
            if($type==='select'){echo '<select id="'.$id.'" name="'.esc_attr($name).'">';foreach($options as$k=>$v)echo '<option value="'.esc_attr($k).'" '.selected((string)$value,(string)$k,false).'>'.self::e($v).'</option>';echo '</select>';}
            elseif($type==='textarea')echo '<textarea id="'.$id.'" name="'.esc_attr($name).'" rows="5" maxlength="5000" '.($required?'required':'').'>'.esc_textarea($value).'</textarea>';
            else echo '<input id="'.$id.'" name="'.esc_attr($name).'" type="'.esc_attr($type).'" '.($type==='checkbox'?'value="1"':'value="'.esc_attr($value).'"').' '.($required?'required':'').' '.($type==='number'?'min="'.((str_contains($name,'saleable')||str_contains($name,'credit_minor')||str_contains($name,'exposure_cap'))?'0':'1').'" step="1" '.($max!==null?'max="'.(int)$max.'"':''):'').' '.($type==='file'?'accept="image/jpeg,image/png,image/webp,application/pdf"':'').'>';
        }
        echo '<button type="submit">'.self::e($title).'</button><p class="kr-feedback" role="status" aria-live="polite"></p></form></details>';
    }
    public static function card(\WC_Order $o,array $r): void {
        try{$p=Cases::project($r);}catch(\Throwable $e){return;}$v=Store::state($r);$rev=$p['revision'];$base='cases/'.$r['id'].'/actions/';$role=$p['role'];
        echo '<article class="kr-returns kr-panel" aria-label="Return '.(int)$r['id'].'"><p class="kr-eyebrow">RETURN #'.(int)$r['id'].' · ORDER #'.self::e($o->get_order_number()).'</p><h3>'.self::e($p['item']['name']).'</h3><p class="kr-next">'.self::e($p['next_step']).'</p>';self::labelButtons($p,$r,$v);echo '<dl class="kr-states">';
        foreach(['case'=>'Case','label'=>'Return label','shipment'=>'Incoming shipment','inspection'=>'Contents inspection','refund'=>'Buyer refund','postage'=>'Return postage']as$key=>$label){$state=is_array($p[$key])?($p[$key]['state']??'under review'):$p[$key];echo '<div><dt>'.self::e($label).'</dt><dd>'.self::e($state==='not_needed'&&$key==='label'&&$v['case']!=='approved'?'Not requested yet':($state==='iou_open'?'Shipping IOU open':ucwords(str_replace('_',' ',$state)))).'</dd></div>';}
        echo '</dl><p><strong>Qty '.(int)$r['quantity'].'</strong> · '.self::e($p['reason']).'</p>';
        echo '<details class="kr-action"><summary>Purchased policy &amp; review context</summary><p>'.self::e($p['policy']['label']??$p['policy']['name']??'Missing purchase-time policy — review required').'</p><div>'.wp_kses_post($p['policy']['description']??'').'</div>';
        foreach($p['review_reasons']as$why)echo '<p>'.self::e($why).'</p>';echo '<p>Current listing terms do not replace the terms recorded at purchase. A historical fee is not automatically deducted from a refund.</p></details>';
        if(!empty($p['label']['cost_minor']))echo '<p><strong>'.($p['label']['state']==='ready'?'Actual return postage':'Recorded label quote/cost').': $'.self::e(number_format($p['label']['cost_minor']/100,2)).' USD.</strong> Responsibility: '.self::e($p['postage']['responsible_party']??'Under review').'. Postage collection is separate from buyer return progress.</p>';
        if(in_array($role,['seller','recipient','admin'],true)&&isset($p['postage']['due_minor']))echo '<p>Shipping IOU due: $'.self::e(number_format($p['postage']['due_minor']/100,2)).' USD. No automatic deduction.</p>';
        if($p['finding'])echo '<p><strong>Return Desk finding:</strong> '.self::e($p['finding']).'</p>';
        foreach($p['deadlines']as$kind=>$d)echo '<p class="kr-muted">'.self::e(ucwords(str_replace('_',' ',$kind))).' target: <time datetime="'.esc_attr(gmdate('c',$d['due_at'])).'">'.self::e(wp_date('M j, Y g:i a T',$d['due_at'],new \DateTimeZone($d['timezone']))).'</time>. Contact us if you need more time; this is a review target.</p>';
        if(isset($p['addresses']))foreach($p['addresses']as$side=>$a)echo '<details class="kr-action"><summary>Confirmed return '.self::e($side).'</summary><address>'.nl2br(self::e(implode("\n",array_filter($a['value'])))).'</address></details>';
        echo '<details class="kr-action"><summary>Messages &amp; evidence</summary><ol class="kr-messages">';
        foreach($p['messages']as$m)echo '<li><strong>'.self::e(ucfirst($m['role'])).($m['visibility']==='admin_private'?' · Private support note':'').'</strong> · '.self::e(wp_date('M j, g:i a',$m['at'])).'<div>'.wp_kses_post($m['body']).'</div></li>';
        echo '</ol>';foreach($p['evidence']as$e)echo '<button class="kr-file" data-kr-file="'.esc_attr('cases/'.$r['id'].'/files/'.$e['id']).'">Download '.self::e($e['name']).($e['visibility']==='admin_private'?' · Private':'').'</button>';
        self::form($base.'message','Send a message',array_merge([['note','Message','textarea']],$role==='admin'?[['visibility','Visible to','select','shared',['shared'=>'Buyer and seller','admin_private'=>'Return Desk only']]]:[]),$rev);
        self::form($base.'evidence','Add photo or screenshot',array_merge([['file','Private JPEG, PNG or WebP · up to 5 MB','file']],$role==='admin'?[['visibility','Visible to','select','shared',['shared'=>'Buyer and seller','admin_private'=>'Return Desk only']]]:[]),$rev);
        echo '</details>';
        self::form($base.'help','Need help?',[['note','What do you need help with?','textarea']],$rev);
        if($role==='buyer'){
            if($v['case']==='approved'&&empty($v['no_return_needed'])&&in_array($v['label']['state'],['pending_address','not_needed','quote_ready'],true))self::form($base.'confirm_origin','Confirm my return-from address',self::addressFields(),$rev);
            if($p['label']['state']==='ready'&&$p['shipment']['state']==='awaiting_handover')self::form($base.'handover','I dropped it off',[['date','Actual drop-off date','date',wp_date('Y-m-d')],['confirm','I handed these items to the carrier','checkbox']],$rev);
            if(in_array($p['label']['state'],['not_needed','pending_address','quote_ready'],true))self::form($base.'withdraw','Withdraw request',[['note','Reason','textarea']],$rev);
        }
        if(in_array($role,['seller','recipient'],true))self::form($base.'seller_response','Respond to return',[['choice','My response','select','agree',['agree'=>'I agree with the request','alternative'=>'I propose another resolution','review'=>'Please review my evidence']],['note','Message for buyer and Return Desk','textarea']],$rev);
        if(in_array($role,['seller','recipient','admin'],true)&&$v['case']==='approved'&&empty($v['no_return_needed'])){
            if(in_array($v['label']['state'],['pending_address','not_needed','quote_ready'],true))self::form($base.'confirm_destination','Confirm return destination',self::addressFields(),$rev);
            if(!in_array($p['shipment']['state'],['receipt_confirmed'],true)&&$p['inspection']['state']!=='completed')self::form($base.'receipt','I received the parcel',[['note','Receipt details','textarea'],['confirm','I physically received this parcel','checkbox']],$rev);
            if($p['inspection']['state']!=='completed')self::form($base.'inspection','Record contents inspection',[['received_quantity','Received units','number',1],['saleable_quantity','Saleable units','number',0],['condition','Actual condition and findings','textarea'],['identifier','Serial / item identifier (optional)','text','',[],null,false],['missing_components','Missing components (optional)','textarea','',[],null,false],['discrepancy','There is a discrepancy','checkbox','',[],null,false],['confirm','I inspected the actual returned contents','checkbox']],$rev);
        }
        if($role==='admin')self::adminActions($r,$v,$rev,$base);
        echo '<p><a href="'.esc_url(self::caseUrl((int)$r['id'])).'">Open private return details</a> · <a href="'.esc_url(home_url('/help/contact/')).'">Contact support</a></p></article>';
    }
    private static function labelButtons(array $p,array $r,array $v): void {
        if(!empty($p['label']['document_id'])&&$p['label']['state']==='ready'){
            $doc=$p['label']['document_id'];$path='cases/'.$r['id'].'/files/'.$doc;
            echo '<div class="kr-buttons"><button data-kr-file="'.esc_attr($path).'" data-case="'.(int)$r['id'].'" data-document="'.esc_attr($doc).'" data-print="1">Print Return Label</button><button data-kr-file="'.esc_attr($path).'">Download label</button></div><p class="kr-muted">Print opens the existing PDF. Use your PDF viewer’s Print command. Reprints never purchase another label.</p>';
            if($url=Tracking::link($v))echo '<a class="kr-link" target="_blank" rel="noopener noreferrer" href="'.esc_url($url).'">Track return with carrier ↗</a><p>Carrier status may be delayed. Buyer drop-off, carrier delivery and contents inspection are separate.</p>';
        }
    }
    private static function adminActions(array $r,array $v,int $rev,string $base): void {
        echo '<details class="kr-action"><summary>Return Desk controls</summary><p>Approval, spending and refund payment are separate decisions. Carrier receipt is not inspection. No hidden postage deduction is included.</p>';
        if(!empty($v['legacy']))self::form($base.'adopt_legacy','Adopt preserved legacy case',[['finding','Purchase-time evidence and legacy-history review','textarea']],$rev);
        self::form($base.'review','Record return decision',[['decision','Decision','select','approved',['approved'=>'Approve physical return','declined'=>'Decline with explanation','no_return_needed'=>'Resolve without physical return']],['finding','Evidence-based finding','textarea'],['reviewed_policy','I reviewed the sold policy and missing historical evidence','checkbox']],$rev);
        self::form($base.'responsibility','Set postage responsibility',[['party','Responsible party','select','pending',['pending'=>'Needs review','platform'=>'KnifeRevive covers it','seller'=>'Seller, if established by policy/evidence','buyer'=>'Buyer — exact prior agreement required']],['finding','Separate postage finding','textarea'],['buyer_agreement','Exact buyer agreement (only if applicable)','textarea','',[],null,false]],$rev);
        self::form($base.'physical_review','Verify missing sold physical-item evidence',[['finding','Evidence of the originally sold physical item','textarea']],$rev);
        self::form($base.'assign_recipient','Assign return recipient',[['seller_id','Registered recipient seller ID','number'],['finding','Dispatch / fulfillment assignment evidence','textarea']],$rev);
        self::form($base.'replace_label','Prepare reviewed label replacement',[['finding','Why the reconciled label needs replacing','textarea']],$rev);
        self::form($base.'tracking_review','Record reviewed carrier update',[['state','Incoming carrier state','select','in_transit',['carrier_accepted'=>'Carrier accepted','in_transit'=>'In transit','carrier_delivered'=>'Carrier delivered','exception'=>'Exception','lost_review'=>'Lost / missing-scan review']],['observed_at','Actual event Unix timestamp','number'],['finding','Reviewed exact carrier evidence','textarea'],['reviewed_evidence','I reviewed the exact return tracking event; this is not inspection','checkbox']],$rev);
        self::form($base.'inspection_review','Resolve inspection review',[['finding','Inspection / discrepancy finding','textarea']],$rev);
        self::form($base.'restock','Restock inspected units',[['quantity','Saleable units to restock once','number',1],['confirm','I reviewed the product ownership and actual saleable units','checkbox']],$rev);
        self::form($base.'refund_prepare','Prepare refund proposal',[['quantity','Units to refund','number',(int)$r['quantity']],['include_shipping','Include original allocated shipping','checkbox','',[],null,false],['include_fees','Include original allocated fees','checkbox','',[],null,false],['allocation_reviewed','I reviewed shipping/fee rights and allocation','checkbox','',[],null,false],['allocation_finding','Shipping / fee allocation finding (if included)','textarea','',[],null,false],['refund_agreed','Refund agreed instead of manual exchange (if applicable)','checkbox','',[],null,false]],$rev);
        if($p=$v['refund']['proposal']??null){echo '<section class="kr-panel"><h4>Exact refund proposal</h4><p>'.self::e(number_format($p['amount_minor']/100,2)).' USD · '.$p['quantity'].' units · Original gateway: '.self::e($p['gateway']).' · Charged order #'.$p['root_order_id'].'</p><p>Original item: $'.self::e(number_format($p['item_minor']/100,2)).'; shipping: $'.self::e(number_format($p['shipping_minor']/100,2)).'; fees: $'.self::e(number_format($p['fee_minor']/100,2)).'. Original tax is included. Return postage / restocking deduction: $0.00.</p>';
            self::form($base.'refund_approve','Approve exact refund',[['proposal_hash','Proposal','hidden',$p['hash']],['confirm','I approve this exact original-payment proposal','checkbox']],$rev);
            if(Policy::settings()['refund_execution']&&Plugin::financialReady())self::form($base.'refund_execute','Submit original-payment refund',[['confirm','Send the approved refund through its original gateway','checkbox']],$rev);
            else echo '<p>Refund execution is OFF. Original-gateway, reconciliation and competing-writer launch checks remain required. Approval does not send money.</p>';echo '</section>';
        }
        self::form($base.'extend_deadline','Extend a review target',[['kind','Deadline key','select','seller_response',array_combine(array_keys($v['deadlines']),array_keys($v['deadlines']))?:['none'=>'No active deadline']],['due_at','New deadline Unix timestamp','number'],['reason','Why this extension is needed','textarea']],$rev);
        self::labelActions($r,$v,$rev,$base);
        self::form($base.'postage_iou','Record separate seller Shipping IOU',[['policy_finding','Applicable sold-policy / responsibility evidence','textarea'],['confirm_responsibility','Assess actual postage only; no automatic deduction','checkbox']],$rev);
        self::form($base.'postage_plan','Approve a Shipping Game Plan',[['schedule[0][amount_minor]','First payment in USD cents','number'],['schedule[0][due_date]','First due date','date'],['schedule[1][amount_minor]','Second payment in USD cents','number'],['schedule[1][due_date]','Second due date','date'],['exposure_cap_minor','Maximum current debt allowed for new discounted labels (USD cents)','number',0],['finding','Why this schedule and exposure are approved','textarea']],$rev);
        echo '<p>Shipping IOU payment, review and approved Game Plans use the existing <a href="'.esc_url(wc_get_account_endpoint_url('shipkit')).'">ShipKit workspace</a>. Return support is available even if seller postage is unpaid. First-return-free and return-review payout holds are OFF.</p>';
        self::form($base.'resolve','Close with a recorded resolution',[['finding','Final resolution','textarea']],$rev);echo '</details>';
    }
    private static function labelActions(array $r,array $v,int $rev,string $base): void {
        echo '<section class="kr-panel"><h4>Return label · ShipKit</h4><p>'.self::e(Labels::capabilities()['reason']).'</p><p><a href="'.esc_url(admin_url('admin.php?page=krev-shipkit')).'">Open Ship Desk</a> · <a href="'.esc_url(admin_url('admin.php?page=wc-orders&action=edit&id='.$r['order_id'])).'">Official Woo order / labels</a></p>';
        if(!Policy::settings()['label_issuance']){echo '<p>Return-label issuance is paused. It needs a separate approved return budget and verified shared postage capacity. Existing labels remain downloadable.</p></section>';return;}
        if(empty($v['label']['attempt_id']))self::form($base.'label_request','Prepare incoming label request',[
            ['mode','Provider path','select','woo_prior_outbound',['woo_prior_outbound'=>'Official Woo return from prior outbound Woo label','external_operator'=>'Approved external-label operator exception']],['outbound_label_id','Prior Woo outbound label ID (native path)','text','',[],null,false],
            ['parcel[weight]','Actual packed weight','number'],['parcel[weight_unit]','Weight unit','select','oz',['oz'=>'oz','lb'=>'lb','g'=>'g','kg'=>'kg']],['parcel[length]','Length','number'],['parcel[width]','Width','number'],['parcel[height]','Height','number'],['parcel[dimension_unit]','Dimensions unit','select','in',['in'=>'in','cm'=>'cm']],['parcel[ship_date]','Intended ship date','date',wp_date('Y-m-d')],['parcel[confirmed]','Actual packed measurements confirmed','checkbox'],['packaging_reviewed','I reviewed sharp/restricted/unsafe-item packaging requirements','checkbox'],
        ],$rev);
        else{
            $a=\KnifeRevive\ShipKit\Application::db()->get('attempt',$v['label']['attempt_id']);$hidden=[['attempt_id','Attempt','hidden',$a['id']]];
            self::form($base.'label_quote','Record exact provider quote',array_merge($hidden,[['amount_minor','Exact cost in USD cents','number'],['carrier','Carrier slug (usps / ups / fedex)'],['service','Provider service'],['evidence_id','This case’s private quote evidence document ID'],['exact_quote_attested','I reviewed the exact real provider quote without markup','checkbox']]),$rev);
            if(!empty($a['quote']))self::form($base.'label_spending','Approve platform return-postage spending',array_merge($hidden,[['quote_hash','Exact quote','hidden',$a['quote']['hash']],['underwrite','KnifeRevive explicitly underwrites this label under its separate return budget','checkbox']]),$rev);
            self::form($base.'label_claim','Claim official purchase',array_merge($hidden,[['confirm','I will use the official provider path for this exact return','checkbox']]),$rev);
            self::form($base.'label_void','Record confirmed provider void',array_merge($hidden,[['provider_reference','Actual provider void reference'],['approved_credit_minor','Provider-approved credit in USD cents (zero if none)','number',0],['evidence_id','Private void evidence document ID'],['provider_void_attested','The provider confirmed this void; this is not just a request','checkbox']]),$rev);
            self::form($base.'label_unknown','Mark purchase response unknown',array_merge($hidden,[['confirm','Reconcile this attempt before any retry','checkbox']]),$rev);
            self::form($base.'label_receipt','Bind actual receipt and private label PDF',array_merge($hidden,[['receipt[account]','Provider billing account identity'],['receipt[label_id]','Actual provider return-label ID'],['receipt[tracking]','Return tracking number'],['receipt[carrier]','Receipt carrier'],['receipt[service]','Receipt service'],['receipt[amount]','Actual charged USD cents','number'],['evidence_id','This case’s private receipt evidence document ID'],['file','Provider label PDF','file'],['provider_receipt_attested','I verified this incoming label, its receipt, amount, addresses and quantities','checkbox']]),$rev);
        }
        echo '</section>';
    }
    public static function menu(): void {add_submenu_page('woocommerce','Return Desk','Return Desk','manage_woocommerce','kr-return-desk',[self::class,'desk']);}
    public static function desk(): void {
        if(!Access::admin())return;echo '<div class="wrap kr-returns"><p class="kr-eyebrow">KNIFEREVIVE</p><h1>Return Desk</h1><p>One case. Clear next steps. Separate money decisions.</p><nav class="kr-tabs" aria-label="Return queues">';
        $queues=['review'=>'Needs review','address'=>'Confirm address','label'=>'Label to issue','shipment'=>'Awaiting buyer shipment','inspection'=>'Inspection due','refund'=>'Refund pending','help'=>'Needs help','all'=>'All'];$queue=sanitize_key($_GET['queue']??'review');
        foreach($queues as$key=>$name)echo '<a '.($key===$queue?'aria-current="page"':'').' href="'.esc_url(admin_url('admin.php?page=kr-return-desk&queue='.$key)).'">'.self::e($name).'</a>';echo '</nav>';
        if(isset($_GET['case'])){try{$r=Store::row(absint($_GET['case']));self::card(wc_get_order($r['order_id']),$r);}catch(\Throwable $e){echo '<p>Return not found.</p>';}}
        else{global$wpdb;$page=max(1,absint($_GET['paged']??1));$rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.\KREV_Returns::table().' ORDER BY id DESC LIMIT 30 OFFSET %d',($page-1)*30),ARRAY_A);$shown=0;
            foreach($rows as$r){$v=Store::state($r);$match=match($queue){'address'=>$v['case']==='approved'&&$v['label']['state']==='pending_address','label'=>in_array($v['label']['state'],['quote_ready','spending_approved','purchasing','purchase_unknown'],true),'shipment'=>$v['label']['state']==='ready'&&$v['shipment']['state']==='awaiting_handover','inspection'=>in_array($v['inspection']['state'],['due','discrepancy_reported','accepted','admin_review'],true),'refund'=>in_array($v['refund']['state'],['approved','submitting','pending','unknown','failed'],true),'help'=>!empty($v['needs_help']),'all'=>true,default=>in_array($v['case'],['submitted','seller_response_due','awaiting_evidence','admin_review'],true)};
                if(!$match)continue;++$shown;echo '<article class="kr-panel"><h2><a href="'.esc_url(admin_url('admin.php?page=kr-return-desk&case='.$r['id'])).'">Return #'.(int)$r['id'].'</a></h2><p>'.self::e(Cases::next($v)).'</p></article>';}
            if(!$shown)echo '<p>No cases match in this page of records.</p>';echo '<a href="'.esc_url(admin_url('admin.php?page=kr-return-desk&queue='.$queue.'&paged='.($page+1))).'">Next records →</a>';
        }
        self::policy();echo '</div>';
    }
    private static function policy(): void {
        $p=Policy::settings();echo '<details class="kr-panel"><summary>Policy preview &amp; launch controls</summary><p>These are operational targets, not a retroactive return window. Missing sold policies route to review. First-return-free, new return-review payout holds and financial execution remain OFF until separately approved and verified.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('kr_returns_policy');echo '<input type="hidden" name="action" value="kr_returns_policy">';
        foreach(['new_requests'=>'Accept new return requests','workers'=>'Run deadline review reminders']as$key=>$label)echo '<label><input type="checkbox" name="'.esc_attr($key).'" value="1" '.checked((bool)$p[$key],true,false).'> '.self::e($label).'</label>';
        foreach(['version'=>'Reviewed policy version','response_hours'=>'Seller response hours','address_hours'=>'Address confirmation hours','handover_days'=>'Buyer handover calendar days','inspection_business_days'=>'Inspection business days']as$key=>$label)echo '<label>'.self::e($label).'<input name="'.esc_attr($key).'" value="'.esc_attr($p[$key]).'" required></label>';
        echo '<p>Return label issuance: '.($p['label_issuance']?'configured ON; shared funding readiness still required':'OFF').'. Refund execution: '.($p['refund_execution']?'configured ON; verified gateway launch proof still required':'OFF').'. Separate return-label budget: $'.number_format($p['label_budget_minor']/100,2).'.</p><button>Save nonfinancial controls</button></form></details>';
    }
    public static function savePolicy(): void {
        if(!Access::admin())wp_die('Return Desk permission required.');check_admin_referer('kr_returns_policy');$p=Policy::settings();
        try{foreach(['response_hours'=>[1,168],'address_hours'=>[1,168],'handover_days'=>[1,60],'inspection_business_days'=>[1,30]]as$key=>$bounds)$p[$key]=Support::integer($_POST[$key]??0,...$bounds);$p['version']=Support::text(wp_unslash($_POST['version']??''),80);if(!trim($p['version']))Support::fail('policy','Enter a reviewed version.');$p['new_requests']=!empty($_POST['new_requests']);$p['workers']=!empty($_POST['workers']);update_option('kr_returns_policy',$p,false);}catch(\Throwable $e){wp_die(esc_html($e->getMessage()));}
        wp_safe_redirect(admin_url('admin.php?page=kr-return-desk'));exit;
    }
}
