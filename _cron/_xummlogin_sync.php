<?php
// Make sure we have enough memory to load the potentially large JSON file from XRPScan.
ini_set('memory_limit', '512M');

// Make sure to not cache this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Database class
require_once 'functions.php';
require_once 'db.php';

// Get the WordPress config file to get the options
require_once __DIR__ . '/../../../../wp-config.php';

// Load all vendor classes (mainly for the WebSockets)
require_once __DIR__ . '/../vendor/autoload.php';

// Used to calculate a tx time
const RIPPLE_EPOCH = 946684800; // Jan 2000 00:00:00 UTC

// Connect to DB and check that we have an active voting in place
$database = new Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Grab the active voting info
$xumm_api_secret       = $database->wp_get_option('xummlogin_api_secret');
$active_voting         = $database->wp_get_option('xummlogin_voting_active');
$active_voting_choices = $database->wp_get_option('xummlogin_voting_active_choices');
$voting_controller     = $database->wp_get_option('xummlogin_voting_controller');
$voting_wallet         = $database->wp_get_option('xummlogin_voting_wallet');
$voting_wallet_minimum = (int)$database->wp_get_option('xummlogin_voting_vote_minimum');
$voters_currency       = $database->wp_get_option('xummlogin_trustline_currency');
$voters_trustline      = $database->wp_get_option('xummlogin_trustline_issuer');
$ledger_index_start    = (int)$database->wp_get_option('xummlogin_voting_active_start_ledger');
$ledger_index_end      = (int)$database->wp_get_option('xummlogin_voting_active_end_ledger');

// Close database
$database->close();

// Grab the passed token to match against the XUMM API Secret
$access_token = isset($_GET['token']) ? $_GET['token'] : '';

// Check if the token passed matches the one stored
if( $access_token == '' || $access_token != $xumm_api_secret ){ exit('Error: Access Denied'); }

// Detect if there's an active voting or not
$has_active_voting = ( $active_voting != '' && $ledger_index_start > 0 );

// Get the hash value of the active vote for the json files
$voting_hash = substr(md5($active_voting), 10);

/**
 *
 * STEP 1 - FETCH VOTING TRANSACTIONS IF VOTING IS ACTIVE
 *
 */

// Array to hold all votes, using the wallet as the key and their vote as the value
$votes = [];

if( $has_active_voting ){

  // Make sure we're good to go before starting the process
  if( $voting_controller == '' ){ exit('Error: No controller address found!'); }
  if( $voting_wallet == '' ){ exit('Error: No voting wallet addre ss found!'); }
  if( $active_voting == '' ){ exit('Error: No active voting!'); }
  if( $ledger_index_start == '' ){ exit('Error: Active voting has not yet started!'); }

  // Check if final votes file exists already so we only run it once
  $filename         = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $voting_hash . '-votes-final.json';
  $has_final_votes  = file_exists($filename);

  // Get final balance if the voting is done and we don't have the results yet
  if( $ledger_index_end == 0 || !$has_final_votes ){

    // Fetch all votings
    $votes = fetch_txs($ledger_index_start, $ledger_index_end);

    // Save to file
    $filetype = $ledger_index_end > 0 ? 'votes-final' : 'votes';
    save_data($filetype, $votes, $active_voting);
  }
}

/**
 *
 * STEP 2 - ALWAYS FETCH TRUSTLINE/CURRENCY BALANCES
 *
 */

// Check if final balance file exists already so we only run it once
$filename           = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $voting_hash . '-balances-final.json';
$has_final_balances = file_exists($filename);

// Array to hold all balances, using the wallet as the key and their balance as the value
$balances = [];

// Get final balance if the voting is done and we don't have the results yet
if( $ledger_index_end > 0 && !$has_final_balances ){

  // Fetch all balances for this trustline for the end ledger from the xrpl api
  $balances = fetch_final_tls($voters_trustline, $voters_currency, $ledger_index_end);

  // Save to file
  if( count($balances) > 0 ){
    save_data('balances-final', $balances, $active_voting);  
  }
}
// Else grab the balance from XRPScan for performance reason
else{

  // Fetch in progress balances for this trustline from xrpscan api
  $balances = fetch_tls($voters_trustline, $voters_currency);

  // Save to file
  if( count($balances) > 0 ){
    save_data('balances_' . strtolower($voters_currency), $balances);  
  }
}

/**
 *
 * STEP 3 - PARSE VOTING AND HOLDER BALANCES TO CALCULATE THE RESULTS IF VOTING IS ACTIVE
 *
 */

// Array to hold all results, using the wallet as the key and their vote as the value
$results = [];

if( $has_active_voting ){
  // Check if final votes file exists already so we only run it once
  $filename          = $_SERVER['DOCUMENT_ROOT'] . '/../' . DATA_DIR . '/' . $voting_hash . '-results-final.json';
  $has_final_results = file_exists($filename);

  // Get final balance if the voting is done and we don't have the results yet
  if( $ledger_index_end == 0 || !$has_final_results ){

    // Grab the proper versions of the json files based in the active voting status
    $vote_file           = $ledger_index_end > 0 ? 'votes-final' : 'votes';
    $balance_file        = $ledger_index_end > 0 ? 'balances-final' : 'balances_' . strtolower($voters_currency);
    $active_voting_file  = $ledger_index_end > 0 ? $active_voting : ''; // The ongoing balance file is not associated with any voting

    // Load votes and balances from files if the current process has none
    $votes    = count($votes) > 0 ? $votes : (array)load_data($vote_file, $active_voting);
    $balances = count($balances) > 0 ? $balances : (array)load_data($balance_file, $active_voting_file);

    // Keep track of counts
    $total_votes   = 0;
    $total_balance = 0;

    // Start by builind the choices array
    $choices = array_map('trim', explode(',', $active_voting_choices));
    foreach ($choices as $choice) {
      $results[$choice] = [
        'count'        => 0,
        'total'        => 0,
        'perc_vote'    => 0.00,
        'perc_balance' => 0.00,
      ];
    }

    // Go through each votes and calculate results
    foreach ($votes as $wallet => $vote) {

      // Get the vote weight based of the wallet address
      $balance = array_key_exists($wallet, $balances) ? $balances[$wallet] : 0;
      
      // If there's a minimum, see if they meets it
      $meets_threshold = ($voting_wallet_minimum == 0) ? true : $balance >= $voting_wallet_minimum;

      // Add their vote to the results if they have a balance greater than 0
      if( $balance > 0 && $meets_threshold && array_key_exists($vote, $results)){

        // Update result for this vote
        $results[$vote]['count']++;
        $results[$vote]['total'] += $balance;

        // Update global value for post processing
        $total_votes++;
        $total_balance += $balance;
      }
    }

    // Go through each votes and calculate their results
    if( $total_votes > 0 ){
      foreach ($results as $vote => &$result) {
        $result['perc_vote']    = ($result['count'] / $total_votes) * 100;
        $result['perc_balance'] = ($result['total'] / $total_balance) * 100;
      }
    }

    // Save to file
    $filetype = $ledger_index_end > 0 ? 'results-final' : 'results';
    save_data($filetype, $results, $active_voting);
  }
}

/**
 *
 * DONE - OUTPUT RESULTS
 *
 */

  echo 'Votes: ' . count($votes) . '<br>';
  echo 'Balances: ' . count($balances) . '<br>';
  echo 'Results:<br><pre>';
  print_r($results);
?>