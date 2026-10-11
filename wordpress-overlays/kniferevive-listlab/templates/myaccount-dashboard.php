<?php
defined( 'ABSPATH' ) || exit;

try {
	KREV_ListLab_Account_Dashboard::render();
} catch ( Throwable $error ) {
	if ( function_exists( 'wc_get_logger' ) ) { wc_get_logger()->error( $error->getMessage(), array( 'source' => 'kniferevive-seller-overview', 'context' => 'template' ) ); }
	echo '<section class="krev-seller-overview"><h2>' . esc_html__( 'Seller Overview', 'kniferevive-listlab' ) . '</h2><div class="woocommerce-info">' . esc_html__( 'Your seller overview is temporarily unavailable. Your account links remain available.', 'kniferevive-listlab' ) . '</div></section>';
}

// Stripe Connect and other account extensions attach compact dashboard-only
// notices here, after the seller overview and account help.
do_action( 'woocommerce_account_dashboard' );
