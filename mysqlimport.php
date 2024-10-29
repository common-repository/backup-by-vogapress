<?php

namespace VPBackup ;

if ( ! defined( 'ABSPATH' ) ) { exit;
}

use Exception;

class Mysqlimport
{

	// Same as mysqldump
	const MAXLINESIZE = 1000000;

	// Available compression methods as constants
	const GZIP = 'Gzip';
	const BZIP2 = 'Bzip2';
	const NONE = 'None';

	// Available connection strings
	const UTF8 = 'utf8';
	const UTF8MB4 = 'utf8mb4';

	// This can be set both on constructor or manually
	private $host;
	private $db;
	private $fileName;
	private $tableName;

	private $fileHandler = null;

	public function __construct($db = DB_NAME, $host = DB_HOST, $type = 'mysql')
	{
		$this->db = $db;
		$this->host = $host;
		$this->dbType = strtolower( $type );
	}

	public function start($filename = '', $tablename = '')
	{
		// Output file can be redefined here
		if ( ! empty($filename) ) {
			$this->fileName = $filename;
		}
		// We must set a name to continue
		if ( empty($this->fileName) ) {
			throw new Exception( 'Input file name is not set' );
		}
		$this->fileHandler = fopen( $filename, 'rb' );
		if ( false === $this->fileHandler ) {
			throw new Exception( 'Input file is not readable' );
		}
		$this->tableName = $tablename;
		return $this->process();
	}

	private function process()
	{
		global $wpdb;
		$query = '';
		$offset = 0;
		$resume = ( $_REQUEST['start'] ? false : Timeout::retrieve( $_REQUEST['jobId'] ) );
		if ( $resume ) {
			fseek( $this->fileHandler, $resume['offset'] );
		}

		while ( ($line = fgets( $this->fileHandler, self::MAXLINESIZE )) ) {
			// do not lock table
			if ( '--' == substr( $line, 0, 2 ) || '' == trim( $line ) || 'LOCK' == substr( $line, 0, 4 ) || 'UNLOCK' == substr( $line, 0, 6 ) ) {
				continue;
			}

			$query .= $line;
			$lastChars = substr( rtrim( $query, " \t\0\x0B" ), -4 );
			if ( preg_match( "/; {0,1}\r{0,1}\n/", $lastChars ) ) {
				$updates = false;
				// MyISAM might not be ready immediately after table creation
				for ( $retries = 10; $retries > 0 && false === $updates; $retries -- ) {
					$updates = $wpdb->query( $query );
					if ( false === $updates && 0 === $retries ) {
						throw new Exception( 'Error performing query: ' . $wpdb->print_error() );
					}
				}
				$query = '';
				$offset = ftell( $this->fileHandler );
				if ( Timeout::timeout() ) {
					// timeout
					Timeout::store($_REQUEST['jobId'], array(
						'offset' => $offset,
					));
					break;
				}
			}
		}
		if ( ! $line ) {
			// unlock first, and relock when continuing
			$wpdb->query( 'UNLOCK TABLES;' );
			// complete
			Timeout::cleanup( $_REQUEST['jobId'] );
			return true;
		}
		return false;
	}
}
