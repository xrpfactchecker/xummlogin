<?php
define('DATA_DIR', 'xl_xrpldata');

// Setup namespaces for the WebSockets
use WSSC\WebSocketClient;
use WSSC\Components\ClientConfig;

function fetch_txs($ledger_index_start, $ledger_index_end = 0){
  global $active_voting, $voting_controller, $voting_wallet;

  // You know the deal
  $debug = false;

  // To keep track of the number of calls made, for debugging purposes only
  $call_count = 0;

  // Socket config
  $config = new ClientConfig();
  $config->setHeaders([
    'Connection'   => 'Upgrade',
    'Upgrade'      => 'websocket',
    'Content-Type' => 'application/json'
  ]);
  $config->setContextOptions(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);

  // Open the connection and send request
  $socket = new WebSocketClient('wss://xrplcluster.com', $config);

  // Build API request to send
  $request = [
    'id'               => 2,
    'command'          => 'account_tx',
    'account'          => $voting_wallet,
    'ledger_index_min' => ($ledger_index_start == 0 ? -1 : $ledger_index_start),
    'ledger_index_max' => ($ledger_index_end == 0 ? -1 : $ledger_index_end),
    'binary'           => false,
    'limit'            => 200,
    'forward'          => true,
  ];

  // Empty array to hold the returned objects
  $votes = [];

  // Start making calls and keep going until all fetched
  $stop = false;
  while (!$stop) {

    // Send request and get response
    $socket->send(json_encode($request));
    $response = json_decode($socket->receive());

    // Make sure we got a success response back
    if( $response->status == 'success' ){

      // Output if debugging
      if($debug){ echo 'Call ' . ++$call_count . ': success<br>'; }

      // Get trustline object
      $transactions = $response->result->transactions;

      // Loop through transactions and get the ones we're interested in
      foreach ($transactions as $transaction) {
        
        // We only care for the payment type
        if( $transaction->tx->TransactionType == 'Payment' ){

          // If it's a payment and it has two memos, then it is likely a poll vote
          if( property_exists($transaction->tx, 'Memos') && count($transaction->tx->Memos) == 2 ){

            // Get the wallet of the voter
            $tx_wallet = $transaction->tx->Account;

            // Get the voting name
            $tx_voting = hex2bin($transaction->tx->Memos[0]->Memo->MemoData);

            // Make sure the voter is not the controller start/end vote 
            // and then that the vote is for the active voting, otherwise ignore
            if( $tx_wallet != $voting_controller && $tx_voting == $active_voting ){

              // Get transaction details
              $tx_vote   = hex2bin($transaction->tx->Memos[1]->Memo->MemoData);
              $tx_date   = $transaction->tx->date + RIPPLE_EPOCH;

              // Update the wallet vote
              $votes[$tx_wallet] = $tx_vote;
            }
          }
        }
      }

      // Check if we need to fetch another batch
      if( !$stop && property_exists($response->result, 'marker') ){
        $request['marker'] = $response->result->marker;
      }
      else{
        $stop = true; 
      }
    }
    else{
      // Output if debugging
      if($debug){ echo 'Call ' . ++$call_count . ': error<br>'; }
      
      // Stop only if we don't have a marker, otherwise retry
      $stop = !isset($request['marker']);
    }
  }

  // Return votes
  return $votes;
}

function fetch_tls($account, $currency){

  // API URL
  $api_url = 'https://api.xrpscan.com/api/v1/account/' . $account . '/trustlines';

  // Config
  $config = [
    'ssl' => [
      'allow_self_signed' => true,
      'verify_peer'       => false,
      'verify_peer_name'  => false,
    ]
  ];

  // Set a one time request to XRPScan's API
  $result     = file_get_contents($api_url, false, stream_context_create($config));
  $trustlines = json_decode($result);

  // Go through lines and add to balance
  $balances = [];
  if( is_array( $trustlines ) ){
    // Go through each and add to balances as needed
    foreach ($trustlines as $index => $trustline) {
      
      // Set vars
      $currency = $trustline->specification->currency;
      $wallet   = $trustline->specification->counterparty;
      $balance  = $trustline->state->balance;

      // Add to balances if their balance is greater than 0 and that this is the right currency
      if( (float)$balance * -1 > 0 && $currency == $currency){
        $balances[$wallet] = (float)$balance * -1;
      }
    }
  }

  // Return balances
  return $balances;
}

