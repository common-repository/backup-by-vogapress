<?php $byg_backups = get_site_option( VPBackup\VPBackup::OPTNAME, array() ); ?>
<hr class="wrap" />
<div>
	<h2>CloudFlare Account Info</h2>
	<p>Please enter your account information to enable VOGA Press server accesses.  Your API key is in <a target="_blank" href="https://www.cloudflare.com/my-account.html">CloudFlare account page</a></p>
	<form method="post">
		<?php wp_nonce_field( VPBackup\VPBackup::NONCE, 'vpb_nonce' ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th>CloudFlare API Key: </th>
					<td><input type="text" name="vpb_cf_token" value="<?php echo esc_attr( $byg_backups['cloudflare_token'] ) ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th>CloudFlare Email: </th>
					<td><input type="text" name="vpb_cf_email" value="<?php echo esc_attr( $byg_backups['cloudflare_email'] ) ?>" class="regular-text" /></td>
				</tr>
			</tbody>
		</table>
		<button type="submit" name="submit" id="submit" class="button button-primary button-large" style="padding: 0 40px; margin-top: 20px">Save</button>
	</form>
</div>
