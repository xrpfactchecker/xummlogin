<?php
class Xummlogin_XUMM{
	private $api_key;
	private $api_secret;

	private $option_replace_form;
	private $option_replace_avatar;

	private $voting_address_controller;
	private $voting_address_wallet;
	private $voting_fee_tx;
	private $voting_fee_vote;
	private $voting_is_active;
	private $voting_has_ended;

	private $voting_active_question;
	private $voting_active_choices;

	private $trustset_fee_tx;

	public function __construct() {

		// Get the settings for the XUMM API
		$this->api_key    = get_option('xummlogin_api_key');
		$this->api_secret = get_option('xummlogin_api_secret');		

		// Get the options for the plugin
		$this->option_replace_form   = get_option('xummlogin_replace_form');
		$this->option_replace_avatar = get_option('xummlogin_replace_avatar');

		// Get the settings for the voting mechanism
		$this->voting_address_controller = get_option('xummlogin_voting_controller');		
		$this->voting_address_wallet     = get_option('xummlogin_voting_wallet');		
		$this->voting_fee_tx             = get_option('xummlogin_voting_tx_fee');		
		$this->voting_fee_vote           = get_option('xummlogin_voting_vote_fee');		
		$this->voting_is_active          = (int)get_option('xummlogin_voting_active_start_ledger') != 0;		
		$this->voting_has_ended          = (int)get_option('xummlogin_voting_active_end_ledger') != 0;		

		// Get the active votings and choices
		$this->voting_active_question = get_option('xummlogin_voting_active');		
		$this->voting_active_choices  = get_option('xummlogin_voting_active_choices');

		// Convert choices to an array
		$this->voting_active_choices = array_map('trim', explode(',', $this->voting_active_choices));

		// Get the settings for the trustline set
		$this->trustset_fee_tx = get_option('xummlogin_trustline_fee');	
	}

	public function xummlogin_add_form_login() {

		// Keep going only if the setting is on
		if( $this->option_replace_form == '1'){

			// To hold the content of the form, to output or return;
			$form_content = '';

			// Check if we have both API Key and Secret in place
			if( $this->api_key == '' || $this->api_key == '' ){

				// Get the XUMM sign in button and add to the form
				$form_content .= '<p class="xl-login-button xl-display-show">';
					$form_content .= '<label>' . __('XUMM Login') . '</label>';
					$form_content .= '<span class="xl-error">API Key and Secret malconfigured. Review settings and try again.</span>';
				$form_content .= '</p>';

			}
			else{

				// Hidden field for the action that will trigger the right method when the ajax call is received.
				$form_content .= '<input type="hidden" name="action" value="process_xumm_payload">';

				// Get the XUMM sign in button and add to the form
				$form_content .= '<p class="xl-login-button xl-display-show">';
					$form_content .= '<label>' . __('XUMM Login') . '</label>';
					$form_content .= do_shortcode('[xummlogin form="false" button="true"]');
				$form_content .= '</p>';

				// Add note a user account will get created
				$form_content .= '<p class="xl-login-note xl-display-show">' . 
					__('Your XRPL address will be bound to your account on this and will be stored.') .
					' <a href="#" class="toggle_form">' . __('Show Form') . '</a>' .
					' <a href="#" class="toggle_form hide">' . __('Hide Form') . '</a>' .
				'</p>';
			}

			// We're done
			echo $form_content;
		}
	}

	public function xummlogin_show_form( $classes ) {

		// Check if we need to show or hide the form based on the settings
		if( $this->option_replace_form != '1'){
    	$classes[] = 'xl-display-form';
    }
    
    return $classes;
	}

	public function xummlogin_check_action(){

    // Start session if it doesn't exists
    if(!session_id()) { session_start(); }

		// Check if we have to trigger an action
		list($segment) = explode('&', $_SERVER['QUERY_STRING']);
		$do_action     = substr($segment, 0, 3) == 'xl-';

		// Action requested?
		if( $do_action ){

			// Check if the action has a param and if so extract it
			if( strpos($segment, '=') !== false ){
				list($action, $param) = explode('=', $segment);
			}
			else{
				$action = $segment;
			}

			// Check which action
			switch ($action) {

				// The various possible actions
				case 'xl-' . ACTION_SIGNIN:		
					$this::xummlogin_prepare_signin();
					break;
				case 'xl-' . ACTION_TRUSTLINE:		
					$this::xummlogin_prepare_trustline();
					break;
				case 'xl-' . ACTION_PAYMENT:		
					$this::xummlogin_prepare_payment();
					break;
				case 'xl-' . ACTION_VOTING:		
					$this::xummlogin_prepare_vote($param);
					break;

				// Processing the payload post XUMM
				case 'xl-payload':		
					$this::xummlogin_process_payload();
					break;
				
				// Do nothing
				default:
					break;
			}
		}
	}

