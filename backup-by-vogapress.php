<?php
/**
 * Plugin Name: Backup by VOGA Press
 * Version: 0.5.8
 * Plugin URI: http://vogapress.com/
 * Description: Simplest way to manage your backups with VOGAPress cloud service. Added with file monitoring to let you know when your website has been compromised.
 * Author: VOGA Press
 * Author URI: http://vogapress.com/
 * Requires at least: 3.0.1
 * Tested up to: 4.5.3
 * Network: True
 *
 * Text Domain: backup-by-vogapress
 * Domain Path: /lang/
 *
 * PHP version 5.2
 *
 * @category WordPress,Restore,Backup
 * @package  Backup_By_Vogapress
 * @author   Raphael Tse <raphael@vogapress.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://vogapress.com
 * @since    0.3.0
 **/

namespace VPBackup ;

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

require(dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'timeout.php');

class VPBackup
{
	CONST VPURL         	= 'https://vogapress.com/';
	CONST ALLOWEDDOMAIN 	= 'vogapress.com';
	CONST OPTNAME		= 'byg-backup';
	CONST NONCE		= 'vogapress-backup';
	CONST TABLENAME		= 'backup_by_vogapress';
	CONST VERSION		= '0.5.8';
	CONST VALIDATE_NUM	= 1;
	CONST VALIDATE_ALPHANUM	= 2;
	CONST VALIDATE_IP	= 3;
	CONST VALIDATE_NAME	= 4;

	/**
	 * The single instance of WordPress_Plugin_Template.
	 * @var     object
	 * @access  private
	 * @since     0.3.0
	 */
	private static $_instance = null;
	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   0.3.0
	 */
	public static $settings = null;
	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   0.3.0
	 */
	private $_version;
	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   0.3.0
	 */
	private $_token;
	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   0.3.0
	 */
	public $script_suffix;
	/**
	 * IPs that are allowed to create session
	 * @var     Array
	 * @access  public
	 * @since   0.3.0
	 */
	private static $white_ips = array(
	'45.55.245.94',
	'104.236.17.183',
	);

	/**
	 * Constructor function.
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function __construct ( $version = VPBackup::VERSION )
	{
		Timeout::init();
		$this->_version = $version;
		$this->_token   = 'backup-by-wordpress';
		self::$_instance   = $this;

		// Load plugin environment variables
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// update to latest
		$current_version = get_site_option( $this->_token . '-version' );
		if ( ! $current_version || $current_version != $version ) {
			$this->install();
		}
		self::$settings = VPBackup::get_option( 'settings' );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// add actions
		$this->add_actions();
	} // End __construct ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function load_localisation ()
	{
		load_plugin_textdomain( 'backup-by-vogapress', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function load_plugin_textdomain ()
	{
		$domain = 'backup-by-vogapress';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . DIRECTORY_SEPARATOR . $domain . DIRECTORY_SEPARATOR . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR ) );
	} // End load_plugin_textdomain ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function install ()
	{
		$this->_log_version_number();
		$this->_htaccess_init();
		$this->_install_tables();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	private function _log_version_number ()
	{
		update_site_option( $this->_token . '-version', $this->_version );
	} // End _log_version_number ()

	/**
	 * Add settings page to admin menu
	 * @access  public
	 * @since   0.3.0
	 * @return void
	 */
	public function add_menu_item ()
	{
		$page = add_menu_page( __( 'Backup', 'backup-by-wordpress' ), __( 'Backup', 'backup-by-wordpress' ), 'export', $this->_token . '-settings',  array( $this, 'settings_page' ), null, 80 );
	}

	/**
	 * Add action link to plugin page
	 * @access  public
	 * @since   0.5.8
	 * @return void
	 */
	public function action_links ( $links, $file )
	{
		if ( $file == plugin_basename( __FILE__ ) ) {
				$link = array( '<a href="' . esc_url( network_admin_url( 'admin.php?page=backup-by-wordpress-settings' ) ) . '">'.esc_html__( 'Settings', 'backup-by-vogapress' ).'</a>' );
			$links = array_merge( $link, $links );
		}
				return $links;
	}

