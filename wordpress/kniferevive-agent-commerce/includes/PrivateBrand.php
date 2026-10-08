<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Restricted block-theme integration: native global styles/logo, without analytics hooks. */
final class PrivateBrand {
    private static string $nonce='';
    public static function headers(): void {
        if(!is_ssl() && wp_get_environment_type()!=='local')wp_die('Use the secure HTTPS KnifeRevive page to share contact details or review a checkout.','Secure connection required',['response'=>403]);
        self::$nonce=base64_encode(random_bytes(18));nocache_headers();header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow, noarchive');header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'nonce-".self::$nonce."'; connect-src 'self'; img-src 'self' data:; font-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    }
    public static function logo(): string {
        $id=(int)get_theme_mod('custom_logo',(int)get_option('site_logo'));
        $file=$id?(string)get_post_meta($id,'_wp_attached_file',true):'';
        $uploads=wp_upload_dir();
        $src=$file && !str_contains($file,'..') && !str_contains($file,':')?trailingslashit($uploads['baseurl']).ltrim($file,'/'):false;
        if(!$src || wp_parse_url($src,PHP_URL_HOST)!==wp_parse_url(home_url('/'),PHP_URL_HOST))return '';
        return '<img src="'.esc_url($src).'" alt="KnifeRevive" class="krev-brand-logo" width="180" height="72">';
    }
    public static function start(string $title,string $attributes=''): void {
        $assets=plugin_dir_url(FILE).'assets/';$css=function_exists('wp_get_global_stylesheet')?wp_get_global_stylesheet():'';
        // Prevent HTML termination while preserving native block-theme CSS variables/styles.
        $css=str_ireplace('</style','\\3C /style',$css);
        echo '<!doctype html><html lang="'.esc_attr(get_bloginfo('language')?:'en').'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.esc_html($title).' | KnifeRevive</title>';
        if($css)echo '<style nonce="'.esc_attr(self::$nonce).'">'.$css.'</style>';
        $paths=['body-font'=>['typography','fontFamily'],'heading-font'=>['elements','heading','typography','fontFamily'],'heading-weight'=>['elements','heading','typography','fontWeight'],'button-bg'=>['elements','button','color','background'],'button-color'=>['elements','button','color','text'],'link-color'=>['elements','link','color','text']];$variables='';
        foreach($paths as $name=>$path){$value=wp_get_global_styles($path);if(!is_string($value) && !is_numeric($value))continue;$value=(string)$value;
            $value=preg_replace('/var:preset\|([a-z-]+)\|([a-z0-9-]+)/','var(--wp--preset--$1--$2)',$value);
            if($value && !preg_match('/[{};<>\r\n]/',$value))$variables.='--krev-'.$name.':'.$value.';';
        }
        if($variables)echo '<style nonce="'.esc_attr(self::$nonce).'">.krev-private{'.$variables.'}</style>';
        if(function_exists('wp_print_font_faces')){
            ob_start();wp_print_font_faces();$fontOutput=ob_get_clean();
            if(preg_match_all('/<style[^>]*>(.*?)<\/style>/is',$fontOutput,$fontStyles))foreach($fontStyles[1] as $fontCss)echo '<style nonce="'.esc_attr(self::$nonce).'">'.str_ireplace('</style','\\3C /style',$fontCss).'</style>';
        }
        echo '<link rel="stylesheet" href="'.esc_url($assets.'storefront.css').'"></head><body class="krev-private"><a class="krev-skip" href="#booking-main">Skip to main content</a><header class="krev-header"><a href="'.esc_url(home_url('/')).'" aria-label="KnifeRevive home">'.(self::logo()?:'<span>KnifeRevive</span>').'</a><a href="'.esc_url(home_url('/#knife-sharpening')).'">Sharpening services</a></header><main id="booking-main" tabindex="-1" '.$attributes.'><div id="session-status" role="status"></div>';
    }
    public static function support(): void {
        echo '<footer class="krev-support"><h2>KnifeRevive Support Crew</h2><p><a href="https://t.me/svetlyoh?text=Hi%20KnifeRevive%20Support%20Crew!%20I%20need%20help%20with%20a%20question%20about%20KnifeRevive." target="_blank" rel="noopener noreferrer">Chat on Telegram</a> · <a href="https://wa.me/14152999611?text=Hi%20KnifeRevive%20Support%20Crew!%20I%20need%20help%20with%20a%20question%20about%20KnifeRevive." target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a> · <a href="mailto:knifereviveofficial@gmail.com?subject=KnifeRevive%20support">Email the Support Crew</a></p>';
        $s=Settings::get();if($s['booking_policy_url'])echo '<p><a href="'.esc_url($s['booking_policy_url']).'">Service cancellation and refund terms</a></p>';
        else echo '<p>Online prepayment is not yet available. Contact support about service, cancellation and rescheduling terms before submitting.</p>';
        echo '</footer>';
    }
    public static function end(bool $booking=false): void {
        self::support();$assets=plugin_dir_url(FILE).'assets/';echo '</main><script src="'.esc_url($assets.'storefront.js').'" defer></script>';
        if($booking)echo '<script src="'.esc_url($assets.'booking.js').'" defer></script>';
        echo '</body></html>';
    }
}
