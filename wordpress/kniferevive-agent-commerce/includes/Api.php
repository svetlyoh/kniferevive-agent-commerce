<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class Api {
    public const NS='kniferevive-agent/v1';
    public static function register(): void {
        foreach ([
            '/capabilities'=>['GET','capabilities'], '/catalog'=>['GET','catalog'], '/service-area'=>['GET','area'], '/availability'=>['GET','availability'],
            '/openapi'=>['GET','openapi'], '/sessions'=>['POST','session'], '/sessions/attach'=>['POST','attach'], '/quotes'=>['POST','quote'],
            '/listings'=>['GET','listings'],'/listings/(?P<product_id>[0-9]{1,10})'=>['GET','listing'],
            '/listing-checkouts'=>['POST','listingCreate'],'/listing-checkouts/(?P<id>[a-f0-9]{32})'=>['GET','listingGet'],
            '/listing-checkouts/(?P<id>[a-f0-9]{32})/quote'=>['POST','listingQuote'],
            '/listing-checkouts/(?P<id>[a-f0-9]{32})/status'=>['GET','listingStatus'],
            '/quotes/(?P<id>[a-f0-9]{32})'=>['GET','getQuote'], '/checkout-attempts'=>['POST','attempt'],
            '/checkout-attempts/(?P<id>[a-f0-9]{32})'=>['GET','status'], '/orders/(?P<id>[a-f0-9]{32})'=>['GET','status'],
            '/orders/(?P<id>[a-f0-9]{32})/change-requests'=>['POST','change'], '/stripe/webhook'=>['POST','webhook']
        ] as $route=>$spec) {
            register_rest_route(self::NS,$route,['methods'=>$spec[0],'permission_callback'=>'__return_true',
                'callback'=>static fn($request) => self::dispatch($spec[1],$request)]);
        }
    }
    public static function dispatch(string $method, $request): \WP_REST_Response|\WP_Error {
        $requestId=Domain::id();
        try {
            if (strlen((string)$request->get_body())>($method==='webhook'?262144:32768)) Domain::fail('REQUEST_TOO_LARGE','Request exceeds the size limit.',413);
            if ($method!=='webhook') self::rate('network:'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',wp_salt('auth')),120);
            $data=self::$method($request);
            $response=new \WP_REST_Response($data);
            $response->header('X-Request-Id',$requestId); $response->header('Referrer-Policy','no-referrer');
            $response->header('X-Content-Type-Options','nosniff'); $response->header('Cache-Control','no-store, private');
            if (in_array($method,['catalog','capabilities'],true)) {
                $etag='"'.Domain::digest($data).'"'; $response->header('ETag',$etag); $response->header('Cache-Control','public, max-age=30');
                if ($request->get_header('If-None-Match')===$etag) { $response->set_status(304); $response->set_data(null); }
            }
            return $response;
        } catch (\Throwable $e) {
            $fault=$e instanceof Fault ? $e : new Fault('TEMPORARILY_UNAVAILABLE','The request could not be completed. Retry the same operation or use the existing site.',503,true);
            return new \WP_Error($fault->codeName,$fault->getMessage(),['status'=>$fault->http,'request_id'=>$requestId,'retryable'=>$fault->retryable,
                'next_action'=>$fault->codeName==='RATE_LIMITED'?'wait_60_seconds':($fault->retryable?'retry_same_operation':'review_request')]);
        }
    }
    public static function rate(string $owner, int $max): void {
        $bucket=Domain::digest(['rate',$owner,intdiv(time(),60)]);
        Store::lock($bucket,static function () use ($bucket,$owner,$max) {
            try { $row=Store::get($bucket,'rate'); $count=$row['data']['count']+1; }
            catch (Fault $e) { if ($e->codeName!=='NOT_FOUND') throw $e; Store::put($bucket,'rate',$owner,time()+120,['count'=>1]); return; }
            if ($count>$max) Domain::fail('RATE_LIMITED','Rate limit reached. Retry after 60 seconds.',429,true);
            Store::update($bucket,['count'=>$count]);
        });
    }
    private static function body($request): array {
        if (!str_starts_with(strtolower((string)$request->get_header('Content-Type')),'application/json')) Domain::fail('INVALID_REQUEST','Send application/json.',415);
        $data=json_decode($request->get_body(),true,32,JSON_THROW_ON_ERROR);
        if (!is_array($data) || (array_is_list($data) && $data!==[])) Domain::fail('INVALID_REQUEST','Send a JSON object.',400);
        return $data;
    }
    public static function owner($request=null): string {
        $token=$request ? (string)$request->get_header('X-Krev-Agent-Session') : '';
        if (!$token) $token=isset($_COOKIE['krev_agent_session']) ? (string)wp_unslash($_COOKIE['krev_agent_session']) : '';
        $parts=explode('.',$token);
        if (count($parts)!==2 || !Domain::validId($parts[0]) || !hash_equals(Domain::token($parts[0]),$token)) Domain::fail('AUTHORIZATION_REQUIRED','A private shopper session is required.',401);
        $row=Store::get($parts[0],'session');
        if ((int)$row['expires']<time() || !hash_equals($row['data']['token_hash'],hash('sha256',$token))) Domain::fail('AUTHORIZATION_REQUIRED','The shopper session expired.',401);
        self::rate('session:'.$parts[0],60);
        return $parts[0];
    }
    public static function sameOrigin(): bool {
        $actual=parse_url($_SERVER['HTTP_ORIGIN']??''); $expected=parse_url(home_url());
        if (!$actual || !$expected || isset($actual['user']) || isset($actual['pass'])) return false;
        foreach (['scheme','host','port'] as $key) if (($actual[$key]??null)!==($expected[$key]??null)) return false;
        return true;
    }
    public static function firstPartyForm(): bool {
        if (self::sameOrigin()) return true;
        // Browsers can send Origin:null for a same-origin form under no-referrer.
        // Session ownership and the quote-specific CSRF token are also mandatory.
        return in_array($_SERVER['HTTP_ORIGIN']??'',['','null'],true) && ($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='same-origin';
    }
    public static function session($request): array {
        $body=self::body($request); Domain::fields($body,[]);
        $owner='guest:'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',wp_salt('auth'));
        self::rate('issue:'.$owner,10);
        $row=Store::idempotent($owner,'session',(string)$request->get_header('Idempotency-Key'),[],static function () use ($owner) {
            $id=Domain::id(); Store::put($id,'session',$owner,time()+7200,['token_hash'=>hash('sha256',Domain::token($id))]); return Store::get($id);
        });
        if ((int)$row['expires']<time()) Domain::fail('AUTHORIZATION_REQUIRED','Use a new session idempotency key.',401);
        return ['session_id'=>$row['id'],'session_token'=>Domain::token($row['id']),'expires_at'=>gmdate('c',(int)$row['expires'])];
    }
    public static function attach($request): array {
        Domain::fields(self::body($request),[]);
        if (!self::sameOrigin()) Domain::fail('FORBIDDEN','Use the first-party shopper page.',403);
        if (!is_ssl() && wp_get_environment_type()!=='local') Domain::fail('FORBIDDEN','A secure shopper page is required.',403);
        $owner=self::owner($request);
        setcookie('krev_agent_session',Domain::token($owner),['expires'=>(int)Store::get($owner,'session')['expires'],'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Strict']);
        return ['attached'=>true];
    }
    public static function capabilities($request): array {
        $s=Settings::get(); $rails=Settings::rails();
        return ['schema_version'=>'1.1','adapter_version'=>VERSION,'merchant'=>'KnifeRevive','merchant_origin'=>'https://kniferevive.com',
            'discovery'=>['anonymous'=>true,'catalog'=>true,'quote_requires_private_session'=>true],
            'sharpening'=>['status'=>Settings::operational()?'configured':'unconfigured','direct_checkout'=>(bool)$rails,'booking_mode'=>$s['slots']?'scheduled':($s['pending_scheduling']?'pending_scheduling':'unconfigured')],
            'technology'=>['catalog'=>true,'direct_checkout'=>false,'checkout_mode'=>'existing_woocommerce_checkout'],
            'listings'=>['discovery'=>true,'categories'=>'all_published','handoff_state'=>ListingCheckout::enabled()?'handoff_enabled':'unavailable',
                'checkout_mode'=>'buyer_completed_native_woocommerce','direct_payment_enabled'=>false,'simple_products'=>true,'variations'=>'unsupported_variation',
                'configured_gateway_ids'=>$s['listing_gateway_ids'],'gateway_availability'=>'validated_per_native_quote',
                'multi_seller'=>'separate_buyer_review_required','quote_reserves_stock'=>false,'service_payment_does_not_book_appointment'=>true],
            'payment_rails'=>$rails,'google_pay'=>['availability'=>'conditional_in_stripe_checkout','autonomous_spending'=>false],
            'stripe_environment'=>$s['stripe_live']?'live':'test','handoff_hosts'=>['kniferevive.com','checkout.stripe.com'],
            'policy_url'=>$s['policy_url']?:null,'return_policy_url'=>$s['return_policy_url']?:null,'openapi_url'=>rest_url(self::NS.'/openapi'),
            'limits'=>['quotes_per_session_per_minute'=>60,'public_requests_per_minute'=>120,'checkout_max_minor'=>$s['max_minor']],
            'timezone'=>'America/Los_Angeles','skill_cost_minor'=>0];
    }
    public static function catalog($request): array {
        $args=$request->get_query_params();
        foreach (['page','per_page'] as $key) if (isset($args[$key])) {
            if (!ctype_digit((string)$args[$key])) Domain::fail('INVALID_REQUEST','Invalid pagination.'); $args[$key]=(int)$args[$key];
        }
        $cache='krev_agent_catalog_'.Domain::digest([$args,Settings::get()['technology_category'],Settings::get()['policy_version']]);
        $value=get_transient($cache);
        if (is_array($value)) return $value;
        $value=Commerce::catalog($args); set_transient($cache,$value,30); return $value;
    }
    public static function area($request): array { Domain::fields($request->get_query_params(),['postal_code'],['postal_code']); return Commerce::area($request['postal_code']); }
    public static function availability($request): array {
        Domain::fields($request->get_query_params(),['postal_code','kind'],['postal_code']);
        $area=Commerce::area($request['postal_code']);
        $kind=$request['kind']??null;
        if ($kind!==null && !in_array($kind,['customer_dropoff','customer_collection','courier_pickup','courier_delivery'],true)) Domain::fail('INVALID_REQUEST','Unknown window kind.');
        return ['status'=>$area['status'],'eligible'=>$area['eligible'],'capacity_unit'=>'jobs','timezone'=>'America/Los_Angeles','windows'=>$area['eligible'] ? Store::slots($kind) : []];
    }
    public static function quote($request): array {
        $owner=self::owner($request); $row=Commerce::quote(self::body($request),$owner,(string)$request->get_header('Idempotency-Key'));
        return self::quoteResponse($row,$owner);
    }
    public static function quoteResponse(array $row,string $owner): array {
        $out=Commerce::publicQuote($row);
        $out['review_url']=add_query_arg(['krev_agent'=>'review','quote'=>$row['id']],home_url('/')).'#session='.rawurlencode(Domain::token($owner));
        // This link is private: the fragment becomes an HttpOnly cookie on the first-party page.
        $out['consent_id']=$row['data']['consent_id']??null; $out['attempt_id']=$row['data']['attempt_id']??null;
        return $out;
    }
    public static function getQuote($request): array { $owner=self::owner($request); return self::quoteResponse(Store::get($request['id'],'quote',$owner),$owner); }
    public static function attempt($request): array { $owner=self::owner($request); return Commerce::status(Commerce::attempt(self::body($request),$owner,(string)$request->get_header('Idempotency-Key'))); }
    public static function status($request): array {
        $owner=self::owner($request); $row=Store::get($request['id'],'attempt',$owner);
        if ((int)$row['expires']<time()) Domain::fail('AUTHORIZATION_REQUIRED','The private order access window expired.',401);
        Payments::reconcile($row['id']); return Commerce::status(Store::get($row['id'],'attempt',$owner));
    }
    public static function change($request): array {
        $owner=self::owner($request); $a=Store::get($request['id'],'attempt',$owner); $input=self::body($request);
        Domain::fields($input,['action','reason'],['action','reason']);
        if (!in_array($input['action'],['cancel','reschedule'],true)) Domain::fail('INVALID_REQUEST','Unknown change request.');
        $input['reason']=Domain::text($input['reason'],300);
        $r=Store::idempotent($owner,'change:'.$a['id'],(string)$request->get_header('Idempotency-Key'),$input,static function () use ($owner,$a,$input) {
            $id=Domain::id(); Store::put($id,'change',$owner,time()+30*86400,['attempt_id'=>$a['id'],'order_id'=>$a['data']['order_id'],'action'=>$input['action'],'reason'=>$input['reason'],'state'=>'requested']);
            return Store::get($id);
        });
        return ['request_id'=>$r['id'],'state'=>'requested','refund_state'=>'not_issued','next_action'=>'merchant_review'];
    }
    public static function webhook($request): array { return Payments::webhook($request->get_body(),(string)$request->get_header('Stripe-Signature')); }
    public static function listings($request): array {
        $args=$request->get_query_params();foreach(['page','per_page','seller'] as $key)if(isset($args[$key])){if(!ctype_digit((string)$args[$key]))Domain::fail('INVALID_REQUEST','Invalid numeric filter.');$args[$key]=(int)$args[$key];}
        return ListingCheckout::catalog($args);
    }
    public static function listing($request): array { return ListingCheckout::product((int)$request['product_id']); }
    public static function listingCreate($request): array { $owner=self::owner($request);return ListingCheckout::response(ListingCheckout::create(self::body($request),$owner,(string)$request->get_header('Idempotency-Key')),$owner); }
    public static function listingGet($request): array { $owner=self::owner($request);return ListingCheckout::response(ListingCheckout::get($request['id'],$owner,true),$owner); }
    public static function listingQuote($request): array { $owner=self::owner($request);return ListingCheckout::response(ListingCheckout::quote($request['id'],self::body($request),$owner,(string)$request->get_header('Idempotency-Key')),$owner); }
    public static function listingStatus($request): array { return ListingCheckout::status($request['id'],self::owner($request)); }
    public static function openapi($request): array { return json_decode(file_get_contents(dirname(__DIR__).'/assets/openapi.json'),true,64,JSON_THROW_ON_ERROR); }
}
