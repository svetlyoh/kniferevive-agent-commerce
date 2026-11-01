<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Rest {
    public const NS='kniferevive-returns/v1';
    public static function auth(\WP_REST_Request $r): bool|\WP_Error {
        if(get_current_user_id()&&wp_verify_nonce($r->get_header('X-WP-Nonce')?:$r['_wpnonce'],'wp_rest'))return true;
        if(!get_current_user_id()&&Guest::csrf($r))return true;
        return new \WP_Error('return_auth','Sign in again or request a fresh secure return link.',['status'=>401]);
    }
    public static function input(\WP_REST_Request $r): array {
        $in=$r->get_json_params();if(!is_array($in))$in=$r->get_body_params();
        if(isset($in['payload'])){$decoded=json_decode((string)$in['payload'],true);if(!is_array($decoded))Support::fail('input','Invalid form data.',422);$in=$decoded;}
        return is_array($in)?$in:[];
    }
    public static function run(callable $work): mixed {try{$out=$work();\KREV_Return_Mail::schedule();return$out;}catch(\Throwable $e){return Support::error($e);}}
    public static function register(): void {
        register_rest_route(self::NS,'/guest/request',['methods'=>'POST','permission_callback'=>static fn()=>Guest::sameOrigin(),'callback'=>static fn($r)=>self::run(fn()=>Guest::request(self::input($r)))]);
        register_rest_route(self::NS,'/guest/exchange',['methods'=>'POST','permission_callback'=>static fn()=>Guest::sameOrigin(),'callback'=>static fn($r)=>self::run(fn()=>Guest::exchange((string)(self::input($r)['token']??'')))]);
        register_rest_route(self::NS,'/orders/(?P<id>\d+)/cases',[
            ['methods'=>'GET','permission_callback'=>[self::class,'auth'],'callback'=>static fn($r)=>self::run(function()use($r){$o=wc_get_order((int)$r['id']);if(!$o||!Access::buyer($o)&&!Access::admin())Support::fail('not_found','Order not found.',404);return array_map([Cases::class,'project'],\KREV_Returns::for_order($o->get_id()));})],
            ['methods'=>'POST','permission_callback'=>[self::class,'auth'],'callback'=>static fn($r)=>self::run(function()use($r){$o=wc_get_order((int)$r['id']);if(!$o)Support::fail('not_found','Order not found.',404);return array_map([Cases::class,'project'],Cases::create($o,self::input($r)));})]
        ]);
        register_rest_route(self::NS,'/cases/(?P<id>\d+)',['methods'=>'GET','permission_callback'=>[self::class,'auth'],'callback'=>static fn($r)=>self::run(fn()=>Cases::project(Store::row((int)$r['id'])))]);
        register_rest_route(self::NS,'/cases/(?P<id>\d+)/actions/(?P<action>[a-z_]+)',['methods'=>'POST','permission_callback'=>[self::class,'auth'],'callback'=>static fn($r)=>self::run(function()use($r){
            $id=(int)$r['id'];$a=(string)$r['action'];$in=self::input($r);
            if($a==='evidence')$row=Files::receive($id,$r->get_file_params()['file']??[],$in);
            elseif(str_starts_with($a,'label_'))$row=Labels::command($id,$a,$in,$r->get_file_params());
            elseif(str_starts_with($a,'refund_'))$row=Refunds::command($id,$a,$in);
            elseif(in_array($a,['receipt','inspection','inspection_review','restock'],true))$row=Inspection::command($id,$a,$in);
            elseif($a==='tracking_review')$row=Tracking::command($id,$in);
            elseif($a==='postage_plan')$row=Money::gamePlan($id,$in);
            elseif($a==='postage_iou')$row=Money::assess($id,$in);
            elseif($a==='telemetry'){$row=Store::row($id);Access::view($row);Jobs::telemetry($id,(string)($in['kind']??''),(string)($in['document_id']??''));}
            else $row=Cases::command($id,$a,$in);
            return Cases::project($row);
        })]);
        register_rest_route(self::NS,'/cases/(?P<id>\d+)/files/(?P<file>[a-f0-9]{32})',['methods'=>'GET','permission_callback'=>[self::class,'auth'],'callback'=>static fn($r)=>self::run(fn()=>Files::response((int)$r['id'],(string)$r['file']))]);
    }
}
