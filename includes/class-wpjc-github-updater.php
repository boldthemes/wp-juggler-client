<?php

/**
 * AJAX-specific functionality for the plugin.
 *
 * @link       https://wpjuggler.com
 * @since      1.0.0
 *
 * @package    WP_Juggler_Server
 * @subpackage WP_Juggler_Server/includes
 */

// Prevent direct access.
if (! defined('WPJC_PATH')) exit;

class WPJC_Github_Updater {

private $wp_juggler_client;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
private $version;
private $plugin_name;
public $cache_key;
public $cache_allowed;

public function __construct($wp_juggler_client, $version, $cache_allowed=true )
{
	
	$this->wp_juggler_client = $wp_juggler_client;
	$this->version = $version;
	$this->plugin_name = 'wpjc';

	$this->cache_key     = 'wpjs_github_updater';
	$this->cache_allowed = $cache_allowed;

}

public function get_github_update_info() {
    
	$remote_info = get_transient($this->cache_key);

    if (false === $remote_info) {
        $response = wp_remote_get('https://api.github.com/repos/rmackovic/wp-juggler-client/releases/latest');
        
		if (!is_wp_error($response) && $response['response']['code'] == 200) {
            $remote_info = json_decode($response['body']);
            
            // Look for the asset that is a ZIP file
            if (!empty($remote_info->assets)) {
                foreach ($remote_info->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $remote_info->download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            set_transient( $this->cache_key , $remote_info, 6 * HOUR_IN_SECONDS);
        }
    }

    return $remote_info;
}

public function github_info($response, $action, $args) {
	if ( 'plugin_information' !== $action ) {
		return $response;
	}

    $plugin_slug = plugin_basename(__FILE__);
    $update_info = $this->get_github_update_info();

    if (isset( $response->slug ) && $response->slug === $plugin_slug) {
        $response->new_version = $update_info->tag_name;
        $response->package = $update_info->download_url;
        $response->slug = $plugin_slug;
    }

    return $response;
}

public function github_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

	//$plugin_file_path = plugin_basename(__FILE__);

	$plugin_file_path = WP_PLUGIN_DIR . '/wp-juggler-client/wp-juggler-client.php';

	//$plugin_main_file = dirname(__FILE__) . 'wp-juggler-client/wp-juggler-client.php';
	//$plugin_relative_path = plugin_basename($plugin_main_file);

	/* if (file_exists($plugin_file_path)) {
    	$plugin_data = get_plugin_data($plugin_file_path);
	} else {
		return $transient;
	} */

	$plugin_data = get_plugin_data($plugin_file_path);

    $plugin_slug = 'wp-juggler-client/wp-juggler-client.php';
    $plugin_current_version = $plugin_data['Version'];
    $update_info = $this->get_github_update_info();

    if ($update_info && version_compare($plugin_current_version, $update_info->tag_name, '<')) {
        $package = $update_info->download_url;

        $obj = new stdClass();
        $obj->slug = $plugin_slug;
        $obj->new_version = $update_info->tag_name;
        $obj->url = $package;
        $obj->package = $package;

        $transient->response[$plugin_slug] = $obj;
    }

    return $transient;
}

public function purge( $upgrader, $options ) {

	if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options[ 'type' ] ) {
		// just clean the cache when new plugin version is installed
		delete_transient( $this->cache_key );
	}

}

private function get_plugin_name($basename)
{
	if (false === strpos($basename, '/')) {
		$name = basename($basename, '.php');
	} else {
		$name = dirname($basename);
	}

	return $name;
}

}
