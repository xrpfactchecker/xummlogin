<?php
// Load vendor plugins needed
require_once __DIR__ . '/../vendor/autoload.php';

// Setup namespaces
use WSSC\WebSocketClient;
use WSSC\Components\ClientConfig;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://twitter.com/xrpfactchecker
 * @since      1.0.0
 *
 * @package    Xummlogin
 * @subpackage Xummlogin/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Xummlogin
 * @subpackage Xummlogin/admin
 * @author     XRP Fact Checker <xrpfactchecker@gmail.com>
 */
class Xummlogin_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name    = $plugin_name;
		$this->version        = $version;

		// Add menu item to Setting menu
		add_action( 'admin_menu', array( $this, 'xummlogin_admin_menu' ) );

		// Init the setting
		add_action( 'admin_init', array( $this, 'xummlogin_display_api_fields' ) );
		add_action( 'admin_init', array( $this, 'xummlogin_display_project_fields' ) );
		add_action( 'admin_init', array( $this, 'xummlogin_display_richlist_fields' ) );
		add_action( 'admin_init', array( $this, 'xummlogin_display_username_fields' ) );		
		add_action( 'admin_init', array( $this, 'xummlogin_display_voting_fields' ) );
		add_action( 'admin_init', array( $this, 'xummlogin_display_voting_management' ) );

		// Hook to check when a new voting tx is saved to fetch the leger index
		add_action( 'update_option_xummlogin_voting_active_start', array( $this, 'xummlogin_saving_voting' ) , 10, 3);
		add_action( 'update_option_xummlogin_voting_active_end', array( $this, 'xummlogin_saving_voting' ) , 10, 3);

		// Add hooks to show/save the custom user fields
		add_action( 'show_user_profile', array( $this, 'xl_custom_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'xl_custom_user_profile_fields' ) );
		add_action( 'user_new_form', array( $this, 'xl_custom_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'xl_save_custom_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'xl_save_custom_user_profile_fields' ) );
		add_action( 'user_register', array( $this, 'xl_save_custom_user_profile_fields' ) );

		// Include the XRPL Address field in the user search
		add_action('pre_user_query', array( $this, 'xl_extend_user_search' ) );
	}

	public function xummlogin_admin_menu(){
    add_menu_page('XUMM Login', 'XUMM Login', 'manage_options', 'xumm-login', [$this, 'xummlogin_options'], 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgaWQ9InN2ZzEzOTMiCiAgIHZlcnNpb249IjEuMSIKICAgZmlsbD0ibm9uZSIKICAgaGVpZ2h0PSIxNzAiCiAgIHdpZHRoPSIzODQiPgogIDxtZXRhZGF0YQogICAgIGlkPSJtZXRhZGF0YTEzOTkiPgogICAgPHJkZjpSREY+CiAgICAgIDxjYzpXb3JrCiAgICAgICAgIHJkZjphYm91dD0iIj4KICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4KICAgICAgICA8ZGM6dHlwZQogICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+CiAgICAgICAgPGRjOnRpdGxlPjwvZGM6dGl0bGU+CiAgICAgIDwvY2M6V29yaz4KICAgIDwvcmRmOlJERj4KICA8L21ldGFkYXRhPgogIDxkZWZzCiAgICAgaWQ9ImRlZnMxMzk3IiAvPgogIDxwYXRoCiAgICAgc3R5bGU9ImZpbGw6IzAwMDAwMDtmaWxsLW9wYWNpdHk6MTtzdHJva2Utd2lkdGg6MDtzdHJva2UtbWl0ZXJsaW1pdDo0O3N0cm9rZS1kYXNoYXJyYXk6bm9uZSIKICAgICBpZD0icGF0aDEzOTEiCiAgICAgZmlsbD0iI2ZmZiIKICAgICBkPSJNMzc1LjkyNyA0OC44ODFjMTAuOTE3LTExLjg2NCAxMC4wMjQtMzAuMjE5LTEuOTkxLTQwLjk5Ny0xMi4wMTQtMTAuNzc5LTMwLjYwNC05LjktNDEuNTE3IDEuOTY1bC03OC4yODcgODUuMDgzLTMxLjY1LTMyLjgxNWMtMTEuMTk1LTExLjYwNy0yOS44LTEyLjA1NC00MS41NTUtMS0xMS43NTUgMTEuMDU0LTEyLjIwOSAyOS40MjUtMS4wMTQgNDEuMDMybDUzLjQ0MiA1NS40MDhjNS42MzEgNS44MzcgMTMuNDY2IDkuMSAyMS42MyA5LjAwNSA4LjE2My0uMDk1IDE1LjkxOS0zLjUzOCAyMS40MDktOS41MDVsOTkuNTMzLTEwOC4xNzZ6bS0yMjEuNTggNzIuODQ5bC0zNC4xLTMzLjk3NSAzNC4xLTMzLjk3NWM3LjM3My03LjI4IDExLjA2LTEzLjQ0OCAxMS4wNi0xOC41MDQgMC01LjI1OC0zLjY4Ny0xMS41MjctMTEuMDYtMTguODA3LTcuMzczLTcuMjgtMTMuNjE5LTEwLjkyLTE4Ljc0LTEwLjkyLTUuMTIgMC0xMS4zNjYgMy42NC0xOC43NCAxMC45Mkw4My4wNzQgNTAuNDQzbC0zNC4xLTMzLjk3NGMtNy4zNzItNy4yOC0xMy42Mi0xMC45Mi0xOC43NC0xMC45Mi00LjkxNSAwLTExLjE2MSAzLjY0LTE4Ljc0IDEwLjkyQzQuMTIzIDIzLjc0OS40MzYgMzAuMDE5LjQzNiAzNS4yNzZjMCA1LjA1NiAzLjY4NyAxMS4yMjQgMTEuMDYgMTguNTA0bDM0LjEgMzMuOTc1LTM0LjEgMzMuOTc1QzQuMTIyIDEyOS4wMS40MzUgMTM1LjI3OS40MzUgMTQwLjUzN2MwIDUuMDU2IDMuNjg3IDExLjIyNCAxMS4wNiAxOC41MDQgNy41NzggNy4wNzggMTMuODI0IDEwLjYxNyAxOC43NCAxMC42MTcgNS4xMiAwIDExLjM2Ny0zLjY0IDE4Ljc0LTEwLjkybDM0LjEtMzMuNjcxIDMzLjc5MiAzMy42NzFjNy4zNzQgNy4yOCAxMy42MiAxMC45MiAxOC43NCAxMC45MiA1LjEyMSAwIDExLjM2Ny0zLjUzOSAxOC43NC0xMC42MTcgNy4zNzMtNy4yOCAxMS4wNi0xMy40NDggMTEuMDYtMTguNTA0IDAtNS4yNTgtMy42ODctMTEuNTI3LTExLjA2LTE4LjgwN3oiCiAgICAgY2xpcC1ydWxlPSJldmVub2RkIgogICAgIGZpbGwtcnVsZT0iZXZlbm9kZCIgLz4KPC9zdmc+Cg==', 70);
    add_submenu_page('xumm-login', 'Settings', 'Settings', 'manage_options', 'xumm-login' );
    add_submenu_page('xumm-login', 'Voting', 'Voting Tool', 'manage_options', 'xumm-login-voting', [$this, 'xummlogin_votings'] );
    add_submenu_page('xumm-login', 'Short Codes', 'Short Codes', 'manage_options', 'xumm-login-shortcodes', [$this, 'xummlogin_shortcodes'] );
	}

	function xl_extend_user_search( $query ){
		// make sure that this code will be applied only for user search
		if ( $query->query_vars['search'] ){
			$search_query = trim( $query->query_vars['search'], '*' );
			if ( $_REQUEST['s'] == $search_query ){
				global $wpdb;

				// add search by xrpl address
				$query->query_from .= " JOIN {$wpdb->usermeta} xrpl_address ON xrpl_address.user_id = {$wpdb->users}.ID AND xrpl_address.meta_key = 'xrpl-r-address'";

				// what fields to include in the search
				$search_by = array( 'user_login', 'xrpl_address.meta_value' );

				// apply to the query
				$query->query_where = 'WHERE 1=1' . $query->get_search_sql( $search_query, $search_by, 'both' );
			}
		}
	}

	public function xl_save_custom_user_profile_fields( $user_id ){

		// Stop now if the logged is not admin
		if ( !current_user_can('administrator') ) {
			return;
		}

		// We're good save meta value
		update_user_meta( $user_id, 'xrpl-r-address', $_POST['xrpl-r-address'] );
	}

	public function xl_custom_user_profile_fields( $user ){

		// Normal users cannot edit their account address, only Admins
		$field_state = current_user_can('administrator') ? '' : 'disabled="disabled"';

		// Get the field's current value if any
		$field_value = ( isset($user->ID) && (int)$user->ID > 0 ) ? get_user_meta($user->ID, 'xrpl-r-address', true) : '';

		// Output field
		echo '<h3 class="heading">XRPL Account Address</h3>';
		echo '<table class="form-table"><tr>';
		echo '<th><label for="contact">r Address</label></th>';
		echo '<td>';
			echo '<input type="text"' . $field_state . ' class="regular-text code" value="' . esc_attr( $field_value ) . '" name="xrpl-r-address" id="xrpl-r-address" />';
			echo '<p class="description" id="xrpl-r-address-description">This address is used to associate this user with an XRPL account when login in with XUMM.</strong></p>';
			echo '</td>';
		echo '</tr></table>';
	}

	public function xummlogin_options() {

		// Make sure current user can manage options
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// We're good, show the goods
		require_once 'partials/'.$this->plugin_name.'-admin-display.php';
	}

	public function xummlogin_shortcodes() {

		// Make sure current user can manage options
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// We're good, show the goods
		require_once 'partials/'.$this->plugin_name.'-shortcodes-admin-display.php';
	}

	public function xummlogin_votings() {

		// Make sure current user can manage options
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Make sure we have the proper wallet address for voting
		if ( get_option('xummlogin_voting_controller') == '' )  {
			wp_die( __( 'The voting module is missing the controller wallet address.' ) );
		}
		if ( get_option('xummlogin_voting_wallet') == '' )  {
			wp_die( __( 'The voting module is missing the voting wallet address.' ) );
		}

		// We're good, show the goods
		require_once 'partials/'.$this->plugin_name.'-voting-admin-display.php';
	}

	public function xummlogin_display_api_fields() {

		// Add XUMM API Access Section
		add_settings_section(
			'xummlogin_api',
			'XUMM API Access', 
			[ $this, 'xummlogin_display_api_info' ],
			'xummlogin_general_settings'
		);

		// Add API Key Field
		add_settings_field(
			'xummlogin_api_key',
			'API Key',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_api', [
				'id'       => 'xummlogin_api_key',
				'name'     => 'xummlogin_api_key',
				'required' => 'true'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_api_key'
		);

		// Add API Secret Field
		add_settings_field(
			'xummlogin_api_secret',
			'API Secret',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_api', [
				'id'       => 'xummlogin_api_secret',
				'name'     => 'xummlogin_api_secret',
				'required' => 'true'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_api_secret'
		);
	}
	
	public function xummlogin_display_project_fields() {

			// Add Project Details Section
		add_settings_section(
			'xummlogin_project',
			'Project Details',
			[ $this, 'xummlogin_display_project_info' ],
			'xummlogin_general_settings'
		);		

		// Add Project Issuer Field
		add_settings_field(
			'xummlogin_trustline_currency',
			'Trustline Currency',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_trustline_currency',
				'name'        => 'xummlogin_trustline_currency',
				'description' => 'Respect the case sensitivity; MyCoin and MYCOIN are different currency on the ledger. No hex code needed.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_trustline_currency'
		);

		// Add Project Issuer Field
		add_settings_field(
			'xummlogin_trustline_issuer',
			'Trustline Issuer',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_trustline_issuer',
				'name'        => 'xummlogin_trustline_issuer',
				'description' => 'XRPL address for the main trustline.' .
					( get_option('xummlogin_trustline_issuer') != '' ? ' <a href="https://xrpscan.com/account/' . get_option('xummlogin_trustline_issuer') . '" target="_blank">Open on XRPScan</a>.' : '')
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_trustline_issuer'
		);


		// Add Project Issuer Field
		add_settings_field(
			'xummlogin_trustline_limit',
			'Trustline Limit',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_trustline_limit',
				'name'        => 'xummlogin_trustline_limit'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_trustline_limit'
		);


		// Add Voting TX Fee Field
		add_settings_field(
			'xummlogin_trustline_fee',
			'TrustSet Fee (in drops)',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_trustline_fee',
				'name'        => 'xummlogin_trustline_fee',
				'placeholder' => DEFAULT_FEE_TX,
				'description' => 'The transaction fee to use for the TrustSet transaction. If empty the default will be used.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_trustline_fee'
		);


		// Add Replace Login Option
		add_settings_field(
			'xummlogin_replace_form',
			'Login Option',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_replace_form',
				'name'        => 'xummlogin_replace_form',
				'description' => 'Check this option to replace the standard WordPress login form with the XUMM login button.',
				'label'       => 'Replace Login Form',
				'subtype'     => 'checkbox'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_replace_form'
		);


		// Add Replace Login Option
		add_settings_field(
			'xummlogin_create_user',
			'User Account',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_create_user',
				'name'        => 'xummlogin_create_user',
				'description' => 'Check this option to automatically create a user account and auto log them in when someone logs with XUMM and doesn\'t have an associated user account.',
				'label'       => 'Auto Create User',
				'subtype'     => 'checkbox'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_create_user'
		);


		// Add Replace Avatar
		add_settings_field(
			'xummlogin_replace_avatar',
			'Avatar Option',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_project', [
				'id'          => 'xummlogin_replace_avatar',
				'name'        => 'xummlogin_replace_avatar',
				'description' => 'Check this option to replace the WordPress avatar for the XRPL avatar from XUMM.',
				'label'       => 'Replace User Avatar',
				'subtype'     => 'checkbox'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_replace_avatar'
		);
	}

	public function xummlogin_display_richlist_fields() {

			// Add Project Details Section
		add_settings_section(
			'xummlogin_richlist',
			'Richlist Settings',
			[ $this, 'xummlogin_display_richlist_info' ],
			'xummlogin_general_settings'
		);		

		// Add Rank Levels
		add_settings_field(
			'xummlogin_rank_levels',
			'Rank Levels',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_richlist', [
				'id'          => 'xummlogin_rank_levels',
				'name'        => 'xummlogin_rank_levels',
				'description' => 'The various balance breakpoint for your holders, comma separated.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_rank_levels'
		);


		// Add Rank Tiers
		add_settings_field(
			'xummlogin_rank_tiers',
			'Rank Tiers',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_richlist', [
				'id'          => 'xummlogin_rank_tiers',
				'name'        => 'xummlogin_rank_tiers',
				'description' => 'The rank tiers matching each Rank Levels, comma separated and equal number.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_rank_tiers'
		);



		// Add Excluded wallets
		add_settings_field(
			'xummlogin_excluded_wallets',
			'Excluded Wallets',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_richlist', [
				'id'          => 'xummlogin_excluded_wallets',
				'name'        => 'xummlogin_excluded_wallets',
				'description' => 'Comma separated list of wallet addresses to exclude from the Richlist and the ranking.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_excluded_wallets'
		);
	}
	
	public function xummlogin_display_username_fields() {

			// Add Project Details Section
		add_settings_section(
			'xummlogin_username',
			'Auto Generated Usernames',
			[ $this, 'xummlogin_display_username_info' ],
			'xummlogin_general_settings'
		);		

		// Add Word List 1
		add_settings_field(
			'xummlogin_username_list1',
			'Word List 1',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_username', [
				'id'          => 'xummlogin_username_list1',
				'name'        => 'xummlogin_username_list1',
				'placeholder' => DEFAULT_WORD_LIST1
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_username_list1'
		);

		// Add Word List 2
		add_settings_field(
			'xummlogin_username_list2',
			'Word List 2',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_username', [
				'id'          => 'xummlogin_username_list2',
				'name'        => 'xummlogin_username_list2',
				'placeholder' => DEFAULT_WORD_LIST2
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_username_list2'
		);

		// Add Word List 1
		add_settings_field(
			'xummlogin_username_list3',
			'Word List 3',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_username', [
				'id'          => 'xummlogin_username_list3',
				'name'        => 'xummlogin_username_list3',
				'placeholder' => DEFAULT_WORD_LIST3
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_username_list3'
		);

		// Add Word List 4
		add_settings_field(
			'xummlogin_username_list4',
			'Word List 4',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_username', [
				'id'          => 'xummlogin_username_list4',
				'name'        => 'xummlogin_username_list4',
				'placeholder' => DEFAULT_WORD_LIST4,
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_username_list4'
		);		
	}

	public function xummlogin_display_voting_fields() {

		// Add Voting Details Section
		add_settings_section(
			'xummlogin_voting',
			'Voting Details',
			[ $this, 'xummlogin_display_voting_info' ],
			'xummlogin_general_settings'
		);		

		// Add Voting Controller Address Field
		add_settings_field(
			'xummlogin_voting_controller',
			'Controller Address',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_controller',
				'name'        => 'xummlogin_voting_controller',
				'description' => 'The wallet address that will control the start and end of the voting.' .
					( get_option('xummlogin_voting_controller') != '' ? ' <a href="https://xrpscan.com/account/' . get_option('xummlogin_voting_controller') . '" target="_blank">Open on XRPScan</a>.' : '')
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_voting_controller'
		);

		// Add Voting Payee Address Field
		add_settings_field(
			'xummlogin_voting_wallet',
			'Voting Address',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_wallet',
				'name'        => 'xummlogin_voting_wallet',
				'description' => 'The wallet address that payments (votes) will be made to. <strong>A separate account dedicated for voting is recommended.</strong>' .
					( get_option('xummlogin_voting_wallet') != '' ? ' <a href="https://xrpscan.com/account/' . get_option('xummlogin_voting_wallet') . '" target="_blank">Open on XRPScan</a>.' : '')
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_voting_wallet'
		);

		// Add Voting TX Fee Field
		add_settings_field(
			'xummlogin_voting_tx_fee',
			'Transaction Fee (in drops)',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_tx_fee',
				'name'        => 'xummlogin_voting_tx_fee',
				'placeholder' => DEFAULT_FEE_TX,
				'description' => 'The transaction fee to use for the vote payment. If empty the default will be used.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_voting_tx_fee'
		);

		// Add Voting Payment Fee in Drops Field
		add_settings_field(
			'xummlogin_voting_vote_fee',
			'Voting Fee (in drops)',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_vote_fee',
				'name'        => 'xummlogin_voting_vote_fee',
				'placeholder' => DEFAULT_FEE_VOTE,
				'description' => 'The cost in XRP the user will pay for the vote. If empty the default will be used.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_voting_vote_fee'
		);


		// Add Voting Minimum
		add_settings_field(
			'xummlogin_voting_vote_minimum',
			'Voting Minimum Threshold',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_vote_minimum',
				'name'        => 'xummlogin_voting_vote_minimum',
				'description' => 'The minimum balance of the token the wallet requires for their vote to count. Leave blank for no minimum.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_voting_vote_minimum'
		);

		// Add cron job details
		add_settings_field(
			'xummlogin_voting_cron_job',
			'CRON Job Details',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_general_settings',
			'xummlogin_voting', [
				'type'        => 'information',
				'description' => get_option('xummlogin_api_secret') != ''
					? 'The following is the CRON job you must setup in order for the voting results to be updated every 5 minutes:<br><br>' .
						'Frequency: <code>*/5 * * * *</code><br>Command: <code>wget -O - ' . trailingslashit(home_url()) . 'wp-content/plugins/xummlogin/_cron/_xummlogin_sync.php?token=' . get_option('xummlogin_api_secret') . '</code>' .
						'<br><br>Depending on your host, you can add the following at the end of the command to stop the output: <code> >/dev/null 2>&1</code>'
					: 'Add and save your API Secret to get this info.'
			]
		);
		register_setting(
			'xummlogin_general_settings',
			'xummlogin_voting_cron_job'
		);
	}

	public function xummlogin_display_voting_management() {

		// Load some of the settings
		$active_vote          = get_option('xummlogin_voting_active');
		$active_vote_choices  = get_option('xummlogin_voting_active_choices');
		$active_vote_tx_start = get_option('xummlogin_voting_active_start');
		$active_vote_tx_end   = get_option('xummlogin_voting_active_end');
		$trustline_currency   = get_option('xummlogin_trustline_currency');

		// Add XUMM Voting Management Section
		add_settings_section(
			'xummlogin_voting',
			'Active Voting', 
			[ $this, 'xummlogin_display_voting_management_info' ],
			'xummlogin_voting_management'
		);

		// Add Active Poll Field
		add_settings_field(
			'xummlogin_voting_active',
			'Voting Name',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_active',
				'name'        => 'xummlogin_voting_active',
				'disabled'    => ( $active_vote_tx_start != '' ),
				'description' => 'Enter the poll question. Once saved the options to Start and End the voting will appear.'
			]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active'
		);

		// Add Active Poll Field
		add_settings_field(
			'xummlogin_voting_active_choices',
			'Voting Choices',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting', [
				'id'          => 'xummlogin_voting_active_choices',
				'name'        => 'xummlogin_voting_active_choices',
				'disabled'    => ( $active_vote_tx_start != '' ),
				'description' => 'A comma separated list of the different choices of the poll. Important to control which choices are used during the results.<br>The choices will be saved on the Start transaction.'
			]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active_choices'
		);

		// Get stats if there's an active vote and it has started
		if( $active_vote != '' && $active_vote_tx_start != '' ){
			$stats = do_shortcode('[xummsyncinfo]');
		}
		else{
			$stats = __('Stats will show once the an Active Voting is in place and started.');
		}

		// Add Active Poll Stats
		add_settings_field(
			'xummlogin_voting_active_stats',
			'Active Voting Stats',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting', [
				'type'        => 'information',
				'description' => $stats]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active_stats'
		);

		// Add XUMM Voting Sequence Section
		add_settings_section(
			'xummlogin_voting_sequence',
			'Active Voting Sequence', 
			[ $this, 'xummlogin_display_voting_sequence_info' ],
			'xummlogin_voting_management'
		);

		// Add Active Poll Start Sequence
		add_settings_field(
			'xummlogin_voting_active_start_ledger',
			'Start Ledger',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting_sequence', [
				'id'          => 'xummlogin_voting_active_start_ledger',
				'name'        => 'xummlogin_voting_active_start_ledger',
				'disabled'    => true,
				'description' => 'Fetched automatically when a start transaction hash is added.' 
			]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active_start_ledger'
		);

		// Get action link based on the current status
		if( $active_vote == '' || $active_vote_choices == '' ){
			$action_link = ' Set an active voting enable this field.';
		}
		else{
			$action_link = ( $active_vote_tx_start == '' ?
				' ' . do_shortcode('[xummvoting active_voting="' . $active_vote . '" vote="start" label="Start Voting" admin="true"]') :
				' <a href="https://xrpscan.com/tx/' . $active_vote_tx_start . '" target="_blank">Open on XRPScan</a>.');
		}

		add_settings_field(
			'xummlogin_voting_active_start',
			'Start TX Hash',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting_sequence', [
				'id'          => 'xummlogin_voting_active_start',
				'name'        => 'xummlogin_voting_active_start',
				'disabled'    => ( $active_vote == '' || $active_vote_choices == ''  ),
				'description' => 'The XRPL ledger transaction hash that the start vote was done.' . $action_link
			]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active_start'
		);


		// Add Active Poll End Sequence
		add_settings_field(
			'xummlogin_voting_active_end_ledger',
			'End Ledger',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting_sequence', [
				'id'          => 'xummlogin_voting_active_end_ledger',
				'name'        => 'xummlogin_voting_active_end_ledger',
				'disabled'    => true,
				'description' => 'Fetched automatically when a end transaction hash is added.' 
			]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active_end_ledger'
		);

		// Get action link based on the current status
		if( $active_vote_tx_end != ''){
			$action_link = ' <a href="https://xrpscan.com/tx/' . $active_vote_tx_end . '" target="_blank">Open on XRPScan</a>.';
		}
		else{
			$action_link = ( $active_vote_tx_start != '' ?
				' ' . do_shortcode('[xummvoting active_voting="' . $active_vote . '" vote="end" label="End Voting" admin="true"]') :
				' Start the voting to enable this field.');
		}

		add_settings_field(
			'xummlogin_voting_active_end',
			'End TX Hash',
			[ $this, 'xummlogin_render_settings_field' ],
			'xummlogin_voting_management',
			'xummlogin_voting_sequence', [
				'id'          => 'xummlogin_voting_active_end',
				'name'        => 'xummlogin_voting_active_end',
				'disabled'    => ( $active_vote_tx_start == '' ),
				'description' => 'The XRPL ledger transaction hash that the end vote was done.' . $action_link
			]
		);
		register_setting(
			'xummlogin_voting_management',
			'xummlogin_voting_active_end'
		);
	}

	public function xummlogin_saving_voting($old_value, $value, $option){

		// Make sure we check the right options
		if( $option == 'xummlogin_voting_active_start' || $option == 'xummlogin_voting_active_end' ){

			// Check if this is the vote start or end
			$trigger = ( $option == 'xummlogin_voting_active_start' ? 'start' : 'end' );

			// Check if this is a new value and that it is not empty
			if( $value != '' ){

				// Get the ledger index from the XRPL
				$ledger_index = $this::get_tx_ledger( $value );

				// Update option
				update_option('xummlogin_voting_active_' . $trigger . '_ledger', $ledger_index, true);
			}
			elseif( $value == '' ){

				// Remove option if the tx is removed
				delete_option('xummlogin_voting_active_' . $trigger . '_ledger');
			}

		}

	}

	public function xummlogin_display_api_info() {
		echo '<p>An API Key and API Secret is required to use this plugin. You can get one here: <a href="https://apps.xumm.dev/" target="_blank">https://apps.xumm.dev/</a>.</p>';
	}

	public function xummlogin_display_project_info() {
		echo '<p>Details about the project and its trustline\'s information.</p>';
	}

	public function xummlogin_display_richlist_info() {
		echo '<p>Various setting for using the Richlist feature.</p>';
	}

	public function xummlogin_display_username_info() {
		echo '<p>Customize the auto-generated username when user logs in for the first time using their XUMM wallet and if the "Auto Create User" option is enabled.<br>At least 3 comma separated word list are required. If empty the default of adjective + animal + verb will be used.</p>';
	}

	public function xummlogin_display_voting_info() {
		echo '<p>Settings for the voting mechanism if you intend to use it for community voting.</p>';
	}

	public function xummlogin_display_voting_management_info() {
		echo '<p>Use this page to set an active vote and initiate the start and end voting transactions. Currently only 1 active vote is supported.</p>';
	}

	public function xummlogin_display_voting_sequence_info() {
		echo '<p>The ledger start and end sequence helps the processing of the vote results by only querying the ledgers (aka blocks) in between. Leave them empty to let the plugin automatically add them when processing.</p>';
	}

	public function xummlogin_render_settings_field($args) {

		// Set default arguments
		$default_args = [
			'type'        => 'input',
			'subtype'     => 'text',			
			'label'       => '',			
			'required'    => false,
			'disabled'    => false,
			'value_type'  => 'normal',
			'wp_data'     => 'option',
			'placeholder' => '',
			'description' => '',
		];

		// Merge back to the full args
		$args = array_merge($default_args, $args);

		if( $args['type'] != 'information' && $args['wp_data'] == 'option' ){
			$wp_data_value = get_option($args['name']);
		}
		elseif( $args['type'] != 'information' && $args['wp_data'] == 'post_meta' ){
			$wp_data_value = get_post_meta($args['post_id'], $args['name'], true );
		}

		// Update required args 
		$required_attr = $args['required'] ? 'required' : '';
		$disabled_attr = $args['disabled'] ? 'disabled' : '';

		switch ($args['type']) {
			case 'input':
				$value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;

				if($args['subtype'] != 'checkbox'){
					$prefix = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
					$suffix = (isset($args['prepend_value'])) ? '</div>' : '';
					$step   = (isset($args['step'])) ? 'step="'.$args['step'].'"' : '';
					$min    = (isset($args['min'])) ? 'min="'.$args['min'].'"' : '';
					$max    = (isset($args['max'])) ? 'max="'.$args['max'].'"' : '';

					if( isset($args['disabled']) && $args['disabled'] == true ){
						echo $prefix.'<input type="'.$args['subtype'].'" id="'.$args['id'].'_disabled" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'_disabled" size="40" ' . $disabled_attr . ' value="' . esc_attr($value) . '" />';
						echo '<input type="hidden" id="'.$args['id'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$suffix;
					}
					else {
						echo $prefix.'<input type="'.$args['subtype'].'" placeholder="'.$args['placeholder'].'" id="'.$args['id'].'" '.$required_attr.' '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$suffix;
					}

				}
				else {
					$checked = ($value) ? 'checked' : '';
					echo '<label for="'.$args['id'].'"><input type="'.$args['subtype'].'" id="'.$args['id'].'" '.$required_attr.' name="'.$args['name'].'" size="40" value="1" '.$checked.' />'.$args['label'].'</label>';
				}
				break;

			default:
				break;
		}

		// Output description if any
		if( $args['description'] ){
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	public function get_tx_ledger($tx){

		// Make sure we got a tx
		if( $tx == '' ){
			wp_die('Error: Missing tx hash, cannot retrieve ledger index.');
		}

		// Request to fetch a transaction's ledger index
		$command = [
			'id'          => 1,
			'command'     => 'tx',
			'transaction' => $tx,
			'binary'      => false
		];

		// Socket config
		$config = new ClientConfig();
		$config->setHeaders([
	    'Connection'   => 'Upgrade',
	    'Upgrade'      => 'websocket',
	    'Content-Type' => 'application/json',
		]);

		// Open the connection and send request
		$client = new WebSocketClient('wss://xrplcluster.com', $config);
		$client->send(json_encode($command));
		$response = $client->receive();

		// Convert resonse to object
		$response_json = json_decode($response);
		$ledger        = $response_json->result->ledger_index;

		return $ledger;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xummlogin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xummlogin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/xummlogin-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xummlogin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xummlogin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/xummlogin-admin.js', array( 'jquery' ), $this->version, false );

	}
}