( function ( $ ) {
	'use strict';

	var config = window.krevProductAttributes || {};
	var messageClass = 'krev-pa-category-message';

	function selectedCategorySlugs() {
		var slugs = [];
		$( '#product_catchecklist input:checked, #product_cat-all input:checked' ).each( function () {
			var slug = config.termMap && config.termMap[ String( this.value ) ];
			if ( slug && slugs.indexOf( slug ) === -1 ) {
				slugs.push( slug );
			}
		} );
		return slugs;
	}

	function hasRelevantGroup( slugs ) {
		var found = false;
		$.each( config.groups || {}, function ( group, groupSlugs ) {
			if ( 'other-finds' !== group && groupSlugs.some( function ( slug ) { return slugs.indexOf( slug ) !== -1; } ) ) {
				found = true;
			}
		} );
		return found;
	}

	function updateMessage() {
		var container = $( '.product_attributes' ).closest( '.panel' );
		container.find( '.' + messageClass ).remove();
		if ( ! hasRelevantGroup( selectedCategorySlugs() ) ) {
			container.prepend( $( '<p>', { class: messageClass, text: config.message } ) );
		}
	}

	$.ajaxPrefilter( function ( options ) {
		var data = options.data || '';
		var isAttributeSearch = 'string' === typeof data
			? data.indexOf( 'woocommerce_json_search_product_attributes' ) !== -1
			: data.action === 'woocommerce_json_search_product_attributes';
		if ( ! isAttributeSearch ) {
			return;
		}
		var extraData = {
			krev_product_id: config.productId || 0,
			krev_categories: selectedCategorySlugs().join( ',' )
		};
		if ( 'string' === typeof data ) {
			options.data = data ? data + '&' + $.param( extraData ) : $.param( extraData );
		} else {
			options.data = $.extend( {}, data, extraData );
		}
	} );

	$( document ).on( 'change', '#product_catchecklist input, #product_cat-all input', function () {
		$( 'select.wc-attribute-search' ).val( null ).trigger( 'change' );
		updateMessage();
	} );

	$( updateMessage );
}( jQuery ) );
