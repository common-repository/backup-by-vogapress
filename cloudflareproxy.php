<?php
namespace VPBackup ;

if ( ! defined( 'ABSPATH' ) ) { exit;
}

class CloudFlareProxy {

		static $ips_v4 = array( array( 0x6715f400, '-1024' ), array( 0x6716c800, '-1024' ), array( 0x671f0400, '-1024' ), array( 0x68100000, '-1048576' ), array( 0x6ca2c000, '-16384' ), array( 0x8d654000, '-16384' ), array( 0xa29e0000, '-131072' ), array( 0xac400000, '-524288' ), array( 0xadf53000, '-4096' ), array( 0xbc726000, '-4096' ), array( 0xbe5df000, '-4096' ), array( 0xc5eaf000, '-1024' ), array( 0xc6298000, '-32768' ), array( 0xc71b8000, '-2048' ), );
		static $ips_v6 = array( array( '2400cb00000000000000000000000000', '2400cb00ffffffffffffffffffffffff' ), array( '24058100000000000000000000000000', '24058100ffffffffffffffffffffffff' ), array( '2405b500000000000000000000000000', '2405b500ffffffffffffffffffffffff' ), array( '26064700000000000000000000000000', '26064700ffffffffffffffffffffffff' ), array( '2803f800000000000000000000000000', '2803f800ffffffffffffffffffffffff' ), );

		static function in_range_ipv4 ($ip) {
		$long = ip2long( $ip );
		foreach ( self::$ips_v4 as $cfip ) {
			if ( ( $long & $cfip[1] ) == $cfip[0] ) {
				return true; }
		}
				return false;
		}

		// convert ipv6 to base-16 number string for comparison
		static function get_ipv6_full($ip)
		{
			$filtered = explode( '/',$ip );
			$ip_blocks = explode( '::', $filtered[0], 2 );
			$left_ip_blocks = explode( ':',$ip_blocks[0] );
			$last_ip_block  = trim( $ip_blocks[1] );
			foreach ( $left_ip_blocks as $key => $val ) {
				$left_ip_blocks[ $key ] = strtolower( str_pad( $val, 4, '0', STR_PAD_LEFT ) );
			}
			$size = count( $left_ip_blocks );
			for ( $i = $size; $i < 7; $i++ ) {
				$left_ip_blocks[ $i ] = '0000';
			}
			$left_ip_blocks[7] = str_pad( $last_ip_block, 4, '0', STR_PAD_LEFT );
			return implode( '',$left_ip_blocks );
		}

		static function in_range_ipv6( $ip ) {
			$str = self::get_ipv6_full( $ip );
			foreach ( self::$ips_v6 as $item ) {
				if ( strcmp( $str,$item[0] ) >= 0 && strcmp( $str,$item[1] ) <= 0 ) {
					return true;
				}
			}
			return false;
		}

		static function in_range( $ip ) {
			if ( false === strpos( $ip, ':' ) ) {
				return self::in_range_ipv4( $ip );
			} else {
				return self::in_range_ipv6( $ip );
			}
		}

		static function get_api_keys() {
			if ( defined( 'CLOUDFLARE_VERSION' ) && get_option( 'cloudflare_api_key' ) ) {
				$token = get_option( 'cloudflare_api_key' );
				$email = get_option( 'cloudflare_api_email' );
			} else {
				$email = VPBackup::$settings['cloudflare_email'];
				$token = VPBackup::$settings['cloudflare_token'];
			}
			if ( $token && $email ) {
				return array( 'token' => $token, 'email' => $email ); }

			return false;
		}

		static function update_whitelist() {
			$hosts = gethostbynamel( VPBackup::ALLOWEDDOMAIN );
			$byg_backup = VPBackup::$settings['cloudflare_wl'];
			$keys = self::get_api_keys();

			if ( $byg_backup['cloudflare'] ) {
				$new_hosts = array_diff( $byg_backup['cloudflare_wl'], $hosts );
				$old_hosts = array_diff( $hosts, $byg_backup['cloudflare_wl'] );
			} else {
				$new_hosts = $hosts;
				$old_hosts = array();

			}
			if ( ! $keys ) {
				return false ;
			}
			foreach ( $old_hosts as $host ) {
				$res = wp_remote_post('https://www.cloudflare.com/api_json.html',array(
					'method' => 'POST',
					'body' => array(
						'a'	=> 'nul',
						'tkn'	=> $keys['token'],
						'email'	=> $keys['email'],
						'key'	=> $host,
					)
				));
				if ( is_wp_error( $res ) ) {
					return false ;
				}
			}
			foreach ( $new_hosts as $host ) {
				$res = wp_remote_post('https://www.cloudflare.com/api_json.html',array(
					'method' => 'POST',
					'body' => array(
						'a'	=> 'wl',
						'tkn'	=> $keys['token'],
						'email'	=> $keys['email'],
						'key'	=> $host,
					)
				));
				if ( is_wp_error( $res ) ) {
					return false ;
				}
			}
			return $hosts;
		}
}
