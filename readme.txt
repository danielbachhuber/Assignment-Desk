=== Plugin Name ===
Contributors: efroese, danielbachhuber
Donate link: 
Tags: news, editorial, journalism, crowdsourcing
Requires at least: 2.9.2
Tested up to: 3.0.1
Stable tag: 0.2

Empower the community to participate in the journalistic process and guide them along the way.

== Description ==

The goal of the Assignment Desk is to empower the community to participate in the journalistic process and guide them along the way.

Community members can submit story pitches, vote on them, and volunteer for them. Editors can moderate pitches and assign them to staff or interested community journalists. 

NOTICE: THIS PLUGIN IS PRE-PRE-RELEASE AND SHOULD NOT BE USED IN PRODUCTION

Much longer description.

== Installation ==

This section describes how to install the plugin and get it working.

1. Download the plugin 
2. Upload to wp-contents/plugins directory
3. Activate and enjoy!

For full functionality, please also install and configure Edit Flow and Co-Authors Plus.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 0.2 =
* Enable pitch forms, add a form to any post or page, and allow the following fields: title, description, category, tags, location, volunteer for roles 
* Pitches get saved to WordPress with post status of 'pitch' if Edit Flow is enabled, 'draft' if Edit Flow is not enabled 
* Define assignment statuses and the default status for new pitches (e.g. 'new', 'approved', 'rejected'). Refactored this to use custom taxonomy API. 
* Define contributor roles (e.g. 'photographer', 'writer'). Pitch submitters can volunteer for a role and editors can add people to an assignment. Assigning functionality is only partially functional, however.
* Define contributor types (e.g. 'community member', 'student') and indicate which can take an assignment. Default for new pitches is all. Refactored this to use custom taxonomy API.
* Settings page now uses the WordPress Settings API

= 0.1 =
* First implementation from Spring 2010. Significant refactoring planned for the next release.

== Upgrade Notice ==
