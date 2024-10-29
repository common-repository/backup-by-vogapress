<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( isset( $_POST['byg-nonce'] ) && wp_verify_nonce( $_POST['byg-nonce'], __FILE__ ) ) {
	$upload_dir = wp_upload_dir();
	$response = wp_remote_post( \VPBackup\VPBackup::VPURL.'tokens/create' , array(
		'method' => 'POST',
		'body' => array(
			'urls[netAdmin]' => network_admin_url(),
			'urls[admin]' 	=> admin_url(),
			'urls[home]'	=> home_url(),
			'multisite'	=> is_multisite(),
			'type'		=> 'backup',
			'nonce'		=> \VPBackup\VPBackup::create_nonce( 'byg-token-register' ),
			'version'	=> \VPBackup\VPBackup::VERSION,
			'slash'		=> DIRECTORY_SEPARATOR,
		),
		));
	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
	} else if ( '200' != $response['response']['code'] ) {
		$messages = json_decode( $response['body'], true );
		if ( ! $messages ) {
			$error = $response['body'] ;
		} else {
			$error = $messages['message'];
		}
	} else {
		$messages = json_decode( $response['body'], true );
		if ( $messages['redirect'] ) {
			wp_redirect( $messages['redirect'] );
			die();
		}
	}
}
?>
<style type="text/css">
	button#submit span {
		padding-right: 10px;
	}
	button#submit .dashicons, .dashicons-before:before {
		width: 28px;
		height: 28px;
		font-size: 28px;
	}
	#message-icon {
		font-size: 28px;
	}
</style>
<div class="wrap">
<h2>Backup By VOGAPress</h2>
<?php if ( $error ) : ?>
<div class="error below-h2">
	<p><?php echo $error; ?></p>
</div>
<?php endif; ?>
<form name="form" method="post" action="?page=backup-by-wordpress-settings&registration=yes&noheader=true">
	<table class="widefat" style="margin-top:20px">
		<tr><td style="text-align:center; padding: 20px">
		<span id="message-icon" class="dashicons dashicons-shield" style="margin: 0 0 10px -10px"></span>
		<h4>We offer FREE personal cloud backup service.  It's our way to say thank you for the wonderful WordPress community support.  See <a target="_blank" href="https://vogapress.com/#!/#price">VOGAPress</a> for details.</h4>
		<h4>Click the button to activate your Backup by VOGAPress.</h4>
		<?php wp_nonce_field( __FILE__, 'byg-nonce' ); ?>
		<p style="margin-top: 30px">
		<button type="submit" name="submit" id="submit" class="button button-primary button-large"><span class="dashicons-before dashicons-yes">Activate</span></button>
		</p>
		</td></tr>
	</table>
</form>
</div>
