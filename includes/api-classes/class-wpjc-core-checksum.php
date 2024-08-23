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

        // get $wp_version
        // get $locale
        $this->include_root = true;
        
        //procitaj exclude files iz opcija - pitanje je samo klijenta ili servera
        //$this->exclude_files = explode( ',', Utils\get_flag_value( $assoc_args, 'exclude', '' ) );

        if ( empty( $wp_version ) ) {
			$details    = self::get_wp_details();
			$wp_version = $details['wp_version'];

			if ( empty( $locale ) ) {
				$locale = $details['wp_local_package'];
			}
		}

        //za wp_get, da li ovo uopste dozvoliti
        //$insecure   = (bool) Utils\get_flag_value( $assoc_args, 'insecure', false );

        //$wp_org_api = new WPJCWpOrgApi( [ 'insecure' => $insecure ] );
        $wp_org_api = new WPJCWpOrgApi();

        try {
			//$checksums = [];
			$checksums = $wp_org_api->get_core_checksums( $wp_version, empty( $locale ) ? 'en_US' : $locale );
		} catch ( Exception $exception ) {
			//WP_CLI::error( $exception );
		}

        if ( ! is_array( $checksums ) ) {
			//WP_CLI::error( "Couldn't get checksums from WordPress.org." );
		}

        $has_errors = false;
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
				//WP_CLI::warning( "File should not exist: {$additional_file}" );
			}
		}

        return [
            'errors' => $has_errors,
            'additional' => $additional_files
        ];

		if ( ! $has_errors ) {
			// WP_CLI::success( 'WordPress installation verifies against checksums.' );
		} else {
			// WP_CLI::error( "WordPress installation doesn't verify against checksums." );
		}

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