function fetch_final_tls($account, $currency, $ledger_index = 0){

  // You know the deal
  $debug = false;

  // To keep track of the number of calls made, for debugging purposes only
  $call_count = 0;

  // Socket config
  $config = new ClientConfig();
  $config->setHeaders([
    'Connection'   => 'Upgrade',
    'Upgrade'      => 'websocket',
    'Content-Type' => 'application/json'
  ]);
  $config->setContextOptions(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);

  // Open the connection and send request
  $socket = new WebSocketClient('wss://xrplcluster.com', $config);

  // Build API request to send
  $request = [
    'id'      => 1,
    'limit'   => 400,
    'command' => 'account_lines',
    'account' => $account
  ];

  // Check if we need to pull from a specific ledger
  if( $ledger_index != 0 ){
    $request['ledger_index'] = $ledger_index;
  }

  // Empty array to hold the returned objects
  $balances = [];

  // Start making calls and keep going until all fetched
  $stop = false;
  while (!$stop) {

    // Send request and get response
    $socket->send(json_encode($request));
    $response = json_decode($socket->receive());

    // Make sure we got a success response back
    if( $response->status == 'success' ){

      // Output if debugging
      if($debug){ echo 'Call ' . ++$call_count . ': success<br>'; }

      // Get trustline object
      $trustlines = $response->result->lines;

      // Loop through transactions and get the ones we're interested in
      foreach ($trustlines as $trustline) {
        //echo $trustline->account . '<br>';
        // Add to balances if their balance is greater than 0 and that this is the right currency
        if( (float)$trustline->balance * -1 > 0 && $trustline->currency == $currency){
          $balances[$trustline->account] = (float)$trustline->balance * -1;
        }
      }

      // Check if we need to fetch another batch
      if( !$stop && property_exists($response->result, 'marker') ){
        $request['marker'] = $response->result->marker;
      }
      else{
        $stop = true; 
      }
    }
    else{
      // Output if debugging
      if($debug){ echo 'Call ' . ++$call_count . ': error<br>'; }
      
      // Stop only if we don't have a marker, otherwise retry
      $stop = !isset($request['marker']);
    }
  }

  // Return balances
  return $balances;
}

function save_data($type, $data, $active_voting = ''){

  // Create results directory if it doesn't exiists
  if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR)) {
      mkdir($_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR, 0755, true);
  }

  // Get the file name based on an active voting or not - balances are onoing and not tied to voting
  if( $active_voting != '' ){
    $active_voting_hash = substr(md5($active_voting), 10);
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $active_voting_hash . '-' . $type . '.json';
  }
  else{
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $type . '.json';
  }

  // Save to json file
  file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

function load_data($type, $active_voting = ''){

  // Get the file name based on an active voting or not - balances are onoing and not tied to voting
  if( $active_voting != '' ){
    $voting_hash = substr(md5($active_voting), 10);
    $filename    = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $voting_hash . '-' . $type . '.json';
  }
  else{
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $type . '.json';
  }

  // Load to json file
  $content = file_exists($filename) ? file_get_contents($filename) : false;

  if( $content !== false ){
    $content = json_decode($content);
  }

  // Return content
  return $content;
}

function xrpl_currency($currency){

  // Leave it as is if it 3 character
  if( strlen($currency) == 3 ){
    return $currency;
  }
  // Otherwise convert to hex and pad to 40 characters long
  else{
    return str_pad( strtoupper( bin2hex($currency) ), 40, '0' );
  } 
}