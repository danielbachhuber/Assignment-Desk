=== Plugin Name ===
Contributors: efroese, danielbachhuber
Tags: editorial workflow, journalism, crowdsourcing, assignment management
Requires at least: 2.9.2
Tested up to: 3.0.1
Stable tag: 0.5

Empower the users to participate in the journalism being done at your site and guide them along the way.

== Description ==

Assignment Desk is an editorial tool for news organizations using WordPress as their content management system. The goal of the plug-in is to make community engagement with the news production process easier and more efficient.

The plug-in allows community members to submit tips or story ideas to the news organization, and volunteer to help with the story in various ways, while preserving editorial oversight.

Once story ideas have been approved, Assignment Desk allows users to participate in the reporting of a particular story. An editor may assign specific roles (e.g. photographer, writer) to the user as well as limit those eligible to contribute by user type (e.g. first time contributor, regular contributor, professional journalist).

Community members can vote their support for story ideas that have either have not yet been assigned or are in production. They can also give feedback on stories in progress.

Assignment Desk hopes bring any community member into the story production process in a structured way. But it could also be used to manage a large staff of professional or semi-pro contributors and distribute assignments to them, while permitting them to suggest ideas, as well.

More details can be found at (http://www.openassignment.org/)

== Installation ==

This section describes how to install and configure the Assignment Desk.

1. Download the plugin from WordPress.org
2. Upload it to the wp-contents/plugins directory of your website. 
3. Activate it. The first time you activate the plugin, the Assignment Desk will install a default set of contributor types, contributor roles, and assignment statuses.
5. Optionally install Edit-Flow. Assignment Desk has been tested with 0.5.2. Edit-Flow gives us several editorial tools with enable a fully integrated newsroom workflow. Installation of this plugin is highly recommended.
4. Optionally install Co-Authors Plus. Assignment Desk has been tested with 2.1.1. Co-Authors Plus allows us to assign multiple users to a post as authors. Multiple authors can then edit the post. Installation of this plugin is highly recommended.
6. Optionally install Adminimize. Assignment Desk has been tested with 1.7.7. Adminimize allows you the ability to hide certain boxes on the post edit screen based on the users user type. The Assignment Desk is not dependent on this plugin.

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
