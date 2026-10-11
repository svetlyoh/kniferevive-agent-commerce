<?php
/** Exercise real WordPress video metadata parsing and ListLab ownership/validation. */
define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' ); define( 'WPINC', 'wp-includes' ); define( 'MB_IN_BYTES', 1048576 );
class WP_Error { public $code; public function __construct( $code, $message = '', $data = null ) { $this->code = $code; } }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function wp_max_upload_size() { return 200 * MB_IN_BYTES; }
function size_format( $v ) { return ($v / MB_IN_BYTES) . ' MB'; }
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function apply_filters( $hook, $v, ...$args ) { return $v; }
function wp_check_filetype_and_ext( $path, $name, $mimes ) { $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ); return array( 'type' => $mimes[$ext] ?? false ); }
function get_post( $id ) { return $GLOBALS['posts'][$id] ?? null; }
function current_user_can( $cap ) { return $GLOBALS['manager'] ?? false; }
function get_current_user_id() { return 12; }
function wp_parse_url( $value ) { return parse_url( $value ); }
function wpcom_site_can_upload_videos() { return $GLOBALS['hosting_available'] ?? true; }
function get_allowed_mime_types() { return $GLOBALS['allowed_video_mimes'] ?? array( 'mp4' => 'video/mp4' ); }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return strip_tags( $v ); }
function sanitize_textarea_field( $v ) { return strip_tags( $v ); }
function wp_kses_post( $v ) { return $v; }
function wc_format_decimal( $v ) { return $v; }
function wc_stock_amount( $v ) { return (int) $v; }
function wc_clean( $v ) { return $v; }
function get_term( $id, $tax ) { return (object) array( 'term_id' => $id ); }
class KREV_ListLab_Policies { public static function valid_return_policy( $id, $cat ) { return true; } }
require ABSPATH . 'wp-admin/includes/media.php';
require dirname( __DIR__ ) . '/includes/class-krev-listlab-video-handler.php';
require dirname( __DIR__ ) . '/includes/class-krev-listlab-categories.php';
require dirname( __DIR__ ) . '/includes/class-krev-listlab-product-writer.php';
$count = 0;
function check( $value, $message ) { global $count; ++$count; if ( ! $value ) throw new RuntimeException( $message ); }
$fixture = $argv[1] ?? ABSPATH . '.codex-tmp/listlab-video-test.mp4';
$file = array( 'name' => 'sample.mp4', 'tmp_name' => $fixture, 'size' => filesize( $fixture ), 'error' => UPLOAD_ERR_OK );
check( true === KREV_ListLab_Video_Handler::validate_file( $file ), 'Real H.264 MP4 is accepted.' );
check( 100 * MB_IN_BYTES === KREV_ListLab_Video_Handler::limit(), 'Upload size is capped at 100 MB.' );
foreach ( array( array( 'name' => 'sample.php' ), array( 'size' => 101 * MB_IN_BYTES ), array( 'error' => UPLOAD_ERR_PARTIAL ), array( 'size' => 0 ) ) as $override ) check( is_wp_error( KREV_ListLab_Video_Handler::validate_file( array_merge( $file, $override ) ) ), 'Reject invalid file/size/upload status.' );
$fake = tempnam( sys_get_temp_dir(), 'krev-video-' ); file_put_contents( $fake, '<?php echo "not a video"; ?>' );
check( is_wp_error( KREV_ListLab_Video_Handler::validate_file( array_merge( $file, array( 'tmp_name' => $fake, 'size' => filesize( $fake ) ) ) ) ), 'Reject executable/text disguised as MP4.' ); unlink( $fake );
$posts = array();
foreach ( array( 1 => array(12,'video/mp4','attachment'), 2 => array(15,'video/mp4','attachment'), 3 => array(12,'image/jpeg','attachment'), 4 => array(12,'video/webm','attachment'), 5 => array(12,'video/quicktime','attachment'), 6 => array(12,'video/mp4','product') ) as $id => $data ) $posts[$id] = (object) array( 'post_author' => $data[0], 'post_mime_type' => $data[1], 'post_type' => $data[2] );
foreach ( array( 1 => true, 2 => false, 3 => false, 4 => true, 5 => true, 6 => false, 0 => false ) as $id => $expected ) check( (bool) KREV_ListLab_Video_Handler::authorized_video( $id ) === $expected, 'Video attachment ownership/type check.' );
$manager = true; check( KREV_ListLab_Video_Handler::authorized_video( 2 ), 'Manager can use another seller video.' ); $manager = false;
check( KREV_ListLab_Video_Handler::hosting_available(), 'Enabled hosting permits uploads.' ); $hosting_available = false;
check( ! KREV_ListLab_Video_Handler::hosting_available(), 'Hosting video restriction is respected.' ); $hosting_available = true;
$allowed_video_mimes = array('jpg'=>'image/jpeg'); check( ! KREV_ListLab_Video_Handler::hosting_available(), 'WordPress allowed MIME policy is respected.' ); unset( $allowed_video_mimes );
foreach ( array( 'https://youtu.be/aqz-KE-bpKQ?si=share' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'https://www.youtube.com/shorts/aqz-KE-bpKQ' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'https://vimeo.com/76979871' => 'https://vimeo.com/76979871', 'https://player.vimeo.com/video/76979871' => 'https://vimeo.com/76979871', '' => '' ) as $input => $expected ) check( KREV_ListLab_Video_Handler::normalize_url( $input ) === $expected, 'Public video URLs are canonicalized.' );
foreach ( array( 'javascript:alert(1)', 'http://youtube.com/watch?v=aqz-KE-bpKQ', 'https://youtube.com.evil.test/watch?v=aqz-KE-bpKQ', 'https://evil.test/video.mp4', 'https://user:pass@youtube.com/watch?v=aqz-KE-bpKQ', 'https://youtube.com:8888/watch?v=aqz-KE-bpKQ', 'https://www.youtube.com/watch?v[]=aqz-KE-bpKQ', 'https://www.youtube.com/watch?v=invalid', array() ) as $input ) check( false === KREV_ListLab_Video_Handler::normalize_url( $input ), 'Reject unsupported or unsafe video URL.' );
check( KREV_ListLab_Video_Handler::embed_url( 'https://youtu.be/aqz-KE-bpKQ' ) === 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', 'YouTube uses privacy-enhanced embed.' );
check( KREV_ListLab_Video_Handler::embed_url( 'https://vimeo.com/76979871' ) === 'https://player.vimeo.com/video/76979871', 'Vimeo uses its own player.' );
$clean = new ReflectionMethod( 'KREV_ListLab_Product_Writer', 'clean' ); $validate = new ReflectionMethod( 'KREV_ListLab_Product_Writer', 'validate' );
$d = $clean->invoke( null, array() ); check( ! $d['video_id_provided'], 'Old clients preserve video by omitting ID.' );
foreach ( array( 0, 1, '4' ) as $id ) { $d = $clean->invoke( null, array( 'video_id' => $id ) ); check( ! isset( $validate->invoke( null, $d, false )['video_id'] ), 'Allow explicit removal and own video.' ); }
foreach ( array( 2, 3, 6, -1, 'abc', array(1), 1.5, true ) as $id ) { $d = $clean->invoke( null, array( 'video_id' => $id ) ); check( isset( $validate->invoke( null, $d, false )['video_id'] ), 'Reject foreign or malformed attachment ID.' ); }
foreach ( array( array('video_url'=>'javascript:alert(1)'), array('video_id'=>1,'video_url'=>'https://vimeo.com/76979871') ) as $data ) { $d = $clean->invoke( null, $data ); check( isset( $validate->invoke( null, $d, false )['video_url'] ), 'Reject invalid link or two video sources.' ); }
echo "Video checks passed: {$count} assertions.\n";
