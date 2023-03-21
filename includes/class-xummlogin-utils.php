<?php
class Xummlogin_utils{

  static function xummlogin_generate_username(){

    // Initiate
    $name = '';

    // Get saved word lists
    $username_word_list1 = get_option('xummlogin_username_list1');  
    $username_word_list2 = get_option('xummlogin_username_list2');  
    $username_word_list3 = get_option('xummlogin_username_list3');  
    $username_word_list4 = get_option('xummlogin_username_list4');  

    // Set the different word lists
    $list1 = $username_word_list1 != '' ? explode(',', $username_word_list1) : explode(',', DEFAULT_WORD_LIST1);
    $list2 = $username_word_list2 != '' ? explode(',', $username_word_list2) : explode(',', DEFAULT_WORD_LIST2);
    $list3 = $username_word_list3 != '' ? explode(',', $username_word_list3) : explode(',', DEFAULT_WORD_LIST3);
    $list4 = $username_word_list4 != '' ? explode(',', $username_word_list4) : explode(',', DEFAULT_WORD_LIST4);

    // Build the name randomly with the lists
    $name .= count($list1) > 0 ? $list1[rand(0, count($list1)-1)] : '';
    $name .= count($list2) > 0 ? $list2[rand(0, count($list2)-1)] : '';
    $name .= count($list3) > 0 ? $list3[rand(0, count($list3)-1)] : '';
    $name .= count($list4) > 0 ? $list4[rand(0, count($list4)-1)] : '';

    // Done, return the generated name
    return $name;
  }

  static function xummlogin_encrypt_decrypt($string, $action = 'encrypt'){
    
    // Call the right function based on the action requested
    if ($action == 'encrypt') {
      $output = self::encrypt( $string, self::xummlogin_get_key() );
    } else if ($action == 'decrypt') {
      // Don't even try to decrypt if no : is present
      $output = ( strpos($string, ':') === false ) ? '' : self::decrypt( $string, self::xummlogin_get_key() );
    }

    return $output;
  }

  static function xummlogin_get_key(){
    return substr( hash('sha256', AUTH_KEY), 0, 32);
  }
  
  private static function encrypt($message, $password){
    if (OPENSSL_VERSION_NUMBER <= 268443727) {
        throw new RuntimeException('OpenSSL Version too old, vulnerability to Heartbleed');
    }
    
    $iv_size        = openssl_cipher_iv_length(AES_METHOD);
    $iv             = openssl_random_pseudo_bytes($iv_size);
    $ciphertext     = openssl_encrypt($message, AES_METHOD, $password, OPENSSL_RAW_DATA, $iv);
    $ciphertext_hex = bin2hex($ciphertext);
    $iv_hex         = bin2hex($iv);
    return "$iv_hex:$ciphertext_hex";
  }

  private static function decrypt($ciphered, $password) {
    $iv_size    = openssl_cipher_iv_length(AES_METHOD);
    $data       = explode(":", $ciphered);
    $iv         = hex2bin($data[0]);
    $ciphertext = hex2bin($data[1]);
    return openssl_decrypt($ciphertext, AES_METHOD, $password, OPENSSL_RAW_DATA, $iv);
  }

  static function xummlogin_store_payload($payload_id, $type = 'misc'){

    // Build object to store
    $payload = [
      'type' => $type,
      'data' => $payload_id,
    ];

    // Store identifier
    $encrypted_payload   = Xummlogin_utils::xummlogin_encrypt_decrypt( json_encode($payload) );
    $_SESSION['payload'] = $encrypted_payload;

    // Setup the return page URL to retrieve the payload
    $return_url  = home_url() . str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
    $return_url .= '?xl-payload';

    // Set the right return URL if this is the admin
    if( is_admin() ){
      $return_url .= '&page=xumm-login-voting';
    }

    return $return_url;
  }

  static function xummlogin_get_payload(){

    // Get the stored payload ID
    $encrypted_payload = Xummlogin_utils::xummlogin_get_session_data('payload');

    // Decrypt the payload ID
    $payload = ( $encrypted_payload != '' ) 
      ? json_decode( Xummlogin_utils::xummlogin_encrypt_decrypt($encrypted_payload, 'decrypt'), true ) 
      : [];

    // We're done
    return $payload;
  }

  static function xummlogin_get_session_data($data_key){

    // Get the stored payload ID
    $data = isset($_SESSION[$data_key]) ? $_SESSION[$data_key] : '';

    return $data;
  }

