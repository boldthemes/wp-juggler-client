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

class WPJC_Plugin_Updater {

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

	$this->cache_key     = 'wpjs_plugin_updater';
	$this->cache_allowed = $cache_allowed;

}

public function request(){

	$remote = get_transient( $this->cache_key );

	if( false === $remote || ! $this->cache_allowed ) {

	// TODO ++ dovuci sve sto im treba za sve pluginove

		$wpjc_server_url = get_option('wpjc_server_url');
		if ($wpjc_server_url){
			$endpoint_url = untrailingslashit($wpjc_server_url) . '/wpjs-plugins/';

			$remote = wp_remote_get( $endpoint_url, [
					'timeout' => 10,
					'headers' => [
						'Accept' => 'application/json'
					]
				]
			);

			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}

			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );
		}

	}

	$remote = json_decode( wp_remote_retrieve_body( $remote ) );

	return $remote;

}

function info( $response, $action, $args ) {

	// do nothing if you're not getting plugin information right now
	if ( 'plugin_information' !== $action ) {
		return $response;
	}

	// TODO ++ovde treba proveriti sve pluginove u nasoj listi... 

	if ( empty( $args->slug ) ) {
		return $response;
	}

	$remote = $this->request();

	if ( ! $remote ) {
		return $response;
	}

	if ( !property_exists( $remote, $args->slug ) ){
		return $response;
	};

	$response = new \stdClass();

	$plugin_slug = $args->slug;

	$response->name           = $remote->$plugin_slug->name;
	$response->slug           = $remote->$plugin_slug->slug;
	$response->version        = $remote->$plugin_slug->version;
	$response->tested         = $remote->$plugin_slug->tested;
	$response->requires       = $remote->$plugin_slug->requires;
	$response->author         = $remote->$plugin_slug->author;
	$response->author_profile = $remote->$plugin_slug->author_profile;
	$response->donate_link    = $remote->$plugin_slug->donate_link;
	$response->homepage       = $remote->$plugin_slug->homepage;
	$response->download_link  = $remote->$plugin_slug->download_url;
	$response->trunk          = $remote->$plugin_slug->download_url;
	$response->requires_php   = $remote->$plugin_slug->requires_php;
	$response->last_updated   = $remote->$plugin_slug->last_updated;

	$response->sections = [
		'description'  => $remote->$plugin_slug->sections->description,
		'installation' => $remote->$plugin_slug->sections->installation,
		'changelog'    => $remote->$plugin_slug->sections->changelog
	];

	if ( ! empty( $remote->banners ) ) {
		$response->banners = [
			'low'  => $remote->$plugin_slug->banners->low,
			'high' => $remote->$plugin_slug->banners->high
		];
	}

	return $response;

}

public function update( $transient ) {

	if ( empty($transient->checked ) ) {
		return $transient;
	}

	$remote = $this->request();

	if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

	if(! $remote ){
		return $transient;
	}

	$all_plugins = get_plugins();
    
    $plugin_slugs = array();
    
    foreach ( $all_plugins as $plugin_file => $plugin_data ) {
        
        $slug = $this->get_plugin_name( $plugin_file ) ;
        
		if ( property_exists( $remote, $slug) ){

			if ( $remote->$slug && version_compare( $plugin_data['Version'], $remote->$slug->version, '<' ) && version_compare( $remote->$slug->requires, get_bloginfo( 'version' ), '<=' ) && version_compare( $remote->$slug->requires_php, PHP_VERSION, '<' ) ) {
				$response              = new \stdClass();
				$response->slug        = $slug;
				$response->plugin      = $plugin_file;
				$response->new_version = $remote->$slug->version;
				$response->tested      = $remote->$slug->tested;
				$response->package     = $remote->$slug->download_url;
				$response->url     	   = $remote->$slug->download_url;
				$transient->response[ $response->plugin ] = $response;
		
			}
		};

    }

	// TODO ++ovde treba odraditi za svaki plugin koji je u nasoj listi i koji je instaliran

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

static function clear_wpjs_plugin_cache(){
	delete_transient( 'wpjs_plugin_updater' );
}

public function bypass_verification_for_updater( $args, $url ) {

	$wpjc_server_url = get_option('wpjc_server_url');

	if ( strpos($url, untrailingslashit($wpjc_server_url)) !==false  ){
		$args['reject_unsafe_urls'] = false;
		$args['sslverify'] = false;
	}

    return $args;
}

}