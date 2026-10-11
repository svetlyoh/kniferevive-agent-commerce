<?php
define( 'ABSPATH', __DIR__ );
class WP_Error { public function __construct( $code, public $message ) {} public function get_error_message() { return $this->message; } }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function current_user_can( $cap ) { return true; }
function check_admin_referer( $action ) {}
function wp_unslash( $v ) { return $v; }
function get_option( $key, $default ) { return $GLOBALS['settings']; }
function update_option( $key, $value, $autoload ) { $GLOBALS['saved'][] = $value; }
function get_term( $id, $taxonomy ) { return (object) array( 'term_id' => $id ); }
function get_terms( $args ) { return array_map( static fn($id) => (object) array( 'term_id' => $id, 'name' => 'Category ' . $id ), range( 1, 440 ) ); }
function esc_html( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function checked( $a, $b, $echo ) { return $a === $b ? 'checked' : ''; }
function wp_nonce_field( $action ) { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function submit_button( $label ) { echo '<button>' . $label . '</button>'; }
require dirname( __DIR__ ) . '/wordpress/kniferevive-listlab-import/includes/class-pricing.php';
$rule = array( 'percent' => '20', 'fixed' => '0', 'minimum' => '0', 'rounding' => '0.01' );
$GLOBALS['settings'] = $rule + array( 'categories' => array( 5 => $rule, 400 => $rule ) ); $GLOBALS['saved'] = array();
$_SERVER['REQUEST_METHOD'] = 'GET'; ob_start(); KREV_Import_Pricing::admin(); $html = ob_get_clean();
if ( substr_count( $html, '<fieldset disabled>' ) !== 438 || substr_count( $html, '<fieldset >' ) !== 2 ) throw new Exception( 'Inactive categories must not submit number inputs.' );
if ( strpos( $html, 'krev_import_pricing_complete' ) < strrpos( $html, '</fieldset>' ) ) throw new Exception( 'Completeness marker must follow every rule.' );
$_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array( 'global' => $rule, 'categories' => array( 5 => array( 'enabled' => 1 ) + $rule ) );
ob_start(); KREV_Import_Pricing::admin(); $html = ob_get_clean();
if ( $GLOBALS['saved'] || ! str_contains( $html, 'No settings were saved' ) ) throw new Exception( 'Truncated request changed settings.' );
$_POST['krev_import_pricing_complete'] = 1;
$_POST['categories'][400] = array( 'enabled' => 1 ) + $rule;
ob_start(); KREV_Import_Pricing::admin(); ob_end_clean();
if ( count( $GLOBALS['saved'] ) !== 1 || $GLOBALS['saved'][0]['categories'] !== $GLOBALS['settings']['categories'] ) throw new Exception( 'Complete request did not retain enabled overrides.' );
echo "Large-catalog rendering, truncation protection and complete-save assertions passed\n";
