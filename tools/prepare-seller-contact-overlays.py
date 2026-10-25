from pathlib import Path
import re
repo=Path(__file__).resolve().parents[1]
base=Path('C:/Users/Svet/Documents/KnifeRevive_Documentation/buyer-seller-contact-2026-10-25/baseline')
target=repo/'theme-overlays/kniferevive/inc';target.mkdir(parents=True,exist_ok=True)
for name in ['product-subtitle.php','knife-card.php','annex.php','account-avatar.php']:
    s=(base/'theme/inc'/name).read_text(encoding='utf-8')
    if name=='product-subtitle.php':
        needle='function kr_shop_seller_contacts(int $productId): string\n{'
        assert needle in s
        s=s.replace(needle,needle+"\n    if (function_exists('kr_seller_contact_markup')) { return kr_seller_contact_markup($productId, 'card'); }",1)
        s=s.replace("if (str_contains($card, 'kr-shop-seller-contact') ||", "if (str_contains($card, 'krc-contact') || str_contains($card, 'kr-shop-seller-contact') ||",1)
    elif name=='knife-card.php':
        assert s.count("    $sharing = '';")==1
        a=s.index("    $sharing = '';")
        b=s.index('\n\n    $details =',a)
        original=s[a:b]
        s=s[:a]+"    if (function_exists('kr_seller_contact_markup')) {\n        $sharing = kr_seller_contact_markup($productId, 'card');\n    } else {\n"+original+"\n    }"+s[b:]
        old='<span class="kr-knife-card__share" aria-label="'
        assert old in s
        s=s.replace(old,'<div class="<?php echo function_exists(\'kr_seller_contact_markup\') ? \'kr-knife-card__contact\' : \'kr-knife-card__share\'; ?>" aria-label="',1)
        s=s.replace('messaging helpers ?></span><?php endif; ?>','messaging helpers ?></div><?php endif; ?>',1)
    elif name=='annex.php':
        pattern=r"\$telegram = function_exists\('kniferevive_telegram_render_icon'\) \? kniferevive_telegram_render_icon\(\$product->get_id\(\)\) : '';\s*\$whatsapp = function_exists\('kniferevive_whatsapp_render_icon'\) \? kniferevive_whatsapp_render_icon\(\$product->get_id\(\)\) : '';\s*\$contacts = \$telegram \. \$whatsapp;"
        def patch(m):return "if (function_exists('kr_seller_contact_markup')) { $contacts = kr_seller_contact_markup($product->get_id(), 'card'); } else { "+m[0]+" }"
        s,n=re.subn(pattern,patch,s)
        assert n==3,('annex contact paths',n)
    elif name=='account-avatar.php':
        # Keep the existing storefront editor and its consent/nonce rules, but stop country-code guessing.
        old='$phone = KR_WhatsApp_Helper::normalize_number($phoneRaw);'
        if old not in s:
            print('Existing phone normalization:',[x for x in s.splitlines() if '$phone =' in x])
            raise RuntimeError('Find exact production phone normalization before overlay')
        s=s.replace(old,"$phone = class_exists('\\KnifeRevive\\SellerContact\\Resolver') ? \\KnifeRevive\\SellerContact\\Resolver::number($phoneRaw) : KR_WhatsApp_Helper::normalize_number($phoneRaw);",1)
        s=s.replace('Show WhatsApp','Allow buyers to contact me on WhatsApp').replace('Show Telegram','Allow buyers to contact me on Telegram')
    (target/name).write_text(s,encoding='utf-8')

p=base/'kniferevive-seller-storefront/includes/class-krev-storefront-renderer.php'
s=p.read_text(encoding='utf-8')
assert s.count('self::contact_action( $source );')==1
s=s.replace('self::contact_action( $source );','self::contact_action( $source, $seller_id );',1)
needle='private static function contact_action( array $products ): void {'
assert needle in s
s=s.replace(needle,"private static function contact_action( array $products, int $seller_id = 0 ): void {\n\t\tif ( function_exists( 'kr_seller_store_contact_markup' ) ) { echo kr_seller_store_contact_markup($seller_id); return; }",1)
out=repo/'wordpress-overlays/kniferevive-seller-storefront/includes/class-krev-storefront-renderer.php'
out.parent.mkdir(parents=True,exist_ok=True);out.write_text(s,encoding='utf-8')
print('Prepared five narrow integrations against the actual production sources. Legacy fallback preserved on companion deactivation.')
