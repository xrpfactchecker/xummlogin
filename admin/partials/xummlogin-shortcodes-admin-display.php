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
  <h2>XUMM Login - Short Codes</h2>
  <form method="POST" action="options.php">  
    <h2>Short Codes</h2>
    <p>List of various short codes available within the plugin and their various parameters.</p>
    <table class="form-table xl-short-code-table">
      <tbody>
        <tr>
          <th scope="row"><label>XUMM Login</label></th>
          <td><code>[xummlogin]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output the XUMM login button.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>form</code></td>
                  <td>No</td>
                  <td>To include an option for the user to login using their username and password. Default is <code>false</code>.</td>
                </tr>
                <tr>
                  <td><code>force</code></td>
                  <td>No</td>
                  <td>To force the user to be logged in to view the content. Default is <code>false</code>. When force is used, only the signin button/link OR the content will be outputted, not both.</td>
                </tr>
                <tr>
                  <td><code>trustline</code></td>
                  <td>No</td>
                  <td>To force the user to be have the trustline set to view the content. Default is <code>false</code>. This is only checked of the user is logged in.</td>
                </tr>
                <tr>
                  <td><code>always</code></td>
                  <td>No</td>
                  <td>Whether to always return the login button/url even if the user is signed in. Default is <code>false</code>.</td>
                </tr>                
                <tr>
                  <td><code>return</code></td>
                  <td>No</td>
                  <td>To use the standard XUMM <code>button</code>, an <code>anchor</code> link or simply get the <code>url</code>. Default is <code>button</code>.</td>
                </tr>
                <tr>
                  <td><code>label</code></td>
                  <td>No</td>
                  <td>Label to use when using an anchor. Default is <code>XUMM Signin</code>.</td>
                </tr>
              </tbody>
            </table>            
          </td>
        </tr>
        <tr>
          <th scope="row"><label>XUMM Logout</label></th>
          <td><code>[xummlogout]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output the a logout button. The user will be redirected to the homepage after logging out.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>  
                <tr>
                  <td><code>confirm</code></td>
                  <td>No</td>
                  <td>To prompt the user to confirm or not. Only works if an anchor tag is returned. Default is <code>true</code>.</td>
                </tr>           
                <tr>
                  <td><code>anchor</code></td>
                  <td>No</td>
                  <td>Whether to return a full anchor tag or just a URL. Default is <code>true</code>.</td>
                </tr>
                <tr>
                  <td><code>label</code></td>
                  <td>No</td>
                  <td>Label to use when using an anchor. Default is <code>Signout</code>.</td>
                </tr>
              </tbody>
            </table>            
          </td>
        </tr>
        <tr>
          <th scope="row"><label>XUMM Messages</label></th>
          <td><code>[xummmessages]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            The usage of this short code is highly recommended. Error, warning and success messages from the plugin are sent to this shortcode.<br>Add to a page to display these important feedback messages related the user's actions.
            <br><br>
            Sample HTML output:
            <br><br>
            <?php
              echo '<code>' . htmlentities('<div class="xl-messages xl-{type} xl-{type}-{feature}">') . '</code><br>';
              echo '<code>&nbsp;&nbsp;' . htmlentities('<p>Not a valid vote for the active voting.</p>') . '</code><br>';
              echo '<code>' . htmlentities('</div>') . '</code>';
            ?>
            <br><br>
            Where <em>{type}</em> is either <code>error</code>, <code>warning</code> or <code>success</code> and <em>{feature}</em> is one of the following: <code>voting</code> or <code>trustline</code>.
            <br><br>
            This shortcode params are mainly for helping with the styling of the various types and features.<br>Test on a separate page to avoid issues; test messages are prioritized over real messages.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>type</code></td>
                  <td>No</td>
                  <td>To force test an <code>error</code>, <code>warning</code> or <code>success</code> message type.</td>
                </tr>
                <tr>
                  <td><code>feature</code></td>
                  <td>No</td>
                  <td>To force test a <code>voting</code> or <code>trustline</code> message feature.</td>
                </tr>
                <tr>
                  <td><code>message</code></td>
                  <td>No</td>
                  <td>To force test a message to output.</td>
                </tr>
                <tr>
                  <td><code>retry</code></td>
                  <td>No</td>
                  <td>To include or not the retry link for the <em>429 Too Many Request</em> error or in the test message. Default is <code>true</code>.</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>        
        <tr>
          <th scope="row"><label>XUMM Trustline</label></th>
          <td><code>[xummline]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output a trustline link for users to add to their account. Your trustline settings will be used to for the TrustSet transaction.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>label</code></td>
                  <td>No</td>
                  <td>The link label to use for display. Default is <code>Set Trustline</code>.</td>
                </tr>
                <tr>
                  <td><code>anchor</code></td>
                  <td>No</td>
                  <td>Whether to return a full anchor tag or just a URL. Default is <code>true</code>.</td>
                </tr>
                <tr>
                  <td><code>check</code></td>
                  <td>No</td>
                  <td>Use this param to check if the logged in user has the trustline setup. Using this will supersede the TrustSet link. Default is <code>false</code>.</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>         
        <tr>
          <th scope="row"><label>XUMM Voting</label></th>
          <td><code>[xummvoting vote="Vote 1"]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output a voting link with a vote value for users to vote.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>active_voting</code></td>
                  <td>No</td>
                  <td>The voting that the vote will be sent to. Default is the current active voting.</td>
                </tr>
                <tr>
                  <td><code>vote</code></td>
                  <td>Yes</td>
                  <td>The vote that will be stored on the ledger with the active voting.</td>
                </tr>
                <tr>
                  <td><code>label</code></td>
                  <td>No</td>
                  <td>The label used if an anchor is returned. Default is the vote.</td>
                </tr>                
                <tr>
                  <td><code>anchor</code></td>
                  <td>No</td>
                  <td>Whether to return a full anchor tag or just a URL. Default is <code>true</code>.</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr> 
        <tr>
          <th scope="row"><label>XUMM Voting Results</label></th>
          <td><code>[xummresults vote="Vote 1"]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output a voting vote's results.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>vote</code></td>
                  <td>Yes</td>
                  <td>The vote choice to get the results from</td>
                </tr>
                <tr>
                  <td><code>active_voting</code></td>
                  <td>No</td>
                  <td>The <em>Voting Name</em> that the results will be based on. Default is the current active voting.</td>
                </tr>
                <tr>
                  <td><code>archived</code></td>
                  <td>No</td>
                  <td>To pull previous voting from the saved results using its <em>Voting Name</em>. If both <code>archived</code> and <code>active_voting</code> are specified, the archived will be used.</td>
                </tr> 
                <tr>
                  <td><code>use</code></td>
                  <td>No</td>
                  <td>To use the <code>singular</code> vote (count as 1) or the <code>balance</code> of the wallet. Default is <code>balance</code>.</td>
                </tr>
                <tr>
                  <td><code>return</code></td>
                  <td>No</td>
                  <td>To return the <code>percentage</code> or total <code>amount</code> for the vote. Default is <code>percentage</code>.</td>
                </tr>
                <tr>
                  <td><code>precision</code></td>
                  <td>No</td>
                  <td>The number of digit to the right of the period for <code>return</code>. Default is <code>4</code>. </td>
                </tr>                
              </tbody>
            </table>
          </td>
        </tr>         
        <tr>
          <th scope="row"><label>XUMM Last Sync</label></th>
          <td><code>[xummsyncinfo]</code></td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output a voting vote's results. No params available.
          </td>
        </tr>
        <tr>
          <th scope="row"><label>XUMM User</label></th>
          <td>
            <code>[xummuser]</code>
          </td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use this short code to output a holder's card or individual pieces of info of a user based on their XRPL Account address.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>token</code></td>
                  <td>No</td>
                  <td>The token to use for the Holder's Card: <code>primary</code> or <code>secondary</code>. Default is <code>primary</code>.</td>
                </tr> 
                <tr>
                  <td><code>prefix</code></td>
                  <td>No</td>
                  <td>Use to insert a string in front of the token balance. The default is empty.</td>
                </tr>                 
                <tr>
                  <td><code>return</code></td>
                  <td>No</td>
                  <td>The information to return. The default is <code>card</code>, other values can be <code>wallet</code>, <code>balance</code>, <code>avatar</code>, <code>rank</code>, <code>level</code> or <code>vote</code>.</td>
                </tr>
                <tr>
                  <td><code>wallet</code></td>
                  <td>No</td>
                  <td>The default is to load the user info from the saved wallet (user or session), but you can force load any wallet by passing an <em>r address</em> to this param.</td>
                </tr>
                <tr>
                  <td><code>trade</code></td>
                  <td>No</td>
                  <td>To add the DEX xApp link to open trading in XUMM. Default is <code>true</code>.</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr> 
        <tr>
          <th scope="row"><label>XUMM Richlist</label></th>
          <td>
            <code>[xummrichlist]</code>
          </td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to display a Richlist of your holders with either a flat top n list or with tiers.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>token</code></td>
                  <td>No</td>
                  <td>The token to use for the Richlist: <code>primary</code> or <code>secondary</code>. Default is <code>primary</code>.</td>
                </tr>                
                <tr>
                  <td><code>type</code></td>
                  <td>No</td>
                  <td>The type of rishlist to output: a <code>flat</code> or with <code>tiers</code>. Default is <code>flat</code>.</td>
                </tr>
                <tr>
                  <td><code>count</code></td>
                  <td>No</td>
                  <td>If the type is 'flat', how many wallets to display in the list. Default is <code>100</code>.</td>
                </tr>
                <tr>
                  <td><code>infinity</code></td>
                  <td>No</td>
                  <td>If the type is 'tiers', what should the top most limit be. Default is <code>∞</code>.</td>
                </tr>
                <tr>
                  <td><code>precision</code></td>
                  <td>No</td>
                  <td>How many digits after the period for any of the list types. Default is <code>4</code>.</td>
                </tr>                
              </tbody>
            </table>
          </td>
        </tr>         
        <tr>
          <th scope="row"><label>XUMM Token</label></th>
          <td>
            <code>[xummtoken]</code>
          </td>
        </tr>
        <tr class="params">
          <th scope="row"></th>
          <td>
            Use to output your token price. Default is the XRP price in USD.
            <table class="short-code-params">
              <thead>
                <tr>
                  <th>Params</th>
                  <th>Required</th>
                  <th>Note</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>base</code></td>
                  <td>No</td>
                  <td>The base to use for the price in the currency+issuer format. This is typically your token. Default is your trustline setting.</td>
                </tr>
                <tr>
                  <td><code>currency</code></td>
                  <td>No</td>
                  <td>The currency to target for the price. Default is XRP, for any other target, use the currency+issuer format.</td>
                </tr>
                <tr>
                  <td><code>precision</code></td>
                  <td>No</td>
                  <td>The number of digit to the right of the period. Default is <code>4</code>. </td>
                </tr>
                <tr>
                  <td><code>anchor</code></td>
                  <td>No</td>
                  <td>Whether to return a full anchor tag or just a URL. Default is <code>true</code>.</td>
                </tr>
                <tr>
                  <td><code>return</code></td>
                  <td>No</td>
                  <td>To return the price with a div <code>container</code> or just the <code>number</code>. Default is <code>container</code>.</td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>  
      </tbody>
    </table>
  </form>
</div>