<?php
require __DIR__ . '/category-specifications.php';
$conditionStart = $passed;
function conditionField($category, $meta=0) { foreach (KREV_ListLab_Attributes::schema($category,$meta) as $field) if ($field['taxonomy']==='pa_condition') return $field; return null; }
$techCondition=conditionField($root_id);
techCheck(!in_array('Circulated',array_column($techCondition['options'],'name'),true) && $techCondition['allow_new_value']===false, 'Tech excludes coin terminology and custom condition bypasses');
$coinRoot=KREV_PA_Tech_Categories::root_id('world-coins');
$coinCondition=conditionField($coinRoot);
techCheck(in_array('Circulated',array_column($coinCondition['options'],'name'),true) && in_array('Uncirculated',array_column($coinCondition['options'],'name'),true) && !in_array('Refurbished',array_column($coinCondition['options'],'name'),true), 'coins get circulated and uncirculated choices without refurbished');
$artRoot=KREV_PA_Tech_Categories::root_id('art');
techCheck(!in_array('For Parts / Repair',array_column(conditionField($artRoot)['options'],'name'),true), 'art excludes electronics repair conditions');
$spiceTerm=get_term_by('slug','world-spices','product_cat');
if (!$spiceTerm) $spiceTerm=get_term(wp_insert_term('Synthetic Spices','product_cat',['slug'=>'world-spices'])['term_id'],'product_cat');
techCheck(array_column(conditionField($spiceTerm->term_id)['options'],'name')===['New'], 'spices expose only New and do not assume unknown goods are new');
$caseTerm=get_term_by('slug','tech-google-2353','product_cat');
$headphoneTerm=get_term_by('slug','tech-google-543626','product_cat');
$metaTerm=get_term_by('slug','cell-phone-accessories','product_cat');
techCheck(KREV_PA_Condition_Options::resolve($caseTerm->term_id,$metaTerm->term_id)['source']==='subcategory' && !in_array('Refurbished',array_column(conditionField($caseTerm->term_id,$metaTerm->term_id)['options'],'name'),true), 'phone case subtype has its own accessory conditions');
techCheck(in_array('Refurbished',array_column(conditionField($headphoneTerm->term_id,$metaTerm->term_id)['options'],'name'),true), 'headphone subtype takes priority over broader accessory collection');
techCheck(KREV_PA_Condition_Options::resolve($root_id,$metaTerm->term_id)['source']==='metacategory' && !in_array('Refurbished',array_column(conditionField($root_id,$metaTerm->term_id)['options'],'name'),true), 'collection fallback applies when no subcategory is selected');
techCheck(KREV_PA_Condition_Options::resolve($root_id)['source']==='category', 'root category is the final fallback');
techCheck(is_wp_error(KREV_ListLab_Attributes::validate($root_id,['pa_condition'=>['Circulated']])), 'native validation rejects wrong-category condition names');
$circulated=get_term_by('name','Circulated','pa_condition');
techCheck(is_wp_error(KREV_ListLab_Attributes::validate($root_id,['pa_condition'=>[(string)$circulated->term_id]])), 'native validation rejects wrong-category condition IDs');
techCheck(is_wp_error(KREV_ListLab_Attributes::validate($root_id,['pa_condition'=>['__krev_custom__:Circulated']])), 'custom value prefixes cannot bypass restricted condition choices');
techCheck(!is_wp_error(KREV_ListLab_Attributes::validate($coinRoot,['pa_condition'=>['Uncirculated']])), 'coin vocabulary is accepted in the native writer');
techCheck(is_wp_error(KREV_ListLab_Attributes::validate($spiceTerm->term_id,['pa_condition'=>['Used']])), 'consumables reject Used before any product mutation');
$bad=$metaInput;$bad['metacategory_id']=0;$bad['title']='Must not be saved';$bad['attributes']['pa_condition']=['Circulated'];$bad['client_uuid']=wp_generate_uuid4();
$oldName=wc_get_product($savedMeta['id'])->get_name();
$badSave=KREV_ListLab_Product_Writer::save($bad,$savedMeta['id']);
techCheck(is_wp_error($badSave) && wc_get_product($savedMeta['id'])->get_name()===$oldName, 'draft save rejects invalid condition before mutating the existing listing');
foreach (['Circulated','Uncirculated'] as $coinGrade) {
 $coinProduct=new WC_Product_Simple();$coinProduct->set_name('Synthetic '.$coinGrade.' coin');$coinProduct->set_category_ids([$coinRoot]);$coinProduct->save();
 KREV_ListLab_Attributes::save($coinProduct,$coinRoot,['pa_condition'=>[$coinGrade]]);$coinProduct->save();
 techCheck(KREV_PA_Condition_Resolver::resolve(wc_get_product($coinProduct->get_id()))['merchant_condition']==='used', $coinGrade.' cosmetic coin grade retains Google Used semantics');
}
echo ($passed-$conditionStart)." additional native condition policy assertions passed.\n";
