<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger_REST {
    public static function routes(): void {
        foreach (['context'=>['GET','context'],'history'=>['GET','history'],'status'=>['GET','status'],'preview'=>['POST','preview'],'send'=>['POST','send']] as $suffix=>[$method,$callback]) register_rest_route('kniferevive/v1','/order-messages/(?P<order_id>\d+)/'.$suffix,['methods'=>$method,'callback'=>[self::class,$callback],'permission_callback'=>[self::class,'permission']]);
    }
    public static function permission(WP_REST_Request $req) {
        if (!get_current_user_id() || !wp_verify_nonce((string)$req->get_header('X-WP-Nonce'),'wp_rest')) return KR_Order_Messenger_Permissions::denied();
        $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); return is_wp_error($scope)?KR_Order_Messenger_Permissions::denied():true;
    }
    public static function response(array $data,int $code=200): WP_REST_Response { $r=new WP_REST_Response($data,$code); $r->header('Cache-Control','no-store, private'); $r->header('Referrer-Policy','no-referrer'); return $r; }
    public static function context(WP_REST_Request $req) { $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); return is_wp_error($scope)?$scope:self::response(KR_Order_Messenger_Context::data($scope)); }
    public static function history(WP_REST_Request $req) { $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); return is_wp_error($scope)?$scope:self::response(['attempts'=>KR_Order_Messenger_Log::history($scope)]); }
    public static function outcome($row): array {
        $status=$row->status;
        if ($status==='attempting' && strtotime($row->created_at_gmt.' UTC')<time()-10*MINUTE_IN_SECONDS) $status='unknown';
        return ['status'=>$status,'message'=>$status==='accepted'?'Update sent ✨':($status==='failed'?"That update didn't go through. Try again.":'Delivery is still in progress or uncertain. Do not resend this draft.'),'detail'=>$status==='accepted'?'Your message was handed off for email delivery.':($status==='failed'?'The transport reported a failure. You can retry explicitly.':'Check status again or contact the site administrator.'),'retry_safe'=>$status==='failed'];
    }
    public static function uuid($value): bool { return is_string($value) && (bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D',$value); }
    public static function status(WP_REST_Request $req) {
        $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); if (is_wp_error($scope)) return $scope;
        $uuid=$req['request_id']; if (!self::uuid($uuid)) return new WP_Error('kr_om_request','Invalid request ID.',['status'=>400]);
        $row=KR_Order_Messenger_Log::existing(KR_Order_Messenger_Log::key($scope,$uuid));
        return $row?self::response(self::outcome($row)):self::response(['status'=>'not_found','message'=>'This request has not been recorded. Contact the administrator before resending if the connection was interrupted.','retry_safe'=>false]);
    }
    public static function input(WP_REST_Request $req) {
        $message=KR_Order_Messenger_Context::message($req['message']); if (is_wp_error($message)) return $message;
        $files=KR_Order_Messenger_Attachments::files($req->get_file_params()); if (is_wp_error($files)) return $files;
        $expected=$req['attachment_count'];
        if (($files && $expected===null) || ($expected!==null && (!is_scalar($expected) || !ctype_digit((string)$expected) || (int)$expected!==count($files)))) return new WP_Error('kr_om_file','One or more screenshots were not received. Choose the files again. No email was sent.',['status'=>413]);
        $images=KR_Order_Messenger_Attachments::validate($files); if (is_wp_error($images)) return $images;
        return ['message'=>$message,'images'=>$images];
    }
    public static function preview(WP_REST_Request $req) {
        $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); if (is_wp_error($scope)) return $scope;
        $key='kr_om_preview_'.get_current_user_id(); $count=(int)get_transient($key); if ($count>=30) return new WP_Error('kr_om_rate','Too many previews. Try again in a minute.',['status'=>429]); set_transient($key,$count+1,MINUTE_IN_SECONDS);
        $input=self::input($req); if (is_wp_error($input)) return $input;
        $mail=new KR_Order_Messenger_Mailer($scope,$input['message'],[],$input['images']);
        return self::response(['html'=>$mail->preview(),'subject'=>$mail->context['subject'],'recipient'=>$mail->context['recipient'],'attachment_delivery'=>'attachments','attachment_count'=>count($input['images'])]);
    }
    public static function send(WP_REST_Request $req) {
        global $wpdb; $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); if (is_wp_error($scope)) return $scope;
        $uuid=$req['request_id']; if (!self::uuid($uuid)) return new WP_Error('kr_om_request','Invalid request ID.',['status'=>400]);
        $input=self::input($req); if (is_wp_error($input)) return $input;
        $digest=hash_hmac('sha256',wp_json_encode([$input['message'],array_column($input['images'],'hash')]),wp_salt('nonce'));
        $key=KR_Order_Messenger_Log::key($scope,$uuid); $existing=KR_Order_Messenger_Log::existing($key);
        if ($existing) return hash_equals($existing->payload_hash,$digest)?self::response(self::outcome($existing)):new WP_Error('kr_om_changed','This request ID belongs to a different draft.',['status'=>409]);
        // Acquire locks in stable sorted order so both vendor and acting-manager limits are atomic.
        $lockedVendor=$scope['vendor_id']; $lockedOrder=$scope['order']->get_id();
        $locks=['kr-om-v-'.md5(DB_NAME.$wpdb->prefix.$scope['vendor_id']),'kr-om-u-'.md5(DB_NAME.$wpdb->prefix.$scope['sender_id'])]; sort($locks); $held=[]; $paths=[]; $claimed=false;
        try {
            foreach ($locks as $lock) {
                if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$lock))!==1) return self::response(['status'=>'busy','message'=>'Another ping is being processed. Keep your draft and try after it finishes.','retry_safe'=>true],409);
                $held[]=$lock;
            }
            // Fresh rechecks after locking; ignore all browser-supplied seller/recipient/order flags.
            $scope=KR_Order_Messenger_Permissions::scope(absint($req['order_id'])); if (is_wp_error($scope)) return $scope;
            if ($scope['vendor_id']!==$lockedVendor || $scope['order']->get_id()!==$lockedOrder) return KR_Order_Messenger_Permissions::denied();
            $existing=KR_Order_Messenger_Log::existing($key); if ($existing) return self::response(self::outcome($existing));
            $context=KR_Order_Messenger_Context::data($scope);
            if (!$context['can_send']) return new WP_Error('kr_om_recipient',$context['disabled_reason'],['status'=>422]);
            if (KR_Order_Messenger_Log::limited($scope)) return new WP_Error('kr_om_rate','Ping limit reached. Try later; your draft is still here.',['status'=>429]);
            $paths=KR_Order_Messenger_Attachments::prepare($input['images']);
            $claimed=KR_Order_Messenger_Log::claim($scope,$uuid,$digest,$context['recipient'],$context['subject'],count($paths));
            if (!$claimed) return new WP_Error('kr_om_audit','Could not safely record this attempt. No email was sent.',['status'=>503]);
            $mail=new KR_Order_Messenger_Mailer($scope,$input['message'],$paths,$input['images']);
            $sent=$mail->deliver();
            $status=$sent?'accepted':'failed';
            if (!KR_Order_Messenger_Log::finish($scope,$uuid,$status,$sent?'':'transport_failed')) return self::response(['status'=>'unknown','message'=>'The mail transport completed, but audit confirmation failed. Do not resend.','retry_safe'=>false]);
            if ($sent) {
                try { $scope['order']->add_order_note('Seller sent a buyer email via KnifeRevive Order Messenger. Sender user #'.$scope['sender_id'].'; '.gmdate('Y-m-d H:i:s').' UTC.',false,false); }
                catch (Throwable $e) { wc_get_logger()->warning('Accepted ping note could not be added. Order #'.$scope['order']->get_id(),['source'=>'kr-order-messenger']); }
            }
            return self::response(self::outcome(KR_Order_Messenger_Log::existing($key)));
        } catch (Throwable $e) {
            if ($claimed) { KR_Order_Messenger_Log::finish($scope,$uuid,'unknown','transport_exception'); return self::response(['status'=>'unknown','message'=>'Delivery status is uncertain. Do not resend this draft.','retry_safe'=>false]); }
            if ($e instanceof InvalidArgumentException) return new WP_Error('kr_om_file','Converted screenshots exceed the payload limits. Resize them first. No email was sent.',['status'=>413]);
            return new WP_Error('kr_om_storage','Private screenshot processing or audit storage is unavailable. No email was sent.',['status'=>503]);
        } finally {
            KR_Order_Messenger_Attachments::cleanup($paths);
            foreach (array_reverse($held) as $lock) $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));
        }
    }
}
