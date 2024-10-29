<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

?>
<div class="wrap">
<h2>Backup By VOGAPress</h2>
<p>Use VOGAPress dashboard to manage your backups.</p>
<table class="widefat">
	<thead>
		<tr>
			<th class="row-title">Name</th>
			<th>Info</th>
		</tr>
	</thead>
	<tbody>
		<tr class="alternate">
			<td class="row-title"><label for="tablecell">Status</label></td>
			<td>
			<?php
				$status = VPBackup\VPBackup::get_status();
			if ( 'inactive' === $status ) {
				echo '<div style="color:#EAAB00; text-transform: capitalize">inactive, re-activate <a href="'.network_admin_url( 'admin.php?page=backup-by-wordpress-settings&registration=yes' ).'">here</a></div>';
			} else {
				echo '<span style="color:#82C352; text-transform: capitalize">'.$status.'</span>';
			}
			?>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell">Multi-Site Enabled</label></td>
			<td><?php echo ( is_multisite() ? 'Yes' : 'No' ); ?></td>
		</tr>
		<tr class="alternate">
			<td class="row-title">Last Activity</td>
			<td><?php echo ( $byg_backup['mdate'] ? date( 'F j, Y, g:i a', $byg_backup['mdate'] ) : '-' ) ; ?></td>
		</tr>
		<tr>
			<td class="row-title">VOGAPress Dashboard</td>
			<td>
				<a target="_blank" class="button-primary" href="<?php echo esc_url( VPBackup\VPBackup::VPURL.'#!/app/backup/'.VPBackup\VPBackup::esc_uuid( $byg_backup['id'] ) ); ?>">Access</a>
			</td>
		</tr>
	</tbody>
</table>
<p>Re-Activation <a href="<?php echo network_admin_url( 'admin.php?page=backup-by-wordpress-settings&registration=yes' );?>">here</a></p>
</div>
