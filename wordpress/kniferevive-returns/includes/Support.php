<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\{Check, Problem};

final class Support {
    public static function fail(string $reason, string $message, int $status=409): never {
        throw new Problem('return_'.$reason, $message, $status);
    }
    public static function text(mixed $value, int $max=5000): string {
        if (!is_scalar($value) || strlen((string)$value)>$max) self::fail('input', 'Use text within the displayed length limit.', 422);
        return sanitize_textarea_field((string)$value);
    }
    public static function note(mixed $value): string {
        if (!is_scalar($value) || strlen((string)$value)>12000) self::fail('input','Use a message up to 12,000 bytes.',422);
        return wp_kses((string)$value, ['p'=>[], 'br'=>[], 'strong'=>[], 'em'=>[], 'ul'=>[], 'ol'=>[], 'li'=>[], 'a'=>['href'=>[], 'title'=>[]]]);
    }
    public static function integer(mixed $value, int $min=1, int $max=10000): int {
        $n=filter_var($value,FILTER_VALIDATE_INT);
        if ($n===false || $n<$min || $n>$max) self::fail('quantity','Enter a whole quantity within the available range.',422);
        return $n;
    }
    public static function key(mixed $value): string {
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]{8,100}$/D',$value)) self::fail('key','Reload this form before submitting.',422);
        return $value;
    }
    public static function root(\WC_Order $order): \WC_Order {
        if (!$order->get_parent_id()) return $order;
        $root=wc_get_order($order->get_parent_id());
        if (!$root || $root->get_parent_id()) self::fail('scope','The original payment order needs review.');
        return $root;
    }
    public static function hash(array $data): string { return Check::hash($data); }
    public static function address(mixed $input): array {
        if (!is_array($input)) self::fail('address','Enter a structured US return address.',422);
        $out=[];
        foreach (['name','address_1','address_2','city','state','postcode','country'] as $key) $out[$key]=sanitize_text_field(self::text($input[$key]??'',200));
        $out['country']=strtoupper($out['country']); $out['state']=strtoupper($out['state']);
        if ($out['country']!=='US' || !$out['name'] || !$out['address_1'] || !$out['city'] || !preg_match('/^[A-Z]{2}$/D',$out['state']) || !preg_match('/^[0-9]{5}(?:-[0-9]{4})?$/D',$out['postcode'])) {
            self::fail('address','Confirm a complete domestic address, including state and ZIP.',422);
        }
        return $out;
    }
    public static function error(\Throwable $e): \WP_Error {
        return $e instanceof Problem ? new \WP_Error($e->reason,$e->getMessage(),['status'=>$e->status]) : new \WP_Error('return_unavailable','The return could not be confirmed. Reopen its existing status before retrying.',['status'=>503]);
    }
}