	public function xummlogin_prepare_signin() {

		// Get where to redirect after logging in
		$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

		// Generate unique payload identifier and store it in a session
		$identifier = ACTION_SIGNIN . '_' . strtoupper(substr(md5(microtime()), 0, 10));

		// Store payload id for later retrieval and get its return URL
		$return_url = Xummlogin_utils::xummlogin_store_payload($identifier, ACTION_SIGNIN);

		// Add a URL to redirect to after the XUMM signin if we have one
		if( $redirect != '' ){
			$return_url = add_query_arg( 'redirect', urlencode($redirect), $return_url);
		}

		// Prep the request
		$command = [
			'TransactionType' => 'SignIn'
		];
    $body = Xummlogin_utils::xummlogin_build_api_request($command, $identifier, $return_url);

	  // Post request to the XUMM API
	  Xummlogin_utils::xummlogin_post_to_xumm($body, $return_url, ACTION_SIGNIN);
	}
	
	public function xummlogin_prepare_trustline() {

		// Get the trustline setup params
		$issuer   = get_option('xummlogin_trustline_issuer');
		$currency = get_option('xummlogin_trustline_currency');
		$limit    = get_option('xummlogin_trustline_limit');
		$tx_fee   = $this->trustset_fee_tx != '' ? $this->trustset_fee_tx : DEFAULT_FEE_TX;

		// Generate unique payload identifier and store it in a session
		$identifier = ACTION_TRUSTLINE . '_' . strtoupper(substr(md5(microtime()), 0, 10));

		// Store payload id for later retrieval and get its return URL
		$return_url = Xummlogin_utils::xummlogin_store_payload($identifier, ACTION_TRUSTLINE);			

		// Make sure we have all of the trustline info
		if( $issuer == '' ){ wp_die(__('Error: Missing trustline issuer. Add to settings.')); }
		if( $currency == '' ){ wp_die(__('Error: Missing trustline currency. Add to settings.')); }
		if( $limit == '' ){ wp_die(__('Error: Missing trustline limit. Add to settings.')); }

		// Convert currency as needed
		$currency = Xummlogin_utils::xummlogin_currency($currency);

		// Prep the request
		$command = [
      'TransactionType' => 'TrustSet',
      'Fee'             => $tx_fee,
      'Flags'           => TF_SET_NO_RIPPLE,
      'LimitAmount' => [
        'currency' => $currency,
        'issuer'   => $issuer,
        'value'    => $limit
      ]
    ];
    $body = Xummlogin_utils::xummlogin_build_api_request($command, $identifier, $return_url);

	  // Post request to the XUMM API
	  Xummlogin_utils::xummlogin_post_to_xumm($body, $return_url, ACTION_TRUSTLINE);
	}

	public function xummlogin_prepare_payment() {

		// Get the payment setup params
		$destination = isset($_GET['destination']) ? sanitize_text_field($_GET['destination']) : '';
		$amount      = isset($_GET['amount']) ? sanitize_text_field($_GET['amount']) : '1';
		$memo        = isset($_GET['memo']) ? sanitize_text_field($_GET['memo']) : '';

		// Generate unique payload identifier and store it in a session
		$identifier = ACTION_PAYMENT . '_' . strtoupper(substr(md5(microtime()), 0, 10));

		// Store payload id for later retrieval and get its return URL
		$return_url = Xummlogin_utils::xummlogin_store_payload($identifier, ACTION_PAYMENT);	     

		$command = [
      'TransactionType' => 'Payment',
      'Fee'             => DEFAULT_FEE_TX,
      'Amount'          => $amount,
      'Destination'     => $destination,
    ];
    $body = Xummlogin_utils::xummlogin_build_api_request($command, $identifier, $return_url);

	  // Add memos if needed
	  if( $memo != '' ){
	  	Xummlogin_utils::xummlogin_add_request_memo($body, [$memo]);
		}

	  // Post request to the XUMM API
	  Xummlogin_utils::xummlogin_post_to_xumm($body, $return_url, ACTION_PAYMENT);
	}

