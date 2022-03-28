<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://twitter.com/xrpfactchecker
 * @since      1.0.0
 *
 * @package    Xummlogin
 * @subpackage Xummlogin/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Xummlogin
 * @subpackage Xummlogin/includes
 * @author     XRP Fact Checker <xrpfactchecker@gmail.com>
 */

class Xummlogin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Xummlogin_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'XUMMLOGIN_VERSION' ) ) {
			$this->version = XUMMLOGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'xummlogin';

		// Make sure this plugin is loaded first to avoid issue when other plugin uses it
		add_action( 'activated_plugin', [$this, 'xummlogin_prioritize_loading'] );

		// Get the options for the plugin
		$this->option_replace_form = get_option('xummlogin_replace_form');

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_xumm_hooks();
		$this->define_xumm_shortcodes();
	}

	public function xummlogin_prioritize_loading(){
		$path = 'xummlogin/xummlogin.php';
		if ( $plugins = get_option( 'active_plugins' ) ) {
			if ( $key = array_search( $path, $plugins ) ) {
				array_splice( $plugins, $key, 1 );
				array_unshift( $plugins, $path );
				update_option( 'active_plugins', $plugins );
			}
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Xummlogin_Loader. Orchestrates the hooks of the plugin.
	 * - Xummlogin_i18n. Defines internationalization functionality.
	 * - Xummlogin_Admin. Defines all hooks for the admin area.
	 * - Xummlogin_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xummlogin-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xummlogin-i18n.php';

		/**
		 * The class for various tools used by different classes of the plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xummlogin-utils.php';

		/**
		 * The class for error/success messages of the plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xummlogin-messaging.php';

		/**
		 * The class for submitting and receiving request to the 
		 * XUMM API
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xummlogin-xumm.php';

		/**
		 * The class for defining the various short codes of the plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xummlogin-shortcodes.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-xummlogin-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-xummlogin-public.php';

		$this->loader = new Xummlogin_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Xummlogin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Xummlogin_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Xummlogin_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Xummlogin_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'login_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'login_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the XUMM API and AJAX functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_xumm_hooks() {

		$plugin_xumm = new Xummlogin_XUMM();

		$this->loader->add_filter('get_avatar', $plugin_xumm, 'xummlogin_custom_avatar', 1 , 5);

		$this->loader->add_filter('login_body_class', $plugin_xumm, 'xummlogin_show_form', 1 , 5);
		$this->loader->add_action('login_form', $plugin_xumm, 'xummlogin_add_form_login');		

		$this->loader->add_action('init', $plugin_xumm, 'xummlogin_check_action');
	}

	/**
	 * Register all of the shortcodes related to the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_xumm_shortcodes() {

		$plugin_xumm = new Xummlogin_ShortCodes( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Xummlogin_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