	/**
	 * Display settings page
	 * @access  public
	 * @since   0.3.0
	 * @return void
	 */
	public function settings_page ()
	{
		include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'settings-page.php' ;
	}

	/**
	 * add action hooks
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function add_actions ()
	{
		$actions = array( 'session', 'data_list', 'data_export', 'data_import', 'file_list', 'file_export', 'file_import', 'register', 'checks', 'refresh' );
		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_vpb_'.$action, array( &$this, $action ) );
			add_action( 'wp_ajax_nopriv_vpb_'.$action, array( &$this, $action ) );
		}

		// Load API for generic admin functions
		if ( is_admin() ) {
			// Add settings page to menu
			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( &$this, 'add_menu_item' ) );
				add_action( 'network_admin_notices', array( &$this, 'notices' ) );
				add_action( 'admin_notices', array( &$this, 'notices' ) );
			} else {
				add_action( 'admin_menu', array( &$this, 'add_menu_item' ) );
				add_action( 'admin_notices', array( &$this, 'notices' ) );
			}

			add_filter( 'plugin_action_links', array( &$this, 'action_links' ), 10, 2 );
		}
	}

	/**
	 * admin notices
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function notices ()
	{
		if ( ! strlen( self::$settings['uuid'] ) ) {
			echo '<div class="update-nag"><a href="'. esc_url( network_admin_url( 'admin.php?page=backup-by-wordpress-settings&registration=yes' ) ) . '" >Backup by VOGAPress</a> requires activation.  Activate <a href="' . esc_url( network_admin_url( 'admin.php?page=backup-by-wordpress-settings&registration=yes' ) ) . '">here</a>.</div>';
		} else {
			self::cloudflare_init();
		}
		if ( '1' == $_REQUEST['bygmessage'] ) {
			if ( ! strlen( self::$settings['uuid'] ) ) {
				echo '<div class="error"><p>Backup by VOGAPress encounters an error during acitvation. Contact <a href="https://vogapress.com/#!/app/support">Support</a> for further assistance.</p></div>';
			} else {
				echo '<div class="updated"><p>Backup by VOGAPress is activated.</p></div>';
			}
		}
	}

	/**
	 * data list
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function data_list ()
	{
		if ( $this->verify_request() ) {
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'mysqldump.php' ;
			global $wpdb;
			$adapter = TypeAdapterFactory::create( 'mysql', $wpdb );

			$filename = 'php://output';
			if ( self::_is_curl_available() ) {
				$filename = Timeout::get_tmp_name( $_REQUEST['jobId'] );
			}
			if ( $_REQUEST['start'] ) {
				$handle = fopen( $filename, ( $_REQUEST['start'] || ! self::_is_curl_available() ? 'wb' : 'ab' ) );
				fwrite( $handle,'[' );
				foreach ( $wpdb->get_results( $adapter->show_tables( DB_NAME ), ARRAY_N ) as $row ) {
					fwrite( $handle, json_encode( array( 'path' => current( $row ), 'subtype' => 'table' ) ) . ',' );
				}
				foreach ( $wpdb->get_results( $adapter->show_views( DB_NAME ), ARRAY_N ) as $row ) {
					fwrite( $handle, json_encode( array( 'path' => current( $row ), 'subtype' => 'view' ) ) . ',' );
				}
				foreach ( $wpdb->get_results( $adapter->show_triggers( DB_NAME ), ARRAY_N ) as $row ) {
					fwrite( $handle, json_encode( array( 'path' => current( $row ), 'subtype' => 'trigger' ) ) . ',' );
				}
				fwrite( $handle,']' );
				fclose( $handle );
				if ( self::_is_curl_available() ) {
					echo '2';
				}
			} else {
				if ( self::_is_curl_available() ) {
					include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'files.php' ;
					$file = new VPBFiles();
					if ( $file->download_curl( $filename ) ) {
						echo '1';
					} else {
						echo '-1';
					}
					unlink( $filename );
				}
			}
			wp_die();
		}
	}

	/**
	 * data export
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function data_export ()
	{
		if ( $this->verify_request() ) {
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'mysqldump.php' ;
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'files.php' ;
			$table   = self::filter( $_REQUEST['table'], self::VALIDATE_NAME );
			$view    = self::filter( $_REQUEST['view'], self::VALIDATE_NAME );
			$trigger = self::filter( $_REQUEST['trigger'], self::VALIDATE_NAME );
			if ( $table ) {
				$export = new Mysqldump( 'mysql', array( 'include-tables' => array( $table ) ) );
			} else if ( $view ) {
				$export = new Mysqldump( 'mysql', array( 'include-views' => array( $view ) ) );
			} else if ( $trigger ) {
				$export = new Mysqldump( 'mysql', array( 'include-triggers' => array( $trigger ) ) );
			}
			if ( $export ) {
				if ( self::_is_curl_available() ) {
					$filename = Timeout::get_tmp_name( $_REQUEST['jobId'] );
					if ( $_REQUEST['last'] ) {
						$file = new VPBFiles();
						if ( $file->download_curl( $filename ) ) {
							echo '1';
						} else {
							echo '-1';
						}
						unlink( $filename );
					} else if ( $export->start( $filename ) ) {
						echo '3';
					} else {
						echo '2';
					}
				} else {
					$export->start();
				}
			}
			wp_die();
		}
	}

	/**
	 * data import
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function data_import ()
	{
		if ( $this->verify_request() && $this->verify_url( $_REQUEST['url'] ) ) {
			$tmp_name = path_join( sys_get_temp_dir(), 'vpb-' . $_REQUEST['jobId'] );
			if ( $_REQUEST['start'] ) {
				if ( $this->_get_remote_file( $_REQUEST['url'], $tmp_name ) ) {
					echo '2';
				} else {
					echo '-1';
				}
				wp_die();

			} else {
				include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'mysqlimport.php' ;
				$import = new Mysqlimport();
				try {
					if ( $import->start( $tmp_name, $_REQUEST['path'] ) ) {
						unlink( $tmp_name );
						echo '1';
					} else {
						echo '2'; // times up
					}
				} catch (Exception $e) {
					echo '-2';
				}
				self::$settings['mtime'] = time();
				$this->update_option( 'settings', self::$settings );
				wp_die();

			}
		}
	}

	/**
	 * file list
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function file_list ()
	{
		if ( $this->verify_request() ) {
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'files.php' ;
			$export = new VPBFiles();
			if ( self::_is_curl_available() ) {
				$filename = Timeout::get_tmp_name( 'vpb-tmp-'.$_REQUEST['jobId'] );
				if ( $_REQUEST['last'] ) {
					if ( $export->download_curl( $filename ) ) {
						unlink( $filename );
						echo '1';
					} else {
						echo '-1';
					}
				} else if ( $export->glob( ABSPATH, $filename ) ) {
					echo '3';
				} else {
					echo '2';
				}
			} else {
				$export->glob( ABSPATH );
			}
			wp_die();
		}
	}

	/**
	 * file import
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function file_import ()
	{
		if ( $this->verify_request() && (empty($_REQUEST['url']) || $this->verify_url( $_REQUEST['url'] )) ) {
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'files.php' ;
			$import = new VPBFiles();
			echo $import->upload( $_REQUEST );
			wp_die();
		}
	}


	/**
	 * file export
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function file_export ()
	{
		if ( $this->verify_request() ) {
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'files.php' ;
			$export = new VPBFiles();
			if ( self::_is_curl_available() ) {
				if ( $export->download_curl( $_REQUEST['file'] ) ) {
					echo '1';
				} else {
					echo '-1';
				}
				wp_die();
			} else {
				$export->download( $_REQUEST['file'] );
			}
			wp_die();
		}
	}

	/**
	 * Get session
	 * @access  public
	 * @since   0.3.0
	 * @return  void
	 */
	public function session()
	{
		// validate permission
		// validate against UUID
		if ( ! strlen( self::$settings['uuid'] ) ) { return ; }
		$signature = md5(
			$_REQUEST['sessionId'] . '|' .
			$_REQUEST['timestamp'] . '|' .
			self::$settings['uuid'], false
		);
		if ( $signature == $_REQUEST['signature'] && abs( time() - intval( $_REQUEST['timestamp'] ) ) < 3600 ) {
			global $wpdb;
			$post = array(
				'sessionSecret'	=> self::create_nonce( $_REQUEST['sessionId'] ),
				'sessionId'    	=> $_REQUEST['sessionId'],
				'version'	=> $this->_version,
				'paths'		=> $this->_get_paths(),
				'wpversion'	=> get_bloginfo( 'version' ),
				'curl'		=> self::_is_curl_available(),
				'tablePrefix'	=> $wpdb->base_prefix,
				'nonce'		=> $_REQUEST['nonce'],
			);
			$resp = wp_remote_post(
				esc_url( self::VPURL . 'jobs/session/' . $_REQUEST['sessionId'] ), array(
				'method'     => 'POST',
				'body'        => $post,
				)
			);
			if ( is_wp_error( $resp ) ) {
				echo '-2';
				wp_die();
			} else if ( 200 == $resp['response']['code'] ) {
				$data = json_decode( $resp['body'], true );
				self::$settings['mdate'] = time();
				if ( $data['valid'] ) {
					self::$settings['referer_names'] = $this->_detect_ip_field();
				} else {
					self::$settings['referer_names'] = null;
				}
				$this->update_option( 'settings', self::$settings );
				echo '1';
				wp_die();
			} else {
				echo '-1';
				wp_die();
			}
		}
	}

