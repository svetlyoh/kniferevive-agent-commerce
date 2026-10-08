<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Durable rows and fenced MySQL advisory locks; no cache-based authorization. */
final class Store {
    public static function table(string $suffix): string { global $wpdb; return $wpdb->prefix . 'krev_agent_' . $suffix; }
    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $records = self::table('records'); $idem = self::table('idem'); $slots = self::table('slots'); $holds = self::table('holds');
        dbDelta("CREATE TABLE $records (
            id varchar(64) NOT NULL,
            kind varchar(24) NOT NULL,
            owner varchar(128) NOT NULL,
            expires bigint NOT NULL,
            data longtext NOT NULL,
            updated bigint NOT NULL,
            PRIMARY KEY  (id),
            KEY lookup (kind,updated),
            KEY owner (owner)
        ) ENGINE=InnoDB $charset;");
        dbDelta("CREATE TABLE $idem (
            scope char(64) NOT NULL,
            request_hash char(64) NOT NULL,
            resource_id varchar(64) NOT NULL,
            expires bigint NOT NULL,
            PRIMARY KEY  (scope)
        ) ENGINE=InnoDB $charset;");
        dbDelta("CREATE TABLE $slots (
            id varchar(64) NOT NULL,
            capacity int NOT NULL,
            start_at bigint NOT NULL,
            end_at bigint NOT NULL,
            kind varchar(32) NOT NULL,
            enabled tinyint NOT NULL DEFAULT 1,
            PRIMARY KEY  (id)
        ) ENGINE=InnoDB $charset;");
        dbDelta("CREATE TABLE $holds (
            slot_id varchar(64) NOT NULL,
            attempt_id varchar(64) NOT NULL,
            expires bigint NOT NULL,
            state varchar(16) NOT NULL,
            PRIMARY KEY  (slot_id,attempt_id),
            KEY attempt (attempt_id),
            KEY capacity (slot_id,state,expires)
        ) ENGINE=InnoDB $charset;");
        foreach (['records','idem','slots','holds'] as $suffix) {
            $table = self::table($suffix);
            $row = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS WHERE Name=%s', $table), ARRAY_A);
            if (!$row || strtolower($row['Engine']) !== 'innodb') Domain::fail('DATABASE_UNAVAILABLE', 'Transactional storage is required.', 503);
        }
        update_option('krev_agent_schema', 1, false);
    }
    public static function lock(string $scope, callable $work): mixed {
        global $wpdb;
        $name = 'krev_agent_' . substr(hash('sha256', self::table('records') . ':' . $scope), 0, 48);
        if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $name)) !== 1) Domain::fail('BUSY', 'Another request is processing. Retry the same operation.', 409, true);
        try {
            self::fence($name);
            $result = $work(); self::fence($name); return $result;
        } finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $name)); }
    }
    private static function fence(string $name): void {
        global $wpdb;
        if ((int)$wpdb->get_var($wpdb->prepare('SELECT IS_USED_LOCK(%s)=CONNECTION_ID()', $name)) !== 1) Domain::fail('DATABASE_UNAVAILABLE', 'Database lock was lost.', 503, true);
    }
    public static function transaction(callable $work): mixed {
        global $wpdb;
        if ($wpdb->query('START TRANSACTION') === false) Domain::fail('DATABASE_UNAVAILABLE', 'Database unavailable.', 503);
        try { $value = $work(); if ($wpdb->query('COMMIT') === false) throw new \RuntimeException(); return $value; }
        catch (\Throwable $e) { $wpdb->query('ROLLBACK'); throw $e; }
    }
    public static function get(string $id, ?string $kind = null, ?string $owner = null): array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('records') . ' WHERE id=%s', $id), ARRAY_A);
        if (!$row || ($kind !== null && $row['kind'] !== $kind) || ($owner !== null && !hash_equals($row['owner'], $owner))) Domain::fail('NOT_FOUND', 'Resource unavailable.', 404);
        $row['data'] = json_decode($row['data'], true, 64, JSON_THROW_ON_ERROR);
        return $row;
    }
    public static function put(string $id, string $kind, string $owner, int $expires, array $data): void {
        global $wpdb;
        $ok = $wpdb->insert(self::table('records'), ['id'=>$id,'kind'=>$kind,'owner'=>$owner,'expires'=>$expires,'data'=>Domain::canonical($data),'updated'=>time()]);
        if ($ok !== 1) Domain::fail('DATABASE_UNAVAILABLE', 'Cannot persist resource.', 503, true);
    }
    public static function update(string $id, array $data): void {
        global $wpdb;
        if ($wpdb->update(self::table('records'), ['data'=>Domain::canonical($data),'updated'=>time()], ['id'=>$id]) === false) Domain::fail('DATABASE_UNAVAILABLE', 'Cannot persist state.', 503, true);
    }
    public static function idempotent(string $owner, string $op, string $key, array $input, callable $create): array {
        global $wpdb;
        $scope = Domain::digest([$owner,$op,Domain::key($key)]); $hash = Domain::digest($input);
        return self::lock('idem:' . $scope, static function () use ($wpdb,$scope,$hash,$create) {
            $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('idem') . ' WHERE scope=%s', $scope), ARRAY_A);
            if ($row) {
                if (!hash_equals($row['request_hash'], $hash)) Domain::fail('IDEMPOTENCY_CONFLICT', 'This key belongs to a different request.', 409);
                return self::get($row['resource_id']);
            }
            return self::transaction(static function () use ($wpdb,$scope,$hash,$create) {
                $row = $create();
                if ($wpdb->insert(self::table('idem'), ['scope'=>$scope,'request_hash'=>$hash,'resource_id'=>$row['id'],'expires'=>time()+86400]) !== 1) Domain::fail('DATABASE_UNAVAILABLE', 'Cannot persist idempotency.', 503);
                return $row;
            });
        });
    }
    public static function syncSlots(array $slots): void {
        global $wpdb;
        self::lock('slots-admin', static function () use ($wpdb,$slots) {
            self::transaction(static function () use ($wpdb,$slots) {
                $table = self::table('slots');
                if ($wpdb->query("UPDATE $table SET enabled=0 WHERE id NOT LIKE 'booking-%'") === false) throw new \RuntimeException();
                foreach ($slots as $slot) {
                    $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%s FOR UPDATE", $slot['id']), ARRAY_A);
                    $start = Domain::slotTime($slot['start']); $end = Domain::slotTime($slot['end']);
                    if ($current && self::used($slot['id']) > 0 && ((int)$current['start_at'] !== $start || (int)$current['end_at'] !== $end || $current['kind'] !== $slot['kind'] || $slot['capacity'] < self::used($slot['id']))) {
                        Domain::fail('SLOT_IN_USE', 'An allocated slot cannot be moved or reduced below usage.', 409);
                    }
                    $sql = $wpdb->prepare("INSERT INTO $table (id,capacity,start_at,end_at,kind,enabled) VALUES(%s,%d,%d,%d,%s,1) ON DUPLICATE KEY UPDATE capacity=VALUES(capacity),start_at=VALUES(start_at),end_at=VALUES(end_at),kind=VALUES(kind),enabled=1", $slot['id'],$slot['capacity'],$start,$end,$slot['kind']);
                    if ($wpdb->query($sql) === false) throw new \RuntimeException();
                }
            });
        });
    }
    private static function used(string $slot): int {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::table('holds') . " WHERE slot_id=%s AND (state='confirmed' OR (state='held' AND expires>%d))", $slot,time()));
    }
    public static function slots(?string $kind = null): array {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table('slots') . " WHERE enabled=1 AND id NOT LIKE 'booking-%%' AND start_at>%d ORDER BY start_at,id LIMIT 200", time()), ARRAY_A);
        $out = [];
        foreach ($rows as $row) {
            if ($kind !== null && $row['kind'] !== $kind) continue;
            $out[] = ['slot_id'=>$row['id'],'kind'=>$row['kind'],'start_at'=>gmdate('c',(int)$row['start_at']),'end_at'=>gmdate('c',(int)$row['end_at']),'timezone'=>'America/Los_Angeles','available_jobs'=>max(0,(int)$row['capacity']-self::used($row['id']))];
        }
        return $out;
    }
    /** Call only inside a transaction; deterministic row-lock ordering avoids deadlocks. */
    public static function hold(array $legs, string $attempt, int $expires): void {
        global $wpdb;
        usort($legs, static fn($a,$b) => strcmp($a['slot_id'],$b['slot_id']));
        foreach ($legs as $leg) {
            $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('slots') . ' WHERE id=%s FOR UPDATE', $leg['slot_id']), ARRAY_A);
            if (!$row || !(int)$row['enabled'] || $row['kind'] !== $leg['kind'] || (int)$row['start_at'] <= $expires || self::used($row['id']) >= (int)$row['capacity']) Domain::fail('SLOT_UNAVAILABLE', 'The selected window cannot be reserved.', 409);
            if ($wpdb->insert(self::table('holds'), ['slot_id'=>$row['id'],'attempt_id'=>$attempt,'expires'=>$expires,'state'=>'held']) !== 1) throw new \RuntimeException();
        }
    }
    public static function confirm(string $attempt, int $paidAt): bool {
        global $wpdb;
        return self::transaction(static function () use ($wpdb,$attempt,$paidAt) {
            $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table('holds') . ' WHERE attempt_id=%s ORDER BY slot_id FOR UPDATE', $attempt), ARRAY_A);
            if (!$rows) return false;
            foreach ($rows as $row) {
                // Once released, a late payment cannot claim a slot even if the processor paid earlier.
                if ($row['state'] !== 'confirmed' && ($row['state'] !== 'held' || (int)$row['expires'] < time() || (int)$row['expires'] < $paidAt)) return false;
            }
            if ($wpdb->update(self::table('holds'), ['state'=>'confirmed'], ['attempt_id'=>$attempt]) === false) throw new \RuntimeException();
            return true;
        });
    }
    public static function release(string $attempt): void {
        global $wpdb;
        if ($wpdb->update(self::table('holds'), ['state'=>'released'], ['attempt_id'=>$attempt]) === false) throw new \RuntimeException();
    }
    public static function backlog(int $limit = 30): array {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table('records') . " WHERE kind IN ('attempt','change') ORDER BY updated ASC LIMIT %d", $limit), ARRAY_A);
        foreach ($rows as &$row) $row['data'] = json_decode($row['data'], true);
        return $rows;
    }
    public static function attemptsForJobs(int $limit = 30): array {
        global $wpdb;
        $rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.self::table('records')." WHERE kind='attempt' AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data,'$.auto_paused')),'false')!='true' AND CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data,'$.next_check_at')),'0') AS UNSIGNED)<=%d ORDER BY updated ASC LIMIT %d",time(),$limit),ARRAY_A);
        foreach ($rows as &$row) $row['data']=json_decode($row['data'],true);
        return $rows;
    }
    public static function attemptForIntent(string $intent): ?string {
        global $wpdb;
        if (!preg_match('/^pi_[A-Za-z0-9]+$/D',$intent)) return null;
        return $wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table('records')." WHERE kind='attempt' AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.verified_intent'))=%s LIMIT 1",$intent));
    }
    public static function unresolvedPurchase(string $owner,string $purchaseHash): ?string {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table('records')." WHERE kind='attempt' AND owner=%s AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.purchase_hash'))=%s AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_state')) IN ('creating','unknown','pending','review_required') LIMIT 1",$owner,$purchaseHash));
    }
    public static function unresolvedListing(string $owner,string $hash): ?string {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table('records')." WHERE kind='listing' AND owner=%s AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.purchase_hash'))=%s AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.handoff_state')) IN ('preparing_cart','cart_ready','order_linked') LIMIT 1",$owner,$hash));
    }
    public static function pruneEphemeral(): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM '.self::table('records')." WHERE kind IN ('rate','session','quote','consent','listing_quote') AND expires<%d",time()-86400));
        // Abandoned review-only PII expires; issued/interrupted financial evidence never does.
        $wpdb->query($wpdb->prepare('DELETE FROM '.self::table('records')." WHERE kind='listing' AND expires<%d
            AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.handoff_state'))='review'
            AND (JSON_EXTRACT(data,'$.order_id') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.order_id'))='null')
            AND (JSON_EXTRACT(data,'$.selection.booking_id') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.selection.booking_id'))='null')
            AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data,'$.creation_started')),'false')='false'",time()-86400));
        // Checkout, event, idempotency, and refund evidence is retained for operator reconciliation.
        $wpdb->query($wpdb->prepare('DELETE FROM '.self::table('records')." WHERE kind='booking' AND expires<%d AND (JSON_EXTRACT(data,'$.listing_intent') IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.listing_intent'))='null')",time()-86400));
    }
}
