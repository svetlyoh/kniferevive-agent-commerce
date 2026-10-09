<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** ZIPs are a screen, not proof of the county containing a street address. */
final class BookingCoverage {
    private static function data(): array {
        static $data;
        if($data===null)$data=json_decode(file_get_contents(dirname(__DIR__).'/assets/booking-coverage.json'),true,32,JSON_THROW_ON_ERROR);
        return $data;
    }
    public static function check(string $postal,bool $custom=true): array {
        $postal=Domain::postal($postal);$data=self::data();$counties=$data['bay_postal_codes'][$postal]??[];
        $bay=array_keys($data['bay_counties']);$pickup=$data['pickup_counties'];
        $unknown=!$counties && !in_array($postal,$data['outside_postal_codes'],true);
        $eligible=(bool)array_intersect($counties,$pickup);
        $review=$unknown || ($counties && (bool)array_diff($counties,$bay)) || ($eligible && (bool)array_diff($counties,$pickup));
        $state=$review?'address_review_required':($eligible?'eligible':($counties?'bay_area_dropoff_only':'outside_bay_area'));
        $message=match($state){
            'eligible'=>'You’re in! We serve this ZIP. Pickup and online payment are available; KnifeRevive confirms your day and address after review.',
            'bay_area_dropoff_only'=>'You’re in our Bay Area zone! Drop-off is available. Pickup isn’t in your area yet — it’s coming in the near future.',
            'outside_bay_area'=>'Not in our zone just yet. We currently sharpen in the SF Bay Area only, so service isn’t available in this ZIP.',
            default=>'Quick address check needed. We can’t confirm service for this ZIP yet — KnifeRevive needs to review the address before online payment.'
        };
        $pickupEligible=$eligible;if($custom){$settings=Settings::get();if($settings['booking_pickup_limit_enabled'])$pickupEligible=$eligible && in_array($postal,$settings['booking_pickup_postal_codes'],true);}
        if($eligible && !$pickupEligible)$message='Drop-off? You’re in! You can pay online for drop-off. Pickup isn’t available in this ZIP yet — it’s coming in the near future.';
        return ['postal_code'=>$postal,'coverage_state'=>$state,'service_available'=>(bool)$counties && !(bool)array_diff($counties,$bay),
            'pickup_eligible'=>$pickupEligible,'prepayment_eligible'=>$eligible,'address_review_required'=>(bool)$review,
            'counties'=>array_values(array_map(static fn($id)=>$data['bay_counties'][$id]??'Outside SF Bay Area',$counties)),
            'message'=>$message,'source_url'=>$data['source_url'],'geography_vintage'=>$data['geography_vintage']];
    }
    public static function pickupPostcodes(): array {
        $settings=Settings::get();if($settings['booking_pickup_limit_enabled'])return $settings['booking_pickup_postal_codes'];
        $data=self::data();return array_values(array_map('strval',array_keys(array_filter($data['bay_postal_codes'],static fn($counties)=>(bool)array_intersect($counties,$data['pickup_counties'])))));
    }
    public static function describe(array $input): array {
        if($input['mode']==='pay_later_dropoff')return ['postal_code'=>'','coverage_state'=>'not_required','service_available'=>true,'pickup_eligible'=>false,'prepayment_eligible'=>false,'address_review_required'=>false,'counties'=>[],'message'=>'Drop off at KnifeRevive and pay when you collect. No ZIP check needed.'];
        return self::check($input['postal_code']);
    }
    public static function requireService(array $input): array {
        $c=self::describe($input);if($input['mode']==='pay_later_dropoff')return $c;
        if($c['coverage_state']==='outside_bay_area')Domain::fail('OUTSIDE_SERVICE_AREA',$c['message'],422);
        if(!$c['counties'])Domain::fail('COVERAGE_REVIEW_REQUIRED',$c['message'],422);
        $courier=$input['mode']==='prepaid_pickup' || $input['return_mode']==='courier_delivery';
        if($courier && !$c['pickup_eligible'])Domain::fail('PICKUP_UNAVAILABLE',$c['message'],422);
        // Other Bay Area residents can request drop-off and pay at service.
        if($input['mode']!=='pay_later_dropoff' && !$c['prepayment_eligible'])Domain::fail('PREPAYMENT_AREA_UNAVAILABLE',$c['message'].' Customer drop-off with payment at service is available in the Bay Area.',422);
        return $c;
    }
}
