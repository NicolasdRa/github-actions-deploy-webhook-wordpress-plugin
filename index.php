<?php

/*
Plugin Name: Github Actions Deploy Webhook
Plugin URI: https://github.com/NicolasdRa/github-actions-deploy-webhook
Description: WordPress plugin to manually trigger a Github Actions deploy workflow via a Webhook.

Version: 1.0.0
Author: Nicolás di Rago
Author URI: https://www.nicolasdirago.com
License: GPLv3 or later
Text Domain: github-actions-deploy-hooks
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/


defined('ABSPATH') or die('You do not have access to this file');


class GithubActionsWebhookDeployPlugin {
 function __construct() {
add_action('admin_menu', array($this, 'makeTopLevelMenu'));
add_action('admin_init', array($this, 'manageSettings'));
}

// registers settings variables
function manageSettings() {
add_settings_section('github-actions-deploy-webhook-settings-section', null, null, 'github-actions-deploy-webhook-settings-page'); 
register_setting( 'GADW_webhook-settings', 'GADW_owner', array('sanitize_callback'=> 'sanitize_text_field', 'default' => ''));
add_settings_field('GADW_owner', 'Owner', array($this,'ownerInputHTML'), 'github-actions-deploy-webhook-settings-page', 'github-actions-deploy-webhook-settings-section');

register_setting( 'GADW_webhook-settings', 'GADW_repo', array('sanitize_callback'=> 'sanitize_text_field', 'default' => ''));
add_settings_field('GADW_repo', 'Repository', array($this,'repoInputHTML'), 'github-actions-deploy-webhook-settings-page', 'github-actions-deploy-webhook-settings-section');

register_setting( 'GADW_webhook-settings', 'GADW_workflow_id', array('sanitize_callback'=> 'sanitize_text_field', 'default' => ''));
add_settings_field('GADW_workflow_id', 'Workflow ID', array($this,'workflowIdInputHTML'), 'github-actions-deploy-webhook-settings-page', 'github-actions-deploy-webhook-settings-section');

register_setting( 'GADW_webhook-settings', 'GADW_access_token', array('sanitize_callback'=> 'sanitize_text_field', 'default' => ''));
add_settings_field('GADW_access_token', 'Access Token', array($this,'accessTokenInputHTML'), 'github-actions-deploy-webhook-settings-page', 'github-actions-deploy-webhook-settings-section');

register_setting( 'GADW_webhook-settings', 'GADW_ref', array('sanitize_callback'=> 'sanitize_text_field', 'default' => ''));
add_settings_field('GADW_ref', 'Trigger Event Name', array($this,'refInputHTML'), 'github-actions-deploy-webhook-settings-page', 'github-actions-deploy-webhook-settings-section');
}

// renders the owner input field
function ownerInputHTML() {
$owner = get_option('GADW_owner');
echo "<input type='text' name='GADW_owner' value='$owner' />";
}

// renders the repo input field
function repoInputHTML() {
$repo = get_option('GADW_repo');
echo "<input type='text' name='GADW_repo' value='$repo' />";
}

// renders the workflow id input field
function workflowIdInputHTML() {
$workflowId = get_option('GADW_workflow_id');
echo "<input type='text' name='GADW_workflow_id' value='$workflowId' />";
}

// renders the access token input field
function accessTokenInputHTML() {
$accessToken = get_option('GADW_access_token');
echo "<input type='text' name='GADW_access_token' value='$accessToken' />";
}

// renders the trigger event name input field
function refInputHTML() {
$ref = get_option('GADW_ref');
echo "<input type='text' name='GADW_ref' value='$ref' />";
}

// creates the top level menu and submenus
function makeTopLevelMenu() {
$documentTitle = 'Github Actions Deploy Webhook';
$menuLabel = 'Deploy Site';
$capability = 'manage_options';
$menuSlug = 'github-actions-deploy-webhook';
$mainPageHTML = array($this, 'mainPageHTML');
$iconUrl = 'dashicons-cloud';
$position = 110;
$subMenuDocumentTitle = 'Github Actions Deploy Webhook Options';
$subMenuLabel = 'Settings';
$subMenuSlug= 'github-actions-deploy-webhook-options';
$optionsSubPageHTML = array($this, 'optionsSubPageHTML');

 add_menu_page($documentTitle, $menuLabel, $capability, $menuSlug, $mainPageHTML, $iconUrl, $position);
 add_submenu_page($menuSlug, $subMenuDocumentTitle, 'Main', $capability, $menuSlug, $mainPageHTML);
 $settingsPageHook = add_submenu_page($menuSlug, $subMenuDocumentTitle, $subMenuLabel, $capability, $subMenuSlug, $optionsSubPageHTML);

 // hook to load assets only on the settings submenu page
 add_action("load-{$settingsPageHook}", array($this, 'loadAssets'));
}

// loads css assets
function loadAssets() {
wp_enqueue_style('deployPluginCss', plugin_dir_url(__FILE__) . 'styles.css');
}

// submits a POST request to the Github Actions webhook
function handleSubmitDeploy() {

  if (isset($_POST['hookNonce']) && wp_verify_nonce($_POST['hookNonce'], 'postWebHook') && current_user_can('manage_options')) {
 
// Replace these placeholders with actual values
$owner = get_option('GADW_owner');
$repo = get_option('GADW_repo');
$workflow_id = get_option('GADW_workflow_id');
$pat = get_option('GADW_access_token');
$ref = 'main';

// Build the API URL
$url = "https://api.github.com/repos/$owner/$repo/actions/workflows/$workflow_id/dispatches";

// Set up the cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

// Set the request headers
$headers = array(
  'Content-Type: application/json',
  "Authorization: Bearer $pat",
  'Accept: application/vnd.github.v3+json',
  'User-Agent: ' . get_bloginfo('name')
);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Set the request body
$data = array(
  'ref' => $ref,
);
$body = json_encode($data);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

// Send the request and get the response
$response = curl_exec($ch);
$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check the response code
if ($response_code == 204) {
  // Success!
  echo "Workflow triggered successfully.\n";
} else {
  // Error
  echo "Error triggering workflow.\n";
  echo "Response code: $response_code\n";
  echo "Response body: $response\n";
}

// Clean up
curl_close($ch);

?>
<div class="notice notice-success">
 <p>Build Process triggered</p>
</div>

<?php
} else { ?>
<div class="notice notice-error">
 <p>Processed banned, you do not have permission to perform that action.</p>
</div>
<?php

}
}

// main page html
function mainPageHTML() { 

?>
<div class="wrap">
 <h1>Github Actions Deploy Webhook</h1>
 <?php if (isset($_POST['justsubmitted'])) $this->handleSubmitDeploy() ?>
 <hr>
 <h2><strong>Build Website</strong></h2>
 <p>Trigger a Github Actions deploy workflow.</p>
 <form action="" method="POST">
  <input type="hidden" name="justsubmitted" value="true">
  <?php wp_nonce_field('postWebHook','hookNonce') ?>
  <div class="gawdp_flex-container">
   <div class="flex-item">
    <div class="flex-item">
     <button type="submit" name="trigger-build-button" class="button button-primary">Deploy site</button>
    </div>
   </div>
 </form>
</div>

<?php

}

// handles the saving of the webhook url to the database
function handleSubmitSaveOptions() {
 if (isset($_POST['hookNonce']) && wp_verify_nonce($_POST['hookNonce'], 'saveUrlWebHook') && current_user_can('manage_options')) {
 
   update_option('github-actions-deploy-webhook', sanitize_text_field($_POST['github-actions-deploy-webhook']));
?>
<div class="notice notice-success">
 <p>Webhook Saved</p>
</div>
<?php }
 else { 
  ?>
<div class="notice notice-error">
 <p>Webhook not saved, you do not have permission to perform that action.</p>
</div>
<?php
 }
}

// options page html
function optionsSubPageHTML() {
$settingField = 'github-actions-deploy-webhook';

?>
<div class="wrap">
 <h2>Settings </h2>
 <?php if (isset($_POST['justsubmitted'])) $this->handleSubmitSaveOptions() ?>
 <hr>
 <form action="options.php" method="POST">
  <input type="hidden" name="justsubmitted" value="true">
  <?php wp_nonce_field('saveUrlWebHook','hookNonce') ?>

  <div class="gawdp_flex-container">
   <div class="gawdp_flex-item">
    <p class="gawdp_input-label">Fill out the fields and save your changes.</p>

   </div>
   <?php
   $settingField = 'GADW_webhook-settings';
   $pageSlug= 'github-actions-deploy-webhook-settings-page';

   settings_fields($settingField);
   do_settings_sections($pageSlug);
    submit_button();
   ?>
  </div>
 </form>
</div>
<?php
}
}

// Instantiates the class
$githubActionsWebhookDeployPlugin = new GithubActionsWebhookDeployPlugin();