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
    public static function check(string $postal): array {
        $postal=Domain::postal($postal);$data=self::data();$counties=$data['bay_postal_codes'][$postal]??[];
        $bay=array_keys($data['bay_counties']);$pickup=$data['pickup_counties'];
        $unknown=!$counties && !in_array($postal,$data['outside_postal_codes'],true);
        $eligible=(bool)array_intersect($counties,$pickup);
        $review=$unknown || ($counties && (bool)array_diff($counties,$bay)) || ($eligible && (bool)array_diff($counties,$pickup));
        $state=$review?'address_review_required':($eligible?'eligible':($counties?'bay_area_dropoff_only':'outside_bay_area'));
        $message=match($state){
            'eligible'=>'Pickup and prepayment are offered in Contra Costa and Santa Clara counties, subject to booking confirmation and payment availability.',
            'bay_area_dropoff_only'=>'Pickup service is not available in your area but will be available in the near future.',
            'outside_bay_area'=>'We do not currently offer sharpening services in your area. We are operating in the SF Bay Area only.',
            default=>'This ZIP code needs address review before KnifeRevive can confirm service coverage or accept prepayment.'
        };
        return ['postal_code'=>$postal,'coverage_state'=>$state,'service_available'=>(bool)$counties && !(bool)array_diff($counties,$bay),
            'pickup_eligible'=>$eligible,'prepayment_eligible'=>$eligible,'address_review_required'=>(bool)$review,
            'counties'=>array_values(array_map(static fn($id)=>$data['bay_counties'][$id]??'Outside SF Bay Area',$counties)),
            'message'=>$message,'source_url'=>$data['source_url'],'geography_vintage'=>$data['geography_vintage']];
    }
    public static function pickupPostcodes(): array {
        $data=self::data();return array_values(array_map('strval',array_keys(array_filter($data['bay_postal_codes'],static fn($counties)=>(bool)array_intersect($counties,$data['pickup_counties'])))));
    }
    public static function requireService(array $input): array {
        $c=self::check($input['postal_code']);
        if($c['coverage_state']==='outside_bay_area')Domain::fail('OUTSIDE_SERVICE_AREA',$c['message'],422);
        if(!$c['counties'])Domain::fail('COVERAGE_REVIEW_REQUIRED',$c['message'],422);
        $courier=$input['mode']==='prepaid_pickup' || $input['return_mode']==='courier_delivery';
        if($courier && !$c['pickup_eligible'])Domain::fail('PICKUP_UNAVAILABLE',$c['message'],422);
        // Other Bay Area residents can request drop-off and pay at service.
        if($input['mode']!=='pay_later_dropoff' && !$c['prepayment_eligible'])Domain::fail('PREPAYMENT_AREA_UNAVAILABLE',$c['message'].' Customer drop-off with payment at service is available in the Bay Area.',422);
        return $c;
    }
}