	public function xummlogin_prepare_vote($vote) {

		global $xumm_messaging;

		// Make sure if we have the proper settings in place
		if( $this->voting_address_wallet == '' ){
			$xumm_messaging->add('error', ACTION_VOTING, '0', __('Voting wallet address is missing. Contact the admin.'));
			return;
		}

		// Make sure if there's an active voting in place
		if( $this->voting_active_question == '' ){
			$xumm_messaging->add('error', ACTION_VOTING, '0', __('No active voting in place.'));
			return;
		}

		// Make sure if we have the proper settings in place
		if( !is_admin() && !$this->voting_is_active ){
			$xumm_messaging->add('error', ACTION_VOTING, '0', __('The active voting has not yet started. Check back soon!'));
			return;
		}

		// Stop if there's no vote
		if( $vote == '' ){
			$xumm_messaging->add('error', ACTION_VOTING, '0', __('No vote sent.'));
			return;
		}
		// Or if the voting has ended
		elseif( $this->voting_has_ended ){
			$xumm_messaging->add('error', ACTION_VOTING, '0', __('Voting has ended, no more votes can be cast.'));
			return;
		}

		// Get the payment setup params
		$destination = $this->voting_address_wallet;
		$tx_fee      = $this->voting_fee_tx != '' ? $this->voting_fee_tx : DEFAULT_FEE_TX;
		$amount      = $this->voting_fee_vote != '' ? $this->voting_fee_vote : DEFAULT_FEE_VOTE;
		$vote        = sanitize_text_field($vote);
		$tx_memos    = [bin2hex($this->voting_active_question)]; // Default the first memo with the active voting

		// Set the proper return URL based on front/back end
		if( is_admin() ){

 			// Make sure if we have choices in place
 			if( !is_array($this->voting_active_choices) || count($this->voting_active_choices) == 0 ){
 				wp_die(__('Error: Voting is missing choices.'));
 			}

			// Update return URL to add the start or end to know which one is triggered
			$trigger    = stripos(hex2bin($vote), 'start') !== false ? 'start' : 'end'; // check if the memo contains start
			$identifier = ACTION_VOTING . $trigger . '_' . strtoupper(substr(md5(microtime()), 0, 10));

			// Check if the trigger is to start and it is already started, stop!
			if( $trigger == 'start' && $this->voting_is_active ){
				wp_die(__('Error: The voting is already active.'));
			}

			// Check if the trigger is to end but it has not started
			if( $trigger == 'end' && !$this->voting_is_active ){
				wp_die(__('Error: There is currently no active voting to end.'));
			}				

			//Update Memo depending on the trigger
			if( $trigger == 'start' ){
				$tx_memos = array_merge($tx_memos, array_map('bin2hex', $this->voting_active_choices));
			}
			else{
				$tx_memos[] = bin2hex('Voting Ends');
			}
		}
		else{

			// Make sure the vote is a valid one
			if( !in_array(hex2bin($vote), $this->voting_active_choices) ){
				$xumm_messaging->add('error', ACTION_VOTING, '0', __('Not a valid vote for the active voting.'));
				return;
			}

			// Set the voting XUMM id
			$identifier = ACTION_VOTING . '_' . strtoupper(substr(md5(microtime()), 0, 10));

			// Add vote to memo
			$tx_memos[] = $vote;
		}

		// Setup the return page URL to be the logging page
		$return_url = Xummlogin_utils::xummlogin_store_payload($identifier, ACTION_VOTING);        

		// Prep the request
		$command = [
      'TransactionType' => 'Payment',
      'Amount'          => $amount,
      'Fee'             => $tx_fee,
      'Destination'     => $destination,
    ];
    $body = Xummlogin_utils::xummlogin_build_api_request($command, $identifier, $return_url);

	  // If admin voting start/end, add controller account to transaction
	  if( is_admin() ){

	  	// Check if controller account is set
	  	if( $this->voting_address_controller != '' ){
				$body['txjson']['Account'] = $this->voting_address_controller;
			}
			else{
				wp_die(__('Error: The voting module is missing the controller wallet address.'));
			}
	  }

	  // Add memos to the request
	  Xummlogin_utils::xummlogin_add_request_memo($body, $tx_memos);

	  // Post request to the XUMM API
	  Xummlogin_utils::xummlogin_post_to_xumm($body, $return_url, ACTION_VOTING);
	}

