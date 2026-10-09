<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Durable recipient jobs. Mailer acceptance is never described as inbox delivery. */
final class BookingOutbox {
    public static function enqueue(array $row,string $stage,array $roles=['customer','seller','admin']): void {
        $seller=BookingSeller::seller($row);
        $recipients=['customer'=>$row['data']['input']['customer']['email'],'admin'=>(string)get_option('admin_email')];
        if($seller)$recipients['seller']=(string)get_userdata($seller)->user_email;
        foreach($recipients as $role=>$address){
            if(!in_array($role,$roles,true))continue;
            $id=hash('sha256',$row['id'].':'.$stage.':'.$role.':'.strtolower($address));
            try{Store::get($id,'booking_mail');continue;}catch(Fault $e){if($e->codeName!=='NOT_FOUND')throw $e;}
            Store::put($id,'booking_mail',$row['id'],time()+90*86400,[
                'booking_id'=>$row['id'],'stage'=>$stage,'role'=>$role,'recipient'=>$address,'seller_id'=>$seller,
                'state'=>is_email($address)?'pending':'failed','attempts'=>0,'next_attempt'=>time(),
                'last_error'=>is_email($address)?null:'invalid_recipient','history'=>[]]);
        }
    }
    public static function boot(): void {add_action('krev_booking_mail',[self::class,'send']);}
    public static function schedule(string $booking): void {
        foreach(self::jobs($booking) as $job){
            $d=$job['data'];if($d['state']!=='pending')continue;
            if(function_exists('as_schedule_single_action') && did_action('action_scheduler_init')){
                try{as_schedule_single_action(max(time()+1,$d['next_attempt']),'krev_booking_mail',[$job['id']],'krev-booking',true);}catch(\Throwable $e){/* Durable row is swept by the existing cron. */}
            }
        }
    }
    public static function jobs(?string $booking=null): array {
        global $wpdb;
        $sql='SELECT * FROM '.Store::table('records')." WHERE kind='booking_mail'";
        if($booking!==null)$sql.=$wpdb->prepare(' AND owner=%s',$booking);
        $rows=$wpdb->get_results($sql.' ORDER BY updated ASC LIMIT 200',ARRAY_A);
        foreach($rows as &$r)$r['data']=json_decode($r['data'],true,32,JSON_THROW_ON_ERROR);
        return $rows;
    }
    public static function sweep(): void {
        global $wpdb;$ids=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.Store::table('records')." WHERE kind='booking_mail' AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.state'))='pending' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(data,'$.next_attempt')) AS UNSIGNED)<=%d ORDER BY updated LIMIT 30",time()));
        foreach($ids as $id){
            try{self::send($id);}catch(\Throwable $e){/* Pending job remains observable. */}
        }
    }
    public static function admin(): void {
        if(!current_user_can('manage_woocommerce'))return;
        echo '<h2>Booking notification health</h2><p>Accepted by mailer is not proof of inbox receipt. Failed and uncertain jobs require reconciliation. Email retry never creates an order.</p>';
        if(isset($_POST['krev_booking_mail_recover'])){
            check_admin_referer('krev_booking_mail_recover');
            try{self::recover((string)wp_unslash($_POST['booking_reference']??''),(string)wp_unslash($_POST['recipient_role']??''),($_POST['recover_confirm']??'')==='yes');echo '<p>Original recipient notification reconciled; existing accepted jobs are not resent.</p>';}
            catch(\Throwable $e){echo '<p>'.esc_html($e instanceof Fault?$e->getMessage():'The original notification needs review.').'</p>';}
        }
        echo '<details><summary>Reconcile one historical recipient notification</summary><p>Check the original mailbox/log history first. Earlier releases had no recipient outbox; an email sent by those releases cannot be automatically detected here. No bulk resends.</p><form method="post">';wp_nonce_field('krev_booking_mail_recover');echo '<label>Original booking reference <input name="booking_reference" pattern="[a-f0-9]{32}" required></label><label>Recipient role <select name="recipient_role"><option value="seller">Actual service seller</option><option value="customer">Booking customer</option><option value="admin">Configured site administrator</option></select></label><label><input type="checkbox" name="recover_confirm" value="yes" required> I checked prior delivery and approve this one recipient notification.</label><button class="button" name="krev_booking_mail_recover">Reconcile original recipient</button></form></details>';
        if(isset($_POST['krev_booking_mail_retry'])){
            check_admin_referer('krev_booking_mail_retry');
            try{self::retry((string)wp_unslash($_POST['mail_job_id']??''),($_POST['retry_confirm']??'')==='yes');echo '<p>Original notification queued for retry.</p>';}
            catch(\Throwable $e){echo '<p>'.esc_html($e instanceof Fault?$e->getMessage():'The original job needs reconciliation.').'</p>';}
        }
        echo '<table class="widefat"><tr><th>Booking/event</th><th>Recipient role</th><th>State</th><th>Attempts</th><th>Last error</th><th>Reconcile</th></tr>';
        foreach(self::jobs() as $job){$d=$job['data'];if(($d['state']==='pending' && (int)$d['next_attempt']<time()-300) || ($d['state']==='sending' && ($d['started_at']??0)<time()-600))echo '<tr><td colspan="6"><strong>Delayed or uncertain notification job needs queue/transport review.</strong></td></tr>';echo '<tr><td>'.esc_html($d['booking_id'].' / '.$d['stage']).'</td><td>'.esc_html($d['role']).'</td><td>'.esc_html($d['state']).'</td><td>'.esc_html((string)$d['attempts']).'</td><td>'.esc_html($d['last_error']??'—').'</td><td>';
            if(in_array($d['state'],['failed','sending'],true)){echo '<form method="post">';wp_nonce_field('krev_booking_mail_retry');echo '<input type="hidden" name="mail_job_id" value="'.esc_attr($job['id']).'"><label><input type="checkbox" name="retry_confirm" value="yes" required> I checked the original mail history and approve this recipient retry.</label><button name="krev_booking_mail_retry" class="button">Retry original mail job</button></form>';}
            echo '</td></tr>';
        }echo '</table>';
    }
    public static function send(string $id): void {
        Store::lock('booking-mail:'.$id,static function()use($id){
            $job=Store::get($id,'booking_mail');$d=$job['data'];
            if($d['state']!=='pending' || $d['next_attempt']>time())return;
            $row=Store::get($d['booking_id'],'booking');
            if($row['data']['booking_state']==='cancelled' || !Booking::merchantVisible($row)){
                $d['state']='failed';$d['last_error']='obsolete_notification_suppressed';Store::update($id,$d);return;
            }
            // Product transfer, disabled vendor, or changed recipients cannot leak the original contact.
            if($d['role']==='seller' && (!BookingSeller::seller($row) || BookingSeller::seller($row)!==$d['seller_id'] || get_userdata($d['seller_id'])->user_email!==$d['recipient'])){
                $d['state']='failed';$d['last_error']='seller_recipient_changed';Store::update($id,$d);return;
            }
            if(!is_email($d['recipient'])){$d['state']='failed';$d['last_error']='invalid_recipient';Store::update($id,$d);return;}
            $d['state']='sending';$d['attempts']++;$d['started_at']=time();Store::update($id,$d);
            $failed=false;$succeeded=false;
            $onFail=static function()use(&$failed){$failed=true;};$onSuccess=static function()use(&$succeeded){$succeeded=true;};
            add_action('wp_mail_failed',$onFail);add_action('wp_mail_succeeded',$onSuccess);
            try{$accepted=wp_mail($d['recipient'],'KnifeRevive sharpening '.$d['stage'].' — '.$row['data']['input']['preferred_date'],self::message($row,$d),['X-Krev-Notification: '.$id]);}
            catch(\Throwable $e){$accepted=false;$failed=true;}
            finally{remove_action('wp_mail_failed',$onFail);remove_action('wp_mail_succeeded',$onSuccess);}
            $accepted=$accepted && !$failed;
            $d['state']=$accepted?'accepted_by_mailer':($d['attempts']>=5?'failed':'pending');
            $d['last_error']=$accepted?null:'mail_transport_failed';$d['next_attempt']=time()+min(3600,60*(2**min(5,$d['attempts'])));
            $d['history'][]=['at'=>time(),'attempt'=>$d['attempts'],'result'=>$accepted?'accepted_by_mailer':'failed','mail_hook_observed'=>$succeeded||$failed];
            Store::update($id,$d);
        });
        $job=Store::get($id,'booking_mail');if($job['data']['state']==='pending')self::schedule($job['owner']);
    }
    private static function message(array $row,array $job): string {
        $i=$row['data']['input'];$id=$row['id'];
        $body='Sharpening booking '.$job['stage'].'. Reference: '.$id."\nRequested day: ".$i['preferred_date'].' (Pacific time).'."\nHandoff: ".$i['mode'].'; return: '.$i['return_mode'].".\n";
        foreach($row['data']['catalog_snapshot'] as $p)$body.=$p['title'].' × '.$p['quantity']."\n";
        $body.=($row['data']['booking_state']==='confirmed'?'The service day is confirmed.':'The requested day awaits merchant confirmation.').($i['mode']==='pay_later_dropoff'?"\nPay when you collect your sharpened knives.\n":"\nOnline payment verified on the original WooCommerce order.\n");
        $order=BookingOrderBridge::linked($row)??BookingEvents::nativeOrder($row);
        $body.=$order?'Native order reference: '.$order->get_order_number().' ('.$order->get_status().").\n":"No WooCommerce order has been created yet.\n";
        if($job['role']==='customer')$body.='Private booking link: '.add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/')).'#booking_access='.Booking::accessToken($row)."\n";
        elseif($job['role']==='seller')$body.='Review in your signed-in seller booking inbox: '.BookingSeller::url()."\n";
        else $body.='Review in WooCommerce → Agent Commerce: '.admin_url('admin.php?page=krev-agent-commerce')."\n";
        return $body.'Support: '.Settings::get()['booking_phone']."\n";
    }
    public static function retry(string $id,bool $confirmed): void {
        if(!current_user_can('manage_woocommerce') || !$confirmed)Domain::fail('FORBIDDEN','An administrator must confirm retrying this notification.',403);
        Store::lock('booking-mail:'.$id,static function()use($id){$job=Store::get($id,'booking_mail');$d=$job['data'];
            if(!in_array($d['state'],['failed','sending'],true))Domain::fail('INVALID_REQUEST','Only failed or uncertain notifications can be manually reconciled.');
            if($d['state']==='sending' && ($d['started_at']??0)>time()-600)Domain::fail('BUSY','The mail worker may still be sending.',409);
            // A crashed sending job is uncertain and is never automatically resent.
            $d['history'][]=['at'=>time(),'result'=>'admin_retry_authorized','admin_id'=>get_current_user_id()];
            $d['state']='pending';$d['next_attempt']=time();Store::update($id,$d);
        });self::schedule(Store::get($id,'booking_mail')['owner']);
    }
    public static function recover(string $id,string $role,bool $approved): void {
        if(!current_user_can('manage_woocommerce') || !$approved)Domain::fail('FORBIDDEN','An administrator must approve this historical recipient notification.',403);
        if(!Domain::validId($id) || !in_array($role,['seller','customer','admin'],true))Domain::fail('INVALID_REQUEST','Use the original reference and a supported recipient role.');
        Store::lock('booking:'.$id,static function()use($id,$role){$r=Store::get($id,'booking');
            if(!in_array($r['data']['booking_state'],['requested','confirmed'],true))Domain::fail('INVALID_REQUEST','Only an active submitted request can reconcile notifications.');
            if($role==='seller' && !BookingSeller::seller($r))Domain::fail('SELLER_UNAVAILABLE','The original active service seller could not be verified.');
            self::enqueue($r,$r['data']['booking_state'],[$role]);
        });self::schedule($id);
    }
}
