<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
		<div class="card">
			<div class="card-head">
				<?php if ( ! empty( $oa_logo_url ) ) : ?>
				<div class="account-logo">
					<img src="<?php echo esc_url( $oa_logo_url ); ?>">
				</div>
				<?php endif; ?>
				<?php if ( ! empty( $oa_name ) ) : ?>
					<div class="acount-action">
						<?php esc_html_e( 'Transfer to official account', 'wp-weixin-pay' ); ?>
						<br/>
						<?php echo esc_html( $oa_name ); ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="card-body">
				<h1><?php esc_html_e( 'Transfer amount', 'wp-weixin-pay' ); ?></h1>
				<form>
					<label>￥</label><input id="pay_amount" <?php echo ( $amount_info['fixed'] ) ? 'disabled' : ''; ?> name="amount" type="number" step="any" min="0" value="<?php echo esc_html( $amount_info['amount'] ); ?>">
					<button class="add-notes" href="#"><?php esc_html_e( 'Add Note', 'wp-weixin-pay' ); ?></button>
					<div class="notes-container">
						<div class="notes-content">
							<input name="notes" maxlength="10" type="text" value="<?php echo esc_html( $amount_info['note'] ); ?>">
							<span><?php esc_html_e( 'Up to 10 chars', 'wp-weixin-pay' ); ?></span>
						</div>
						<div class="notes-actions">
							<a class="cancel" href="#"><?php esc_html_e( 'Cancel', 'wp-weixin-pay' ); ?></a>
							<a href="#"><?php esc_html_e( 'OK', 'wp-weixin-pay' ); ?></a>
						</div>
					</div>
					<div class="mask"></div>
					<input type="hidden" id="nonce_str" value="<?php echo esc_attr( $amount_info['nonce_str'] ); ?>" >
					<button class="submit" type="submit"><?php esc_html_e( 'Transfer', 'wp-weixin-pay' ); ?></button>
				</form>
			</div>
		</div>
	</body>
</html>
