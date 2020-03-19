/* global wx, WpWeixinPay, WeixinJSBridge */
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
	var wechatData,
		beats         = 500,
		beat_interval = 1000;

	wx.config( {
		debug: false,
		appId: WpWeixinPay.weixin.appid,
		timestamp: WpWeixinPay.weixin.timestamp,
		nonceStr: WpWeixinPay.weixin.nonceStr,
		signature: WpWeixinPay.weixin.signature,
		jsApiList: [
			'hideAllNonBaseMenuItem'
		]
	} );

	wx.ready( function() {
		wx.hideAllNonBaseMenuItem();

		wx.error( function( res ) {
			window.alert( res.errMsg );
		} );
	} );

	$( '#pay_amount' ).on( 'keyup', function( e ) {
		e.preventDefault();
		$( this ).currencyFormat();
	} );

	if ( 0 < $( '.notes-content input' ).val().length ) {
		$('.add-notes').remove();
	}

	$( '.add-notes' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '.notes-content input' ).focus();
		$( '.mask' ).show();
		$( '.notes-container' ).show();
	} );

	$( '.notes-actions a' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '.mask' ).hide();
		$( '.notes-container' ).hide();
	});

	$( '.notes-actions a.cancel' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '.notes-content input' ).val( '' );
	} );


	$( '.submit' ).on( 'click', function( e ) {
		e.preventDefault();

		var data   = {
				amount: parseFloat( $( '#pay_amount' ).val() ),
				notes: $( '.notes-content input' ).val(),
				nonceStr: $( '#nonce_str' ).val(),
				action: 'wp_weixin_pay'
			},
			button = $( this );

		button.attr( 'disabled', 'disabled' );

		if ( ! data.amount ) {
			button.removeAttr( 'disabled' );

			return;
		}

		$.ajax( {
			url: WpWeixinPay.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {

				if ( response.success ) {
					wechatData = response.data;

					callWXPay( wechatData );
				} else {
					var message = '';
					/* jshint ignore:start */
					$.each( response.data, function( idx, value ) {
						message += value.message + "\n";
					} );
					/* jshint ignore:end */

					window.alert( message );
				}
			},
			error: function ( jqXHR, textStatus ) {
				WpWeixinPay.debug && window.console.log( textStatus );
			}
		} );
		
	} );

   function onBridgeReady() {
		var api    = 'getBrandWCPayRequest',
			params = {
				'appId': '' + wechatData.appId ,
				'timeStamp': '' + wechatData.timeStamp ,
				'nonceStr': '' + wechatData.nonceStr ,
				'package': '' + wechatData['package'],
				'signType': 'MD5',
				'paySign': '' + wechatData.paySign
			},
			data   = {
				action: 'wp_weixin_pay_init_check',
				transactionId: wechatData.transactionId,
				nonceStr: $( '#nonce_str' ).val()
			};

		$.ajax( {
			url: WpWeixinPay.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {

				if ( response.success ) {
					invokeBridge( api, params );
				}
			},
			error: function ( jqXHR, textStatus ) {
				WpWeixinPay.debug && window.alert( textStatus );
			}
		} );

	}

	function callWXPay( wechatData ) {

		if ( 'failure' === wechatData.result ) {

			return;
		}

		if ( 'undefined' === typeof WeixinJSBridge ) {

			if ( document.addEventListener ) {
				document.addEventListener( 'WeixinJSBridgeReady', onBridgeReady, false) ;
			} else if ( document.attachEvent ) {
				document.attachEvent( 'WeixinJSBridgeReady', onBridgeReady );
				document.attachEvent( 'onWeixinJSBridgeReady', onBridgeReady );
			}
		} else {
			onBridgeReady();
		}
	}

	function invokeBridge( api, params ) {
		WeixinJSBridge.invoke( api, params, function( res ) {

			if ( 'get_brand_wcpay_request:ok' === res.err_msg ) {
				wpWeixinPayCheck();
			} else if ( 'get_brand_wcpay_request:cancel' === res.err_msg ) {

				if ( wechatData.CancelPayUrl ) {
					window.location = wechatData.CancelPayUrl;
				} else {
					$( '.submit' ).removeAttr( 'disabled' );
				}
			} else if ( 'get_brand_wcpay_request:fail' === res.err_msg ) {
				window.alert( res.err_desc );

				if ( wechatData.FailedPayUrl ) {
					window.location = wechatData.FailedPayUrl;
				} else {
					WeixinJSBridge.invoke( 'closeWindow', {}, function(){} );
				}
			} else {
				 window.setTimeout( function() {

					if ( wechatData.FailedPayUrl ) {
						window.location = wechatData.FailedPayUrl;
					} else {
						WeixinJSBridge.invoke( 'closeWindow', {}, function(){} );
					}
				}, 10000 );
			}
		} );
	}

	function wpWeixinPayCheck() {
		var data = {
			action: 'wp_weixin_pay_check',
			transactionId: wechatData.transactionId
		};

		$.ajax( {
			url: WpWeixinPay.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				if ( response.success ) {

					if ( response.data.confirmed ) {
						WeixinJSBridge.invoke( 'closeWindow', {}, function(){} );
					} else {

						if ( beats-- > 0 ) {
							setTimeout( wpWeixinPayCheck, beat_interval );
						} else {

							if ( wechatData.TimeoutPayUrl ) {
								window.location = wechatData.TimeoutPayUrl;
							} else {
								window.alert( 'timeout' );
								WeixinJSBridge.invoke( 'closeWindow', {}, function(){} );
							}
						}
					}
				} else {

					if ( WpWeixinPay.debug ) {
						window.alert( JSON.stringify( response ) );
					} else {
						WeixinJSBridge.invoke( 'closeWindow', {}, function(){} );
					}
				}
			},
			error: function ( jqXHR, textStatus ) {
				WpWeixinPay.debug && window.alert( textStatus );
			}
		} );
	}

} );