  static function xummlogin_get_cookie_data($data_key){

    // Get the stored payload ID
    $data = isset($_COOKIE[$data_key]) ? $_COOKIE[$data_key] : '';

    return $data;
  }

  static function xummlogin_log_error($data, $id, $method = 'response'){

    // Get directory and make sure it exists
    $log_dir = plugin_dir_path( __FILE__ ) . '/../logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    // Get filename and write log
    $filename = $log_dir . '/' . $id . '_' . $method . '.json';
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
  }

  static function xummlogin_get_api_data($payload_id){

    // Send a request to check the last payload
    $response = wp_remote_get('https://xumm.app/api/v1/platform/payload/ci/' . $payload_id, [
      'method'    => 'GET',
      'headers'   => [
        'Content-Type' => 'application/json',
        'X-API-Key'    => get_option('xummlogin_api_key'),
        'X-API-Secret' => get_option('xummlogin_api_secret')
      ]
    ]);

    // Return the response in JSON
    $body = json_decode( $response['body'], true );
    return $body;
  }

  static function xummlogin_build_api_request($command, $identifier, $return_url){

    // Build command XUMM API request body
    $body = [
      'txjson' => $command,
      'options' => [
          'submit' => true,
          'return_url' => [
            'web' => $return_url
          ]
      ],
      'custom_meta' => array(
          'identifier' => $identifier
      )
    ];

    return $body;
  }

  static function xummlogin_post_to_xumm($request, $return_url, $request_label, $redirect = true){

    global $xumm_messaging;

    // Add mobile redirect if on mobile
    if (wp_is_mobile()){
      $request['options']['return_url']['app'] = $return_url;
    }

    // Encode in JSON
    $body = wp_json_encode($request);

    // Set credentials
    $headers = [
      'Content-Type' => 'application/json',
      'X-API-Key'    => get_option('xummlogin_api_key'),
      'X-API-Secret' => get_option('xummlogin_api_secret')
    ];
    
    // Send request and get response
    $response = wp_remote_post('https://xumm.app/api/v1/platform/payload', [
      'method'  => 'POST',
      'headers' => $headers,
      'body'    => $body
    ]);

    // If all is good, output the URL to be redirected to for the XUMM signing process
    if( !is_wp_error( $response ) ) {

      // Decode response
      $body = json_decode( $response['body'], true );

      // To force an error for testing purposes
      //$body = json_decode( "{\"error\":{\"reference\":\"c864f2f7-a573-46dd-bfdb-08241c8a56d9\",\"code\":429,\"message\":\"Too many requests (rate limit): 38 calls made, limit: 30. Try again in 20 seconds.\"}}", true );

      if( isset($body['error']) && $body['error'] ){

        // get the error code
        $error_code = isset($body['error']['code']) ? $body['error']['code'] : 0;

        // Log Error
        //Xummlogin_utils::xummlogin_log_error($request, $request['custom_meta']['identifier'], 'request');
        //Xummlogin_utils::xummlogin_log_error($response, $request['custom_meta']['identifier'], 'response');

        // Add error to plugin error object
        $xumm_messaging->add('error', strtolower($request_label), (string)$error_code);
      }
      else{
        $goto_url = $body['next']['always'];
        if( $redirect ){
          wp_redirect($goto_url);
        }
        else{
          echo $goto_url;
        }
        exit;
      }
    }
    else{

      // Log Error
      //Xummlogin_utils::xummlogin_log_error($request, $request['custom_meta']['identifier'], 'request');
      //Xummlogin_utils::xummlogin_log_error($response, $request['custom_meta']['identifier'], 'response');

      // Add error to plugin error object
      $xumm_messaging->add('error', strtolower($request_label), '0');
    }
  }

  static function xummlogin_add_request_memo(&$request, $memos){
    // Make sure we have a memo to parse
    if( is_array($memos) && count($memos) > 0 ){

      // Set up parent
      $request['txjson']['Memos'] = [];

      // Explode in case we have multiple memo and cycle to build the memo array to send
      foreach ($memos as $memo) {
        $request['txjson']['Memos'][] = [
          'Memo' => [
            'MemoData' => $memo
          ]
        ];
      }
    } 
  }

