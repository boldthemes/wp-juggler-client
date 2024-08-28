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

class WPJC_Service
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

	private function get_algorithm()
	{
		$supported_algorithms = [
			'HS256',
			'HS384',
			'HS512',
			'RS256',
			'RS384',
			'RS512',
			'ES256',
			'ES384',
			'ES512',
			'PS256',
			'PS384',
			'PS512'
		];

		$algorithm = apply_filters('jwt_auth_algorithm', 'HS256');

		if (!in_array($algorithm, $supported_algorithms)) {
			return false;
		}

		return $algorithm;
	}
	
	public function wpjc_check_token()
	{

		function get_full_current_url() {
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			$full_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			return $full_url;
		}

		$current_url = get_full_current_url();

		$parsed_url = wp_parse_url( $current_url );

		// TODO - flow vezan za generisanje temp url-a

		if( $parsed_url['path'] != '/wpjs/' ){
			return;
		}

		$token = (isset($_GET['wpjs_token'])) ? sanitize_text_field($_GET['wpjs_token']) : false;

		if( !$token ){
			return;
		}

		$url_aim = (isset($_GET['wpjs_redirect'])) ? sanitize_text_field($_GET['wpjs_redirect']) : false;

		if( !$url_aim ){
			$dest_url = admin_url();
		} else {
			$dest_url = $url_aim;
		}

		if (is_user_logged_in()) {
			wp_redirect( $dest_url );
			exit;
		}

		$wpjc_user = $this->validate_wpjs_token($token);

		if (!$wpjc_user) {
			wp_redirect( home_url() );
			exit;
		}

		$user = get_user_by('login', $wpjc_user);

		if ( ! $user ) {
			wp_redirect( home_url() );
			exit;
		}

		$auto_login = get_user_meta($user->ID, 'wpjuggler_auto_login', true);

		if ($auto_login != 'on'){
			wp_redirect( home_url() );
			exit;
		}

		// Log the user in
		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID);
		do_action('wp_login', $user->login, $user);

		wp_redirect( $dest_url );
		exit;

	}

	private function validate_wpjs_token($token)
	{
		if (!$token) {
			return false;
		}

		$api_key = get_option('wpjc_api_key') ? esc_attr(get_option('wpjc_api_key')) : '';

		$algorithm = $this->get_algorithm();

		if ($api_key == '' || $algorithm === false) {
			return false;
		}

		try {
			WPJC\Firebase\JWT\JWT::$leeway = 60 * 10; // ten minutes
			$decoded_token = WPJC\Firebase\JWT\JWT::decode($token, new WPJC\Firebase\JWT\Key($api_key, $algorithm));
		} catch (Exception $e) {
			return false;
		}

		$wp_username = false;

		if (property_exists($decoded_token, 'wpjs_username')) {
			$wp_username = sanitize_text_field($decoded_token->wpjs_username);	
		} else {
			return false;
		}

		//Da li je istekao
		if (time() > intval($decoded_token->exp)) {
			return false;
		}

		// todo sta se desava ukoliko ga nemamo u bazi - treba poslati zahtev api-ju da proverimo usera i da nam on posalje zahtev da ga registruje i ako sve prodje kako treba ponovo ga validiramo

		return $wp_username;
	}
}
