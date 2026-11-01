<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\{Application,Assisted,Check,Documents,Ledger,Scope,ScopeAccess,Projection};

/** Fixed, incoming case scope: never substitutes the outbound remaining-items scope. */
final class ReturnScope implements Scope,ScopeAccess {
    public function __construct(private int $case,private bool $trusted=false){}
    public function authorize(int $seller,int $order): void {
        $r=Store::row($this->case);if(!$this->trusted)Access::requireRole($r,['admin']);
        if((int)$r['seller_id']!==$seller||(int)$r['order_id']!==$order)Support::fail('scope','The incoming return identity changed.');
    }
    public function snapshot(int $seller,int $order): array {
        $this->authorize($seller,$order);$r=Store::row($this->case);$v=Store::state($r);$o=wc_get_order($order);$i=$o?->get_item($r['order_item_id']);
        if(!$o||!$i||!$seller||$v['case']!=='approved'||!empty($v['no_return_needed'])||!isset($v['addresses']['origin'],$v['addresses']['destination']))Support::fail('scope','Approve the physical return and confirm both return addresses.');
        if(strtolower($o->get_currency())!=='usd'||in_array($v['reason_claimed']??'',['unsafe','authenticity'],true))Support::fail('scope','This return needs a supported manual resolution.');
        if(($v['policy']['physical']??null)!==true&&!isset($v['physical_review']))Support::fail('scope','Verify the sold physical-item evidence before issuing a label.');
        if((int)$r['quantity']>Policy::available($o,$i,(int)$r['id']))Support::fail('quantity','Other refunds or return allocations changed these units.');
        return ['eligible'=>true,'direction'=>'return','return_case'=>$this->case,'seller_id'=>$seller,'order_id'=>$order,'currency'=>'usd',
            'items'=>[$i->get_id()=>['quantity'=>(int)$r['quantity'],'name'=>$i->get_name(),'product_id'=>$i->get_product_id()]],
            'origin'=>$v['addresses']['origin']['value'],'destination'=>$v['addresses']['destination']['value'],
            'recipient'=>$v['recipient_seller_id']??$seller,'decision'=>$v['decision'],'addresses'=>$v['addresses'],
            'parent_order'=>Support::root($o)->get_id(),'refunded_minor'=>(int)round((float)Support::root($o)->get_total_refunded()*100),
            'cost_bearer'=>'platform_underwriting'];
    }
}

