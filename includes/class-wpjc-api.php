<?php

use Tmeister\Firebase\JWT\JWT;
use Tmeister\Firebase\JWT\Key;

/**
 * API-specific functionality for the plugin.
 *
 * @link       https://wpjuggler.com
 * @since      1.0.0
 *
 * @package    WP_Juggler_Server
 * @subpackage WP_Juggler_Server/includes
 */

// Prevent direct access.
if (! defined('WPJC_PATH')) exit;

class WPJC_Api
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

	public function api_validate_api_key()
	{
		$auth_header = !empty($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']) : false;
		/* Double check for different auth header string (server dependent) */
		if (!$auth_header) {
			$auth_header = !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? sanitize_text_field($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) : false;
		}

		if (!$auth_header) {
			return false;
		}

		/**
		 * Check if the auth header is not bearer, if so, return the user
		 */
		if (strpos($auth_header, 'Bearer') !== 0) {
			return false;
		}

		[$token] = sscanf($auth_header, 'Bearer %s');

		$api_key = get_option('wpjc_api_key') ? esc_attr(get_option('wpjc_api_key')) : '';

		if ($api_key && $api_key == $token) {
			return true;
		} else {
			return false;
		}
	}

	public function api_register_routes()
	{
		register_rest_route('juggler/v1', '/getPlugins/', array(
			'methods' => 'POST',
			'callback' => array($this, 'get_plugins'),
			'args' => array(),
			'permission_callback' => array($this, 'api_validate_api_key')
		));

		register_rest_route('juggler/v1', '/activatePlugin/', array(
			'methods' => 'POST',
			'callback' => array($this, 'activate_plugin'),
			'args' => array(),
			'permission_callback' => array($this, 'api_validate_api_key')
		));

		register_rest_route('juggler/v1', '/deactivatePlugin/', array(
			'methods' => 'POST',
			'callback' => array($this, 'deactivate_plugin'),
			'args' => array(),
			'permission_callback' => array($this, 'api_validate_api_key')
		));
	}

	public function test_connection(WP_REST_Request $request)
	{

		$parameters = json_decode($request->get_body(), true);

		if (array_key_exists('pluginSlug', $parameters) && array_key_exists('pluginVersion', $parameters)) {

			$plugin_slug = sanitize_text_field($parameters['pluginSlug']);
			$plugin_version = sanitize_text_field($parameters['pluginVersion']);

			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			include_once(ABSPATH . 'wp-admin/includes/file.php');
			include_once(ABSPATH . 'wp-admin/includes/misc.php');
			include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
			include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

			$installed_plugins = get_plugins();

			$data = array();

			foreach ($installed_plugins as $plugin_path => $plugin_info) {
				$data[$plugin_path] = array(
					'Name' => $plugin_info['Name'],
					'Version' => $plugin_info['Version'],
				);
				/* if (strpos($plugin_path, $plugin_slug . '/') === 0) {
					$plugin_file = $plugin_path;
					break;
				} */
			}

			wp_send_json_success($data, 200);
		} else {
			wp_send_json_error(new WP_Error('Missing param', 'Either domain or title or uid missing'), 400);
		}
	}

	public function activate_plugin(WP_REST_Request $request)
	{
		$parameters = json_decode($request->get_body(), true);

		if (array_key_exists('pluginSlug', $parameters)) {

			if (! function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_slug = sanitize_text_field($parameters['pluginSlug']);

			$installed_plugins = get_plugins();
			$plugin_file = '';

			foreach ($installed_plugins as $plugin_path => $plugin_info) {
				if (strpos($plugin_path, $plugin_slug . '/') === 0) {
					$plugin_file = $plugin_path;
					break;
				}
			}

			if (!$plugin_file) {
				return;
			}

			if (!is_plugin_active($plugin_file)) {
				try {
					$result = activate_plugin($plugin_file);
					if (is_wp_error($result)) {
						wp_send_json_error($result, 500);
						return;
					}
				} catch (Exception $ex) {
					wp_send_json_error( new WP_Error('activation_failed', __('Failed to activate the plugin.'), array('status' => 500)), 500 );
					return;
				}
			}
			$data = array();
			wp_send_json_success($data, 200);
		} else {
			wp_send_json_error(new WP_Error('Missing param', 'Plugin slug is missing'), 400);
		}
	}

	public function deactivate_plugin(WP_REST_Request $request)
	{
		$parameters = json_decode($request->get_body(), true);

		if (array_key_exists('pluginSlug', $parameters)) {

			if (! function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_slug = sanitize_text_field($parameters['pluginSlug']);

			$installed_plugins = get_plugins();
			$plugin_file = '';

			foreach ($installed_plugins as $plugin_path => $plugin_info) {
				if (strpos($plugin_path, $plugin_slug . '/') === 0) {
					$plugin_file = $plugin_path;
					break;
				}
			}

			if (!$plugin_file) {
				return;
			}

			if (is_plugin_active($plugin_file)) {
				try {
					$result = deactivate_plugins($plugin_file);
					if (is_wp_error($result)) {
						wp_send_json_error($result, 500);
						return;
					}
				} catch (Exception $ex) {
					wp_send_json_error( new WP_Error('deactivation_failed', __('Failed to deactivate the plugin.'), array('status' => 500)), 500 );
					return;
				}
			}
			$data = array();
			wp_send_json_success($data, 200);
		} else {
			wp_send_json_error(new WP_Error('Missing param', 'Plugin slug is missing'), 400);
		}
	}

	public function get_plugins(WP_REST_Request $request)
	{

		if (! function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();

		$data = array();

		foreach ($installed_plugins as $plugin_path => $plugin_info) {
			$data[$plugin_path] = array(
				'Name' => $plugin_info['Name'],
				'Version' => $plugin_info['Version'],
				'Active' => is_plugin_active($plugin_path)
			);
		}

		wp_send_json_success($data, 200);
	}
}
