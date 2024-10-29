<?php

namespace VPBackup ;

if ( ! defined( 'ABSPATH' ) ) { exit;
}

class VPBFiles
{
	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;
	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;
	/**
	 * basePath
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $basePath;
	/**
	 * fileHandle
	 * @var     int
	 * @access  private
	 * @since   1.0.0
	 */
	private $fileHandle;
	/**
	 * File Mode Constants
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	const S_IFIFO  = 0010000  ;/* named pipe (fifo) */
	const S_IFCHR  = 0020000  ;/* character special */
	const S_IFDIR  = 0040000  ;/* directory */
	const S_IFBLK  = 0060000  ;/* block special */
	const S_IFREG  = 0100000  ;/* regular */
	const S_IFLNK  = 0120000  ;/* symbolic link */
	const S_IFSOCK = 0140000  ;/* socket */
	const S_IFWHT  = 0160000  ;/* whiteout */

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $version = '1.0.0' )
	{
		$this->_version = $version;
		$this->_token = 'vbp-files';
	} // End __construct ()

	private function get_absolute_path ($path, $parent = ABSPATH)
	{
		if ( DIRECTORY_SEPARATOR !== substr( $path,0,1 ) ) {
			$path = path_join( $parent, $path );
		}
		$path = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path );
		$parts = array_filter( explode( DIRECTORY_SEPARATOR, $path ), 'strlen' );
		$absolutes = array();
		foreach ( $parts as $part ) {
			if ( '.' == $part ) { continue;
			}
			if ( '..' == $part ) {
				array_pop( $absolutes );
			} else {
				$absolutes[] = $part;
			}
		}
		return DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $absolutes );
	}

	private function mkdir ($dir)
	{
		if ( ! is_dir( $dir ) ) {
			for ( $parent = dirname( $dir );
			$parent != ABSPATH && ! is_dir( $parent );
			$parent = dirname( $parent ) ) {
			}

			if ( $stat = stat( $parent ) ) {
				$dir_perms = $stat['mode'] & 0007777;
			} else {
				$dir_perms = 0777;
			}
				mkdir( $dir, $dir_perms, true );
		}
	}

	public function get_remote_file( $src, $dst )
	{
		$resp = wp_remote_get(
			$src, array( 'filename' => $dst )
		);
		if ( is_wp_error( $resp ) || $resp->statusCode != 200 ) {
			return false;
		}
		return true;
	}

	public function upload ($stats)
	{
		set_time_limit( 0 );
		$path = $this->get_absolute_path( $stats['path'] );

		if ( ! empty($stats['url']) ) {
			$tmpPath = Timeout::get_tmp_name( $stats['path'] );

			if ( $stats['start'] ) {
				$get_params = array(
					'stream'   => true,
					'filename' => $tmpPath,
				);
				if ( isset( $stats['settings_retrieve'] ) ) {
					$get_params['body'] = array(
						'DB_NAME' => DB_NAME,
						'DB_USER' => DB_USER,
						'DB_PASSWORD' 	=> DB_PASSWORD,
						'DB_HOST' 	=> DB_HOST,
						'DB_CHARSET' 	=> DB_CHARSET,
						'DB_COLLATE' 	=> DB_COLLATE,
					);
				}
				$resp = wp_remote_get( $stats['url'], $get_params );
				if ( is_wp_error( $resp ) ) {
					return -1;
				} else if ( 200 != $resp['response']['code'] ) {
					return -1;
				}
				return 2;
			} else {
				$lstats = lstat( $tmpPath );
				$md5 = md5_file( $tmpPath );
				// check if the uploaded file is correct
				if ( $lstats['size'] != $stats['size'] || $md5 != $stats['md5'] ) {
					unlink( $tmpPath );
					return -1;
				}
				if ( ! copy( $tmpPath, $path ) ) {
					unlink( $tmpPath );
					return -2;
				}
				chmod( $path, $stats['mode'] & 0777 );
				touch( $path, $stats['mtime'] );
				unlink( $tmpPath );
			}
			return 1;

		} else if ( ( $stats['mode'] & self::S_IFLNK ) == self::S_IFLNK ) {
			// php sometimes are confused with existing file structure
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
			chdir( dirname( $path ) );
			$link_path = $stats['link']['linkPath'];
			// Windows OS requires absolute path
			if ( 0 == strncasecmp( PHP_OS, 'WIN', 3 ) ) {
				$link_path = $this->get_absolute_path( $link_path, $path );
			}

			if ( symlink( $link_path, $path ) ) {
				chmod( $path, $stats['mode'] & 0777 );
				touch( $path, $stats['mtime'] );
				return true;
			} else {
				clearstatcache();
				unlink( $path );
				clearstatcache( true, $path );
			}
			return false;

		} else if ( ( $stats['mode'] & self::S_IFDIR ) == self::S_IFDIR ) {
			if ( ! file_exists( $path ) ) {
				mkdir( $path, $stats['mode'] & 0777, true );
				touch( $path, $stats['mtime'] );
			}
			return true;

		}
		return false;
	}

	public function download ($file)
	{
		set_time_limit( 0 );
		$fileName = $this->get_absolute_path( $file );
		// validate file is within the wordpress directory
		$realPath = realpath( $fileName );

		if ( empty($file) || empty($realPath) || ! is_readable( $realPath ) ) {
			header( 'HTTP/1.0 404 Not Found' );
			return false ;
		}
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename='.basename( $file ) );
		header( 'Expires: 0' );
		header( 'Pragma: public' );
		readfile( $fileName );
		return true;
	}
	public function download_curl ($file)
	{
		set_time_limit( 0 );
		$fileName = $this->get_absolute_path( $file );
		$realPath = realpath( $fileName );

		if ( empty($file) || empty($realPath) || ! is_readable( $realPath ) ) {
			header( 'HTTP/1.0 404 Not Found' );
			return false ;
		}
		if ( function_exists( 'curl_file_create' ) ) {
			$post = array( 'signature' => $_REQUEST['signature'], 'timestamp' => $_REQUEST['timestamp'], 'file_content' => curl_file_create( $realPath ) );
		} else {
			$post = array( 'signature' => $_REQUEST['signature'], 'timestamp' => $_REQUEST['timestamp'], 'file_content' => '@'.$realPath );
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL,\VPBackup\VPBackup::VPURL.'jobs/download/'.$_REQUEST['jobId'] );
		curl_setopt( $ch, CURLOPT_POST,1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $ch );
		curl_close( $ch );

		if ( false === $result || '1' !== $result ) {
			return false;
		}
		return true;
	}
	public function glob( $path, $filename = 'php://output' )
	{
		$this->basePath = $path;
		$this->fileHandle = fopen( $filename, ( $_REQUEST['start'] ? 'wb' : 'ab' ) );
		clearstatcache();
		$resume = ( $_REQUEST['start'] ? false : Timeout::retrieve( $_REQUEST['jobId'] ) );
		if ( ! $resume ) {
			fwrite( $this->fileHandle,'[' );
			$traveled = array( untrailingslashit( $path ) );
			$stack 	  = array( untrailingslashit( $path ) );
			$offset	  = -1;
		} else {
			$traveled = $resume['traveled'];
			$stack    = $resume['stack'];
			$offset	  = $resume['offset'];
		}
		while ( $p = array_shift( $stack ) ) {
			foreach ( scandir( $p ) as $pos => $file ) {
				if ( '.' == $file || '..' == $file || $pos <= $offset ) {
					continue;
				}

				$fullpath = $this->get_absolute_path( $file, $p );
				$stat = $this->_file_stat( $fullpath );

				if ( ( $stat['mode'] & self::S_IFDIR ) == self::S_IFDIR && ! in_array( $stat['path'], $traveled ) ) {
					array_push( $stack, $fullpath );

				} else if ( ( $stat['mode'] & self::S_IFLNK ) == self::S_IFLNK &&
					($stat['link']['mode'] & self::S_IFDIR ) == self::S_IFDIR ) {
					$link_fullpath = $this->get_absolute_path( $stat['link']['path'] );

					# do not walk thru if we have or will backup the directory
					$founded = false;
					foreach ( $traveled as $tv ) {
						if ( preg_match( '#^'.$tv.'#', $link_fullpath ) ) {
							$founded = true;
							break ;
						}
					}

					# sanity check to see if the link is self pointing
					if ( ! $founded && $fullpath != $link_fullpath ) {
						array_push( $stack, $fullpath );
						array_push( $traveled, $link_fullpath );
					}
					# not remembering every travelled path fully to save memory
				}
				if ( Timeout::timeout() ) {
					array_unshift( $stack, $p );
					Timeout::store($_REQUEST['jobId'], array(
						'traveled' 	=> $traveled,
						'stack'		=> $stack,
						'offset'	=> $pos,
					));
					fclose( $this->fileHandle );
					return false;
				}
			}
			$offset = -1;
		}
		fwrite( $this->fileHandle,']' );
		Timeout::cleanup( $_REQUEST['jobId'] );
		fclose( $this->fileHandle );
		return true;
	}
	private function _file_stat( $path, $echo = true )
	{
		$statKeys = array( 'ino', 'uid', 'mode', 'gid', 'size', 'mtime' );
		$stats = lstat( $path );
		if ( $stats ) {
			$stats = array_intersect_key( $stats, array_flip( $statKeys ) );
		} else {
			$stats = array( 'mode' => 0 );
		}
		$stats['path'] = preg_replace( '#^'.$this->basePath.'#', '', $path );
		$stats['level'] = count( array_filter( explode( DIRECTORY_SEPARATOR, $stats['path'] ) ) );
		$stats['readable'] = is_readable( $path );
		if ( ($stats['mode'] & VPBFiles::S_IFLNK) == VPBFiles::S_IFLNK ) {
			// handle self pointing link errors
			$link_fullpath = $this->get_absolute_path( readlink( $path ), dirname( $path ) );
			if ( $link_fullpath == $path ) {
				$stats['link'] = array( 'linkPath' => readlink( $path ) );
			} else {
				$stats['link'] = $this->_file_stat( $link_fullpath, false );
				$stats['link']['linkPath'] = readlink( $path );
			}
		} else if ( ($stats['mode'] & VPBFiles::S_IFSOCK) == VPBFiles::S_IFSOCK ) {
			// nothing special
		} else if ( ($stats['mode'] & VPBFiles::S_IFWHT) == VPBFiles::S_IFWHT ) {
			// nothing special
		} else if ( $stats['mode'] & VPBFiles::S_IFREG && $stats['readable'] ) {
			$stats['md5'] = md5_file( $path );
		}
		if ( $echo ) { fwrite( $this->fileHandle, json_encode( $stats ).',' );
		}
		return $stats ;
	}
}
