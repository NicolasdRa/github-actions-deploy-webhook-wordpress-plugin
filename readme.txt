=== Plugin Name ===
Contributors: Nicolasdra
Tags: github actions, deploy, hooks
Requires at least: 5.0
Tested up to: 6.1.1
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html


== DEPLOY WEBHOOK GITHUB ACTIONS PLUGIN ==

A WordPress plugin to manually trigger a deploy workflow via the Github Actions REST API after updating content.

== FEATURE ==
Trigger a deploy workflow via Github Actions after updating content. Users with manage_capabilities are only allowed to perform the action from the the Wordpress admin menu.
 
== INSTALLATION ==
You can install "Github Actions Webhook Deploy Plugin" manually.

*  Download or clone the plugin from the repository
*  Create a .zip file
*  Login to your WordPress site and move to Plugins -> Add new -> Upload plugin
* Upload the newly created .zip file on your machine, activate and enjoy.
 
== SETTINGS ==
Fill out the form with the data required on the Settings Page. Find a reference below:

OWNER: The username or organization name that owns the repository.
REPO: The name of the repository.
WORKFLOW_ID: You can either write the full name of your .yml file, ie. "manual-trigger-workflow.yml" or the ID of the workflow that you want to trigger. You can find the ID of a workflow by going to the "Actions" tab of your repository on GitHub, clicking on the name of the workflow, and looking at the URL of the page. The ID is the number that appears after the last forward slash in the URL.
PERSONAL_ACCESS_TOKEN: A personal access token (PAT) with the repo scope. You can create a PAT by going to "Settings" > "Developer settings" > "Personal access tokens" in your GitHub account.
REF: The name of your repository's main branch. Important: At the moment the plugin is able to create a webhook for a workflow_dispatch trigger without inputs. This feature is planned for later versions.
 
== TO DO ==
 Add feature: accept inputs.
 
== AUTHOR ==
Created by Nicolás di Rago.
https://www.nicolasdirago.com/

== CHANGELOG ==

= 1.0.0 =
* First release
