=== Plugin Name ===
Contributors: efroese, danielbachhuber
Donate link: 
Tags: news, editorial, journalism, crowdsourcing
Requires at least: 2.9.2
Tested up to: 3.0.1
Stable tag: 0.4

Empower the community to participate in the journalistic process and guide them along the way.

== Description ==

The goal of the Assignment Desk is to empower the community to participate in the journalistic process and guide them along the way.

Community members can submit story pitches, vote on them, and volunteer for them. Editors can moderate pitches and assign them to staff or interested community journalists. 

NOTICE: THIS PLUGIN IS PRE-PRE-RELEASE AND SHOULD NOT BE USED IN PRODUCTION. WE WILL BREAK YOUR SETTINGS.

Much longer description.

== Installation ==

This section describes how to install the plugin and get it working.

1. Download the plugin 
2. Upload to wp-contents/plugins directory
3. Activate and enjoy!
4. Optionally install Coauthors-plus. Tested with 2.1.1.
5. Optionally install Edit-Flow. Tested with 0.5.2.

For full functionality, please also install and configure Edit Flow and Co-Authors Plus.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 0.5 =
* Hide Assignment Desk functionality if post is published
* Ability to show or hide the_content for a story in progress
* Logged-out users can now properly see posts which haven't been published
* Filter all pitches by normal post statuses, contributor types, or sort by post date, due date, or votes
* Volunteering can be restricted to defined contributor types
* Ability to sort by due date, votes, or number of volunteers in Manage Posts view
* Filter by assignment status UI in Manage Posts has UI similar to normal post statuses
* Styled the listing of contributors in the post meta box and improved markup
* Setting for changing the text of the voting button

= 0.4 =
* Pitch form offers customizable labels and descriptions for every field
* Pitch form supports Edit Flow due date field with jQuery datepicker
* Voting functionality built; admin has ability to define text for vote button; avatars shown for those who have voted
* Columns in users view calculate total words, average words, pitches, and volunteer count
* Nonces on all the forms
* Default settings implemented
* Moved pitch form settings to its own view; no data migration
* Moved public-facing functionality settings to its own view; no data migration

= 0.3 =
* Manage Posts - Can filter by assignment_status
* Manage Posts - Can filter by eligible contributor types
* Manage Posts - Added an eligible contributor types column.
* Added default terms in custom taxonomies on plugin activation.
* Remove user_type id from all users if term is deleted.
* Added a simple dashboard widget that shows pending assignments.
* Users can accept or decline pending assignments.

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
