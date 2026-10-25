<?php
namespace KnifeRevive\CampaignLanding;
defined('ABSPATH') || exit;

final class Campaign {
    private static bool $dirty=false;
    const OPTION='kr_campaign_landing_v1';
    const TITLE='Buy & Sell Knives, Tech & More | KnifeRevive';
    const DESCRIPTION='Discover kitchen knives, technology and other finds. Browse seller listings or start your own shop on KnifeRevive. Learn about Dispatch.';
    public static function slots(): array {
        return [
            'wusthof'=>['label'=>'Wüsthof Gourmet','slug'=>'wusthof-gourmet-chefs-knife-8-inch','fallback'=>'/product-category/chefs-knife/','alt'=>'Wüsthof Gourmet chef’s knife, side view showing the photographed blade, handle and wear'],
            'fire-tv'=>['label'=>'Fire TV Stick 4K Select','slug'=>'amazon-fire-tv-stick-4k-select-with-alexa-voice-remote','fallback'=>'/technology-trade-desk/','alt'=>'Amazon Fire TV Stick 4K Select retail box, with its original printed remote artwork'],
            'cutco'=>['label'=>'Cutco 1728','slug'=>'model-1728-msrp-150','fallback'=>'/product-category/chefs-knife/','alt'=>'Cutco 1728 knife with black handle, side view of the photographed blade and markings'],
        ];
    }
    public static function config(): array {return array_merge(['page_id'=>0,'products'=>[],'measurement'=>false],(array)get_option(self::OPTION,[]));}
    public static function active(): bool {
        return !is_admin() && is_singular('page') && (get_queried_object_id()===(int)self::config()['page_id'] || is_page('your-next-find'));
    }
    public static function boot(): void {
        add_shortcode('kniferevive_next_find',[self::class,'render']);
        add_action('wp_enqueue_scripts',[self::class,'assets'],120);
        add_filter('pre_get_document_title',static fn($title)=>self::active()?self::TITLE:$title,99);
        add_filter('get_canonical_url',static fn($url)=>self::active()?home_url('/your-next-find/'):$url);
        add_action('wp_head',[self::class,'head'],5);
        add_filter('jetpack_open_graph_tags',[self::class,'jetpack_og'],99);
        add_filter('jetpack_seo_meta_tags',static function($tags){if(self::active()&&is_array($tags))$tags['description']=self::DESCRIPTION;return $tags;},99);
        foreach(['save_post_product','before_delete_post','woocommerce_update_product','woocommerce_product_set_stock_status','woocommerce_variation_set_stock_status','woocommerce_product_set_stock','woocommerce_variation_set_stock'] as $hook)add_action($hook,[self::class,'product_changed'],20);
        add_action('shutdown',static function(){if(self::$dirty)self::purge_page();},30);
        add_action('update_option_'.self::OPTION,static function($old,$new){self::purge_page(absint($old['page_id']??0));self::purge_page(absint($new['page_id']??0));},20,2);
    }
    public static function product_changed(mixed $value): void {
        $id=is_object($value)&&method_exists($value,'get_id')?(int)$value->get_id():(is_scalar($value)?absint($value):0);
        if(!$id)return;
        $parent=wp_get_post_parent_id($id);if($parent)$id=$parent;
        foreach(array_keys(self::slots()) as $key)if(self::product_id($key)===$id){self::$dirty=true;return;}
    }
    public static function purge_page(int $id=0): void {
        if(!$id)$id=absint(self::config()['page_id']);
        if(!$id){$page=get_page_by_path('your-next-find',OBJECT,'page');$id=$page?(int)$page->ID:0;}
        if(!$id||get_post_type($id)!=='page')return;
        clean_post_cache($id);if(function_exists('wp_cache_post_change'))wp_cache_post_change($id);
        do_action('kr_campaign_landing_cache_purged',$id); // Host/CDN integration point; no product/customer data.
    }
    public static function attribution(?array $query=null): array {
        $query=$query??wp_unslash($_GET);$safe=[];
        foreach(['utm_source','utm_medium','utm_campaign','utm_content'] as $key){
            $v=$query[$key]??null;
            if(is_string($v)&&preg_match('/^[A-Za-z0-9][A-Za-z0-9 _.:+\-]{0,95}$/D',$v))$safe[$key]=$v;
        }
        return $safe;
    }
    public static function url(string $path,bool $campaign=true): string {
        // Paths are code-owned; never turn an incoming query value into a redirect destination.
        if(!str_starts_with($path,'/')||str_starts_with($path,'//')||str_contains($path,'\\'))$path='/categories/';
        $url=home_url($path);return $campaign?add_query_arg(self::attribution(),$url):$url;
    }
    public static function image(string $key,bool $hero=false): string {
        static $meta=null;
        if($meta===null)$meta=json_decode((string)file_get_contents(dirname(KRCL_FILE).'/assets/images/manifest.json'),true)?:[];
        if(!isset($meta[$key]))return '';
        $variants=$meta[$key]['variants'];$base=plugins_url('assets/images/',KRCL_FILE);$srcset=[];
        foreach($variants as $v)$srcset[]=$base.$v['file'].' '.$v['width'].'w';
        $v=$variants[min(1,count($variants)-1)];$alt=self::slots()[$key]['alt']??'';
        return '<picture><img src="'.esc_url($base.$v['file']).'" srcset="'.esc_attr(implode(', ',$srcset)).'" sizes="'.esc_attr($key==='dispatch'?'(max-width: 760px) 90vw, 520px':($hero?'(max-width: 760px) 55vw, 400px':'(max-width: 620px) 90vw, 380px')).'" width="'.(int)$v['width'].'" height="'.(int)$v['height'].'" alt="'.esc_attr($alt).'" loading="'.($hero?'eager':'lazy').'" decoding="async"'.($hero&&$key==='wusthof'?' fetchpriority="high"':'').'></picture>';
    }
    public static function product_id(string $key): int {
        $slot=self::slots()[$key]??null;if(!$slot)return 0;
        $configured=absint(self::config()['products'][$key]??0);
        if($configured)return $configured;
        $post=get_page_by_path($slot['slug'],OBJECT,'product');return $post?(int)$post->ID:0;
    }
    public static function product(string $key): array {
        $slot=self::slots()[$key];$fallback=['label'=>$slot['label'],'title'=>$slot['label'],'url'=>self::url($slot['fallback']),'available'=>false,'reason'=>'woocommerce_unavailable'];
        if(!function_exists('wc_get_product'))return $fallback;
        $id=self::product_id($key);$p=$id?wc_get_product($id):false;
        if(!$p){$fallback['reason']='product_missing';return $fallback;}
        // Saved IDs remain authoritative when a legitimate listing slug changes.
        // New bindings are checked against the approved source slug in the owner settings editor.
        if($p->get_status()!=='publish'||post_password_required($id)||get_post_field('post_password',$id)!==''||$p->get_catalog_visibility()==='hidden'){$fallback['reason']='not_public';return $fallback;}
        if(!$p->is_in_stock()){$fallback['reason']='out_of_stock';return $fallback;}
        $url=$p->get_permalink();
        if(wp_parse_url($url,PHP_URL_HOST)!==wp_parse_url(home_url('/'),PHP_URL_HOST)){$fallback['reason']='invalid_permalink';return $fallback;}
        return ['label'=>$slot['label'],'title'=>html_entity_decode(wp_strip_all_tags($p->get_name()),ENT_QUOTES|ENT_HTML5,'UTF-8'),'url'=>add_query_arg(self::attribution(),$url),'available'=>true,'reason'=>'public_in_stock'];
    }
    public static function assets(): void {
        if(!self::active())return;
        wp_enqueue_style('kr-campaign-landing',plugins_url('assets/campaign.css',KRCL_FILE),[],KRCL_VERSION);
        // No new SDK, pixel, consent choice or endpoint. Optional custom events use the official sender only.
        if(self::config()['measurement']===true && class_exists('KnifeRevive\\MetaControls\\Controls') && \KnifeRevive\MetaControls\Controls::ready()){
            wp_enqueue_script('kr-campaign-landing',plugins_url('assets/campaign.js',KRCL_FILE),[],KRCL_VERSION,true);
            wp_add_inline_script('kr-campaign-landing','window.krCampaign='.wp_json_encode(['enabled'=>true,'content'=>in_array(self::attribution()['utm_content']??'', ['marketplace','dispatch'],true)?self::attribution()['utm_content']:'other']).';','before');
        }
    }
    public static function jetpack_og(array $tags): array {
        if(!self::active())return $tags;
        $tags['og:title']=self::TITLE;$tags['og:description']=self::DESCRIPTION;$tags['og:url']=home_url('/your-next-find/');
        $tags['og:image']=[plugins_url('assets/images/og-marketplace-1080.webp',KRCL_FILE)];
        $tags['og:image:width']='1080';$tags['og:image:height']='1080';$tags['og:image:alt']='KnifeRevive campaign with the actual Wüsthof, Fire TV Stick and Cutco seller photographs';return $tags;
    }
    public static function head(): void {
        if(!self::active()||defined('WPSEO_VERSION')||defined('RANK_MATH_VERSION')||defined('AIOSEO_VERSION'))return;
        if(!(class_exists('Jetpack_SEO')&&apply_filters('jetpack_seo_meta_tags_enabled',true)))echo '<meta name="description" content="'.esc_attr(self::DESCRIPTION).'">';
        if(!class_exists('Jetpack'))echo '<meta property="og:title" content="'.esc_attr(self::TITLE).'"><meta property="og:description" content="'.esc_attr(self::DESCRIPTION).'"><meta property="og:url" content="'.esc_url(home_url('/your-next-find/')).'"><meta property="og:image" content="'.esc_url(plugins_url('assets/images/og-marketplace-1080.webp',KRCL_FILE)).'">';
    }
    public static function button(string $label,string $path,string $event,string $class=''): string {
        return '<a class="kr-campaign-button '.esc_attr($class).'" href="'.esc_url(self::url($path)).'" data-kr-campaign-event="'.esc_attr($event).'">'.esc_html($label).'<span aria-hidden="true">↗</span></a>';
    }
    public static function categories(): array {
        $defs=[['Kitchen Knives','Useful kitchen tools. Compare the brand, photos and actual condition.','/shop/?kr_knives_only=1','chefs-knife','knife'],['Tech','Streaming devices, computers and practical accessories.','/technology-trade-desk/','technology','tech'],['Books','Specific editions. Second chapters. Your next good read.','/books/','books','book'],['Art Trade Gallery','Original works, artists and pieces with a point of view.','/art-trade-gallery/','art','art'],['Other Finds','World coins, regional pantry finds and interesting one-offs.','/other-finds/','other_finds','finds']];
        return array_map(static function($d){$term=get_term_by('slug',$d[3],'product_cat');$live=$term&&!is_wp_error($term)&&(int)$term->count>0;return ['label'=>$d[0],'copy'=>$d[1],'url'=>self::url($live?$d[2]:'/categories/'),'live'=>$live,'icon'=>$d[4]];},$defs);
    }
    public static function render(): string {
        if(!self::active())return '';
        $products=[];foreach(array_keys(self::slots()) as $key)$products[$key]=self::product($key);
        ob_start();require dirname(KRCL_FILE).'/templates/landing.php';return (string)ob_get_clean();
    }
}
