<?php
/** Native profile, collection, import-contract and feed checks in the fenced DB. */
require __DIR__ . '/metacategories.php';
$startSpecs = $passed;
foreach ( KREV_PA_Config::all_definitions() as $slug => $definition ) {
    $taxonomy = wc_attribute_taxonomy_name( $slug );
    if ( ! taxonomy_exists( $taxonomy ) ) register_taxonomy( $taxonomy, 'product' );
}
$catalog = KREV_PA_Specifications::catalog();
techCheck(count($catalog['profiles']) === 1489, 'all Tech, Home & Garden and Collectibles rows have explicit specification profiles');
$validProfiles = true;
foreach ($catalog['profiles'] as $row) {
    if (count($row['fields']) !== 6 || count(array_unique($row['fields'])) !== 6 || array_diff($row['fields'], array_keys($catalog['fields']))) $validProfiles = false;
}
techCheck($validProfiles, 'every subcategory has exactly six distinct defined fields');
$phoneSchema = KREV_ListLab_Categories::schema($phoneTerm->term_id);
techCheck(array_column($phoneSchema['specification_profile']['fields'], 'taxonomy') === ['pa_storage','pa_display-size','pa_network-technology','pa_carrier-status','pa_battery-health','pa_color'], 'phone profile uses phone specifications rather than desktop CPU fields');
$phoneAttrs = array_column(KREV_ListLab_Attributes::schema($phoneTerm->term_id), 'taxonomy');
techCheck(in_array('pa_mpn',$phoneAttrs,true) && in_array('pa_condition',$phoneAttrs,true) && !in_array('pa_processor',$phoneAttrs,true), 'ListLab exposes the selected subtype profile plus condition and manufacturer code');
$homeTerm = get_term_by('slug','home-garden-google-6792','product_cat');
techCheck(count(KREV_PA_Specifications::schema($homeTerm->term_id)['specification_profile']['fields']) === 6, 'Home & Garden selected child advertises six fields');
$collectReport = KREV_PA_Tech_Categories::import_batch(100,'collectibles');
techCheck(!is_wp_error($collectReport) && $collectReport['completed'] === 34 && $collectReport['total'] === 34, 'all 34 official Collectibles children import natively');
KREV_PA_Metacategories::setup();
$collectRoot = KREV_PA_Tech_Categories::root_id('collectibles');
$cards = get_term_by('slug','collectibles-google-6997','product_cat');
$pokemon = get_term_by('slug','pokemon-cards','product_cat');
techCheck($pokemon && KREV_PA_Metacategories::is_meta($pokemon->term_id) && (int)$pokemon->parent === $collectRoot, 'Pokémon Cards is an independent collection under Collectibles');
techCheck(KREV_PA_Tech_Categories::mapping($pokemon->term_id) === null && KREV_PA_Tech_Categories::mapping($cards->term_id)['id'] === '6997', 'Pokémon collection never replaces the actual trading-card Google ID');
techCheck(count(KREV_ListLab_Categories::schema($collectRoot)['subcategories']) === 34 && count(KREV_PA_Metacategories::choices($collectRoot)) === 1, 'Collectibles selector separates searchable Google children from Pokémon collection');
techCheck(KREV_ListLab_Categories::selection([$pokemon->term_id]) === ['category_id'=>$collectRoot,'subcategory_id'=>0], 'collection-only Pokémon input resolves to its own parent rather than Tech');
techCheck(!KREV_PA_Metacategories::valid($root_id,$pokemon->term_id), 'Pokémon collection cannot be assigned across unrelated category roots');
techCheck(!KREV_PA_Metacategories::matches('mini-computers',['267'],'iPhone compatible with desktop backup',false), 'phone compatibility text cannot override its specific Google type');
techCheck(!KREV_PA_Metacategories::matches('iphones',['201'],'Apple Watch compatible with iPhone',true), 'watch compatibility text does not misclassify a watch as an iPhone');
techCheck(KREV_PA_Metacategories::matches('pokemon-cards',['6997'],'Pokémon Charizard',false) && !KREV_PA_Metacategories::matches('pokemon-cards',['6997'],'Magic the Gathering',false), 'Pokémon grouping requires franchise evidence rather than all trading cards');
$cardSchema = KREV_PA_Specifications::schema($cards->term_id)['specification_profile'];
techCheck(array_column($cardSchema['fields'],'taxonomy') === ['pa_card-game-franchise','pa_set-expansion','pa_card-name','pa_card-number','pa_rarity-finish','pa_card-grade'], 'trading-card profile includes set, number, finish and evidence-based grade');
$newInput = $metaInput; $newInput['category_id']=$collectRoot; $newInput['subcategory_id']=(int)$cards->term_id; $newInput['metacategory_id']=(int)$pokemon->term_id;
$newInput['client_uuid']=wp_generate_uuid4();$newInput['title']='Synthetic Pokémon fixture';
$newInput['attributes']=['pa_condition'=>['Used'],'pa_card-game-franchise'=>['Pokémon'],'pa_card-name'=>['Charizard'],'pa_card-number'=>['__krev_custom__:004'],'pa_card-grade'=>['Unknown'],'pa_mpn'=>['__krev_custom__:SV-004']];
$savedCard=KREV_ListLab_Product_Writer::save($newInput);
techCheck(!is_wp_error($savedCard) && $savedCard['metacategory_id']===(int)$pokemon->term_id, 'ListLab saves Pokémon and its Google trading-card child together');
$cardProduct=wc_get_product($savedCard['id']);
techCheck(KREV_Google_Category_Mapper::resolve_for_product($cardProduct)['id']==='6997', 'saved Pokémon feed retains Google trading-card category');
techCheck(in_array('004',wp_get_object_terms($cardProduct->get_id(),'pa_card-number',['fields'=>'names']),true), 'numeric card code retains leading zeros as a real specification');
techCheck(KREV_ListLab_Product_Reader::dto($cardProduct)['subcategory_id']===(int)$cards->term_id, 'reopening preserves Google child independently from the collection');
// Inspect the feed projection without network workers or persistence side effects.
$mapper=new KREV_Product_Mapper();$detailsMethod=new ReflectionMethod($mapper,'product_details');$detailsMethod->setAccessible(true);
$details=$detailsMethod->invoke($mapper,$cardProduct);
techCheck((bool)array_filter($details,static fn($d)=>$d['attributeValue']==='004'), 'confirmed card specifications project to Google productDetails');
techCheck(!(bool)array_filter($details,static fn($d)=>$d['attributeValue']==='Unknown'), 'unknown grade is omitted from Google specifications');
update_post_meta($image,'_wp_attached_file','synthetic/specifications-fixture.jpg');
$cardProduct->set_status('publish');$cardProjection=$mapper->map($cardProduct,false);
techCheck(!is_wp_error($cardProjection) && $cardProjection['productAttributes']['mpn']==='SV-004' && $cardProjection['productAttributes']['googleProductCategory']==='6997', 'full Google projection exports a confirmed manufacturer code without replacing the category ID');
$homeInput=$newInput;$homeInput['category_id']=(int)$homeTerm->parent;$homeInput['subcategory_id']=(int)$homeTerm->term_id;$homeInput['metacategory_id']=0;$homeInput['client_uuid']=wp_generate_uuid4();$homeInput['title']='Synthetic Home fixture';$homeInput['attributes']=['pa_material'=>['Steel'],'pa_dimensions'=>['12 × 8 inches'],'pa_condition'=>['Used']];
$savedHome=KREV_ListLab_Product_Writer::save($homeInput);$homeProduct=wc_get_product($savedHome['id']);$homeDetails=$detailsMethod->invoke($mapper,$homeProduct);
techCheck((bool)array_filter($homeDetails,static fn($d)=>$d['attributeValue']==='Steel'), 'Home & Garden specifications also project to Google productDetails');
$homeProduct->set_status('publish');$homeProjection=$mapper->map($homeProduct,false);
techCheck(!is_wp_error($homeProjection) && $homeProjection['productAttributes']['material']==='Steel', 'confirmed material also fills the standard Google material attribute');
KREV_ListLab_Attributes::save($phone,$phoneTerm->term_id,['pa_model-code'=>['A2111'],'pa_condition'=>['Used'],'pa_storage'=>['64 GB']]);
$phone->set_image_id($image);$phone->set_description('Synthetic model-code separation fixture.');$phone->save();$phoneProjection=$mapper->map($phone,false);
techCheck(!is_wp_error($phoneProjection) && !isset($phoneProjection['productAttributes']['mpn']), 'a stated phone model code is never fabricated into an MPN');
techCheck((bool)array_filter($phoneProjection['productAttributes']['productDetails'] ?? [],static fn($d)=>$d['attributeValue']==='A2111'), 'separate phone model code is retained in Google productDetails');
require_once dirname(__DIR__).'/wordpress/kniferevive-listlab-import/kniferevive-listlab-import.php';
$importReq=new WP_REST_Request('GET');$importReq->set_param('category_id',$cards->term_id);
$importSchema=KREV_Marketplace_Imports::schema($importReq)->get_data();
techCheck(count($importSchema['specification_profile']['fields'])===6 && in_array('metacategory_id',$importSchema['listing_fields'],true) && in_array('subcategory_id',$importSchema['listing_fields'],true), 'Muse public schema advertises six fields and separate collection/subcategory IDs');
$badImport=new WP_REST_Request('POST');$badImport->set_body_params(['source_url'=>'https://www.facebook.com/marketplace/item/999999994/','source_price'=>'25','currency'=>'USD','title'=>'Synthetic cross-root test','category_id'=>$root_id,'metacategory_id'=>$pokemon->term_id]);
$_SERVER['REMOTE_ADDR']='192.0.2.44';delete_transient('krev_import_site_rate');
$badImport->set_header('Content-Type','application/json');$badImport->set_body(wp_json_encode($badImport->get_body_params()));
$badPrepared=KREV_Marketplace_Imports::prepare($badImport);
techCheck(is_wp_error($badPrepared) && $badPrepared->get_error_code()==='import_metacategory', 'public preparation rejects cross-category collections before storing a draft');
$pricingBefore=KREV_Import_Pricing::settings();$cardPricing=$pricingBefore;$cardPricing['categories'][$cards->term_id]=['percent'=>'40','fixed'=>'0','minimum'=>'0','rounding'=>'0.01'];update_option(KREV_Import_Pricing::OPTION,$cardPricing,false);
$validImport=new WP_REST_Request('POST');$validImport->set_header('Content-Type','application/json');
$validImport->set_body(wp_json_encode(['source_url'=>'https://www.facebook.com/marketplace/item/999999993/','source_price'=>'25','currency'=>'USD','title'=>'Synthetic Pokémon import','description'=>'Synthetic test data only.','category_id'=>$collectRoot,'subcategory_id'=>$cards->term_id,'metacategory_id'=>$pokemon->term_id,'featured_image_id'=>$image,'photo_manifest'=>['source_count'=>1,'gallery_complete'=>true],'attributes'=>['pa_card-name'=>['Charizard'],'pa_condition'=>['Used']]]));
$preparedCard=KREV_Marketplace_Imports::prepare($validImport);
techCheck(!is_wp_error($preparedCard) && $preparedCard->get_data()['price']['regular_price']==='35.00' && $preparedCard->get_data()['product_created']===false, 'public preparation applies the selected Google child pricing rule while retaining a private preparation');
$tokenFragment=parse_url($preparedCard->get_data()['review_url'],PHP_URL_FRAGMENT);parse_str($tokenFragment,$tokenValues);
$previewRequest=new WP_REST_Request('POST');$previewRequest->set_header('Content-Type','application/json');$previewRequest->set_body(wp_json_encode(['token'=>$tokenValues['import']]));
$preparedStatus=KREV_Marketplace_Imports::status($previewRequest)->get_data();
techCheck($preparedStatus['schema']['specification_profile']['google_id']==='6997' && (int)$preparedStatus['data']['listing']['metacategory_id']===(int)$pokemon->term_id, 'private review schema retains six card fields and the separate Pokémon assignment');
update_option(KREV_Import_Pricing::OPTION,$pricingBefore,false);
kr_ensure_annex_pages();
foreach (['home-garden','collectibles','pokemon-cards'] as $pageSlug) {
    $page = get_page_by_path($pageSlug, OBJECT, 'page');
    techCheck($page && $page->post_status==='publish' && strpos($page->post_content, 'kniferevive_metacategory')!==false, $pageSlug.' has an idempotent native landing page');
    $pageMarkup = kr_metacategory_landing_shortcode(['slug'=>$pageSlug]);
    techCheck(strpos($pageMarkup,'<h1>')!==false && strpos($pageMarkup,'kr-other-feature')!==false && strpos($pageMarkup,'List an item')!==false, $pageSlug.' renders the Other Finds theme and native collection inventory');
}
techCheck(kr_metacategory_landing_shortcode(['slug'=>'not-a-collection'])==='', 'unsupported landing-page groups render no content');
$annexLinks=kr_annex_navigation_links();
techCheck(strpos($annexLinks,'technology-trade-desk/#iphones')!==false && strpos($annexLinks,'technology-trade-desk/#laptops')!==false && strpos($annexLinks,'technology-trade-desk/#mini-computers')!==false && strpos($annexLinks,'/pokemon-cards/')!==false && strpos($annexLinks,'/collectibles/')!==false, 'shared Annex links include new pages and Tech section anchors');
echo ($passed-$startSpecs)." additional native specification assertions passed.\n";
