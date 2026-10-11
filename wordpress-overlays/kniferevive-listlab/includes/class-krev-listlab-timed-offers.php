<?php
defined( 'ABSPATH' ) || exit;

/**
 * Native WooCommerce scheduled-sale helpers shared by ListLab and storefronts.
 * Pricing and dates always remain on the WC_Product.
 */
final class KREV_ListLab_Timed_Offers {
	public static function state( WC_Product $product ) {
		$from = $product->get_date_on_sale_from( 'edit' );
		$to = $product->get_date_on_sale_to( 'edit' );
		$timezone = wp_timezone();
		$now = new DateTimeImmutable( 'now', $timezone );
		$regular = $product->get_regular_price( 'edit' );
		$sale = $product->get_sale_price( 'edit' );
		$state = 'none';
		if ( '' !== $sale && $to instanceof WC_DateTime ) {
			if ( $to->getTimestamp() <= $now->getTimestamp() ) $state = 'expired';
			elseif ( $from instanceof WC_DateTime && $from->getTimestamp() > $now->getTimestamp() ) $state = 'scheduled';
			elseif ( $product->is_on_sale() ) $state = 'active';
		}
		return array(
			'enabled' => 'none' !== $state,
			'state' => $state,
			'active' => 'active' === $state,
			'scheduled' => 'scheduled' === $state,
			'regular_price' => $regular,
			'sale_price' => $sale,
			'starts_at' => $from instanceof WC_DateTime ? wp_date( DATE_ATOM, $from->getTimestamp(), $timezone ) : null,
			'ends_at' => $to instanceof WC_DateTime ? wp_date( DATE_ATOM, $to->getTimestamp(), $timezone ) : null,
			'ends_at_epoch' => $to instanceof WC_DateTime ? $to->getTimestamp() : 0,
			'timezone' => $timezone->getName(),
			'timezone_label' => self::timezone_label( $timezone, $now ),
		);
	}

	public static function apply( WC_Product $product, array $data ) {
		$enabled = ! empty( $data['timed_offer_enabled'] );
		if ( ! $enabled ) {
			$product->set_date_on_sale_from( null );
			$product->set_date_on_sale_to( null );
			return true;
		}
		$regular = (string) $product->get_regular_price( 'edit' );
		$sale = (string) $product->get_sale_price( 'edit' );
		if ( '' === $regular || '' === $sale || (float) $sale >= (float) $regular ) {
			return new WP_Error( 'listlab_timed_offer_price', 'Sale price must be lower than the regular price.', array( 'status' => 422, 'errors' => array( 'sale_price' => 'Sale price must be lower than the regular price.' ) ) );
		}
		$end = self::parse( $data['sale_end_date'] ?? '', $data['sale_end_time'] ?? '' );
		if ( is_wp_error( $end ) ) return $end;
		$mode = 'scheduled' === ( $data['sale_start_mode'] ?? '' ) ? 'scheduled' : 'immediately';
		$start = null;
		if ( 'scheduled' === $mode ) {
			$start = self::parse( $data['sale_start_date'] ?? '', $data['sale_start_time'] ?? '' );
			if ( is_wp_error( $start ) ) return $start;
		} else {
			$start = new DateTimeImmutable( 'now', wp_timezone() );
		}
		if ( $end->getTimestamp() <= $start->getTimestamp() ) {
			return new WP_Error( 'listlab_timed_offer_range', 'Offer end time must be later than the start time.', array( 'status' => 422, 'errors' => array( 'sale_end_date' => 'Offer end time must be later than the start time.' ) ) );
		}
		if ( $end->getTimestamp() <= time() ) {
			return new WP_Error( 'listlab_timed_offer_past', 'Choose an offer end time in the future.', array( 'status' => 422, 'errors' => array( 'sale_end_date' => 'Choose an offer end time in the future.' ) ) );
		}
		$product->set_date_on_sale_from( 'scheduled' === $mode ? new WC_DateTime( '@' . $start->getTimestamp() ) : null );
		$product->set_date_on_sale_to( new WC_DateTime( '@' . $end->getTimestamp() ) );
		return true;
	}

	public static function render( WC_Product $product, $mode = 'full' ) {
		$offer = self::state( $product );
		if ( 'active' !== $offer['state'] && 'scheduled' !== $offer['state'] ) return '';
		$ends = $offer['ends_at_epoch'] ? wp_date( 'F j, Y \\a\\t g:i A T', $offer['ends_at_epoch'], wp_timezone() ) : '';
		if ( 'scheduled' === $offer['state'] ) {
			$starts = $offer['starts_at'] ? wp_date( 'F j, Y \\a\\t g:i A T', strtotime( $offer['starts_at'] ), wp_timezone() ) : '';
			return '<aside class="krev-timed-offer krev-timed-offer--' . esc_attr( $mode ) . ' is-scheduled"><strong>' . esc_html__( 'Special offer starts', 'kniferevive-listlab' ) . '</strong><p>' . esc_html( $starts ) . '</p></aside>';
		}
		$label = 'badge' === $mode ? esc_html__( 'Sale ends soon', 'kniferevive-listlab' ) : esc_html__( 'Limited-time offer', 'kniferevive-listlab' );
		if ( 'badge' === $mode ) return '<aside class="krev-timed-offer krev-timed-offer--badge"><strong class="krev-timed-offer__label">' . $label . '</strong><p class="krev-timed-offer__deadline">' . sprintf( esc_html__( 'Ends %s', 'kniferevive-listlab' ), esc_html( $ends ) ) . '</p></aside>';
		return '<aside class="krev-timed-offer krev-timed-offer--' . esc_attr( $mode ) . '" data-krev-timed-offer data-sale-end="' . esc_attr( (string) $offer['ends_at_epoch'] ) . '"><strong class="krev-timed-offer__label">' . $label . '</strong><span class="krev-timed-offer__countdown" aria-hidden="true"><span><b data-krev-days>--</b><small>' . esc_html__( 'Days', 'kniferevive-listlab' ) . '</small></span><span><b data-krev-hours>--</b><small>' . esc_html__( 'Hrs', 'kniferevive-listlab' ) . '</small></span><span><b data-krev-minutes>--</b><small>' . esc_html__( 'Min', 'kniferevive-listlab' ) . '</small></span><span><b data-krev-seconds>--</b><small>' . esc_html__( 'Sec', 'kniferevive-listlab' ) . '</small></span></span><p class="krev-timed-offer__deadline">' . sprintf( esc_html__( 'Ends %s', 'kniferevive-listlab' ), esc_html( $ends ) ) . '</p><button class="krev-timed-offer__toggle" type="button" data-krev-timed-offer-toggle aria-expanded="true">' . esc_html__( 'Hide countdown', 'kniferevive-listlab' ) . '</button></aside>';
	}

	private static function parse( $date, $time ) {
		$date = trim( (string) $date ); $time = trim( (string) $time );
		if ( '' === $date || '' === $time ) return new WP_Error( 'listlab_timed_offer_date', 'Choose when this timed offer ends.', array( 'status' => 422, 'errors' => array( 'sale_end_date' => 'Choose when this timed offer ends.' ) ) );
		$value = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();
		if ( ! $value || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) return new WP_Error( 'listlab_timed_offer_date', 'Enter a valid offer date and time.', array( 'status' => 422, 'errors' => array( 'sale_end_date' => 'Enter a valid offer date and time.' ) ) );
		return $value;
	}

	private static function timezone_label( DateTimeZone $timezone, DateTimeImmutable $date ) {
		return $date->format( 'T' ) . ' (' . $timezone->getName() . ')';
	}
}
