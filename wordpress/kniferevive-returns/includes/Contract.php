<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

/** Prospective, disclosed contract only. Never repairs historical purchased terms. */
final class Contract {
    public static function apply(array $snapshot, array $config, int $purchasedAt, string $disclosedVersion): array {
        if (($config['enabled']??false)!==true) return $snapshot;
        $version=$config['version']??null;
        if (!is_string($version)||!preg_match('/^[a-zA-Z0-9._-]{1,80}$/D',$version)||$disclosedVersion!==$version) return $snapshot;
        $effective=$config['effective_at']??null;$approved=$config['approved_at']??null;$actor=$config['approved_by']??null;
        if (!is_int($effective)||!is_int($approved)||!is_int($actor)||$actor<=0||$approved<=0||$effective<$approved||$purchasedAt<$effective) return $snapshot;
        // This release supports the existing 15-day policy only. New windows,
        // charges, holds and untracked-date guesses cannot enter through config.
        $policy=$snapshot['policy']??[];
        if (($policy['type']??'')!=='returns'||(int)($policy['days']??0)!==15||($config['days']??null)!==15||($config['anchor']??'')!=='delivery') return $snapshot;
        if (!empty($snapshot['anchor'])) return $snapshot;
        $snapshot['anchor']='delivery';
        $snapshot['checkout_disclosure_version']=$version;
        $snapshot['prospective_contract']=['version'=>$version,'effective_at'=>$effective,'approved_by'=>$actor,'approved_at'=>$approved,'days'=>15,'unknown_delivery'=>'human_review'];
        return $snapshot;
    }
}
