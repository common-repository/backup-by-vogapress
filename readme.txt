=== Plugin Name ===
Contributors: vogapress
Tags: backup, WordPress backup, WP backup, back up, full backup, database backup, files backup, website backup, wooCommerce backup, wooCommerce, replicate, restore, restoration, duplicate, snapshot, multisite, file, db, database, storage, cloud storage
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simplest way to manage and monitor your backups with VOGAPress cloud service. FREE cloud backup service is available for personal WordPress site.

== Description ==

Do you have an adequate WordPress backup strategy to ensure you can recover from disasters and hacks?  While hosting companies often offer backup solutions to recover from hardware failure, their offerings are basic replicate and restore functions without monitoring the states of the files.  This makes it harder to detect what and when files are tempered by hackers.

Backup by VOGAPress is a cloud service that backups everything: your source files, uploads, and database.  It is designed to help manage your WordPress backups by giving you more insights to the files.  Our cloud service keeps track and notifiy you of the file changes without putting heavy burden on your hosting.  Its easy to use interface offers visual comparison of files so you can decide the right version to restore.  Automated restore is a simple 2-clicks action with options to select individual file, file types, and, database.

Replicate, Review, and Restore.  Try Backup by VOGAPress to see what you're missing.  **We offer free tier personal plan and professional business plan.  No credit card is required to register.**

See [VOGAPress](https://vogapress.com) for details.

Please use the support form in VOGAPress for quicker response

Backup By VOGAPress offers these goodies:

*   Backups are stored in multiple datacenters, your data is always online
*   Automated easy restore, 2-clicks to start the process
*   30 days of backup 
*   Works with Single, Multisite WordPress, and wooCommerce
*   Review file changes with syntax and difference highlighting
*   Email notification to keep you informed of the statuses
*   Secured data transferring to keep away hackers eavesdropping
*   Low server requirements and efficent use of bandwidths, does not require Cron to work.
*   Works with large and complex directory and links structure
*   Built-in server uptime monitoring
*   Simple plan based on storage usage per WordPress instance 
*   And many more ...

== Installation ==

1. Upload `backup-by-vogapress.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create VOGAPress backup instance by clicking Activate button in the admin page

== Frequently Asked Questions ==

= Where is my backup data stored? =

Your backup data is stored in multiple datacenters in the cloud.  They will always be available to access from our user interface for review and restore.

= Is my data safe? =

The data transferred between the WordPress server and VOGAPress data center are through secured HTTPs channels.  Files and data are stored encrypted at rest in our data center.  PHP curl library is required on the WordPress server to enable secured communication.

= Does my server require special setup? =

No, Backup by VOGAPress is designed to work in plenty of environments.  It consumes very little storage and bandwidths by performing incremental backups.  The plugin is compatible with WordPress v3.0.1 or higher and PHP v5.2 or higher.

= Does it support Multisite? =

Yes, it does work in WordPress Multisite.  All subsites are included in one service plan.  The plugin admin page is in the network site.

= Do I need to register an account with VOGAPress? =

Yes, you will need to register an VOGAPress account to enable our cloud backup service.  You may use email or social account for the registration.  No credit card is required to open an account.

= Is there a free tier service plan to backup my personal WordPress website? =

Yes, we offer a free tier Personal backup service plan.  See the feature set on [VOGAPress](https://vogapress.com/#!/#price).  It's our way to say thank you for the wonderful and supportive WordPress community.

== Screenshots ==

1. Connect WordPress to VOGAPress with the Activate button.
2. Server status of VOGAPress backup.
3. Review your server state from VOGAPress Dashboard.
4. Manage your backups.
5. Review the latest file changes.
6. See the changes in details.
7. Easy restore with a button click.

== Changelog ==

= 0.5.8 =
* Added settings link in plugin page

= 0.5.7 =
* Added error handling of undefined file path

= 0.5.6 =
* Fixed MySQL table dump issue with keys during resume

= 0.5.5 =
* Fixed MySQL table dump issue with keys

= 0.5.4 =
* Improved MyISAM support by removing lock table statements

= 0.5.3 =
* Fixed data import when MyISAM table not ready immediately after creation

= 0.5.2 =
* Fixed data import special character error

= 0.5.1 =
* Added file restore validator

= 0.5.0 =
* Reduced memory required for data export
* Fixed export of MySQL Trigger structure 

= 0.4.9 =
* Fixed error in file export and import

= 0.4.8 =
* Fixed settings page display after registration

= 0.4.7 =
* Improved file handling on slower hosts
* Fixed links pointing to WordPress root path
* Added migration supports

= 0.4.6 =
* Improved support of modsecurity and Apache

= 0.4.5 =
* Improved support of large database

= 0.4.4 =
* Added auto detection of remote IP

= 0.4.3 =
* Added flexible export runtime

= 0.4.2 =
* Included more Proxy IP address lookup

= 0.4.1 =
* Enabled curl data export

= 0.4.0 =
* Improved MySQL dump performance of large table

= 0.3.9 =
* Extended session time to support large backup.

= 0.3.8 =
* Reduced memory usage for large directory.
* Added Apache Mod Security supports.

= 0.3.7 =
* Added CloudFlare firewall settings update.

= 0.3.6 =
* Compatible with CloudFlare plugin.
* Added IPv6 to CloudFlare proxy address validation.

= 0.3.5 =
* Removed filter_var dependency.
* Added compatilibity checker.

= 0.3.4 =
* Improved link structure supports.
* Improved UI.
* Support WordPress 4.3

= 0.3.3 =
* Support HTTPS data transfer.
* Improved CloudFlare supports.

= 0.3.2 =
* Support CloudFlare proxy.
* Improve handling operational timeout.

= 0.3.1 =
* Removed link path restriction.

= 0.3 =
* First public release.
