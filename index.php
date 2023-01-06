<?php

/*
Plugin Name: Deploy Webhook Github Actions
Plugin URI: https://github.com/NicolasdRa/github-actions-deploy-webhook
Description: WordPress plugin to manually trigger a Github Actions deploy workflow via a Webhook.

Version: 1.0.0
Author: Nicolás di Rago
Author URI: https://www.nicolasdirago.com
License: GPLv3 or later
Text Domain: deploy-hook-github-actions
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

$settingsFields = array(
 array(
 'name' => 'GADW_owner',
 'label' => 'OWNER:',
 ),

 array(
 'name' => 'GADW_repo',
 'label' => 'REPO:',
 ),

 array(
 'name' => 'GADW_workflow_id',
 'label' => 'WORKFLOW ID:',
 ),

 array(
 'name' => 'GADW_access_token',
 'label' => 'PERSONAL ACCESS TOKEN:',
 ),

 array(
 'name' => 'GADW_ref',
 'label' => 'REF:',
 ),
);

 // creates fields html
function renderFieldHtml($arg) {
 $data = get_option($arg['name']);
  echo "<input type='text' name='{$arg['name']}' id='{$arg['name']}' value='$data' />";
}

// builds field Html, registers settings & adds fields
foreach ($settingsFields as $field) {
  register_setting( 'GADW_webhook-settings', $field['name'], array('sanitize_callback'=> 'sanitize_text_field', 'default' => ''));
  add_settings_field($field['name'], $field['label'], 'renderFieldHtml', 'github-actions-deploy-webhook-settings-page', 'github-actions-deploy-webhook-settings-section', $field);
}

}

// creates the top level menu and submenus
function makeTopLevelMenu() {
$documentTitle = 'Deploy Webhook Github Actions';
$menuLabel = 'GA Deploy';
$capability = 'manage_options';
$menuSlug = 'github-actions-deploy-webhook';
$mainPageHTML = array($this, 'mainPageHTML');
$iconUrl = 'dashicons-cloud';
$position = 110;
$subMenuDocumentTitle = 'Deploy Webhook Github Actions Options';
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

  // check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

 // check if nonce is set and valid before processing form data
  if (isset($_POST['hookNonce']) && wp_verify_nonce($_POST['hookNonce'], 'postWebHook')) {
 
// get the form data
$owner = get_option('GADW_owner');
$repo = get_option('GADW_repo');
$workflow_id = get_option('GADW_workflow_id');
$pat = get_option('GADW_access_token');
$ref = get_option('GADW_ref');;

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
 ?>
<div class="notice notice-success">
 <p>"Workflow triggered successfully.</p>
</div>
<?php
} else {
  // Error
 ?>
<div class="notice notice-error">
 <p><strong>Error triggering workflow.</strong></p>
 <p><?php echo "Response code: $response_code."; ?> <?php echo "Response body: $response\n"; ?></p>
</div>
<?php
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

 // check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

 // verify nonce & update options
 if (isset($_POST['hookNonce']) && wp_verify_nonce($_POST['hookNonce'], 'saveUrlWebHook') ) {

  // options to save
$GADW_options = array(
  'GADW_owner' => 'GADW_owner',
  'GADW_repo' => 'GADW_repo',
  'GADW_workflow_id' => 'GADW_workflow_id',
  'GADW_access_token' => 'GADW_access
_token',
  'GADW_ref' => 'GADW_ref',
);

// sanitize and update options
  foreach ($GADW_options as $option => $key) {
  update_option($key, sanitize_text_field($_POST[$key])); 
}

?>
<div class="notice notice-success">
 <p>Data saved successfully.</p>
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
 <h2><strong>Webhook URL Settings</strong></h2>
 <p>Make sure to fill out the fields and save your changes. This data is necessary for the plugin to create the webhook.</p>

 <form action="" method="POST">
  <input type="hidden" name="justsubmitted" value="true">
  <?php wp_nonce_field('saveUrlWebHook','hookNonce') ?>

  <div class="gawdp_flex-container">
   <?php
   $settingField = 'GADW_webhook-settings';
   $pageSlug= 'github-actions-deploy-webhook-settings-page';

   settings_fields($settingField);
   do_settings_sections($pageSlug);
    submit_button();
   ?>
  </div>
 </form>

 <hr>
 <div class="gawdp_flex-item">
  <h2><strong>Quick Reference</strong></h2>
  <ul class="gawdp_list">
   <li class="gawdp_list-item"><strong>OWNER: </strong>The username or organization name that owns the repository.</li>
   <li class="gawdp_list-item"><strong>REPO: </strong>The name of the repository.</li>
   <li class="gawdp_list-item"><strong>WORKFLOW_ID: </strong>You can either write the full name of your .yml file, ie. "manual-trigger-workflow.yml" or the ID of the workflow that you want to trigger. You can find the ID of a workflow by going to the "Actions" tab of your repository on GitHub, clicking on the name of the workflow, and looking at the URL of the page. The ID is the number that appears after the last forward slash in the URL.</li>
   <li class="gawdp_list-item"><strong>YOUR_GITHUB_PERSONAL_ACCESS_TOKEN: </strong>A personal access token (PAT) with the repo scope. You can create a PAT by going to "Settings" > "Developer settings" > "Personal access tokens" in your GitHub account.
   </li>
   <li class="gawdp_list-item"><strong>REF: </strong>The name of your repository's main branch.
   </li>
  </ul>
  <p class="gawdp_remark"><strong>Important: </strong>At the moment the plugin is able to create a webhook for a workflow_dispatch trigger without inputs. This feature is planned for later versions.</p>
  <hr>
  <h2 class="gawdp_extra-info"><strong>Extra Info</strong></h2>
  <a href="https://github.com/NicolasdRa/github-actions-deploy-webhook-wordpress-plugin" class="">Plugin repository on Github</a>
  <a href="https://docs.github.com/en/rest/webhooks?apiVersion=2022-11-28#repository-webhooks" class="gawdp_input-label">Github Actions Deploy Webhook Documentation</a>

 </div>
</div>
<?php
}
}

// Instantiates the class
$githubActionsWebhookDeployPlugin = new GithubActionsWebhookDeployPlugin();