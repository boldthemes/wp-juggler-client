<?php

/**
 * wp-admin specific functionality of the plugin.
 *
 * Registers styles and scripts, adds the custom administration page,
 * and processes user input on the "search/replace" form.
 *
 * @link       https://wpjuggler.com
 * @since      1.0.0
 *
 * @package    WP_Juggler_Client
 * @subpackage WP_Juggler_Client/includes
 */

// Prevent direct access.
if (! defined('WPJC_PATH')) exit;

class WPJC_Admin
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
	 * @var      string    $wp_juggler_client       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct($wp_juggler_client, $version)
	{
		$this->wp_juggler_client = $wp_juggler_client;
		$this->version = $version;
		$this->plugin_name = 'wpjc';
	}

	/**
	 * Register any CSS and JS used by the plugin.
	 * @since    1.0.0
	 * @access 	 public
	 * @param    string $hook Used for determining which page(s) to load our scripts.
	 */
	public function enqueue_plugin_assets($suffix)
	{
		if (str_ends_with($suffix, 'wpjc-dashboard')) {
			wp_enqueue_script(
				$this->plugin_name . '-dashboard',
				plugin_dir_url(__DIR__) . 'assets/dashboard/wpjc-dashboard.js',
				array('jquery'),
				'',
				[
					'in_footer' => true,
				]
			);

			wp_enqueue_style(
				$this->plugin_name . '-dashboard',
				plugin_dir_url(__DIR__) . 'assets/dashboard/wpjc-dashboard.css',
				[],
				''
			);

			$nonce = wp_create_nonce($this->plugin_name . '-dashboard');

			wp_localize_script(
				$this->plugin_name . '-dashboard',
				$this->plugin_name . '_dashboard_object',
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'nonce' => $nonce,
					'adminurl' => admin_url(),
				)
			);
		}

		if (str_ends_with($suffix, 'wpjc-settings')) {
			wp_enqueue_script(
				$this->plugin_name . '-settings',
				plugin_dir_url(__DIR__) . 'assets/settings/wpjc-settings.js',
				array('jquery'),
				'',
				[
					'in_footer' => true,
				]
			);

			wp_enqueue_style(
				$this->plugin_name . '-settings',
				plugin_dir_url(__DIR__) . 'assets/settings/wpjc-settings.css',
				[],
				''
			);

			$nonce = wp_create_nonce($this->plugin_name . '-settings');

			wp_localize_script(
				$this->plugin_name . '-settings',
				$this->plugin_name . '_settings_object',
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'nonce' => $nonce,
					'adminurl' => admin_url(),
				)
			);
		}
	}

	public function register_menu_page()
	{

		$cap = apply_filters('wpjc_capability', 'manage_options');

		add_menu_page(
			__('WP Juggler', 'wp-juggler-client'),
			__('WP Juggler', 'wp-juggler-client'),
			$cap,
			"wpjc-dashboard",
			[$this, 'render_admin_page'],
			"",
			30
		);


		add_submenu_page(
			'wpjc-dashboard',
			__('Dashboard', 'wp-juggler-client'),
			__('Dashboard', 'wp-juggler-client'),
			$cap,
			"wpjc-dashboard"
		);
	}

	public function register_menu_page_end()
	{

		$cap = apply_filters('wpjc_capability', 'manage_options');

		add_submenu_page(
			'wpjc-dashboard',
			__('Settings', 'wp-juggler-client'),
			__('Settings', 'wp-juggler-client'),
			$cap,
			'wpjc-settings',
			[$this, 'render_admin_page']
		);
	}

	public function render_user_meta($user)
	{

?>
		<h3><?php _e("WP Juggler Settings", "wp-juggler-client"); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="wpjuggler_auto_login"><?php _e("Auto Login", "wp-juggler-client"); ?></label></th>
				<td>
					<input type="checkbox" name="wpjuggler_auto_login" id="wpjuggler_auto_login" <?php checked(get_user_meta($user->ID, 'wpjuggler_auto_login', true), 'on'); ?> />
					<span class="description"><?php _e("Enable auto login for this user.", "wp-juggler-client"); ?></span>
				</td>
			</tr>
		</table>

	<?php

	}

	public function save_user_meta($user_id)
	{
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}
	
		update_user_meta($user_id, 'wpjuggler_auto_login', isset($_POST['wpjuggler_auto_login']) ? 'on' : 'off');
	}

	public function render_admin_page()
	{
	?>
		<div id="app"></div>
<?php
	}
}
