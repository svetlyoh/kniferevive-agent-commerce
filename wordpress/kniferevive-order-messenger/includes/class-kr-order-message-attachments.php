<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger_Attachments {
    public static function limits(): array {
        $s=KR_Order_Messenger_Admin::settings();
        $file=$s['file_mb']*1024*1024; $total=$s['total_mb']*1024*1024;
        $iniFile=wp_convert_hr_to_bytes(ini_get('upload_max_filesize')); $iniPost=wp_convert_hr_to_bytes(ini_get('post_max_size'));
        if ($iniFile>0) $file=min($file,$iniFile);
        if ($iniPost>0) $total=min($total,max(0,$iniPost-262144));
        $count=$s['screenshots']; $iniCount=(int)ini_get('max_file_uploads'); if ($iniCount>0) $count=min($count,$iniCount);
        return ['count'=>$count,'file_bytes'=>$file,'total_bytes'=>$total,'max_dimension'=>8192,'max_pixels'=>16000000,'formats'=>['image/png','image/jpeg'],'delivery'=>'attachments'];
    }
    public static function files(array $uploaded) {
        if (!$uploaded) return [];
        if (array_diff(array_keys($uploaded),['screenshots'])) return new WP_Error('kr_om_file','Only screenshot attachments are supported.',['status'=>400]);
        $f=$uploaded['screenshots']; $out=[];
        if (!is_array($f['name']??null)) {
            if (($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return [];
            $out[]=$f;
        } else foreach ($f['name'] as $i=>$name) $out[]=['name'=>$name,'type'=>$f['type'][$i]??'','tmp_name'=>$f['tmp_name'][$i]??'','size'=>$f['size'][$i]??0,'error'=>$f['error'][$i]??UPLOAD_ERR_NO_FILE];
        return array_values(array_filter($out,static fn($f)=>($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE));
    }
    public static function validate(array $files,bool $http=true) {
        $limits=self::limits(); $total=0; $validated=[];
        if (count($files)>$limits['count']) return new WP_Error('kr_om_file','Too many screenshots.',['status'=>400]);
        foreach ($files as $file) {
            $path=$file['tmp_name']??''; $ext=strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION));
            if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_string($path) || !is_file($path) || ($http && !is_uploaded_file($path))) return new WP_Error('kr_om_file','Screenshot upload failed. Try choosing the image again.',['status'=>400]);
            if (!self::private_path($path)) return new WP_Error('kr_om_file','The server upload temporary directory must be outside the public web root. Contact the administrator.',['status'=>503]);
            $size=filesize($path); $total+=$size;
            if (!$size || $size>$limits['file_bytes'] || $total>$limits['total_bytes']) return new WP_Error('kr_om_file','Screenshots exceed the allowed size.',['status'=>413]);
            $mime=wp_get_image_mime($path); $dimensions=@getimagesize($path);
            if (!in_array($ext,['png','jpg','jpeg'],true) || !in_array($mime,$limits['formats'],true) || !$dimensions || ($mime==='image/png' && $ext!=='png') || ($mime==='image/jpeg' && !in_array($ext,['jpg','jpeg'],true))) return new WP_Error('kr_om_file','Use genuine PNG or JPEG screenshots. SVG, WebP and other files are not supported.',['status'=>400]);
            [$w,$h]=$dimensions;
            if (!$w || !$h || $w>$limits['max_dimension'] || $h>$limits['max_dimension'] || $w*$h>$limits['max_pixels']) return new WP_Error('kr_om_file','That image is too large in dimensions. Resize it first.',['status'=>400]);
            $memory=wp_convert_hr_to_bytes(ini_get('memory_limit'));
            if ($memory>0 && memory_get_usage(true)+$w*$h*8+32*1024*1024>$memory) return new WP_Error('kr_om_file','That image needs too much memory. Resize it first.',['status'=>400]);
            if (!function_exists('imagecreatefromstring')) return new WP_Error('kr_om_image','Screenshot processing is unavailable. Please contact the site administrator.',['status'=>503]);
            $image=@imagecreatefromstring(file_get_contents($path)); if (!$image) return new WP_Error('kr_om_file','This screenshot could not be decoded.',['status'=>400]);
            imagedestroy($image);
            $validated[]=['source'=>$path,'mime'=>$mime,'extension'=>$mime==='image/png'?'png':'jpg','bytes'=>$size,'hash'=>hash_file('sha256',$path)];
        }
        return $validated;
    }
    public static function private_path(string $path): bool {
        if (!($real=realpath($path))) return false;
        $norm=static fn($p)=>strtolower(str_replace('\\','/',rtrim($p,'/\\'))).'/';
        foreach ([ABSPATH,WP_CONTENT_DIR,$_SERVER['DOCUMENT_ROOT']??''] as $root) if ($root && ($root=realpath($root)) && str_starts_with($norm($real),$norm($root))) return false;
        return true;
    }
    public static function directory(): string {
        $base=realpath(sys_get_temp_dir()); if (!$base || !is_writable($base)) throw new RuntimeException('Private temporary storage unavailable.');
        $path=$base.DIRECTORY_SEPARATOR.'kr-om-'.substr(hash('sha256',ABSPATH),0,16);
        if (!is_dir($path) && !mkdir($path,0700,true)) throw new RuntimeException('Private temporary storage unavailable.');
        $real=realpath($path); if (!$real) throw new RuntimeException('Private temporary storage unavailable.');
        foreach ([ABSPATH,WP_CONTENT_DIR,$_SERVER['DOCUMENT_ROOT']??''] as $root) {
            if (!$root || !($root=realpath($root))) continue;
            $norm=static fn($p)=>strtolower(str_replace('\\','/',rtrim($p,'/\\'))).'/';
            if (str_starts_with($norm($real),$norm($root))) throw new RuntimeException('Temporary screenshots cannot be stored in a public web root.');
        }
        return $real;
    }
    public static function prepare(array $validated): array {
        if (!$validated) return [];
        $paths=[]; $total=0; $limits=self::limits();
        try {
            $dir=self::directory();
            foreach ($validated as $i=>$file) {
                $path=$dir.DIRECTORY_SEPARATOR.'screenshot-'.($i+1).'-'.bin2hex(random_bytes(12)).'.'.$file['extension'];
                $paths[]=$path;
                $image=@imagecreatefromstring(file_get_contents($file['source'])); if (!$image) throw new RuntimeException('Screenshot processing failed.');
                try { if ($file['extension']==='png') { imagealphablending($image,false); imagesavealpha($image,true); $ok=imagepng($image,$path,6); } else $ok=imagejpeg($image,$path,90); } finally { imagedestroy($image); }
                if (!$ok) throw new RuntimeException('Screenshot processing failed.');
                $bytes=filesize($path); $total+=$bytes;
                if ($bytes>$limits['file_bytes'] || $total>$limits['total_bytes']) throw new InvalidArgumentException('Converted screenshots exceed the payload limits. Resize them first.');
                chmod($path,0600);
            }
            return $paths;
        } catch (Throwable $e) { self::cleanup($paths); throw $e; }
    }
    public static function cleanup(array $paths): void { foreach ($paths as $path) if (is_file($path)) @unlink($path); }
    public static function sweep(): void {
        try { $dir=self::directory(); foreach (glob($dir.DIRECTORY_SEPARATOR.'screenshot-*')?:[] as $path) if (is_file($path) && filemtime($path)<time()-2*HOUR_IN_SECONDS) @unlink($path); } catch (Throwable $e) { /* The admin notice/send response describes unavailable storage. */ }
    }
}
