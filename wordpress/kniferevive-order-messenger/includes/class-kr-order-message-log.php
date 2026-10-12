<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger_Log {
    public static function table(string $suffix=''): string { global $wpdb; return $wpdb->prefix.'kr_order_messages'.$suffix; }
    public static function install(): void {
        global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php'; $t=self::table(); $d=self::table('_dedupe'); $charset=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $t (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint unsigned NOT NULL,
            vendor_id bigint unsigned NOT NULL,
            sender_user_id bigint unsigned NOT NULL,
            recipient_email_hash char(64) NOT NULL,
            subject text NOT NULL,
            attachment_count tinyint unsigned NOT NULL,
            client_request_uuid char(36) NOT NULL,
            status varchar(12) NOT NULL,
            created_at_gmt datetime NOT NULL,
            attempted_at_gmt datetime NOT NULL,
            error_code varchar(40) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            UNIQUE KEY request (vendor_id,order_id,client_request_uuid),
            KEY sender_date (sender_user_id,created_at_gmt),
            KEY vendor_date (vendor_id,created_at_gmt),
            KEY order_vendor_date (order_id,vendor_id,created_at_gmt)
        ) $charset;");
        // Minimal tombstones retain duplicate protection after detailed logs expire.
        dbDelta("CREATE TABLE $d (
            request_key char(64) NOT NULL,
            order_id bigint unsigned NOT NULL,
            vendor_id bigint unsigned NOT NULL,
            payload_hash char(64) NOT NULL,
            status varchar(12) NOT NULL,
            created_at_gmt datetime NOT NULL,
            error_code varchar(40) NOT NULL DEFAULT '',
            PRIMARY KEY  (request_key),
            KEY order_id (order_id)
        ) $charset;");
        if ($wpdb->get_var("SHOW TABLES LIKE '$t'")!==$t || $wpdb->get_var("SHOW TABLES LIKE '$d'")!==$d) throw new RuntimeException('Order Messenger audit storage could not be created.');
        update_option('kr_om_db_version',KR_OM_VERSION,false);
        if (!wp_next_scheduled('kr_om_cleanup')) wp_schedule_event(time()+HOUR_IN_SECONDS,'daily','kr_om_cleanup');
        if (!wp_next_scheduled('kr_om_sweep')) wp_schedule_event(time()+HOUR_IN_SECONDS,'hourly','kr_om_sweep');
    }
    public static function key(array $scope,string $uuid): string { return hash('sha256',$scope['vendor_id'].':'.$scope['order']->get_id().':'.$uuid); }
    public static function existing(string $key) { global $wpdb; return $wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('_dedupe').' WHERE request_key=%s',$key)); }
    public static function limited(array $scope): bool {
        global $wpdb; $s=KR_Order_Messenger_Admin::settings(); $t=self::table(); $now=time();
        $hour=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE order_id=%d AND vendor_id=%d AND created_at_gmt>=%s",$scope['order']->get_id(),$scope['vendor_id'],gmdate('Y-m-d H:i:s',$now-HOUR_IN_SECONDS)));
        $vendor=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE vendor_id=%d AND created_at_gmt>=%s",$scope['vendor_id'],gmdate('Y-m-d H:i:s',$now-DAY_IN_SECONDS)));
        $sender=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE sender_user_id=%d AND created_at_gmt>=%s",$scope['sender_id'],gmdate('Y-m-d H:i:s',$now-DAY_IN_SECONDS)));
        return $hour>=$s['order_hour'] || $vendor>=$s['seller_day'] || $sender>=$s['seller_day'];
    }
    public static function claim(array $scope,string $uuid,string $digest,string $recipient,string $subject,int $count): bool {
        global $wpdb; $now=gmdate('Y-m-d H:i:s'); $key=self::key($scope,$uuid);
        $ok=$wpdb->insert(self::table('_dedupe'),['request_key'=>$key,'order_id'=>$scope['order']->get_id(),'vendor_id'=>$scope['vendor_id'],'payload_hash'=>$digest,'status'=>'attempting','created_at_gmt'=>$now]);
        if (!$ok) return false;
        $ok=$wpdb->insert(self::table(),['order_id'=>$scope['order']->get_id(),'vendor_id'=>$scope['vendor_id'],'sender_user_id'=>$scope['sender_id'],'recipient_email_hash'=>hash_hmac('sha256',strtolower($recipient),wp_salt('auth')),'subject'=>$subject,'attachment_count'=>$count,'client_request_uuid'=>$uuid,'status'=>'attempting','created_at_gmt'=>$now,'attempted_at_gmt'=>$now]);
        if (!$ok) { $wpdb->delete(self::table('_dedupe'),['request_key'=>$key]); return false; }
        return true;
    }
    public static function finish(array $scope,string $uuid,string $status,string $error=''): bool {
        global $wpdb; $a=$wpdb->update(self::table('_dedupe'),['status'=>$status,'error_code'=>$error],['request_key'=>self::key($scope,$uuid)]);
        $b=$wpdb->update(self::table(),['status'=>$status,'error_code'=>$error],['order_id'=>$scope['order']->get_id(),'vendor_id'=>$scope['vendor_id'],'client_request_uuid'=>$uuid]);
        return $a!==false && $b!==false;
    }
    public static function history(array $scope): array {
        if (!KR_Order_Messenger_Admin::settings()['history']) return [];
        global $wpdb; $rows=$wpdb->get_results($wpdb->prepare('SELECT id,status,attachment_count,client_request_uuid,created_at_gmt,error_code FROM '.self::table().' WHERE order_id=%d AND vendor_id=%d ORDER BY id DESC LIMIT 30',$scope['order']->get_id(),$scope['vendor_id']),ARRAY_A);
        foreach ($rows as &$row) { $row['id']=(int)$row['id']; $row['attachment_count']=(int)$row['attachment_count']; } unset($row);
        return $rows;
    }
    public static function recent(): array { global $wpdb; return $wpdb->get_results('SELECT order_id,vendor_id,sender_user_id,status,attachment_count,created_at_gmt FROM '.self::table().' ORDER BY id DESC LIMIT 50'); }
    public static function cleanup(): void {
        global $wpdb; $cutoff=gmdate('Y-m-d H:i:s',time()-KR_Order_Messenger_Admin::settings()['retention_days']*DAY_IN_SECONDS);
        $wpdb->query($wpdb->prepare('DELETE FROM '.self::table().' WHERE created_at_gmt<%s',$cutoff));
        foreach ([self::table(),self::table('_dedupe')] as $t) $wpdb->query($wpdb->prepare("UPDATE $t SET status='unknown',error_code='stale_attempt' WHERE status='attempting' AND created_at_gmt<%s",gmdate('Y-m-d H:i:s',time()-10*MINUTE_IN_SECONDS)));
        KR_Order_Messenger_Attachments::sweep();
    }
    public static function remove_order(int $id): void { global $wpdb; $wpdb->delete(self::table(),['order_id'=>$id]); $wpdb->delete(self::table('_dedupe'),['order_id'=>$id]); }
}
