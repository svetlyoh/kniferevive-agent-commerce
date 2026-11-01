<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\{Application,Check};

final class Jobs {
    public static function mail(int $id,string $key,string $email,string $subject,string $body): bool {
        if(!method_exists('KREV_Return_Mail','enqueue_message'))return false;
        $html=WC()->mailer()->wrap_message($subject,$body);
        return \KREV_Return_Mail::enqueue_message($id,$key,$email,$subject,$html,['Content-Type: text/html; charset=UTF-8']);
    }
    public static function enqueue(int $id,string $event): bool {
        $r=Store::row($id);$v=Store::state($r);$d=\KREV_Return_Workflow::details($id);$o=wc_get_order($r['order_id']);if(!$o)return false;
        $private=$event==='message'&&($v['messages'][array_key_last($v['messages'])]['visibility']??'')==='admin_private';
        $seller=get_userdata($r['seller_id']);$recipients=['admin'=>\KREV_Order_Contact::EMAIL];
        if(!$private){$recipients['buyer']=Support::root($o)->get_billing_email();if($seller)$recipients['seller']=$seller->user_email;$recipient=get_userdata((int)($v['recipient_seller_id']??0));if($recipient)$recipients['recipient']=$recipient->user_email;}
        $item=$o->get_item($r['order_item_id']);$title=$item?->get_name()??'Purchased item';
        foreach($recipients as$role=>$email){
            if(!is_email($email))continue;
            $link=$role==='admin'?admin_url('admin.php?page=kr-return-desk&case='.$id):Screens::caseUrl($id);
            $eventTitles=['requested'=>'Return requested','created'=>'Return requested','approved'=>'Return approved','declined'=>'Return decision recorded','label_ready'=>'Your return label is ready','message'=>'New message in your return','withdrawn'=>'Return request withdrawn','shipped'=>'Return shipment recorded','received'=>'Return received for review','resolved'=>'Return case resolved'];
            $titleCopy=$eventTitles[$event]??('Return: '.str_replace('_',' ',(string)$v['case']));
            $body='<p>'.esc_html($titleCopy).'. Here’s the next step.</p><p><strong>Order #'.esc_html($o->get_order_number()).' · Return #'.$id.'</strong><br>'.esc_html($title).' · Qty '.(int)$r['quantity'].'</p>';
            $body.='<p>'.esc_html(Cases::next($v)).'</p><p><a href="'.esc_url($link).'">View return details</a></p><p>Sign in as the purchasing customer or assigned seller. Guest buyers can request a new secure email-access link.</p><p><a href="'.esc_url(home_url('/help/contact/')).'">Need help?</a></p>';
            if($role==='buyer'&&class_exists('KREV_Email_Design'))$body.=\KREV_Email_Design::contact((int)$r['seller_id']);
            $eventIdentity=$event==='label_ready'?($v['label']['document_id']??$d['revision']):$d['revision'];
            $key=hash('sha256','returns-v2:'.$id.':'.$eventIdentity.':'.$event.':'.$role.':'.strtolower($email));
            if(!self::mail($id,$key,$email,'Order #'.$o->get_order_number().': '.$titleCopy.' — KnifeRevive',$body))return false;
        }
        return true;
    }
    public static function telemetry(int $id,string $kind,string $document): void {
        // Bounded, descriptive server events. Never "physically printed".
        if(!in_array($kind,['label_downloaded','evidence_downloaded','print_dialog_opened'],true))return;
        $r=Store::row($id);Access::view($r);$state=Store::state($r);if(in_array($kind,['label_downloaded','print_dialog_opened'],true)&&(($state['label']['document_id']??'')!==$document||$state['label']['state']!=='ready'))Support::fail('document','Current return label not found.',404);$db=Application::db();
        $actor=get_current_user_id()?:Guest::identity(Support::root(wc_get_order($r['order_id'])));
        $key=hash('sha256',$id.':'.$actor.':'.$kind.':'.$document.':'.gmdate('Y-m-d-H'));
        $record=['id'=>$key,'seller_id'=>(int)$r['seller_id'],'case'=>$id,'kind'=>$kind,'document'=>$document,'actor'=>get_current_user_id(),'at'=>time(),'meaning'=>$kind==='print_dialog_opened'?'Print action invoked; physical printing unverified':'Authorized server download response prepared; reading unverified'];
        $db->transaction(function()use($db,$key,$record){if(!$db->get('return_telemetry',$key))$db->put('return_telemetry',$key,$record,true);});
    }
    public static function tick(): void {
        if(!Policy::settings()['workers'])return;
        global$wpdb;
        $db=Application::db();$cursorAttempt=(string)get_option('kr_returns_attempt_cursor','');$attempts=array_values(array_filter($db->list('attempt'),static fn($a)=>($a['scope']['direction']??'')==='return'));usort($attempts,static fn($a,$b)=>strcmp($a['id'],$b['id']));$next=array_values(array_filter($attempts,static fn($a)=>strcmp($a['id'],$cursorAttempt)>0));if(!$next){update_option('kr_returns_attempt_cursor','',false);$next=$attempts;}
        foreach(array_slice($next,0,10)as$a){try{
            $id=(int)$a['scope']['return_case'];$flow=Labels::flow($id,true);
            if(in_array($a['state'],['funded','funding_reserved'],true)){try{$flow->assertCurrent($a['id']);}catch(\Throwable $e){$flow->cancelByPolicy($a['id'],'return_scope_or_quote_changed_before_purchase');}}
            Labels::recover($id,$a['id'],true);
        }catch(\Throwable $e){/* Original attempt/receipt remains for operator recovery; no new purchase. */}update_option('kr_returns_attempt_cursor',$a['id'],false);}
        $cursor=(int)get_option('kr_returns_deadline_cursor',0);
        $ids=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.\KREV_Returns::table().' WHERE id>%d ORDER BY id LIMIT 20',$cursor));
        if(!$ids){update_option('kr_returns_deadline_cursor',0,false);return;}
        foreach($ids as$id){
            $r=Store::row((int)$id);$v=Store::state($r);
            foreach($v['deadlines']??[]as$kind=>$deadline){
                if(($deadline['due_at']??PHP_INT_MAX)>=time()||in_array($v['case'],['withdrawn','declined','resolved'],true))continue;
                $key=hash('sha256','deadline:'.$id.':'.$kind.':'.$deadline['due_at']);
                self::mail((int)$id,$key,\KREV_Order_Contact::EMAIL,'KnifeRevive return needs review','<p>Return #'.(int)$id.' reached its '.esc_html(str_replace('_',' ',$kind)).' target. Review the case; no automatic denial, refund, payout hold or sanction was applied.</p><p><a href="'.esc_url(admin_url('admin.php?page=kr-return-desk&case='.(int)$id)).'">Open Return Desk</a></p>');
            }
            update_option('kr_returns_deadline_cursor',(int)$id,false);
        }
        \KREV_Return_Mail::schedule();
    }
}
