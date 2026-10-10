<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Public native product data and an exact-identifier index; no customer/order data. */
final class ListingDiscovery {
    public const FILTERS=['search','sku','model','mpn','gtin','brand','category','seller','stock_status','scope','page','per_page'];
    private const IDENTIFIERS=['sku','model','mpn','gtin','brand'];
    public static function boot(): void {
        if((int)get_option('krev_agent_identity_schema')!==1)self::install();
        if(!self::ready()){
            // Native taxonomies/product factories are unavailable before WooCommerce init.
            if(did_action('init'))self::batch();else add_action('init',[self::class,'batch'],99);
        }
        add_action('woocommerce_after_product_object_save',static fn($p)=>self::index($p->get_id()));
        add_action('set_object_terms',static function($id,$terms,$tt,$taxonomy){if($taxonomy==='product_brand' || $taxonomy==='pa_model-number')self::index((int)$id);},20,4);
        add_action('deleted_post',static function($id){global $wpdb;$wpdb->delete(Store::table('identity'),['product_id'=>$id]);});
        foreach(['created_term','edited_term','delete_term'] as $hook)add_action($hook,static function($id,$tt,$taxonomy){if(in_array($taxonomy,['product_brand','pa_model-number'],true))update_option('krev_agent_identity_cursor',0,false);},20,3);
        foreach(['added_post_meta','updated_post_meta','deleted_post_meta'] as $hook)add_action($hook,static function($mid,$id,$key){if(in_array($key,['_sku','_global_unique_id','_wc_gla_mpn','_google_mpn','_mpn','mpn'],true))self::index((int)$id);},20,3);
    }
    public static function install(): void {
        global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$table=Store::table('identity');$charset=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            product_id bigint unsigned NOT NULL,
            field varchar(16) NOT NULL,
            value_hash char(64) NOT NULL,
            PRIMARY KEY  (product_id,field,value_hash),
            KEY exact_value (value_hash,field,product_id)
        ) ENGINE=InnoDB $charset;");
        update_option('krev_agent_identity_schema',1,false);update_option('krev_agent_identity_cursor',0,false);
    }
    /** Upgrade/backfill is bounded and runs on merchant maintenance, never scans per search. */
    public static function batch(): void {
        global $wpdb;$cursor=get_option('krev_agent_identity_cursor',0);if($cursor==='done')return;
        $ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type='product' AND ID>%d ORDER BY ID LIMIT 100",(int)$cursor));
        foreach($ids as $id)self::index((int)$id);
        update_option('krev_agent_identity_cursor',count($ids)<100?'done':(int)end($ids),false);
    }
    public static function ready(): bool {return get_option('krev_agent_identity_cursor')==='done';}
    public static function normalize(string $value,string $field=''): string {
        $value=preg_replace('/\s+/u','',sanitize_text_field($value));
        return mb_strtoupper($field==='gtin'?str_replace('-','',$value):$value,'UTF-8');
    }
    public static function validGtin(string $value): bool {
        if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$value))return false;
        $sum=0;$weight=3;for($i=strlen($value)-2;$i>=0;--$i){$sum+=(int)$value[$i]*$weight;$weight=$weight===3?1:3;}
        return (10-$sum%10)%10===(int)substr($value,-1);
    }
    public static function serviceIds(): array {
        $s=Settings::get();$ids=[];foreach(['services','booking_services','listing_services'] as $key)foreach($s[$key] as $service)$ids[]=(int)$service['product_id'];
        return array_values(array_unique($ids));
    }
    public static function serviceCategories(): array {
        $term=get_term_by('slug','knife-sharpening','product_cat');if(!$term)return [];
        $children=get_term_children($term->term_id,'product_cat');return [(int)$term->term_id,...(is_wp_error($children)?[]:array_map('intval',$children))];
    }
    public static function isSharpening($p): bool {
        return $p && (in_array($p->get_id(),self::serviceIds(),true) || (bool)array_intersect($p->get_category_ids(),self::serviceCategories()));
    }
    public static function publicProduct($p): bool {
        return $p && $p->get_status()==='publish' && $p->get_catalog_visibility()!=='hidden' && !$p->is_type('variation') && $p->get_meta('_krev_listlab_archived')!=='yes' && (get_option('woocommerce_hide_out_of_stock_items')!=='yes' || $p->is_in_stock());
    }
    private static function plain($value): ?string {
        $value=is_scalar($value)?sanitize_text_field((string)$value):'';
        return $value!=='' && !in_array(strtolower($value),['unknown','n/a','na','none','not specified','not applicable'],true)?$value:null;
    }
    public static function identity($p): array {
        $out=['sku'=>self::plain($p->get_sku()),'brand'=>null,'model_number'=>null,'mpn'=>null,'gtin'=>null,'identifier_sources'=>[],'specifications'=>[],'images'=>[],'category_details'=>[]];
        if($out['sku']!==null)$out['identifier_sources']['sku']=['source'=>'woocommerce_sku','verification'=>'seller_claim'];
        $raw=method_exists($p,'get_global_unique_id')?(string)$p->get_global_unique_id():'';$gtin=self::normalize($raw,'gtin');
        if($raw!==''){$valid=self::validGtin($gtin);$out['gtin']=$valid?$gtin:null;$out['identifier_sources']['gtin']=['source'=>'woocommerce_global_unique_id','verification'=>'seller_claim','validation'=>$valid?'valid_check_digit':'invalid'];}
        foreach(['_wc_gla_mpn','_google_mpn','_mpn','mpn'] as $key)if(($mpn=self::plain($p->get_meta($key)))!==null){$out['mpn']=$mpn;$out['identifier_sources']['mpn']=['source'=>$key,'verification'=>'seller_claim'];break;}
        $brandTerms=taxonomy_exists('product_brand')?wp_get_post_terms($p->get_id(),'product_brand'):[];
        if(!is_wp_error($brandTerms) && $brandTerms){
            usort($brandTerms,static fn($a,$b)=>count(get_ancestors($b->term_id,'product_brand','taxonomy'))<=>count(get_ancestors($a->term_id,'product_brand','taxonomy')));
            $brand=reset($brandTerms);$ancestors=get_ancestors($brand->term_id,'product_brand','taxonomy');if($ancestors){$root=get_term((int)end($ancestors),'product_brand');if($root && !is_wp_error($root))$brand=$root;}
            $name=self::plain($brand->name);
            if($name!==null && !in_array(strtolower($name),['technology','technology & ai systems','tech','knife revive','kniferevive'],true)){$out['brand']=$name;$out['identifier_sources']['brand']=['source'=>'product_brand','verification'=>'seller_claim'];}
        }
        foreach($p->get_attributes() as $attr){
            if(!$attr->get_visible())continue;
            $values=$attr->is_taxonomy()?wp_get_post_terms($p->get_id(),$attr->get_name(),['fields'=>'names']):$attr->get_options();if(is_wp_error($values))continue;
            $values=array_values(array_filter(array_map([self::class,'plain'],$values),static fn($v)=>$v!==null));
            $out['specifications'][]=['name'=>sanitize_text_field(wc_attribute_label($attr->get_name())),'key'=>$attr->get_name(),'values'=>$values,'verification'=>'seller_claim'];
            if($attr->get_name()==='pa_model-number' && count($values)===1){$out['model_number']=$values[0];$out['identifier_sources']['model_number']=['source'=>'pa_model-number','verification'=>'seller_claim'];}
        }
        foreach(array_slice(array_unique(array_filter([$p->get_image_id(),...$p->get_gallery_image_ids()])),0,20) as $id){$url=wp_get_attachment_image_url($id,'full');if($url && get_post_status($id)==='inherit')$out['images'][]=['url'=>esc_url_raw($url),'alt'=>sanitize_text_field(get_post_meta($id,'_wp_attachment_image_alt',true))];}
        $terms=wp_get_post_terms($p->get_id(),'product_cat');if(!is_wp_error($terms))foreach($terms as $term)$out['category_details'][]=['id'=>(int)$term->term_id,'slug'=>$term->slug,'name'=>sanitize_text_field($term->name),'parent'=>(int)$term->parent];
        $out['weight']=['value'=>self::plain($p->get_weight()),'unit'=>get_option('woocommerce_weight_unit','kg')];
        $out['dimensions']=['length'=>self::plain($p->get_length()),'width'=>self::plain($p->get_width()),'height'=>self::plain($p->get_height()),'unit'=>get_option('woocommerce_dimension_unit','cm')];
        $out['identifier_sources']=(object)$out['identifier_sources'];return $out;
    }
    public static function index(int $id): void {
        global $wpdb;if(get_post_type($id)!=='product')return;$p=wc_get_product($id);if(!$p)return;
        $data=self::identity($p);$table=Store::table('identity');$rows=[];
        foreach(self::IDENTIFIERS as $field){$value=$data[$field==='model'?'model_number':$field];if($value!==null)$rows[]=$wpdb->prepare('(%d,%s,%s)',$id,$field,hash('sha256',self::normalize($value,$field)));}
        Store::transaction(static function()use($wpdb,$table,$id,$rows){
            if($wpdb->delete($table,['product_id'=>$id])===false)Domain::fail('DATABASE_UNAVAILABLE','Product identity index is unavailable.',503);
            if($rows && $wpdb->query("INSERT INTO $table (product_id,field,value_hash) VALUES ".implode(',',$rows))===false)Domain::fail('DATABASE_UNAVAILABLE','Product identity index is unavailable.',503);
        });
    }
    private static function scope(array $args,string $default='all_published'): string {
        $scope=$args['scope']??$default;if(!in_array($scope,['all_published','goods'],true))Domain::fail('INVALID_REQUEST','Choose goods or all_published scope.');return $scope;
    }
    private static function where(array $args): string {
        global $wpdb;$p='p';$where="p.post_type='product' AND p.post_status='publish'";
        $where.=" AND NOT EXISTS (SELECT 1 FROM $wpdb->postmeta a WHERE a.post_id=p.ID AND a.meta_key='_krev_listlab_archived' AND a.meta_value='yes')";
        $visibility=wc_get_product_visibility_term_ids();$hidden=array_filter([$visibility['exclude-from-catalog']??0,$visibility['exclude-from-search']??0]);
        // catalog-only and search-only are both public; hidden has both exclusion terms.
        if(count($hidden)===2)$where.=' AND (SELECT COUNT(*) FROM '.$wpdb->term_relationships.' v WHERE v.object_id=p.ID AND v.term_taxonomy_id IN ('.implode(',',array_map('intval',$hidden)).'))<2';
        if(get_option('woocommerce_hide_out_of_stock_items')==='yes' && !empty($visibility['outofstock']))$where.=' AND NOT EXISTS (SELECT 1 FROM '.$wpdb->term_relationships.' v WHERE v.object_id=p.ID AND v.term_taxonomy_id='.(int)$visibility['outofstock'].')';
        if(self::scope($args)==='goods'){
            $ids=self::serviceIds();if($ids)$where.=' AND p.ID NOT IN ('.implode(',',$ids).')';
            $terms=self::serviceCategories();if($terms)$where.=' AND NOT '.self::termExists('product_cat',$terms);
        }
        if(isset($args['category'])){$slug=Domain::text($args['category'],100);$term=get_term_by('slug',$slug,'product_cat');$where.=$term?' AND '.self::termExists('product_cat',[(int)$term->term_id,...array_map('intval',get_term_children($term->term_id,'product_cat'))]):' AND 1=0';}
        if(isset($args['seller']))$where.=$wpdb->prepare(' AND p.post_author=%d',Domain::integer($args['seller'],1,PHP_INT_MAX));
        if(isset($args['stock_status'])){if(!in_array($args['stock_status'],['instock','outofstock','onbackorder'],true))Domain::fail('INVALID_REQUEST','Invalid stock status.');$where.=$wpdb->prepare(" AND EXISTS (SELECT 1 FROM {$wpdb->wc_product_meta_lookup} stock WHERE stock.product_id=p.ID AND stock.stock_status=%s)",$args['stock_status']);}
        foreach(self::IDENTIFIERS as $field)if(isset($args[$field]))$where.=' AND '.self::exact($field,Domain::text($args[$field],100));
        return $where;
    }
    private static function termExists(string $taxonomy,array $terms): string {
        global $wpdb;return $wpdb->prepare("EXISTS (SELECT 1 FROM $wpdb->term_relationships tr JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id WHERE tr.object_id=p.ID AND tt.taxonomy=%s AND tt.term_id IN (".implode(',',array_map('intval',$terms)).'))',$taxonomy);
    }
    private static function exact(?string $field,string $value): string {
        global $wpdb;$table=Store::table('identity');$hash=hash('sha256',self::normalize($value,$field??''));
        if($field==='gtin' && !self::validGtin(self::normalize($value,'gtin')))Domain::fail('INVALID_REQUEST','Use a GTIN with a valid check digit.');
        if($field===null){$gtinHash=hash('sha256',self::normalize($value,'gtin'));return $wpdb->prepare("EXISTS (SELECT 1 FROM $table i WHERE i.product_id=p.ID AND (i.value_hash=%s OR (i.field='gtin' AND i.value_hash=%s)))",$hash,$gtinHash);}
        return $wpdb->prepare("EXISTS (SELECT 1 FROM $table i WHERE i.product_id=p.ID AND i.field=%s AND i.value_hash=%s)",$field,$hash);
    }
    public static function catalog(array $args): array {
        global $wpdb;Domain::fields($args,self::FILTERS);$scope=self::scope($args);$page=Domain::integer($args['page']??1,1,100);$per=Domain::integer($args['per_page']??10,1,100);
        if(!self::ready() && array_intersect(array_keys($args),['search',...self::IDENTIFIERS]))Domain::fail('DISCOVERY_INDEX_BUILDING','Exact product search is being refreshed. Browse categories or use the product page.',503,true);
        $where=self::where($args);$order='p.ID ASC';$search=null;
        if(isset($args['search'])){$search=Domain::text($args['search'],100);$exact=self::exact(null,$search);$like='%'.$wpdb->esc_like($search).'%';$where.=$wpdb->prepare(" AND ($exact OR p.post_title LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s)",$like,$like,$like);$order="$exact DESC, p.ID ASC";}
        $total=(int)$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts p WHERE $where");
        $ids=$wpdb->get_col($wpdb->prepare("SELECT p.ID FROM $wpdb->posts p WHERE $where ORDER BY $order LIMIT %d OFFSET %d",$per,($page-1)*$per));$items=[];
        foreach($ids as $id){try{$item=ListingCheckout::product((int)$id);if($scope==='goods' && self::isSharpening(wc_get_product($id)))continue;}catch(Fault $e){if($e->codeName==='NOT_FOUND')continue;throw $e;}
            $matched=[];foreach(self::IDENTIFIERS as $field){$value=$item[$field==='model'?'model_number':$field];if($value!==null && ((isset($args[$field]) && self::normalize($args[$field],$field)===self::normalize($value,$field)) || ($search!==null && self::normalize($search,$field)===self::normalize($value,$field))))$matched[]=$field;}
            $item['matched_fields']=$matched?:($search!==null?['keyword']:[]);$item['match_type']=$matched?'exact_identifier':($search!==null?'keyword':'browse');$items[]=$item;
        }
        return ['schema_version'=>'1.2','scope'=>$scope,'items'=>$items,'page'=>$page,'per_page'=>$per,'total'=>$total,'pages'=>(int)ceil($total/$per),'fetched_at'=>gmdate('c')];
    }
    public static function categories(array $args): array {
        global $wpdb;Domain::fields($args,['scope']);$scope=self::scope($args,'goods');$where=self::where(['scope'=>$scope]);
        $counts=$wpdb->get_results("SELECT tt.term_id,COUNT(DISTINCT p.ID) AS count FROM $wpdb->posts p JOIN $wpdb->term_relationships tr ON tr.object_id=p.ID JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id WHERE tt.taxonomy='product_cat' AND $where GROUP BY tt.term_id",OBJECT_K);
        $terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);$items=[];
        if(!is_wp_error($terms))foreach($terms as $term){$count=(int)($counts[$term->term_id]->count??0);if(!$count)continue;$url=get_term_link($term);if(is_wp_error($url))continue;$items[]=['id'=>(int)$term->term_id,'slug'=>$term->slug,'name'=>sanitize_text_field($term->name),'parent'=>(int)$term->parent,'canonical_url'=>$url,'visible_product_count'=>$count,'supported_filters'=>self::FILTERS];}
        return ['schema_version'=>'1.2','scope'=>$scope,'count_semantics'=>'direct_public_goods_assignments_including_unsupported_checkout','items'=>$items,'fetched_at'=>gmdate('c')];
    }
}