  static function xummlogin_currency($xrpl_currency){

    // Leave it as is if it 3 character
    if( strlen($xrpl_currency) == 3 ){
      return $xrpl_currency;
    }
    // Otherwise convert to hex and pad to 40 characters long
    else{
      return str_pad( strtoupper( bin2hex($xrpl_currency) ), 40, '0' );
    } 
  }

  static function xummlogin_load_data($data_type, $voting = ''){

    // Get the filetype to load
    $filetype = ($data_type == 'lastsync') ? 'results' : $data_type;

    // Get the filenames depending if this is for the voting or not
    if( $voting != '' ){
      $voting_hash       = substr(md5($voting), 10);
      $filename_progress = $_SERVER['DOCUMENT_ROOT'] . '/../xl_xrpldata/' . $voting_hash . '-' . $filetype . '.json';
      $filename_final    = $_SERVER['DOCUMENT_ROOT'] . '/../xl_xrpldata/' . $voting_hash . '-' . $filetype . '-final.json';
    }
    else{
      $filename_progress = $_SERVER['DOCUMENT_ROOT'] . '/../xl_xrpldata/' . $data_type . '.json';
      $filename_final    = ''; // Only used for voting
    }

    // Default
    $content = false;

    // Check if the finale cache file exists and load its content
    if ( $filename_final != '' && file_exists($filename_final) ) {
      $content = ($data_type == 'lastsync') ? filemtime($filename_final) : file_get_contents($filename_final);
    }
    // If not grab the progress file if it exists
    elseif ( $filename_progress != '' && file_exists($filename_progress) ) {
      $content = ($data_type == 'lastsync') ? filemtime($filename_progress) : file_get_contents($filename_progress);
    }

    // JSON decode if we have content
    if( $data_type != 'lastsync' && $content !== false ){
      $content = json_decode($content, true);
    }

    // Return content
    return $content;
  }   

  static function xummlogin_time_since($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime( date('F j, Y, g:i a', $datetime) );
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
  }  
  
  static function xummlogin_initialize_richlist_grouping($levels, $tiers){

    // Setup
    $groups = [];
    $levels = array_map('trim', explode(',', $levels));
    $tiers  = ($tiers != '') ? array_map('trim', explode(',', $tiers)) : [];

    // Initialize array that we'll use to store the grouping
    $level_count = count($levels);

    for ($level_index = 0; $level_index < $level_count; $level_index++) { 

      // Get current level
      $level = $levels[ $level_index ];

      // Create the groupings
      $groups[] = [
        'min'     => $level,
        'max'     => ( $level_index + 1 < $level_count ) ? $levels[ $level_index + 1 ] : null,
        'tier'    => isset( $tiers[$level_index] ) ? $tiers[$level_index] : '',
        'loaded'  => false,
        'total'   => 0,
        'wallets' => []
      ];
    }

    return $groups;
  }

  static function xummlogin_rank_wallets($wallets, $find_wallet = '', $levels = array()){

    // Sort descending
    arsort($wallets);

    // Return values' default
    $rank       = 0;
    $group_rank = 0;

    // Go through each and find position
    $last_balance = 0;
    $wallet_index = 0; // for overall position
    $group_index  = 0; // for group position

    foreach ($wallets as $r_address => $balance) {

      // Increment overall ranking
      $wallet_index++;

      // Get the rank based if there's a new balance or tied with the previous one
      $rank = ($balance != $last_balance) ? $wallet_index : $rank;

      // Increment group ranking, but reset if it is a new one
      if( count($levels) > 0 && ( ($balance >= $levels[0] && $balance < $levels[1]) || ($balance >= $levels[0] && $levels[1] == -1) ) ){
        $group_index++;

        $group_rank = ($balance != $last_balance) ? $group_index : $group_rank;
      }

      // Keep track of the last balance
      $last_balance = $balance;

      // Check if we need to find a particular wallet 
      if( $find_wallet != '' && $find_wallet == $r_address ){
        return [$rank, $group_rank];
      }
    }
  }

  static function xummlogin_get_user_wallet(){

    // First see if the wallet matches a logged in user
    $wallet = is_user_logged_in() ? get_user_option('xrpl-r-address', get_current_user_id()) : '';

    // If not check if there is one in a session cookie
    $wallet = ($wallet == '') ? Xummlogin_utils::xummlogin_encrypt_decrypt( Xummlogin_utils::xummlogin_get_cookie_data('xrpl-r-address'), 'decrypt' ) : $wallet;

    return $wallet;
  }
}
?>