<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\{Application,Documents};

final class Files {
    public static function scopeAccess(bool $allowed,array $scope,int $actor): bool {
        if (($scope['kind']??'')!=='buyer_return_evidence') return $allowed;
        try {
            $r=Store::row((int)($scope['case_id']??0));$role=Access::view($r,$actor);
            return (int)$scope['seller_id']===(int)$r['seller_id'] && (int)$scope['order_id']===(int)$r['order_id'] && (($scope['visibility']??'')==='shared'||$role==='admin');
        } catch (\Throwable $e) { return false; }
    }
    public static function documentAccess(mixed $allowed,array $doc,int $actor): mixed {
        $scope=$doc['scope']??[];
        if (($scope['kind']??'')==='buyer_return_evidence') {
            if (!self::scopeAccess(false,$scope,$actor)||$doc['attempt']!=='return-case-'.(int)$scope['case_id']) return false;
            $v=Store::state(Store::row((int)$scope['case_id']));
            foreach ($v['evidence']??[] as $e) if ($e['id']===$doc['id']&&$e['visibility']===$scope['visibility']) return true;
            return false;
        }
        $a=Application::db()->get('attempt',$doc['attempt']);
        if (($a['scope']['direction']??'')!=='return') return $allowed;
        try {
            $r=Store::row((int)$a['scope']['return_case']);Access::view($r,$actor);$v=Store::state($r);
            return (int)$doc['order_id']===(int)$r['order_id']&&(int)$doc['seller_id']===(int)$r['seller_id']&&($v['label']['document_id']??'')===$doc['id'] && ($v['label']['attempt_id']??'')===$a['id'] && $v['label']['state']==='ready' && in_array($a['state'],['label_confirmed','projected'],true) && isset($a['receipt']);
        } catch (\Throwable $e) { return false; }
    }
    public static function receive(int $id,array $file,array $input): array {
        if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||empty($file['tmp_name'])||!is_uploaded_file($file['tmp_name'])) Support::fail('file','Choose a supported screenshot or photo.',422);
        return self::storeLocal($id,$file['tmp_name'],$input,$file['name']??'Evidence');
    }
    /** Internal fixture/import entry; authorization is the same as HTTP upload. */
    public static function storeLocal(int $id,string $path,array $input,string $name='Evidence'): array {
        $r=Store::row($id);$role=Access::view($r);$visibility=$input['visibility']??'shared';
        if(!is_file($path)||is_link($path)||str_contains($path,'://')) Support::fail('file','A local evidence file is required.',422);
        $input['file_sha256']=hash_file('sha256',$path);Support::key($input['request_key']??'');
        if((int)($input['expected_revision']??-1)!==\KREV_Return_Workflow::details($id)['revision']) Support::fail('changed','Reload this case before adding evidence.');
        if (!in_array($visibility,['shared','admin_private'],true)||($visibility==='admin_private'&&$role!=='admin')) Support::fail('forbidden','Only Return Desk can attach private support evidence.',403);
        if (count(Store::state($r)['evidence']??[])>=20) Support::fail('limit','Ask support before adding more than twenty files to one case.');
        $scope=['kind'=>'buyer_return_evidence','case_id'=>$id,'order_id'=>(int)$r['order_id'],'seller_id'=>(int)$r['seller_id'],'visibility'=>$visibility];
        // Shared storage owns MIME screening, private encryption and metadata removal.
        $doc=(new Documents(Application::db()))->storeImageEvidence($scope,$path,hash_file('sha256',$path));
        return Store::edit($id,$input,'evidence',['buyer','seller','recipient','admin'],static function ($r,&$v) use($doc,$visibility,$name) {
            if (count($v['evidence'])>=20) Support::fail('limit','The case evidence limit was reached.');
            $v['evidence'][]=['id'=>$doc['id'],'visibility'=>$visibility,'name'=>substr(sanitize_file_name($name),0,100),'mime'=>$doc['mime'],'bytes'=>$doc['bytes'],'at'=>time(),'actor'=>get_current_user_id(),'metadata_removed'=>true];
        });
    }
    public static function response(int $id,string $docId): \WP_REST_Response {
        $r=Store::row($id);Access::view($r);$doc=Application::db()->get('document',$docId);
        if (!$doc) Support::fail('not_found','Private document not found.',404);
        $v=Store::state($r);$e=array_values(array_filter($v['evidence'],static fn($e)=>$e['id']===$docId));
        $label=($v['label']['document_id']??'')===$docId&&($v['label']['state']??'')==='ready';
        if (!$label && !$e) Support::fail('not_found','Private document not found.',404);
        $authorized=(new Documents(Application::db()))->authorized($docId,get_current_user_id());
        $body=$authorized['body']??file_get_contents($authorized['path']);
        if (!is_string($body)) Support::fail('file','The private file needs recovery.',503);
        $mime=$doc['mime']??'application/pdf';
        // A response-delivered telemetry event is descriptive, never "printed".
        Jobs::telemetry($id,$label?'label_downloaded':'evidence_downloaded',$docId);
        $response=new \WP_REST_Response(['private_body'=>$body],200);
        $response->header('Content-Type',$mime);
        $response->header('Content-Disposition','attachment; filename="KnifeRevive-return-'.($label?'label.pdf':($mime==='image/png'?'evidence.png':($mime==='image/webp'?'evidence.webp':'evidence.jpg'))).'"');
        $response->header('Cache-Control','private, no-store, max-age=0');$response->header('X-Content-Type-Options','nosniff');
        $response->header('Content-Security-Policy',"sandbox; default-src 'none'");$response->header('Referrer-Policy','no-referrer');
        return $response;
    }
    public static function serve(bool $served,\WP_REST_Response $response,\WP_REST_Request $request): bool {
        if (!preg_match('#^/kniferevive-returns/v1/cases/[0-9]+/files/[a-f0-9]{32}$#D',$request->get_route())||$response->get_status()!==200) return $served;
        $data=$response->get_data();if (!isset($data['private_body'])) return $served;
        echo $data['private_body'];return true;
    }
}
