<?php

namespace VPBackup ;

if ( ! defined( 'ABSPATH' ) ) { exit;
}

use Exception;

class Timeout {
	static public $startTime;
	static public $changeable = false;
	static public $maxTime = 0;
	static public function init() {
		if ( isset( $_REQUEST['limit'] ) ) {
			self::$changeable = false ;
			self::$maxTime = intval( ini_get( 'max_execution_time' ) );
			self::$maxTime = max( 5, ( self::$maxTime ? self::$maxTime : 30 ) - intval( $_REQUEST['limit'] ) );
		} else {
			self::$maxTime = intval( ini_get( 'max_execution_time' ) );
			self::$maxTime = ( self::$maxTime ? self::$maxTime : 30 );
			set_time_limit( self::$maxTime + 60 );
			self::$changeable = (self::$maxTime + 60) == intval( ini_get( 'max_execution_time' ) );
			if ( self::$changeable ) { self::$maxTime += 60; }
		}
		self::$startTime = (time() - $_SERVER['REQUEST_TIME']) + self::get_time();
	}
	static public function get_time() {
		$rusage = getrusage();
		return $rusage['ru_utime.tv_sec'];
	}
	static public function time_lapsed() {
		return self::get_time() - self::$startTime;
	}
	static public function near_limit( $threshold = 5 ) {
		// 5 seconds for margin of rounding errors
		return (self::$maxTime - $threshold <= self::time_lapsed());
	}
	static public function extend_time() {
		set_time_limit( 60 );
		self::$maxTime += 60;
	}
	static public function timeout($threshold = 5) {
		if ( self::near_limit( $threshold ) ) {
			if ( self::$changeable ) {
				self::extend_time();
				return false;
			} else {
				return true;
			}
		}
		return false;
	}
	static public function filename_escape($name) {
		return preg_replace( '/[^A-Za-z0-9_\-]/', '_', $name );
	}
	static public function get_tmp_name($name) {
		return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::filename_escape( $name );
	}
	static public function store($id, $data) {
		$handle = fopen( self::get_tmp_name( 'vpb-state-'.$id ), 'w' );
		fwrite( $handle, json_encode( $data ) );
		fclose( $handle );
	}
	static public function retrieve($id) {
		$filename = self::get_tmp_name( 'vpb-state-'.$id );
		if ( is_readable( $filename ) ) {
			$handle = fopen( $filename, 'r' );
			$x = json_decode( stream_get_contents( $handle ),true );
			fclose( $handle );
			return $x;
		}
		return false;
	}
	static public function cleanup($id) {
		$filename = self::get_tmp_name( 'vpb-state-'.$id );
		if ( file_exists( $filename ) ) {
			unlink( $filename );
		}
	}
}