	/**
	 * Registration

	 * @access public
	 * @since  0.3.0
	 * @return void
	 */
	public function register()
	{
		// validate permission
		// validate against UUID
		if ( $_REQUEST['nonce'] == self::create_nonce( 'byg-token-register' ) ) {
			$this->update_option(
				'settings', array(
				'id'         	=> $_REQUEST['id'],
				'uuid'        	=> $_REQUEST['uuid'],
				'referer_names' => null,
				)
			);
			echo '1';
			wp_die();
		}
	}

	/**
	 * Verify IP Address
	 * @access  private
	 * @since   0.3.0
	 * @return  boolean
	 */
	private function verify_ip() {
		// cloud flare proxy support
		if ( isset($_SERVER['HTTP_CF_CONNECTING_IP']) ) {
			require_once(dirname( __FILE__ ).DIRECTORY_SEPARATOR.'cloudflareproxy.php');

			if ( ! CloudFlareProxy::in_range( $_SERVER['REMOTE_ADDR'] ) ) {
				$ips = array( $_SERVER['HTTP_CF_CONNECTING_IP'] );
			} else {
				$ips = $this->_get_remote_ip();
			}
		} else {
			$ips = $this->_get_remote_ip();
		}
		return ( 0 < count( array_intersect( self::_get_white_ips(), $ips ) ) );
	}

