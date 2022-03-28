<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://twitter.com/xrpfactchecker
 * @since      1.0.0
 *
 * @package    Xummlogin
 * @subpackage Xummlogin/admin/partials
 */
?>
<div class="wrap">
  <div id="icon-themes" class="icon32"></div>  
  <h2>XUMM Login - Voting Management</h2>
  <form method="POST" action="options.php">  
    <?php 
    settings_fields( 'xummlogin_voting_management' );
    do_settings_sections( 'xummlogin_voting_management' ); 
    ?>
    <?php //submit_button(); ?> 
    <p class="submit">
      <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
      <input type="button" name="clear-active-voting" id="clear-active-voting" class="button" value="Clear Active Voting">
    </p>    
  </form> 
</div>