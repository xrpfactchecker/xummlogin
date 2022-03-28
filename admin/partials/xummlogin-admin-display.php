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
  <h2>XUMM Login - Settings</h2>
  <form method="POST" action="options.php">  
    <?php 
    settings_fields( 'xummlogin_general_settings' );
    do_settings_sections( 'xummlogin_general_settings' ); 
    ?>             
    <?php submit_button(); ?>  
  </form> 
</div>