	/**
	 * Verify key
	 * @access  private
	 * @since   0.3.0
	 * @return  boolean
	 */
	private function verify_request()
	{
		$timestamp = self::filter( $_REQUEST['timestamp'], self::VALIDATE_NUM );
		$sessionId = self::filter( $_REQUEST['sessionId'], self::VALIDATE_ALPHANUM );
		$signature = self::filter( $_REQUEST['signature'], self::VALIDATE_ALPHANUM );
		$jobId = self::filter( $_REQUEST['jobId'], self::VALIDATE_ALPHANUM );

		if ( ! $timestamp || ! $sessionId || ! $signature || ! $jobId || ! strlen( self::$settings['uuid'] ) ) {
			return false ;
		}

		$secret = self::create_nonce( $sessionId );
		$secret2 = self::create_nonce_2( $sessionId );
		$md = md5( $timestamp . '|' . $secret, false );
		$md2 = md5( $timestamp . '|' . $secret2, false );

		return ( $md == $signature || $md2 == $signature ) &&
			abs( intval( $timestamp ) - time() ) < 3600 && $this->verify_ip();
	}

	/**
	 * Verify url
	 * @access  private
	 * @since   0.3.0
	 * @return  boolean
	 */
	private function verify_url($url)
	{
		$urlcomp = parse_url( $url );
		return preg_match( '/vogapress\.com$/i', $urlcomp['host'] );
	}

