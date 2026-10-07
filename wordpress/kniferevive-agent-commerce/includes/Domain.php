<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

final class Fault extends \RuntimeException {
    public function __construct(public readonly string $codeName, string $message, public readonly int $http = 422, public readonly bool $retryable = false) {
        parent::__construct($message);
    }
}

/** Pure validation used by the API, persistence boundary, and behavioral tests. */
final class Domain {
    public static function fail(string $code, string $message, int $status = 422, bool $retry = false): never {
        throw new Fault($code, $message, $status, $retry);
    }
    public static function fields(array $data, array $allowed, array $required = []): void {
        if (array_diff(array_keys($data), $allowed) || array_diff($required, array_keys($data))) {
            self::fail('INVALID_REQUEST', 'Unexpected or missing fields.');
        }
    }
    public static function text(mixed $value, int $max = 200): string {
        if (!is_string($value) || strlen($value) > $max || preg_match('/[\x00-\x1f]/', $value)) self::fail('INVALID_REQUEST', 'Invalid text field.');
        return trim(wp_strip_all_tags($value));
    }
    public static function integer(mixed $value, int $min, int $max): int {
        if (!is_int($value) || $value < $min || $value > $max) self::fail('INVALID_REQUEST', 'Integer outside the permitted range.');
        return $value;
    }
    public static function cents(mixed $value): int {
        $value = (string)$value;
        if (!preg_match('/^([0-9]{1,9})(?:\.([0-9]{1,2}))?$/D', $value, $m)) self::fail('INVALID_AMOUNT', 'Unsupported money amount.');
        return (int)$m[1] * 100 + (int)str_pad($m[2] ?? '', 2, '0');
    }
    public static function decimal(int $minor): string { return sprintf('%d.%02d', intdiv($minor, 100), $minor % 100); }
    public static function canonical(mixed $value): string {
        $sort = static function ($v) use (&$sort) {
            if (!is_array($v)) return $v;
            if (!array_is_list($v)) ksort($v, SORT_STRING);
            foreach ($v as $k => $item) $v[$k] = $sort($item);
            return $v;
        };
        return json_encode($sort($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
    public static function digest(mixed $value): string { return hash('sha256', self::canonical($value)); }
    public static function id(): string { return bin2hex(random_bytes(16)); }
    public static function validId(mixed $id): bool { return is_string($id) && (bool)preg_match('/^[a-f0-9]{32}$/D', $id); }
    public static function key(mixed $key): string {
        if (!is_string($key) || !preg_match('/^[A-Za-z0-9:_-]{12,128}$/D', $key)) self::fail('IDEMPOTENCY_REQUIRED', 'Supply a 12–128 character Idempotency-Key.', 400);
        return $key;
    }
    public static function postal(mixed $zip): string {
        if (!is_string($zip) || !preg_match('/^[0-9]{5}$/D', $zip)) self::fail('INVALID_REQUEST', 'A five-digit US postal code is required.');
        return $zip;
    }
    public static function httpsHost(string $url, string $host): bool {
        if (strlen($url)>4096 || preg_match('/[\x00-\x20]/',$url)) return false;
        $p = parse_url($url);
        return is_array($p) && ($p['scheme'] ?? '') === 'https' && strtolower($p['host'] ?? '') === $host
            && !isset($p['user'], $p['pass']) && !isset($p['user']) && !isset($p['pass']) && (!isset($p['port']) || $p['port'] === 443);
    }
    public static function slotTime(string $value): int {
        if (!preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:Z|[+-]\d\d:\d\d)$/D', $value)) self::fail('INVALID_SETTINGS', 'Slot timestamps require seconds and an explicit offset.');
        try { $date = new \DateTimeImmutable($value); } catch (\Throwable $e) { self::fail('INVALID_SETTINGS', 'Invalid slot timestamp.'); }
        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors && ($errors['warning_count'] || $errors['error_count'])) self::fail('INVALID_SETTINGS', 'Invalid slot date.');
        // UTC is accepted; non-UTC representations must match LA's offset at that instant.
        if (!str_ends_with($value, 'Z') && $date->format('P') !== $date->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('P')) {
            self::fail('INVALID_SETTINGS', 'Slot offset does not match America/Los_Angeles.');
        }
        return $date->getTimestamp();
    }
    public static function stripeSignature(string $body, string $header, string $secret, int $now): bool {
        if (!$secret || strlen($body) > 262144) return false;
        $time = null; $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            if ($k === 't' && ctype_digit($v)) $time = (int)$v;
            if ($k === 'v1') $signatures[] = $v;
        }
        if ($time === null || abs($now - $time) > 300) return false;
        $expected = hash_hmac('sha256', $time . '.' . $body, $secret);
        foreach ($signatures as $signature) if (hash_equals($expected, $signature)) return true;
        return false;
    }
    public static function settledSession(array $session, array $attempt, bool $live): string {
        if (($session['metadata']['attempt_id'] ?? '') !== $attempt['id'] || ($session['id'] ?? '') !== ($attempt['provider_id'] ?? '')
            || ($session['currency'] ?? '') !== 'usd' || ($session['amount_total'] ?? null) !== $attempt['quote']['total_minor']
            || ($session['livemode'] ?? null) !== $live || ($session['mode'] ?? '') !== 'payment'
            || ($session['metadata']['quote_hash']??'') !== $attempt['quote']['quote_hash']) {
            self::fail('MANUAL_REVIEW_REQUIRED', 'Payment details do not match the order.', 409);
        }
        if (($session['payment_status'] ?? '') !== 'paid') return '';
        $intent = $session['payment_intent'] ?? '';
        if (is_array($intent)) $intent = $intent['id'] ?? '';
        if (!is_string($intent) || !preg_match('/^pi_[A-Za-z0-9]+$/D', $intent)) self::fail('MANUAL_REVIEW_REQUIRED', 'Payment reference is unavailable.', 409);
        return $intent;
    }
    public static function token(string $id): string { return $id . '.' . hash_hmac('sha256', 'krev-agent-session:' . $id, wp_salt('auth')); }
}
