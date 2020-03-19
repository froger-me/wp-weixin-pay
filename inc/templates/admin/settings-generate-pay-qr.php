<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$base_qr_url         = home_url( 'wp-weixin/get-qrcode/hash/' );
$url_nonce           = wp_create_nonce( 'wp_weixin_qr_code' );
$base_payment_qr_url = home_url( 'wp-weixin-pay/transfer/' );
$base_payment_qr_src = home_url( 'wp-weixin/get-qrcode/hash/' . base64_encode( $base_payment_qr_url . '|' . $url_nonce ) ); // @codingStandardsIgnoreLine

if ( $custom_transfer ) : ?>
<div class="stuffbox qr-box">
	<div class="inside">
		<h2><?php esc_html_e( 'Payment QR code', 'wp-weixin' ); ?></h2>
		<div class="qr-wrapper">
			<img data-base_url="<?php echo esc_url( $base_qr_url ); ?>" data-default_url="<?php echo esc_url( $base_payment_qr_url ); ?>" id="payment_qr" src="<?php echo esc_url( $base_payment_qr_src ); ?>" />
			<span class="error"><?php esc_html_e( 'Impossible to generate the QR code', 'wp-weixin' ); ?></span>
		</div>	
		<table class="form-table">
			<tbody>
				<tr class="wp_weixin-qr-amount-section">
					<th scope="row"><?php esc_html_e( 'Pre-filled amount', 'wp-weixin' ); ?></th>
					<td>
						<label>￥</label><input type="number" step="any" id="wp_weixin_qr_amount" name="wp_weixin-qr-amount" value="">
						<p class="description">
							<?php esc_html_e( 'Used to pre-fill the amount on the money transfer screen.', 'wp-weixin' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_weixin-qr-amount-fixed-section">
					<th scope="row"><?php esc_html_e( 'Fixed amount', 'wp-weixin' ); ?></th>
					<td>
						<input id="wp_weixin_qr_amount_fixed" type="checkbox" name="wp_weixin-qr-amount-fixed" value="">
						<p class="description">
							<?php esc_html_e( 'Prevent the user from changing the amount on the money transfer screen.', 'wp-weixin' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_weixin-qr-product-name-section">
					<th scope="row"><?php esc_html_e( 'Product Name', 'wp-weixin' ); ?></th>
					<td>
						<input id="wp_weixin_qr_product_name" type="text" name="wp_weixin_qr_product_name" value="">
						<p class="description">
							<?php esc_html_e( 'Product name that will appear on the WeChat payment details.', 'wp-weixin' ); ?><br>
							<?php esc_html_e( 'If filled, the value will use the notes field of the money transfer screen and therefore the "Add Note" link will not be displayed.', 'wp-weixin' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<button data-img="payment_qr" class="qr-button qr-payment-button button button-primary">
				<?php esc_html_e( 'Get QR code', 'wp-weixin' ); ?>
			</button>
		</p>
	</div>
</div>
<?php endif; ?>