	/**
	 * get remote ip
	 * @access  private
	 * @since   0.3.0
	 * @return  string
	 */
	private function _get_remote_ip()
	{
		$headers = $_SERVER;
		$the_ip = '';
		$the_ips = array();
		if ( self::$settings['referer_names'] ) {
			$fields = array_intersect( array( 'HTTP_CLIENT_IP', 'X-Real-IP', 'X-Forwarded-For', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ), self::$settings['referer_names'] );
		} else {
			$fields = array();
		}
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $headers ) && strpos( $headers[ $field ], ',' ) ) {
				// it's csv values
				$the_ip = array_unshift( array_map( 'trim', explode( ',', $headers[ $field ] ) ) );

			} else if ( array_key_exists( $field, $headers ) ) {
				$the_ip = $headers[ $field ];

			}
			if ( strlen( $the_ip ) ) {
				// remove port
				$the_ip = preg_replace( '#:.*$#','', $the_ip );
				if ( self::filter( $the_ip, self::VALIDATE_IP ) ) {
					array_push( $the_ips, $the_ip );
				}
			}
			$the_ip = '';
		}
		return $the_ips;
	}

	/**
	 * get remote file
	 * @access  private
	 * @since   0.3.0
	 * @return  string
	 */
	private function _get_remote_file( $src, $dst )
	{
		set_time_limit( 0 );
		$resp = wp_remote_get(
			$src, array(
			'stream' => true,
			'filename' => $dst,
			)
		);
		if ( is_wp_error( $resp ) || 200 != $resp['response']['code'] ) {
			return false;
		}
		return true;
	}

	/**
	 * get system paths
	 * @access  private
	 * @since   0.3.0
	 * @return  array
	 */
	private function _get_paths( )
	{
		$paths = array(
			'plugin'	=> str_replace( ABSPATH,'',trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) ),
			'theme'		=> str_replace( ABSPATH,'',trailingslashit( get_theme_root() ) ),
		);
		if ( ! is_multisite() ) {
			$upload_dir	= wp_upload_dir();
			$paths['upload'] = array( str_replace( ABSPATH, '', trailingslashit( $upload_dir['basedir'] ) ) );
		} else {
			if ( function_exists( 'wp_get_sites' ) ) {
				// beyond 10000 subsite, wp_is_large_network will trigger empty return
				$blog_list = wp_get_sites( array(
					'limit' => 10000,
				) );
			} else {
				$blog_list = get_blog_list( 0, 'all' );
			}
			$paths['upload'] = array();
			foreach ( $blog_list as $blog ) {
				switch_to_blog( $blog['blog_id'] );
				$upload_dir = wp_upload_dir();
				array_push( $paths['upload'],str_replace( ABSPATH,'',trailingslashit( $upload_dir['basedir'] ) ) );
				restore_current_blog();
			}
		}
		return $paths;
	}
	/**
	 * checks for compatibility
	 * @access  public
	 * @since   0.3.5
	 * @return  void
	 */
	public function checks()
	{
		if ( $this->verify_ip() ) {
			echo 'I';
			echo ( $this->verify_request() ? 'R' : 'r' );
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'files.php' ; echo 'F';
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'mysqldump.php' ; echo 'D';
			include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'mysqlimport.php' ; echo 'I';
			$tmpname = Timeout::get_tmp_name( $_REQUEST['jobId'] ); echo 'T';
			echo ( $this->_get_remote_file( self::VPURL, $tmpname ) ? 'D' : 'd' );
			if ( file_exists( $tmpname ) ) {
				echo 'E';
				unlink( $tmpname );
			} else {
				echo 'e';
			}
			$export = new VPBFiles(); echo 'F';
			if ( self::_is_curl_available() ) {
				echo 'C';
				echo ( $export->download_curl( 'index.php' ) ? 'U' : 'u' );
			} else {
				echo 'c';
			}
			wp_die();
		}
	}
	/**
	 * detect if curl is available
	 * @access  private
	 * @since   0.3.3
	 * @return  boolean
	 */
	private function _is_curl_available( )
	{
		return function_exists( 'curl_init' );
	}

	/**
	 * create nonce
	 * @access  public static
	 * @since   0.3.0
	 * @return  boolean
	 */
	public static function create_nonce($action)
	{
		$i = (( time() >> 17 ) + ( ( time() >> 16 ) > 0 ? 1 : 0 ));
		return substr( wp_hash( $i . '|' . $action, 'nonce' ), -12, 10 );
	}
	/**
	 * create nonce 2
	 * @access  public static
	 * @since   0.3.9
	 * @return  boolean
	 */
	public static function create_nonce_2($action)
	{
		$i = (( time() >> 17 ) + ( ( time() >> 16 ) > 0 ? 1 : 0 )) - 1;
		return substr( wp_hash( $i . '|' . $action, 'nonce' ), -12, 10 );
	}


	/**
	 * Registration

	 * @access public static
	 * @since  0.3.0
	 * @return string
	 */
	public static function get_status()
	{
		$timestamp = time();
		$query_string = build_query(
			array(
			'signature' => md5( $timestamp . '|' . self::$settings['uuid'] ),
			'timestamp' => $timestamp,
			)
		);
		$resp = wp_remote_get(
			esc_url( self::VPURL . 'backups/' . self::$settings['id'] . '/status/' ) .'?'.$query_string, array(
			'method'    => 'GET',
			'body'        => $get,
			)
		);
		if ( is_wp_error( $resp ) ) {
			return 'unknown';
		} else if ( 200 == $resp['response']['code'] ) {
			$data = json_decode( $resp['body'], true );
			return $data['status'];
		}
		return 'unknown';
	}

	/**
	 * Escape UUID

	 * @access public static
	 * @since  0.3.0
	 * @return string
	 */
	public static function esc_uuid($x) {
		return preg_replace( '/[^A-Za-z0-9\-]/','',$x );
	}

	/**
	 * Filter function to validate inputs
	 * @access public static
	 * @since  0.3.5
	 * @return string
	 */
	public static function filter($x, $type) {
		if ( function_exists( 'filter_var' ) ) {
			switch ( $type ) {
				case self::VALIDATE_NUM :
					return filter_var( $x, FILTER_VALIDATE_INT );
				case self::VALIDATE_ALPHANUM :
					return filter_var( $x, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[a-zA-Z0-9]+$/' ) ) );
				case self::VALIDATE_IP :
					return filter_var( $x, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
				case self::VALIDATE_NAME :
					return filter_var( $x, FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[a-zA-Z0-9_\$]+$/' ) ) );
			}
		} else {
			switch ( $type ) {
				case self::VALIDATE_NUM :
					return ( is_numeric( $x ) ? $x : false );
				case self::VALIDATE_ALPHANUM :
					return ( preg_match( '/^[a-zA-Z0-9]+$/', $x ) ? $x : false );
				case self::VALIDATE_IP :
					return ( ip2long( $x ) ? $x : false );
				case self::VALIDATE_NAME :
					return ( preg_match( '/^[a-zA-Z0-9_\$]+$/', $x ) ? $x : false );
			}
		}
	}

	/**
	 * cloudflare related actions
	 * @access private
	 * @since  0.3.6
	 * @return void
	 */
	private function cloudflare_init() {
		if ( ! isset($_SERVER['HTTP_CF_CONNECTING_IP']) ) {
			return ;
		}
		if ( isset( $_POST['vpb_nonce'] )
			&& wp_verify_nonce( $_POST['vpb_nonce'], self::NONCE )
		) {
			self::$settings['cloudflare_email'] = $_POST['vpb_cf_email'];
			self::$settings['cloudflare_token'] = $_POST['vpb_cf_token'];
			$this->update_option( 'settings', self::$settings );
			echo "<div class='updated'><p>Backup by VOGAPress information saved.</p></div>";
		}

		require_once(dirname( __FILE__ ).DIRECTORY_SEPARATOR.'cloudflareproxy.php');
		if ( ! CloudFlareProxy::get_api_keys() ) {
			echo '<div class="update-nag"><p>Backup by VOGAPRess requires CloudFlare account information to configure the firewall settings. <a href="' . esc_url( network_admin_url( 'admin.php?page=backup-by-wordpress-settings' ) ) . '">Here</a></p></div>';
		} else {
			$whitelist = CloudFlareProxy::update_whitelist();
			if ( ! $whitelist ) {
				echo '<div class="error"><p>Backup by VOGAPRess failed to update CloudFlare firewall settings.</p></div>';
			} else {
				self::$settings['cloudflare_wl'] = $whitelist;
				$this->update_option( 'settings', self::$settings );
			}
		}
	}

	/**
	 * Get Allowed IPs
	 * @access private static
	 * @since  0.3.8
	 * @return Array
	 */
	private static function _get_white_ips()
	{
		$hosts = gethostbynamel( self::ALLOWEDDOMAIN );
		if ( $hosts ) {
			return array_unique( array_merge( $hosts, self::$white_ips ) ); }

		return self::$white_ips;
	}

	/**
	 * htaccess init
	 * @access private
	 * @since  0.3.8
	 * @return void
	 */
	private function _htaccess_init()
	{

		if ( ! function_exists( 'insert_with_markers' ) ) {
			include( ABSPATH . '/wp-admin/includes/misc.php' );
		}

		$rules = array();
		$rules[] = '<ifmodule mod_security.c>';
		foreach ( self::_get_white_ips() as $ip ) {
			$rules[] = sprintf( 'SetEnvIfNoCase REMOTE_ADDR ^%s$ MODSEC_ENABLE=Off', str_replace( '.','\.',$ip ) );
		}
		$rules[] = '</ifmodule>';
		$htaccess_file = ABSPATH.'.htaccess';
		return insert_with_markers( $htaccess_file, 'Backup by VOGA Press', (array) $rules );
	}

	/**
	 * refresh
	 * @access public
	 * @since  0.3.8
	 * @return void
	 */
	public function refresh()
	{
		if ( $this->verify_ip() ) {
			$this->_htaccess_init();
			echo '1';
			wp_die();
		}
	}

	/**
	 * detect ip field
	 * @access private
	 * @since  0.4.4
	 * @return array
	 */
	private function _detect_ip_field() {
		$white_ips = $this->_get_white_ips();
		$headers = $_SERVER;
		$the_ip = '';
		$fields = array( 'HTTP_CLIENT_IP', 'X-Real-IP', 'X-Forwarded-For', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
		$ret = array();
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $headers ) && strpos( $headers[ $field ], ',' ) ) {
				// it's csv values
				$the_ip = array_unshift( array_map( 'trim',explode( ',', $headers[ $field ] ) ) );

			} else if ( array_key_exists( $field, $headers ) ) {
				$the_ip = $headers[ $field ];

			}
			if ( strlen( $the_ip ) ) {
				// remove port
				$the_ip = preg_replace( '#:.*$#','', $the_ip );
				if ( self::filter( $the_ip, self::VALIDATE_IP ) && in_array( $the_ip, $white_ips ) ) {
					array_push( $ret, $field );
				}
			}
			$the_ip = '';
		}
		return $ret;

	}
	/**
	 * install tables
	 * @access private
	 * @since  0.4.7
	 * @return void
	 */
	private function _install_tables()
	{
		global $wpdb;
		$table_name = self::TABLENAME ;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			text text NOT NULL,
			UNIQUE KEY id (id),
			INDEX name (name(255))
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// importing from existing
		$settings = VPBackup::get_option( 'settings' );
		if ( ! $settings ) {
			$settings = get_site_option( self::OPTNAME, array() );
			$wpdb->insert(
				self::TABLENAME,
				array(
					'name' => 'settings',
					'text' => json_encode( $settings ),
				)
			);
		}
	}
	/**
	 * update option
	 * @access private
	 * @since  0.4.7
	 * @return void
	 */
	private function update_option($name, $values)
	{
		global $wpdb;
		$wpdb->update(
			self::TABLENAME,
			array(
				'name' => $name,
				'text' => json_encode( $values ),
			),
			array(
				'name' => $name,
			)
		);
	}
	/**
	 * get option
	 * @access public
	 * @since  0.4.7
	 * @return void
	 */
	public static function get_option($name)
	{
		global $wpdb;
		$val = $wpdb->get_results($wpdb->prepare('select * from '.self::TABLENAME.' where name=%s',
		array( $name )), ARRAY_A);

		if ( empty( $val ) ) { return false ; }

		return json_decode( $val[0]['text'], true );
	}
}

new VPBackup();
