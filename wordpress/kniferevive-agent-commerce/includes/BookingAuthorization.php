<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

final class BookingAuthorization {
    public const VERSION='booking-contact-sharing-v1';
    /** Called only after first-party CSRF checks and an unchecked human consent control. */
    public static function grant(array $row): array {
        return ['source'=>'first_party_form','principal'=>$row['owner'],'merchant'=>'KnifeRevive',
            'purpose'=>'fulfill_this_sharpening_booking','booking_id'=>$row['id'],'version'=>self::VERSION,
            'selection_hash'=>Domain::digest($row['data']['input']),'proof_reference'=>Domain::id(),
            'granted_at'=>time(),'expires_at'=>(int)$row['data']['access_expires'],'revoked_at'=>null];
    }
    public static function address(array $row): string {
        $a=$row['data']['address_consent']??null;
        if(!$a)return $row['data']['booking_state']==='draft'?'needs_user':'unknown';
        if(!empty($a['revoked_at']))return 'revoked';
        if(($a['expires_at']??0)<time())return 'expired';
        if(($a['principal']??'')!==$row['owner'] || ($a['booking_id']??'')!==$row['id'] || !hash_equals($a['selection_hash']??'',Domain::digest($row['data']['input'])))return 'needs_user';
        if(isset($row['data']['input']['pickup_address']) && empty($row['data']['coverage_verified']))return 'merchant_review_required';
        return 'granted_for_order';
    }
    public static function payment(array $row): string {
        // No delegated host spending integration is installed. Never trust client assertions.
        if($row['data']['booking_state']==='cancelled')return 'revoked';
        if(empty($row['data']['listing_intent']))return 'needs_user';
        try{$intent=Store::get($row['data']['listing_intent'],'listing');}catch(Fault $e){return 'unknown';}
        $d=$intent['data'];
        if(empty($d['approved_at']) || empty($d['quote']))return 'needs_user';
        if(($d['quote_expires']??0)<time())return 'expired';
        // Purchase review is distinct from provider authentication or wallet spending authority.
        try{$fresh=ListingCheckout::price($d['selection'],$d['context']);}catch(\Throwable $e){return 'needs_user';}
        return hash_equals($fresh['quote_hash']??'',$d['quote']['quote_hash']??'')?'authorized_for_quote':'needs_user';
    }
}
