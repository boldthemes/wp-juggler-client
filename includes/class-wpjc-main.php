<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0
 * @package    WP_Juggler_Client
 * @subpackage WP_Juggler_Client/includes
 */

// Prevent direct access.
if ( ! defined( 'WPJC_PATH' ) ) exit;

class WP_Juggler_Client {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      WPJC_Loader   $loader   Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.0
	 */
	public function __construct() {
		$this->plugin_name 	= 'wp-juggler-client';
		$this->version 		= WPJC_VERSION;
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once WPJC_PATH . 'includes/class-wpjc-loader.php';
		require_once WPJC_PATH . 'includes/class-wpjc-i18n.php';
		require_once WPJC_PATH . 'includes/class-wpjc-admin.php';
		require_once WPJC_PATH . 'includes/class-wpjc-ajax.php';
		
		$this->loader = new WPJC_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WPJC_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new WPJC_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		// Initialize the admin class.
		$plugin_admin  = new WPJC_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_ajax  = new WPJC_AJAX( $this->get_plugin_name(), $this->get_version() );
		
		/// Register the admin pages and scripts.
		
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_menu_page', 9 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_menu_page_end' );
		
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_plugin_assets' );
		
		$this->loader->add_action( 'wp_ajax_wpjc_get_dashboard', $plugin_ajax, 'ajax_get_dashboard' );

		$this->loader->add_action( 'wp_ajax_wpjc_get_settings', $plugin_ajax, 'ajax_get_settings' );
		$this->loader->add_action( 'wp_ajax_wpjc_save_settings', $plugin_ajax, 'ajax_save_settings' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0
	 * @return    WPJC    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
