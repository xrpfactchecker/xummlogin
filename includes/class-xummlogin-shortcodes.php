<?php
class Xummlogin_ShortCodes{

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
   * @param      string    $plugin_name       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version     = $version;

		add_shortcode('xummlogin'   , [$this, 'xummlogin_button']);
		add_shortcode('xummline'    , [$this, 'xummlogin_trustline_link']);
		add_shortcode('xummpayment' , [$this, 'xummlogin_payment_link']);
		add_shortcode('xummvoting'  , [$this, 'xummlogin_vote_link']);
    add_shortcode('xummresults' , [$this, 'xummlogin_vote_results']);
    add_shortcode('xummmessages', [$this, 'xummlogin_action_messages']);
    add_shortcode('xummsyncinfo', [$this, 'xummlogin_sync_info']);
    add_shortcode('xummtoken'   , [$this, 'xummlogin_token_price']);
    add_shortcode('xummuser'    , [$this, 'xummlogin_user_info']);
    add_shortcode('xummrichlist', [$this, 'xummlogin_rich_list']);
	}

  public function xummlogin_button( $atts = array() ) {

    // Merge params
    extract(shortcode_atts(array(
     'form'     => 'false',
     'return'   => 'button',
     'label'    => __('XUMM Signin'),
    ), $atts));

    // Include the form or just the button
    $show_form = $form != 'false';

    // Build URL for the signin
    $url = '?xl-' . ACTION_SIGNIN;

    // Add redirect if we have one
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '';
    if( $redirect_to != '' ){
      $url = add_query_arg( 'redirect', urlencode($redirect_to), $url);
    }

    // Check what we need to return
    if( $return == 'url' ){
      $signin_cta = $url;
    }
    else{

      // Get the label if we're returning a button or anchor
      $cta_label  = ( $return == 'button' ) ? file_get_contents(dirname(plugin_dir_path( __FILE__ )) .'/public/images/signin.svg') : $label;
      $signin_cta = '<a href="' . $url . '" class="xl-button xl-button-' . ACTION_SIGNIN . '">' . $cta_label . '</a>';
    }

    // Add standard form if enabled
    if( $show_form ){

      // Get wordpress login form
      $login_form = wp_login_form(['echo' => false]);

      // Get position of where to insert the XUMM button
      $pos = strpos($login_form, '<p class="login-remember">'); // NOT IDEAL

      // Insert XUMM Login button
      $login_form = substr_replace($login_form, $signin_cta, $pos, 0);

      return $login_form;
    }
    else{
      return $signin_cta;
    }
  }

  public function xummlogin_user_info( $atts = array() ) {

    // Get some settings
    $has_active_vote   = (int)get_option('xummlogin_voting_active_start_ledger') != 0;    
    $currency          = get_option('xummlogin_trustline_currency');
    $rank_levels       = get_option('xummlogin_rank_levels');
    $rank_tiers        = get_option('xummlogin_rank_tiers');
    $excluded_wallets  = get_option('xummlogin_excluded_wallets');

    // Merge params
    extract(shortcode_atts(array(
     'return'   => 'card',
     'trade'    => 'true',
     'wallet'   => ''
    ), $atts));

    // If not wallet param was passes, get the wallet from the logged in user if not from the save wallet in session
    if( $wallet == '' ){
      $wallet = Xummlogin_utils::xummlogin_get_user_wallet();
    }

    // Get the wallet's balances from the cached file
    $saved_balances = (array)Xummlogin_utils::xummlogin_load_data('balances_' . strtolower($currency));

    // If there's still no wallet, we'll show a login avatar with an emppty card
    $has_wallet = ($wallet != '');
    $level_slug = 'none';
    if( !$has_wallet ){
    
      $wallet     = 'r' . str_pad('', 33, '*');
      $balance    = 0.00;
      $avatar     = 'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20id%3D%22svg843%22%20version%3D%221.1%22%20viewBox%3D%220%200%2016%2016%22%20class%3D%22bi%20bi-qr-code-scan%22%20fill%3D%22%23ffffff%22%20height%3D%2216%22%20width%3D%2216%22%3E%3Cdefs%20id%3D%22defs847%22%20%2F%3E%3Cpath%20id%3D%22path833%22%20d%3D%22M0%20.5A.5.5%200%200%201%20.5%200h3a.5.5%200%200%201%200%201H1v2.5a.5.5%200%200%201-1%200v-3Zm12%200a.5.5%200%200%201%20.5-.5h3a.5.5%200%200%201%20.5.5v3a.5.5%200%200%201-1%200V1h-2.5a.5.5%200%200%201-.5-.5ZM.5%2012a.5.5%200%200%201%20.5.5V15h2.5a.5.5%200%200%201%200%201h-3a.5.5%200%200%201-.5-.5v-3a.5.5%200%200%201%20.5-.5Zm15%200a.5.5%200%200%201%20.5.5v3a.5.5%200%200%201-.5.5h-3a.5.5%200%200%201%200-1H15v-2.5a.5.5%200%200%201%20.5-.5ZM4%204h1v1H4V4Z%22%20%2F%3E%3Cpath%20id%3D%22path835%22%20d%3D%22M7%202H2v5h5V2ZM3%203h3v3H3V3Zm2%208H4v1h1v-1Z%22%20%2F%3E%3Cpath%20id%3D%22path837%22%20d%3D%22M7%209H2v5h5V9Zm-4%201h3v3H3v-3Zm8-6h1v1h-1V4Z%22%20%2F%3E%3Cpath%20id%3D%22path839%22%20d%3D%22M9%202h5v5H9V2Zm1%201v3h3V3h-3ZM8%208v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8H8Zm2%202H9V9h1v1Zm4%202h-1v1h-2v1h3v-2Zm-4%202v-1H8v1h2Z%22%20%2F%3E%3Cpath%20id%3D%22path841%22%20d%3D%22M12%209h2V8h-2v1Z%22%20%2F%3E%3Cpath%20id%3D%22path1391%22%20d%3D%22M%2012.743131%2C7.0309344%20C%2013.015616%2C6.7348118%2012.993327%2C6.2766755%2012.693434%2C6.007659%2012.393569%2C5.7386182%2011.929567%2C5.7605578%2011.657181%2C6.0567013%20L%209.7031555%2C8.1803531%208.9131785%2C7.3612984%20C%208.6337553%2C7.0715902%208.1693784%2C7.0604336%207.8759771%2C7.336339%207.5825737%2C7.6122443%207.5712439%2C8.07078%207.8506698%2C8.3604881%20l%201.3339008%2C1.3829702%20c%200.140547%2C0.1456906%200.3361063%2C0.2271343%200.5398783%2C0.2247629%200.203747%2C-0.00237%200.3973351%2C-0.088308%200.5343641%2C-0.2372426%20z%20M%207.2125473%2C8.8492283%206.3614189%2C8.0012205%207.2125473%2C7.153212%20C%207.3965759%2C6.9715055%207.4886009%2C6.8175536%207.4886009%2C6.6913568%20c%200%2C-0.1312381%20-0.092025%2C-0.287711%20-0.2760536%2C-0.4694182%20C%207.0285182%2C6.0402315%206.8726198%2C5.9493779%206.7448015%2C5.9493779%20c%20-0.1277947%2C0%20-0.2836937%2C0.090854%20-0.4677466%2C0.2725607%20L%205.4335896%2C7.0699214%204.5824612%2C6.2219386%20C%204.3984584%2C6.0402315%204.24251%2C5.9493779%204.1147167%2C5.9493779%20c%20-0.1226782%2C0%20-0.2785773%2C0.090854%20-0.4677465%2C0.2725607%20-0.1839791%2C0.1817072%20-0.2760049%2C0.3382052%20-0.2760049%2C0.4694182%200%2C0.1261968%200.092026%2C0.2801487%200.2760557%2C0.4618552%20L%204.4981473%2C8.0012205%203.647021%2C8.8492283%20C%203.4629674%2C9.0309348%203.3709403%2C9.1874077%203.3709403%2C9.3186464%20c%200%2C0.1261961%200.092027%2C0.280148%200.2760556%2C0.4618552%200.1891435%2C0.1766652%200.3450426%2C0.2649974%200.4677459%2C0.2649974%200.1277932%2C0%200.2837166%2C-0.090854%200.4677445%2C-0.2725603%20L%205.4336147%2C8.9325189%206.2770549%2C9.7729387%20c%200.1840529%2C0.1817071%200.3399519%2C0.2725603%200.4677466%2C0.2725603%200.1278183%2C0%200.2837167%2C-0.088333%200.4677458%2C-0.2649974%200.1840286%2C-0.1817072%200.2760536%2C-0.3356591%200.2760536%2C-0.4618552%200%2C-0.1312387%20-0.092025%2C-0.2877116%20-0.2760536%2C-0.4694181%20z%22%20style%3D%22clip-rule%3Aevenodd%3Bfill%3A%233052ff%3Bfill-opacity%3A1%3Bfill-rule%3Aevenodd%3Bstroke-width%3A0.18897638%3Bstroke-miterlimit%3A4%3Bstroke-dasharray%3Anone%3Bstroke%3A%23ffffff%3Bstroke-opacity%3A1%22%20%2F%3E%3C%2Fsvg%3E';
      $rank       = '?';
      $level      = ( $rank_levels != '' ) ? '?' : '';
      $vote       = '';
    }
    else{

      // Get the XRPL avatar
      $avatar = 'https://xumm.app/avatar/' . $wallet . '.png';

      // Get the users token balabce
      $balance = (array_key_exists($wallet, $saved_balances) ? (float)$saved_balances[ $wallet ] : 0.00);

      // Start by removing the excluded wallets from the list of holders
      if( $excluded_wallets != '' ){
        $excluded_wallets = array_map('trim', explode(',', $excluded_wallets));

        // Go through each one and remove them from the array that's use for the ranking
        foreach ($excluded_wallets as $excluded_wallet) {
          unset( $saved_balances[ $excluded_wallet ] );
        }
      }

      // Get the user's rank level if they exists and if the user has a balance
      $level = ( $rank_levels != '' ) ? '-' : ''; 
      $rank  = '-';  

      if( $rank_levels != '' && $balance > 0 ){

        // Get ranks list from settings
        $ranks = array_map('trim', explode(',', $rank_levels));

        // Go through ranks to find user's level
        foreach ($ranks as $rank_index => $rank_amount) {
          if( (float)$balance < (float)$rank_amount ){
            break;
          }
          elseif( $rank_index == count($ranks) - 1 ){
            $rank_index++;
          }
        }

        // Add empty slot for holders that have to low to be in any groups
        array_unshift($ranks, '0');

        // Set the rank from and to
        $rank_from = $ranks[ $rank_index ];
        $rank_to   = ( $rank_index + 1 == count($ranks) ) ? -1 : $ranks[ $rank_index + 1 ];

        // Get the user's rank
        $user_ranks = Xummlogin_utils::xummlogin_rank_wallets($saved_balances, $wallet, [$rank_from, $rank_to]);
        list($rank, $group_rank) = $user_ranks;

        // Get the tiers if they exists, otherwise just use the level brackets
        if( $rank_tiers != '' ){

          // Get tiers list from settings
          $tiers = array_map('trim', explode(',', $rank_tiers));
          array_unshift($tiers, __('Holder')); // For holders with small balances

          // Set the rank level
          $level = $tiers[ $rank_index ];

          // Get the slug for the class name
          $level_slug = strtolower( preg_replace( '/[^a-z0-9 ]/i', '', $level) );

          // Tweak display based on if there's tiers or not
          $level = ($rank_tiers != '') ? $level . '#' . $group_rank : $level . ' (#' . $group_rank . ')';
        }
        else{

          // Add the from
          $level = $rank_from;

          // Add the to depending if it is the top or not 
          $level .= ( $rank_to == -1 ) ? ' ' . __('and up') : ' ' . __('to') . ' ' . $rank_to;
        }
      }
      // Get just the overall rank
      elseif( $balance > 0 ){

        // Get the user's rank
        $user_ranks = Xummlogin_utils::xummlogin_rank_wallets($saved_balances, $wallet);
        list($rank) = $user_ranks;
      }

      // Get the user's last vote if there is an active voting and they voted
      $vote = '';
      if( $has_active_vote ){
        $active_vote = get_option('xummlogin_voting_active');    
        $saved_votes = (array)Xummlogin_utils::xummlogin_load_data('votes', $active_vote);
        $vote        = (array_key_exists($wallet, $saved_votes) ? $saved_votes[ $wallet ] : __('No active vote'));
      }
    }

    // Return the right info based on the return param
    switch ($return) {

      case 'wallet':
        return '<div class="xl-card-wallet">' . $wallet . '</div>';
        break;

      case 'balance':
        return '<div class="xl-card-balance">' . round($balance, 4) . ' ' . $currency . '</div>';
        break;

      case 'avatar':
        return '<img class="xl-card-avatar" width="65" height="65" src="' . $avatar . '">';
        break;

      case 'rank':
        return '<div class="xl-card-rank">' . $rank . '</div>';
        break;

      case 'level':
        return '<div class="xl-card-level">' . $level . '</div>';
        break;

      case 'vote':
        return '<div class="xl-card-vote">' . $vote . '</div>';
        break;

      default:

        // extra classes on the card
        $card_classes = [];
        if( $level != '' ){
          $card_classes[] = 'xl-level-' . $level_slug;
        }
        if( !$has_wallet ){
          $card_classes[] = 'xl-no-wallet';
        }        

        // Set trading link if it was enabled
        $trade_link = '';

        if( $trade == 'true' ){
          $issuer        = get_option('xummlogin_trustline_issuer');
          $currency_code = Xummlogin_utils::xummlogin_currency( $currency );

          $trade_link = ' &bull; <a href="https://xumm.app/detect/xapp:xumm.dex?base=' . $currency_code . '+' . $issuer . '&quote=xrp" target="_blank" title="' . __('Trade on the XUMM DEX xApp!') . '">' . __('Trade') . '</a>';
        }
        
        // Get the signin link from the short code
        $signin_url = do_shortcode('[xummlogin return="url"]');

        // build the card's markup
        $holders_card = 
          '<div class="xl-card' . ( count($card_classes) > 0 ? ' ' . implode(' ', $card_classes) : '' ) . '">' .
            '<div class="xl-card-header">' .
              '<div class="xl-card-wallet"><strong> ' . substr_replace($wallet, '</strong>', 9, 0) . '</div>' .
            '</div>' .
            '<div class="xl-card-body">' .
              '<div class="xl-card-avatar"><a href="' . $signin_url . '"><img class="xl-card-avatar" width="65" height="65" src="' . $avatar . '"></a></div>' .
              '<div class="xl-card-balance"><strong>' . __('Balance') . ': </strong>' . round($balance, 4) . ' ' . $currency . $trade_link . '</div>' .
              '<div class="xl-card-rank"><strong>' . __('Rank') . ': </strong>' . $rank . ' / ' . count($saved_balances) . '</div>';

              // Add level if any
              if( $level != '' ){

                $holders_card .=
                '<div class="xl-card-level"><strong>' . __('Level') . ': </strong>' . $level . '</div>';
              }

              // Add last active vote if any
              if( $vote != '' ){
                $holders_card .= '<div class="xl-card-level"><strong>' . __('Vote') . ': </strong>' . $vote . '</div>';
              }

              // Add note to sign in if there's no wallet
              if( !$has_wallet ){
                $holders_card .= '<div class="xl-card-note">' . __('Click on QR Image to Sign In') . '</div>';
              }

            $holders_card .=
            '</div>' .
            '<div class="xl-card-footer">' .
            '</div>' .
          '</div>';

        return $holders_card;
        break;
    }
  }

  public function xummlogin_rich_list( $atts = array() ) {

    // Get some settings
    $currency          = get_option('xummlogin_trustline_currency');
    $rank_levels       = get_option('xummlogin_rank_levels');
    $rank_tiers        = get_option('xummlogin_rank_tiers');
    $excluded_wallets  = get_option('xummlogin_excluded_wallets');

    // Merge params
    extract(shortcode_atts(array(
     'type'      => 'flat',
     'infinity'  => '∞',
     'count'     => '100',
     'precision' => '4'
    ), $atts));

    // Get the wallet's balances from the cached file
    $holders = (array)Xummlogin_utils::xummlogin_load_data('balances_' . strtolower($currency));

    // Sort descending
    arsort($holders);    

    // Get a saved wallet if any
    $wallet = Xummlogin_utils::xummlogin_get_user_wallet();

    // Start by removing the excluded wallets from the list of holders
    if( $excluded_wallets != '' ){
      $excluded_wallets = array_map('trim', explode(',', $excluded_wallets));

      // Go through each one and remove them from the array that's use for the ranking
      foreach ($excluded_wallets as $excluded_wallet) {
        unset( $holders[ $excluded_wallet ] );
      }
    }

    // Check list style, with groupings or flat
    if( $type != 'flat' && $rank_levels != '' ){

      // Initialie the groupings based on the ranks and levels
      $groups = Xummlogin_utils::xummlogin_initialize_richlist_grouping($rank_levels, $rank_tiers);

      // Go through holder's balance and add to respective groups
      foreach ($holders as $holder_wallet => $holder_balance) {

        // Go through each group and insert in the proper one
        foreach ($groups as &$group) {

          // Check the wallet's balance against the group's min and max
          if( (float)$holder_balance >= (float)$group['min'] && ( is_null( $group['max'] ) || (float)$holder_balance < (float)$group['max'] ) ){

            // Add to group's wallet
            $group['wallets'][] = [
              $holder_wallet => $holder_balance
            ];

            // Update group's total
            $group['total'] += $holder_balance;

            // Stop checking for groups
            break;
          }
        }
      }

      // Remove reference so we can reuse
      unset($group);

      // Reverse group to show largest to smallest
      $groups = array_reverse($groups);

      // Group table headings
      $output  = '<table class="xl-richlist xl-richlist-tiers">';
        $output .= '<thead>';
          $output .= '<tr>';
          $output .= '<th>' . ($rank_tiers != '' ? __('Tiers') : __('# Accounts')) . '</th>';
          $output .= '<th>' . __('Balance From') . '</th>';
          $output .= '<th>' . __('To') . '</th>';
          $output .= '<th>' . __('Total') . '</th>';
          $output .= '</tr>';
        $output .= '</thead>';

      // Go through each group and output
      foreach ($groups as $group) {
        $output .= '<tbody>';
          $output .= '<tr>';
            $output .= '<td>' . ($rank_tiers != '' ? $group['tier'] . ' <span>(' . count($group['wallets']) . ')' : count($group['wallets'])) . '</span></td>';
            $output .= '<td>' . $group['min'] . '</td>';
            $output .= '<td>' . ( !is_null($group['max']) ? $group['max'] : $infinity ) . '</td>';
            $output .= '<td>' . number_format($group['total'], $precision) . ' ' . $currency . '</td>';
          $output .= '</tr>';
          $output .= '<tr style="display:none;"><td colspan="4"></tr>';
        $output .= '</tbody>';
        
      }
      $output .= '</table>';

      // Output the holder's array in JS for the expanding when clicking on the row
      wp_localize_script($this->plugin_name, 'currency', [$currency]);
      wp_localize_script($this->plugin_name, 'precision', [$precision]);
      wp_localize_script($this->plugin_name, 'holders', $groups);
    }
    // Output a flat ranking list
    else{
      
      // Go through each wallet and output
      $output  = '<table class="xl-richlist xl-richlist-flat">';
        $output .= '<thead>';
          $output .= '<tr>';
          $output .= '<th colspan="2">' . __('Wallets') . '</th>';
          $output .= '<th>' . __('Balance') . '</th>';
          $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';        

        // Var to hold progress
        $holders_count = 0;
        $last_balance  = 0;

        // Go through each holders and output until we reach the limit
        foreach ($holders as $holder_wallet => $holder_balance) {

          // Get the rank based if there's a new balance or tied with the previous one
          $rank = ($holder_balance != $last_balance) ? $holders_count + 1 : $rank;

          // Check if this holder matches the saved wallet
          $row_class = ($holder_wallet == $wallet) ? ' class="xl-is-user"' : '';

          // Setup row
          $output .= '<tr' . $row_class . '>';
            $output .= '<td>' . $rank . '</td>';
            $output .= '<td>' . $holder_wallet . '</td>';
            $output .= '<td>' . number_format($holder_balance, $precision) . ' ' . $currency . '</td>';
          $output .= '</tr>';

          // Stop once we reach the number needed
          if( ++$holders_count == $count ){
            break;
          }

          // Keep track of the last balance
          $last_balance = $holder_balance;
        }
      
      // Close table
      $output .= '</tbody>';        
      $output .= '</table>';
    }

    return $output;
  }

	public function xummlogin_trustline_link( $atts = array() ) {

    // Merge params
    extract(shortcode_atts(array(
     'anchor'   => 'true',
     'label'    => __('Set Trustline'),
    ), $atts));
    
    // Build URL so set the trustline
    $url = '?xl-' . ACTION_TRUSTLINE;

    // We're done, return the the whole anchor or just the link
    $content = ($anchor == 'true' ? '<a href="' . $url . '" class="xl-button xl-button-' . ACTION_TRUSTLINE . '">' . $label . '</a>' : $url);
    return $content;
	}

	public function xummlogin_payment_link( $atts = array() ) {

    // No params for now
    extract(shortcode_atts(array(
      'anchor'   => 'true',
      'amount'      => '1', //drops in XRP
      'destination' => '',
      'label'       => __('Send Tip')
    ), $atts));
    
    // Build URL so set the payment
    $url = '?xl-' . ACTION_PAYMENT;
    $url = add_query_arg( 'amount', $amount, $url);
    $url = add_query_arg( 'destination', $destination, $url);

    // We're done, return the the whole anchor or just the link
    $content = ($anchor == 'true' ? '<a href="' . $url . '" class="xl-button xl-button-' . ACTION_PAYMENT . '">' . $label . '</a>' : $url);
    return $content;
	}

	public function xummlogin_vote_link( $atts = array() ) {

    // Make sure we have a vote
    if( $atts['vote'] == ''){
      return __('Vote is missing!');
    }

    // Get active voting
    $active_voting = get_option('xummlogin_voting_actives');

    // Set/merge the params
    extract(shortcode_atts(array(
      'admin'         => 'false',
      'anchor'        => 'true',
      'active_voting' => $active_voting,
      'label'         => $atts['vote'],
      'vote'          => ''
    ), $atts));

    // Build URL so set the voting info
    $url = '?';

    // Build voting link
    $voting_args = bin2hex($vote);
    $url         = add_query_arg( 'xl-' . ACTION_VOTING, $voting_args, $url);

    // We're done, return the the whole anchor or just the link
    $content = ($anchor == 'true' ? '<a href="' . $url . '" class="xl-button xl-button-' . ACTION_VOTING . '">' . ucfirst($label) . '</a>' : $url);
    return $content;

    // We're done, return the URL
    return '<a href="' . $url . '">' . ucfirst($vote) . '</a>';
	}

  public function xummlogin_vote_results( $atts = array() ) {

    // Set/merge the params
    extract(shortcode_atts(array(
      'active_voting' => get_option('xummlogin_voting_active'),
      'vote'          => '',
      'archived'      => '',
      'precision'     => '4',
      'use'           => 'balance',
      'return'        => 'percentage',
    ), $atts));

    // Make sure we have a vote
    if( $atts['vote'] == ''){
      return __('Vote is missing!');
    }

    // Set defaults
    $vote_total      = 0;
    $vote_percentage = 0;

    // Check if we need to use the vote's wallet balance or 1 like a normal vote
    $total_to_use    = ($use == 'balance') ? 'total' : 'count';
    $perc_to_use     = ($use == 'balance') ? 'perc_balance' : 'perc_vote';

    // Makes sure we have an active voting or an archived one
    if( $active_voting != '' || $archived != ''){

      // Prioritize archived one
      $results_to_load = $archived != '' ? $archived : $active_voting;

      // Get the results on the cached file
      $results = Xummlogin_utils::xummlogin_load_data('results', $results_to_load);

      // Make sure we got results before continuing and if we do that we got that vote's results
      if( $results !== false && array_key_exists($vote, $results) ){

        // Set results
        foreach ($results as $vote_key => $result) {
          
          // Check if we have the vote we need
          if( $vote_key == $vote ){

            $vote_total      = $result[ $total_to_use ];
            $vote_percentage = $result[ $perc_to_use ];

            break;
          }
        }
      }
    }

    // Truncate digits if needed
    if( $precision != '' ){
      $vote_total      = round( $vote_total, (int)$precision );
      $vote_percentage = round( $vote_percentage, (int)$precision );

      // Add commas to total
      $vote_total      = number_format($vote_total, (int)$precision, '.', ',');
      $vote_percentage = number_format($vote_percentage, (int)$precision, '.', ',');
    }    

    // Return perc or total depending on the args
    return ($return == 'percentage' ? $vote_percentage : $vote_total);
  }

  public function xummlogin_sync_info( $atts = array() ) {

    // Set/merge the params
    extract(shortcode_atts(array(
      'active_voting' => get_option('xummlogin_voting_active'),
      'archived'      => ''
    ), $atts));

    // Makes sure we have an active voting or an archived one
    if( $active_voting != '' || $archived != ''){

      // Get saved currency
      $trustline_currency = get_option('xummlogin_trustline_currency');

      // Prioritize archived one
      $results_to_load = $archived != '' ? $archived : $active_voting;

      // Get the results on the cached file
      $last_sync = (int)Xummlogin_utils::xummlogin_load_data('lastsync', $results_to_load);

      // Only output the stats if we have some
      if( $last_sync > 0 ){

        // Get the votes and balances from the cached JSON files    
        $votes    = (array)Xummlogin_utils::xummlogin_load_data('votes', $results_to_load);
        $balances = (array)Xummlogin_utils::xummlogin_load_data('balances_' . strtolower($trustline_currency));

        // Build the stats display
        $stats = 
          '<span class="xl-stats-votes">' . sprintf( __('%s Unique Votes') , number_format(count($votes), 0, '.', ',') ) . '</span>' .
          '<span class="xl-stats-holders">' . sprintf( __('%s Holders') , number_format(count($balances), 0, '.', ',') ) . '</span>' .
          '<span class="xl-stats-synced">' . sprintf( __('Synced %s') , ucwords( Xummlogin_utils::xummlogin_time_since($last_sync)) ) . '</span>';
      }
      else{
        $stats = __('Not Yet Synced');
      } 

      // Return the stats
      return '<div class="xl-stats">' . $stats . '</div>';
    }
  }

  public function xummlogin_token_price( $atts = array() ) {

      // Get saved trustline info
      $currency_code = get_option('xummlogin_trustline_currency');
      $base_currency = Xummlogin_utils::xummlogin_currency($currency_code);
      $base_issuer   = get_option('xummlogin_trustline_issuer');
      
      // If no base params are passed and no trustline info is set, default the base.
      if( !isset($args['base']) && ( $base_currency == '' || $base_issuer == '' ) ){
        $base_currency = 'XRP';
        $base_issuer   = '';
      }
      
      // If no currency params are passed and no trustline info is set, default the base.
      if( !isset($args['currency']) ){

        // Set the default to USD if it is in XRP
        $target_currency = $base_currency == 'XRP' ? 'USD' : 'XRP';
        $target_issuer   = $base_currency == 'XRP' ? 'rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B' : '';        
      }

      // Set up default parameters
      extract(shortcode_atts(array(
       'base'      => $base_currency . ( $base_issuer !='' ? '+' . $base_issuer : '' ),
       'precision' => '4',
       'return'    => 'container',
       'currency'  => $target_currency . ( $target_issuer !='' ? '+' . $target_issuer : '' )
      ), $atts));

      // Get response back
      $response = wp_remote_get('https://data.ripple.com/v2/exchange_rates/'.$base.'/'.$currency);

      // Check if we got a good result back
      if( !is_wp_error($response) ){
        $body  = json_decode( $response['body'], true );
        $price = round($body['rate'], $precision);
      }
      else{
        $price = __('N/A');
      }

      // Return the price
      if( $return == 'container'){
        return '<div class="xl-token xl-token-' . strtolower($currency_code) . '">' . $price . ' ' . $target_currency . '</div>';
      }
      else{
        return $price;
      }
  }

  public function xummlogin_action_messages( $atts = array() ) {

    global $xumm_messaging, $xumm_api_error_codes;

    // Merge params
    extract(shortcode_atts(array(
     'type'    => '',
     'feature' => '',
     'message' => '',
     'retry'   => 'true',
    ), $atts));

    // Hover message
    $title_message = __('Click to remove');

    // If a test message was passed, force it and stop
    if( $message != '' ){
      $try_again     = ( $retry == 'true' ) ? ' ' . __('<a href="">Try again</a> shortly.') : '';
      $user_message .= '<div title="' . $title_message . '" class="xl-messages xl-' . $type . ' xl-' . $type . '-' . $feature . '"><p>' . $message . $try_again . '</p></div>';
    }
    // If not process as normal 
    else{

      // To colect errors
      $user_message = '';
      $messages     = $xumm_messaging->get_messages();

      // Check if the error object has any errors to display
      if( count($messages) > 0 ){

        // Cycle through the type of errors
        foreach ($messages as $message) {

          // Get error message from code if none was passed
          $note = $message['message'];

          if( $note == '' ){
            $note = array_key_exists( $message['code'], $xumm_api_error_codes ) ? $xumm_api_error_codes[ $message['code'] ] : __('An error occured.');
          }

          // Set user message
          $try_again     = ( $retry == 'true' && $message['code'] == '429' ) ? ' ' . __('<a href="">Try again</a> shortly.') : '';
          $user_message .= '<div title="' . $title_message . '" class="xl-messages xl-' . $message['type'] . ' xl-' . $message['type'] . '-' . $message['feature'] . '"><p>' . $note . $try_again . '</p></div>';
        }
      }
    }

    // Return message
    return $user_message;
  }
}
?>