	public function xummlogin_process_payload() {

		// Get the saved payload
		$payload = Xummlogin_utils::xummlogin_get_payload();

		// Make sure we have something to process
		if( count($payload) > 0 && isset( $payload['data'] ) ){

			// Get the payload ID and then fetch the response from XUMM
			$payload_id = $payload['data'];
			$response   = Xummlogin_utils::xummlogin_get_api_data( $payload_id );

			// Check which action triggered this payload
			switch ($payload['type']) {

				case ACTION_SIGNIN:
					$this::xummlogin_process_signin( $response );
					break;
				
				case ACTION_TRUSTLINE:
					$this::xummlogin_process_trustline( $response );
					break;

				case ACTION_PAYMENT:
					$this::xummlogin_process_payment( $response );
					break;

				case ACTION_VOTING:
					$this::xummlogin_process_voting( $response );
					break;

				default:
					break;
			}
		}

		// Clear payload
		unset($_SESSION['payload']);
	}

	public function xummlogin_process_signin($response) {

		global $xumm_messaging;

		// Check if the last payload was a signin and if so grab the returned wallet address
		$account_wallet = '';
		if( isset($response['payload']['tx_type']) && strtolower($response['payload']['tx_type']) == ACTION_SIGNIN ){
			$account_wallet = isset($response['response']['account']) ? (string)$response['response']['account'] : '';
		}

		// If we got an account, we're good, check if it belongs to a user, if not create one if the option is enabled
		if( $account_wallet != '' ){
			$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';

			// Set meta args for selecting the user based on their wallet address
			$args  = array(
				'meta_key'     => 'xrpl-r-address',
				'meta_value'   => $account_wallet,
				'meta_compare' => '=' // exact match only
			);			 
			$user_query = new wp_user_query( $args );
			$user_id    = 0;
			
			// Check if we found a match, if not, we'll need to create a user if the option is turned on
			if( count($user_query->results) > 0 ){
				$user    = $user_query->results[0]; // Grab first result only
				$user_id = $user->data->ID;
			}
			elseif( get_option('xummlogin_create_user') == '1' ){

				// Generate a unique username
				$username = Xummlogin_utils::xummlogin_generate_username();
				while (username_exists($username)) {
					$username = Xummlogin_utils::xummlogin_generate_username();
				}
				
				// Generate password
				$password = wp_generate_password();

				// Create user
				$user_id = wp_create_user($username, $password); // No emails

				// Add the wallet address to the newly created user
				add_user_meta($user_id, 'xrpl-r-address', $account_wallet);
			}

			// Authenticate the user if we have one, otherwise save the wallet address in a session
			if( $user_id > 0 ){
		    wp_clear_auth_cookie();
		    wp_set_current_user($user_id);
		    wp_set_auth_cookie($user_id);

		    // Clear any potential non-user cookie from before
				setcookie('xrpl-r-address', '', time()-3600, '/');
				if (!isset($_COOKIE['xrpl-r-address'])){
					$_COOKIE['xrpl-r-address'] = ''; // workaround to prevent redirect to read cookie
				}

		    // Redirect where they were trying to go before login in
		    if( $redirect_url != '' ){
					wp_redirect($redirect_url);
					exit;
				}
			}
			else{
				// Store wallet encrypted
				setcookie('xrpl-r-address', Xummlogin_utils::xummlogin_encrypt_decrypt($account_wallet), time()+60*60*24*30, '/');
				if (!isset($_COOKIE['xrpl-r-address'])){
					$_COOKIE['xrpl-r-address'] = Xummlogin_utils::xummlogin_encrypt_decrypt($account_wallet); // workaround to prevent redirect to read cookie
				}				
			}
		}
		else{
			$xumm_messaging->add('warning', ACTION_SIGNIN, '0', __('Your signin request was cancelled.'));
			return;
		}
	}

	public function xummlogin_process_trustline($response) {

		global $xumm_messaging;

		// Get account that did the transaction
		$account = $response['response']['account'];

		// Check if the account is null, if it is the request was cancelled
		if( is_null($account) ){
			$xumm_messaging->add('warning', ACTION_TRUSTLINE, '0', __('Your trustline request was cancelled.'));
			return;
		}
		else{
			$xumm_messaging->add('success', ACTION_TRUSTLINE, '0', __('The trustline was set successfully!'));
			return;
		}
	}

