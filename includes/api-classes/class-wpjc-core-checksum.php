<?php

if (! defined('WPJC_PATH')) exit;

require_once WPJC_PATH . 'includes/api-classes/class-wpjc-wp-org-api.php';

class WPJCCoreChecksum {

    // https://github.com/wp-cli/checksum-command/blob/main/src/Checksum_Core_Command.php

    private $include_root = false;
    
    private $exclude_files = [];
    
    public static function normalize_directory_separators( $path ) {
		return str_replace( '\\', '/', $path );
	}

    public function get_core_checksum(){
        $wp_version = '';
		$locale     = '';

        $this->include_root = true;
        
        if ( empty( $wp_version ) ) {
			$details    = self::get_wp_details();
			$wp_version = $details['wp_version'];

			if ( empty( $locale ) ) {
				$locale = $details['wp_local_package'];
			}
		}

        try {
			$checksums = $this->get_core_checksum_transient($wp_version, empty( $locale ) ? 'en_US' : $locale );
		} catch ( Exception $exception ) {
			return false;
		}

        if ( ! is_array( $checksums ) ) {
			return false;
		}

        $has_errors = false;
		$error_files = [];
		foreach ( $checksums as $file => $checksum ) {
			// Skip files which get updated
			if ( 'wp-content' === substr( $file, 0, 10 ) ) {
				continue;
			}

			if ( in_array( $file, $this->exclude_files, true ) ) {
				continue;
			}

			if ( ! file_exists( ABSPATH . $file ) ) {
				//WP_CLI::warning( "File doesn't exist: {$file}" );
				$has_errors = true;
				continue;
			}

			$md5_file = md5_file( ABSPATH . $file );
			if ( $md5_file !== $checksum ) {
				//WP_CLI::warning( "File doesn't verify against checksum: {$file}" );
				$error_files[] = $file;
				$has_errors = true;
			}
		}

        $core_checksums_files = array_filter( array_keys( $checksums ), [ $this, 'filter_file' ] );
		$core_files           = $this->get_files( ABSPATH );
		$additional_files     = array_diff( $core_files, $core_checksums_files );

        if ( ! empty( $additional_files ) ) {
			foreach ( $additional_files as $additional_file ) {
				if ( in_array( $additional_file, $this->exclude_files, true ) ) {
					continue;
				}
			}
		}

        return [
            'errors' => $has_errors,
            'additional' => array_values((array) $additional_files),
			'error_files' => $error_files
        ];

	

		if ( ! $has_errors ) {
			// WP_CLI::success( 'WordPress installation verifies against checksums.' );
		} else {
			// WP_CLI::error( "WordPress installation doesn't verify against checksums." );
		}

    }

	private function get_core_checksum_transient($wp_version, $locale)
	{
		$cache_key = 'checksum_core/' . $wp_version . '/' . $locale;

		$checksum = get_transient($cache_key);

		if ( false === $checksum || '' == $checksum ) {

			$wp_org_api = new WPJCWpOrgApi();

			try {
				$checksum = $wp_org_api->get_core_checksums( $wp_version, $locale );
				set_transient($cache_key, $checksum, 5 * DAY_IN_SECONDS);

			} catch (Exception $exception) {
				$checksum = false;
			}
		}

		if($checksum == ''){
			$checksum = false;
		}

		return $checksum;
	}

    protected function get_files( $path ) {
		$filtered_files = array();
		try {
			$files = new RecursiveIteratorIterator(
				new RecursiveCallbackFilterIterator(
					new RecursiveDirectoryIterator(
						$path,
						RecursiveDirectoryIterator::SKIP_DOTS
					),
					function ( $current ) use ( $path ) {
						return $this->filter_file( self::normalize_directory_separators( substr( $current->getPathname(), strlen( $path ) ) ) );
					}
				),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $files as $file_info ) {
				if ( $file_info->isFile() ) {
					$filtered_files[] = self::normalize_directory_separators( substr( $file_info->getPathname(), strlen( $path ) ) );
				}
			}
		} catch ( Exception $e ) {
			// TODO Add error handling if necessary
		}

		return $filtered_files;
	}

    protected function filter_file( $filepath ) {
		if ( true === $this->include_root ) {
			return ( 1 !== preg_match( '/^(\.htaccess$|\.maintenance$|wp-config\.php$|wp-content\/)/', $filepath ) );
		}

		return ( 0 === strpos( $filepath, 'wp-admin/' )
			|| 0 === strpos( $filepath, 'wp-includes/' )
			|| 1 === preg_match( '/^wp-(?!config\.php)([^\/]*)$/', $filepath )
		);
	}

    private static function get_wp_details() {
		$versions_path = ABSPATH . 'wp-includes/version.php';

		if ( ! is_readable( $versions_path ) ) {
			/* WP_CLI::error(
				"This does not seem to be a WordPress install.\n" .
				'Pass --path=`path/to/wordpress` or run `wp core download`.'
			);*/
		}

		$version_content = file_get_contents( $versions_path, false, null, 6, 2048 );

		$vars   = [ 'wp_version', 'wp_db_version', 'tinymce_version', 'wp_local_package' ];
		$result = [];

		foreach ( $vars as $var_name ) {
			$result[ $var_name ] = self::find_var( $var_name, $version_content );
		}

		return $result;
	}

    private static function find_var( $var_name, $code ) {
		$start = strpos( $code, '$' . $var_name . ' = ' );

		if ( ! $start ) {
			return null;
		}

		$start = $start + strlen( $var_name ) + 3;
		$end   = strpos( $code, ';', $start );

		$value = substr( $code, $start, $end - $start );

		return trim( $value, "'" );
	}

}