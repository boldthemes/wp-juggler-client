<?php

use Tmeister\Firebase\JWT\JWT;
use Tmeister\Firebase\JWT\Key;

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

class WPJC_Server_Api
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $wp_juggler_client    The ID of this plugin.
	 */
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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $wp_juggler_server       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct($wp_juggler_client, $version)
	{
		$this->wp_juggler_client = $wp_juggler_client;
		$this->version = $version;
		$this->plugin_name = 'wpjc';
	}

	public static function activate_site(){

		$end_point = 'activateSite';
		$data = [
			'site_url' => get_site_url()
		];
		
		$response = WPJC_Server_Api::call_server_api( $end_point, $data );

		return $response;

	}

	private static function call_server_api( $endpoint, $data )
    {
		$api_key = get_option('wpjc_api_key');
		$site_url = get_option('wpjc_server_url');

		if (!$site_url || !$api_key){
			return false;
		}
        
		$url = rtrim($site_url, '/') . '/wp-json/juggler/v1/' . $endpoint;

        $response = wp_remote_post($url, array(
            'body'    => json_encode($data),
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-type' => 'application/json',
            ),
			// TODO SHOULD BE REMOVED FOR PRODUCTION !!!!!!!
			'sslverify'   => false
        ));

		$response_code = wp_remote_retrieve_response_code($response);

		if ($response_code != '200' && $response_code != '201') {

			$body = wp_remote_retrieve_body($response);

			$data = json_decode($body, true);

			if ( json_last_error() === JSON_ERROR_NONE ) {

				if (array_key_exists('success', $data) && !$data['success']) {
					$error_code = $data['data'][0]['code'];
					$error_msg = $data['data'][0]['message'];
				} else {
					$error_code = $data['code'];
					$error_msg = $data['message'];
				}
				$response = new WP_Error($error_code, $error_msg);
			} else {
				$response = new WP_Error('Error: ' . $response_code, 'Error retreiveng data');
			}
		}

		return $response;
    }

}