	public function xummlogin_process_payment($response) {

		global $xumm_messaging;

		// Get account that did the transaction
		$account = $response['response']['account'];

		// Check if the account is null, if it is the request was cancelled
		if( is_null($account) ){
			$xumm_messaging->add('warning', ACTION_PAYMENT, '0', __('Your payment request was cancelled.'));
			return;
		}
		else{
			$xumm_messaging->add('success', ACTION_PAYMENT, '0', __('The payment was made successfully!'));
			return;
		}
	}

	public function xummlogin_process_voting($response) {
		
		global $xumm_messaging;

		// Get account that did the transaction
		$account = $response['response']['account'];

		// Check if the request is made from the admin, which means it is the start/end vote
		if( is_admin() ){

			// Check if the account is null, if it is the request was cancelled
			if( is_null($account) ){
   			add_action( 'admin_notices', [$this, 'xummlogin_error_cancelled'] );	
			}
			// Check if it matches the controller account
			elseif( $account == $this->voting_address_controller ){

				// Get the transaction ID and save to the options
				$tx_id = $response['response']['txid'];

				// Update options
				$vote_trigger = stripos($response['custom_meta']['identifier'], 'start') !== false ? 'start' : 'end';
				update_option('xummlogin_voting_active_' . $vote_trigger, $tx_id, true);

				// Redirect to itself
				wp_redirect('/wp-admin/admin.php?page=xumm-login-voting');
				exit;
			}
			// The transaction was successfull but with the wrong voting controller
			else{
				add_action( 'admin_notices', [$this, 'xummlogin_error_wrong_controller'] );	
			}
		}
		else{

			// Check if the account is null, if it is the request was cancelled
			if( is_null($account) ){
   			$xumm_messaging->add('warning', ACTION_VOTING, '0', __('Your voting request was cancelled.'));
				return;
			}
			// If not, make sure we got a tx hash which means it was a success
			elseif( isset($response['response']['dispatched_result']) && $response['response']['dispatched_result'] == 'tesSUCCESS' ){

				// Check if we can try to get the vote details
				if( isset($response['payload']['computed']['Memos'][1]['Memo']['MemoData']) ){
					$casted_vote = $response['payload']['computed']['Memos'][1]['Memo']['MemoData'];
					$xumm_messaging->add('success', ACTION_VOTING, '0', sprintf(__('Your vote for <strong>%s</strong> was successfully cast on the XRP Ledger! Please <strong>allow up to 5 minutes</strong> for it to reflect in the totals.'), $casted_vote));
				}
				// If not output a default success message.
				else{
					$xumm_messaging->add('success', ACTION_VOTING, '0', __('Your vote was cast successfully on the XRP Ledger! Please <strong>allow up to 5 minutes</strong> for it to reflect in the totals.'));
				}

				return;
			}
			else{
				// Check the status of the transaction
	   		$xumm_messaging->add('error', ACTION_VOTING, '0', __('Your vote was not cast.'));
				return;
			}

		}
	}

	public function xummlogin_error_wrong_controller() {
    echo '<div class="error notice">';
    	echo '<p>' . __( 'The voting start/end was not made by the controller address. The voting is not started!' ) . '</p>';
		echo '</div>';
	}

	public function xummlogin_error_cancelled() {
    echo '<div class="error notice">';
    	echo '<p>' . __( 'The voting start/end was cancelled. The voting is not started!' ) . '</p>';
		echo '</div>';
	}

	public function xummlogin_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {

		//Check if the option is enabled
		if( $this->option_replace_avatar == '1' ){

			// Apply filter
	    $user = false;
	 
	 		// Check if we have an ID
	    if ( is_numeric($id_or_email) ) { 
	      $id   = (int) $id_or_email;
	      $user = get_user_by('id', $id);
	    }
	    elseif ( is_object($id_or_email) ) {
	      if ( !empty($id_or_email->user_id) ) {
	        $id   = (int) $id_or_email->user_id;
	        $user = get_user_by( 'id' , $id );
	      }
	    } 
	    else {
	      $user = get_user_by( 'email', $id_or_email );   
	    }
	 
	 		// Continue if we got a user object
	    if ( $user && is_object($user) ) {
	 
	 				// See if we have a saved r address for this user
	    		$user_r_address = get_user_option('xrpl-r-address', $user->data->ID);

	    		// If we do, use their XRPL avatar from XUMM
	        if ( $user_r_address != '' ) {
	          $avatar = 'https://xumm.app/avatar/' . $user_r_address . '.png';
	          $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	        }
	    }
	  }

    return $avatar;
	}
}
?>