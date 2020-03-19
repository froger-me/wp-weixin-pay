/* global WpWeixinPay */
( function( $ ) {
	$.fn.currencyFormat = function() {
		this.each( function() {
			var val = this.value;
			
			if ( '' === val || '.' === val ) {
				this.value = '';

				return;
			}

			var split = val.split( '.' );

			if ( '' === split[0] ) {
				split[0]   = 0;
				this.value = split.join( '.' );
			}

			if ( split[0].startsWith( 0 ) ) {
				if ( split[0] !== '0' || split[1] ) {
					split[0]   = parseInt( split[0] );
					this.value = split.join( '.' );
				}
				
			}

			if ( split[1] && split[1].length > 2 ) {
				split[1]   = split[1].substring( 0, 2 );
				this.value = split.join( '.' );
			}

		} );

		return this;
	};
} )( jQuery );

jQuery( document ).ready( function( $ ) {

	$( '#wp_weixin_qr_amount' ).on( 'keyup', function( e ) {
		e.preventDefault();
		$( this ).currencyFormat();
	} );


	$( '.qr-payment-button' ).on( 'click', function( e ) {
		e.preventDefault();

		var button 	    = $( this ),
			img 	    = $( '#' + button.data( 'img' ) ),
			data        = {
				amount: $( '#wp_weixin_qr_amount' ).val(),
				fixed: $( '#wp_weixin_qr_amount_fixed' ).prop( 'checked' ),
				productName: $( '#wp_weixin_qr_product_name' ).val(),
				url: img.data( 'default_url' )
			};
		

		data.action = 'wp_weixin_pay_get_settings_qr';

		$.ajax( {
			url: WpWeixinPay.ajax_url,
			type: 'POST',
			data: data
		} ).done( function( response ) {

			if ( response.success ) {
				img.attr( 'src', img.data( 'base_url' ) + response.data );
				img.css( 'visibility', 'visible' );
				img.parent().children( 'span' ).hide();
			} else {
				img.css( 'visibility', 'hidden' );
				img.parent().children( 'span' ).show();
			}
		} ).fail( function( qXHR, textStatus ) {
			WpWeixinPay.debug && window.console.log( textStatus );
		} );
	} );

} );