final class Labels {
    public static function canVoid(bool $allowed,array $a): bool {
        if(($a['scope']['direction']??'')!=='return')return$allowed;
        $r=Store::row((int)$a['scope']['return_case']);$v=Store::state($r);return in_array($v['shipment']['state'],['awaiting_handover'],true)&&$v['inspection']['state']==='not_due';
    }
    public static function capabilities(): array {
        return ['native_return_from_prior_woo_label'=>true,'native_operator_only'=>true,
            'quote_return_programmatic'=>false,'submit_return_programmatic'=>false,'lookup_return_by_attempt'=>false,
            'standalone_return'=>false,'external_label_private_attachment'=>Documents::ready(),
            'reason'=>'Woo 2.4.3 supports native return labels from an existing outbound Woo label. No standalone or timeout-safe programmatic purchase contract has been verified.'];
    }
    public static function flow(int $case,bool $trusted=false): Assisted {
        $p=Policy::settings();$g=\KnifeRevive\ShipKit\Policy::gates();
        // Both authorities must be ready. Existing outbound budgets, pauses and
        // cash/liability checks cannot be bypassed with a Returns setting.
        $g['return_labels']=!empty($p['label_issuance']);$g['purchases']=$g['purchases']&&$g['return_labels'];
        $g['return_label_limit']=(int)$p['label_limit_minor'];$g['return_budget']=(int)$p['label_budget_minor'];
        $g['policy_snapshot']['return_policy_version']=$p['version'];
        return new Assisted(Application::db(),new Ledger(Application::db()),new ReturnScope($case,$trusted),static fn()=>time(),$g);
    }
    private static function ownAttempt(int $id,string $attempt): array {
        $r=Store::row($id);Access::requireRole($r,['admin']);$a=Application::db()->get('attempt',Check::id($attempt));
        if(!$a||($a['scope']['direction']??'')!=='return'||($a['scope']['return_case']??0)!==$id||(int)$a['seller_id']!==(int)$r['seller_id'])Support::fail('scope','Incoming label attempt not found.',404);
        return $a;
    }
    private static function phase(int $id,array $input,string $action,array $data): array {
        return Store::edit($id,$input,$action,['admin'],static function($r,&$v)use($data){$v['label']=array_merge($v['label'],$data);});
    }
    /** Durable case intent before shared engine work. A crash leaves a visible,
     * recoverable intent keyed by the same request, never a second purchase. */
    public static function command(int $id,string $action,array $input,array $files=[]): array {
        $r=Store::row($id);Access::requireRole($r,['admin']);$db=Application::db();$flow=self::flow($id);
        if($action==='label_request') {
            $parcel=$input['parcel']??[];
            if(!is_array($parcel))Support::fail('parcel','Enter actual packed measurements.',422);
            $mode=$input['mode']??'';
            if(!in_array($mode,['woo_prior_outbound','external_operator'],true)||empty($input['packaging_reviewed']))Support::fail('provider','Choose the official native Woo return or a separately approved external-label exception and review packaging restrictions.');
            $outbound=Support::text($input['outbound_label_id']??'',100);
            if($mode==='woo_prior_outbound'&&!$outbound)Support::fail('provider','This native Woo path requires the actual prior outbound Woo label ID. No dummy outbound label will be purchased.');
            $r=Store::edit($id,$input,$action,['admin'],static function($r,&$v)use($parcel,$mode,$outbound,$input){
                if(!in_array($v['label']['state'],['pending_address','not_needed'],true)||$v['case']!=='approved'||!empty($v['no_return_needed']))Support::fail('label','Reopen the existing incoming label workflow.');
                (new ReturnScope((int)$r['id']))->snapshot((int)$r['seller_id'],(int)$r['order_id']);
                $v['label']=['state'=>'pending_address','intent_key'=>$input['request_key'],'parcel'=>$parcel,'mode'=>$mode,'outbound_label_id'=>$outbound,'packaging_reviewed_by'=>get_current_user_id()];
            });
            $a=$flow->request((int)$r['seller_id'],(int)$r['order_id'],$parcel,'return-'.$id.'-'.$input['request_key']);
            return self::recover($id,$a['id']);
        }
        $a=self::ownAttempt($id,(string)($input['attempt_id']??''));
        $v=Store::state($r);if(($v['label']['attempt_id']??null)!==$a['id'])Support::fail('label','Recover the case intent before continuing.');
        // Every shared-engine mutation is preceded by a revision-bound case
        // command. Replay uses the same immutable quote/claim/receipt identity.
        $r=Store::edit($id,$input,$action,['admin'],static function($r,&$v)use($action,$input){
            if($v['case']!=='approved')Support::fail('case','The return approval changed.');
            $v['label']['last_intent']=['action'=>$action,'key'=>$input['request_key'],'at'=>time()];
        });
        switch($action){
            case 'label_quote':
                $amount=filter_var($input['amount_minor']??null,FILTER_VALIDATE_INT);
                if(!$amount||$amount<1||$amount>100000||empty($input['exact_quote_attested']))Support::fail('quote','Attest the exact provider quote in cents, without markup.',422);
                $a=$flow->quote($a['id'],get_current_user_id(),$amount,0,sanitize_key($input['carrier']??''),Support::text($input['service']??'',150),self::evidence($id,$input),time()+900,0);
                break;
            case 'label_spending':
                if(empty($input['underwrite'])||empty($input['quote_hash']))Support::fail('spending','Separately approve KnifeRevive underwriting of this exact quote.');
                $a=$flow->approvePlatformReturn((int)$r['seller_id'],$a['id'],Support::text($input['quote_hash'],64));
                break;
            case 'label_claim':
                $a=$flow->claim($a['id'],get_current_user_id());
                break;
            case 'label_unknown':
                $flow->unknown($a['id'],(string)($a['claim']??''));break;
            case 'label_no_purchase':
                $a=$flow->confirmedNoLabel($a['id'],(string)($a['claim']??''),self::evidence($id,$input));break;
            case 'label_void':
                if(empty($input['provider_void_attested']))Support::fail('void','Review actual provider void evidence; requesting a void is not a confirmed void.');
                $a=(new \KnifeRevive\ShipKit\Operations($db,new Ledger($db)))->voidLabel($a['id'],Support::key($input['provider_reference']??''),(int)($input['approved_credit_minor']??0),self::evidence($id,$input));
                break;
            case 'label_cancel':
                $a=$flow->cancel((int)$r['seller_id'],$a['id']);break;
            case 'label_receipt':
                if(isset($a['receipt'],$a['document'])){(new Projection($db))->run($a['id']);break;}
                if(empty($input['provider_receipt_attested'])||empty($input['receipt'])||!is_array($input['receipt']))Support::fail('receipt','Review the actual provider receipt and matching PDF.');
                $receipt=$input['receipt'];$receipt['evidence']=self::evidence($id,$input);$receipt['currency']='usd';$receipt['amount']=(int)($receipt['amount']??0);
                $file=$files['file']??null;
                if(!$file||($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']))Support::fail('document','Attach the private provider PDF.',422);
                // Storage before receipt confirmation leaves an inaccessible
                // orphan on failure, not a published or falsely confirmed label.
                $doc=(new Documents($db))->store($a,$file['tmp_name'],$receipt['evidence']);
                $a=$flow->confirm($a['id'],(string)($a['claim']??''),$receipt);
                $db->transaction(function()use($db,$a,$doc){$fresh=$db->get('attempt',$a['id']);if(isset($fresh['document'])&&$fresh['document']!==$doc['id'])Support::fail('document','A label document is already bound. Reuse it.');$fresh['document']=$doc['id'];$db->put('attempt',$a['id'],$fresh);});
                (new Projection($db))->run($a['id']);break;
            default: Support::fail('label','Unsupported incoming label command.',422);
        }
        return self::recover($id,$a['id']);
    }
    private static function evidence(int $id,array $input): string {
        $doc=Application::db()->get('document',Check::id((string)($input['evidence_id']??'')));
        if(!$doc||($doc['scope']['case_id']??0)!==$id||($doc['scope']['kind']??'')!=='buyer_return_evidence')Support::fail('evidence','Attach this case’s private quote/receipt evidence before the provider action.');
        (new Documents(Application::db()))->authorized($doc['id'],get_current_user_id());return $doc['sha256'];
    }
    public static function recover(int $id,string $attempt,bool $trusted=false): array {
        $a=$trusted?Application::db()->get('attempt',Check::id($attempt)):self::ownAttempt($id,$attempt);if(!$a)Support::fail('label','Incoming attempt not found.');$r=Store::row($id);$o=wc_get_order($r['order_id']);
        return Store::locked($o,static function()use($id,$a){
            $r=Store::row($id);$v=Store::state($r);
            if(($a['scope']['direction']??'')!=='return'||($a['scope']['return_case']??0)!==$id)Support::fail('scope','Label recovery scope mismatch.');
            $state=match($a['state']){'needs_quote'=>'pending_address','quoted'=>'quote_ready','funded'=>'spending_approved','purchase_claimed'=>'purchasing','reconciling'=>'purchase_unknown','label_confirmed','projected'=>isset($a['document'])?'ready':'purchase_unknown','canceled','confirmed_no_label'=>'not_needed','voided'=>'voided',default=>'purchase_unknown'};
            if(($v['label']['attempt_id']??null)===$a['id']&&($v['label']['engine_hash']??'')===Check::hash($a))return$r;
            $v['label']=array_merge($v['label'],['state'=>$state,'attempt_id'=>$a['id'],'engine_hash'=>Check::hash($a)]);
            if(isset($a['quote']))$v['label']=array_merge($v['label'],['carrier'=>$a['quote']['carrier'],'service'=>$a['quote']['service'],'cost_minor'=>$a['quote']['label']]);
            if(isset($a['receipt'],$a['document'])&&$state==='ready'){
                $v['label']=array_merge($v['label'],['carrier'=>$a['receipt']['carrier'],'service'=>$a['receipt']['service'],'cost_minor'=>$a['receipt']['amount'],'tracking'=>$a['receipt']['tracking'],'document_id'=>$a['document'],'available_at'=>$v['label']['available_at']??time()]);
                if(!isset($v['deadlines']['handover'])&&($v['shipment']['state']??'')==='awaiting_handover')$v['deadlines']['handover']=Policy::deadline(time(),'handover',Policy::settings()['handover_days']);
                unset($v['deadlines']['address_confirmation']);
            }
            return Store::save($r,$v,$state==='ready'?'label_ready':'label_progress');
        });
    }
    public static function project(mixed $result,array $a): mixed {
        if(($a['scope']['direction']??'')!=='return')return$result;
        self::recover((int)$a['scope']['return_case'],$a['id'],true);return['id'=>'return-case-'.$a['scope']['return_case'],'direction'=>'return'];
    }
}
