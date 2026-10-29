# Buyer restock, price-drop and saved-search reminders

Audit date: October 29, 2026 (America/Los_Angeles).


Active Set Reminders 1.0.1. Email-only opt-in alerts; confirmation, request state and queue conditions apply.

| Kind | Exact subject |
| --- | --- |
| Manage link | Manage Your KnifeRevive Reminders |
| Confirmation | Confirm your KnifeRevive reminder 🔔 |
| Activated | 🎉 You’re in! KnifeRevive reminder activated |
| Multiple updates | 🔔 Your KnifeRevive finds are here |
| Single-alert fallback | 🔔 A KnifeRevive find you’re watching is here |
| Single price drop | 🔥 Price drop: {product_name} — {formatted_price} |
| Single back in stock | It’s back! {product_name} — {formatted_price} |
| Single new search match | New match: {product_name} — {formatted_price} |

**Shared greeting:** Hey {first_name}! or Hey there!

**Manage:** “Your private link is ready. It expires in seven days.” **Manage my reminders**.

**Confirm:** “One quick step! Confirm this email to switch your chosen reminder on. This link expires in seven days. If you didn’t request it, you can ignore this email.” **Confirm my reminder**.

**Activated:** “We got you! Your reminder is on. We’ll email when your chosen find is back, drops in price, or a new listing matches your search.”

**Alert:** “Hey, a find you’re watching has an update 👀”; {product_name}; optional product image; “Just dropped from {old_price} to {new_price}.” / “Back in stock at {price}.” / “Your saved search has a match at {price}.” **Take a look**.

“Availability and prices can change. Restock and price-drop reminders finish after one alert; saved searches stay on until you pause or delete them.”

**Shared ending:** “You receive these email-only alerts because you requested them.” **Manage, pause or delete reminders**. **Privacy · KnifeRevive**. Wrapper heading: KnifeRevive Reminders.


## Source and exact wording reference


### Source: [service.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-reminders/includes/service.php)

SHA-256: `cc4fcd05195841e82ca97c671d2433ef1bdabc34dfc41686ef40c891ff37ca6f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Manage Your KnifeRevive Reminders
- Your private link is ready. It expires in seven days.
- Manage my reminders
- Confirm your KnifeRevive reminder 🔔
- One quick step! Confirm this email to switch your chosen reminder on. This link expires in seven days. If you didn’t request it, you can ignore this email.
- Confirm my reminder
- 🎉 You’re in! KnifeRevive reminder activated
- We got you! Your reminder is on. We’ll email when your chosen find is back, drops in price, or a new listing matches your search.
- 🔔 Your KnifeRevive finds are here
- 🔔 A KnifeRevive find you’re watching is here
- Hey, a find you’re watching has an update 👀
- 🔥 Price drop:
- New match:
- <img width="160" alt="
- Just dropped from
- Back in stock at
- Your saved search has a match at
- Availability and prices can change. Restock and price-drop reminders finish after one alert; saved searches stay on until you pause or delete them.
- Hey there!
- You receive these email-only alerts because you requested them.
- Manage, pause or delete reminders
- KnifeRevive Reminders

<details><summary>Exact template/renderer source, beginning at line 354</summary>

```php
    public static function message(array $group): array {
        [$job,$r,$data]=$group[0]; $manage=add_query_arg('kr_manage',self::token($job->email_hash),self::url());
        $button=static fn($href,$text)=>'<p><a style="display:inline-block;padding:14px 20px;background:#087985;color:#fff;text-decoration:none;border-radius:6px" href="'.esc_url($href).'">'.esc_html($text).'</a></p>';
        $subject='Manage Your KnifeRevive Reminders'; $body='<p>Your private link is ready. It expires in seven days.</p>'.$button($manage,'Manage my reminders');
        if ($job->kind==='confirmation') {
            $subject='Confirm your KnifeRevive reminder 🔔';
            $body='<p>One quick step! Confirm this email to switch your chosen reminder on. This link expires in seven days. If you didn’t request it, you can ignore this email.</p>'.$button(add_query_arg('kr_confirm',$data['token'],self::url()),'Confirm my reminder');
        } elseif ($job->kind==='welcome') { $subject='🎉 You’re in! KnifeRevive reminder activated'; $body='<p>We got you! Your reminder is on. We’ll email when your chosen find is back, drops in price, or a new listing matches your search.</p>'; }
        elseif ($job->kind==='alert') {
            $subject=count($group)>1 ? '🔔 Your KnifeRevive finds are here' : '🔔 A KnifeRevive find you’re watching is here'; $body='<p>Hey, a find you’re watching has an update 👀</p>';
            foreach ($group as [$j,$rem,$d]) {
                $p=wc_get_product((int)$d['product_id']); if (!$p) continue;
                $name=$p->get_name(); $price=html_entity_decode(wp_strip_all_tags(wc_price($d['price'])),ENT_QUOTES,'UTF-8');
                if (count($group)===1) $subject=($d['type']==='price_drop'?'🔥 Price drop: ':($d['type']==='availability'?'It’s back! ':'New match: ')).$name.' — '.$price;
                $body.='<h2>'.esc_html($name).'</h2>';
                $image=wp_get_attachment_image_url($p->get_image_id(),'woocommerce_thumbnail'); if ($image) $body.='<img width="160" alt="'.esc_attr($name).'" src="'.esc_url($image).'">';
                $body.='<p>'.($d['type']==='price_drop'?'Just dropped from '.wc_price($d['old_price']).' to ':($d['type']==='availability'?'Back in stock at ':'Your saved search has a match at ')).wc_price($d['price']).'.</p>'.$button($p->get_permalink(),'Take a look');
            }
            $body.='<p>Availability and prices can change. Restock and price-drop reminders finish after one alert; saved searches stay on until you pause or delete them.</p>';
        }
        $first=$r && $r->user_id ? (string)get_user_meta((int)$r->user_id,'first_name',true) : '';
        $body='<p>'.esc_html($first ? 'Hey '.$first.'!' : 'Hey there!').'</p>'.$body;
        $body.='<p>You receive these email-only alerts because you requested them.</p>'.$button($manage,'Manage, pause or delete reminders').'<p><a href="'.esc_url(get_privacy_policy_url()?:home_url('/privacy-policy/')).'">Privacy</a> · KnifeRevive</p>';
        if (function_exists('WC') && WC()->mailer()) $html=WC()->mailer()->wrap_message('KnifeRevive Reminders',$body); else $html='<html><body><h1>KnifeRevive Reminders</h1>'.$body.'</body></html>';
        return [$subject,$html];
    }
```

</details>
